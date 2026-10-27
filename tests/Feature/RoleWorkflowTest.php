<?php

namespace Tests\Feature;

use App\Models\EcgImage;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function patient(): Patient
    {
        return Patient::create(['subject_id' => 'PAT-100', 'age_category' => 'Adult', 'sex' => 'Male']);
    }

    private function examination(Patient $patient, User $doctor, string $status = 'in_progress'): Examination
    {
        return Examination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => $status, 'started_at' => now()]);
    }

    public function test_admin_creates_patient_without_an_ecg_and_can_edit_details(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.patients.create'))->assertOk()->assertSee('Add Patient');
        $this->post(route('admin.patients.store'), ['subject_id' => 'PAT-100', 'age_category' => 'Adult', 'sex' => 'Male', 'case_status' => 'completed'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.patients.show', Patient::first()));
        $patient = Patient::firstOrFail();
        $this->assertSame('pending', $patient->case_status);
        $this->assertSame(0, $patient->ecgImages()->count());
        $this->put(route('admin.patients.update', $patient), ['subject_id' => 'PAT-101', 'age_category' => 'Older Adult', 'sex' => 'Female'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.patients.show', $patient));
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'subject_id' => 'PAT-101', 'age_category' => 'Older Adult', 'sex' => 'Female']);
    }

    public function test_patient_id_must_be_unique_and_demographics_valid(): void
    {
        $patient = $this->patient();
        $this->actingAs($this->user('admin'))->post(route('admin.patients.store'), ['subject_id' => $patient->subject_id, 'age_category' => 'Invalid', 'sex' => 'Invalid'])
            ->assertSessionHasErrors(['subject_id', 'age_category', 'sex']);
        $this->assertDatabaseCount('patients', 1);
    }

    public function test_doctor_can_edit_only_their_active_patient_and_cannot_change_case_status(): void
    {
        $patient = $this->patient();
        $doctor = $this->user('doctor');
        $otherDoctor = $this->user('doctor');
        $exam = $this->examination($patient, $doctor);
        $data = ['subject_id' => 'PAT-UPDATED', 'age_category' => 'Older Adult', 'sex' => 'Female', 'case_status' => 'completed'];
        $this->actingAs($otherDoctor)->get(route('doctor.patients.edit', $patient))->assertForbidden();
        $this->put(route('doctor.patients.update', $patient), $data)->assertForbidden();
        $this->actingAs($doctor)->get(route('doctor.patients.edit', $patient))->assertOk();
        $this->put(route('doctor.patients.update', $patient), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'subject_id' => 'PAT-UPDATED', 'case_status' => 'pending']);
        $exam->update(['status' => 'completed']);
        $this->put(route('doctor.patients.update', $patient), $data)->assertForbidden();
        $this->post(route('admin.patients.store'), $data)->assertForbidden();
    }

    public function test_consultant_can_preview_doctors_and_associated_patients(): void
    {
        $patient = $this->patient();
        $doctor = $this->user('doctor');
        $this->examination($patient, $doctor);
        $consultant = $this->user('consultant');
        $this->actingAs($consultant)->get(route('consultant.doctors'))->assertOk()->assertSee($doctor->name)->assertDontSee('Edit Doctor');
        $this->get(route('consultant.doctors.show', $doctor))->assertOk()->assertSee($patient->subject_id)->assertDontSee('Edit Doctor');
        $this->get(route('consultant.doctors.patients.show', ['user' => $doctor, 'patient' => $patient]))->assertOk()->assertDontSee('Save Findings');
        $unrelatedDoctor = $this->user('doctor');
        $this->get(route('consultant.doctors.patients.show', ['user' => $unrelatedDoctor, 'patient' => $patient]))->assertNotFound();
        $this->get(route('consultant.doctors.show', $consultant))->assertNotFound();
    }

    public function test_consultant_cannot_mutate_patient_users_uploads_or_findings(): void
    {
        $patient = $this->patient();
        $doctor = $this->user('doctor');
        $this->actingAs($this->user('consultant'));
        $this->post(route('admin.patients.store'), ['subject_id' => 'FORBIDDEN'])->assertForbidden();
        $this->put(route('doctor.patients.update', $patient), ['subject_id' => 'FORBIDDEN'])->assertForbidden();
        $this->post(route('doctor.patients.start', $patient))->assertForbidden();
        $this->post(route('doctor.interpretations.store', $patient), [])->assertForbidden();
        $this->post(route('admin.ecg-images.store'), [])->assertForbidden();
        $this->post(route('admin.doctors.store'), [])->assertForbidden();
        $this->put(route('admin.doctors.update', $doctor), [])->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'subject_id' => 'PAT-100']);
        $this->assertDatabaseCount('examinations', 0);
    }

    public function test_admin_can_add_doctors_and_consultants(): void
    {
        $this->actingAs($this->user('admin'));
        foreach (['doctor', 'consultant'] as $role) {
            $this->post(route('admin.'.$role.'s.store'), [
                'name' => 'New '.ucfirst($role), 'email' => $role.'@example.test',
                'specialization' => 'Cardiology', 'status' => 'active',
                'password' => 'Test-password-2026', 'password_confirmation' => 'Test-password-2026',
                'role' => 'admin',
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.'.$role.'s'));
            $this->assertDatabaseHas('users', ['email' => $role.'@example.test', 'role' => $role]);
        }
    }

    public function test_admin_uploads_ecg_to_patient_and_only_one_doctor_claims_active_case(): void
    {
        Storage::fake('local');
        $patient = $this->patient();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.ecg-images.store'), [
            'patient_id' => $patient->id,
            'images' => [UploadedFile::fake()->image('ecg.png', 1200, 900)],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.patients.show', $patient));
        $image = EcgImage::firstOrFail();
        $this->assertSame($patient->id, $image->patient_id);
        Storage::disk('local')->assertExists($image->storage_path);
        $doctor = $this->user('doctor');
        $this->actingAs($doctor)->post(route('doctor.patients.start', $patient))->assertSessionHasNoErrors();
        $this->actingAs($this->user('doctor'))->post(route('doctor.patients.start', $patient))->assertSessionHasErrors('patient');
        $this->assertDatabaseCount('examinations', 1);
        $this->assertDatabaseHas('examinations', ['patient_id' => $patient->id, 'doctor_id' => $doctor->id]);
    }

    public function test_inactive_consultant_loses_access_and_guest_is_redirected(): void
    {
        $this->get(route('consultant.doctors'))->assertRedirect(route('login'));
        $consultant = $this->user('consultant');
        $consultant->update(['status' => 'inactive']);
        $this->actingAs($consultant)->get(route('consultant.doctors'))->assertRedirect(route('login'));
    }

    public function test_uploading_another_ecg_reopens_a_completed_patient_for_review(): void
    {
        Storage::fake('local');
        $patient = $this->patient();
        $patient->update(['case_status' => 'completed']);
        $this->actingAs($this->user('admin'))->post(route('admin.ecg-images.store'), [
            'patient_id' => $patient->id,
            'images' => [UploadedFile::fake()->image('follow-up.png', 1200, 900)],
        ])->assertSessionHasNoErrors();
        $this->assertSame('pending', $patient->fresh()->case_status);
    }
}
