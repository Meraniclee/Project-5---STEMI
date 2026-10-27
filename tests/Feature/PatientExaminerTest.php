<?php

namespace Tests\Feature;

use App\Models\EcgImage;
use App\Models\EcgInterpretation;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientExaminerTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(string $name): User
    {
        return User::factory()->create(['name' => $name, 'role' => 'doctor', 'status' => 'active']);
    }

    private function recordFindings(Patient $patient, User $doctor): Examination
    {
        $exam = Examination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
        $image = EcgImage::create([
            'patient_id' => $patient->id, 'uploaded_by' => $doctor->id,
            'original_name' => 'test.png', 'storage_path' => 'test.png',
            'mime_type' => 'image/png', 'file_size' => 100,
        ]);
        EcgInterpretation::create([
            'ecg_image_id' => $image->id, 'examination_id' => $exam->id, 'doctor_id' => $doctor->id,
            'category' => 'Inconclusive', 'rhythm' => 'Undetermined', 'assessment' => 'Inconclusive',
            'lead_findings' => ['I' => ['signal_quality' => 'Adequate']], 'annotation_complete' => false,
        ]);
        return $exam;
    }

    public function test_patient_lists_show_distinct_doctors_when_partial_findings_have_been_saved(): void
    {
        $patient = Patient::create(['subject_id' => 'EXAMINED-100']);
        $wong = $this->doctor('Wong');
        $lim = $this->doctor('Dr. Lim');
        $this->recordFindings($patient, $wong);
        $this->recordFindings($patient, $wong);
        $this->recordFindings($patient, $lim);

        foreach (['admin', 'doctor', 'consultant'] as $role) {
            $viewer = $role === 'doctor' ? $wong : User::factory()->create(['role' => $role, 'status' => 'active']);
            $response = $this->actingAs($viewer)->get(route($role.'.patients'))->assertOk();
            $response->assertSee('Examined by Dr Wong')->assertSee('Examined by Dr. Lim')->assertDontSee('Dr Dr. Lim');
            $this->assertSame(1, substr_count($response->getContent(), 'Examined by Dr Wong'));
            $this->assertSame(1, substr_count($response->getContent(), 'Examined by Dr. Lim'));
            $response->assertViewHas('patients', function ($patients) {
                $patient = $patients->first();
                return $patient->relationLoaded('examinedExaminations')
                    && $patient->examinedExaminations->every(fn ($exam) => $exam->relationLoaded('doctor'));
            });
        }
        $this->actingAs($wong)->get(route('doctor.my-patients'))->assertOk()->assertSee('Examined by Dr Wong');
    }

    public function test_claiming_a_patient_without_saving_findings_does_not_mark_them_examined(): void
    {
        $patient = Patient::create(['subject_id' => 'CLAIMED-100']);
        $doctor = $this->doctor('Dr Wong');
        Examination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
        $this->actingAs($doctor)->get(route('doctor.my-patients'))->assertOk()
            ->assertSee('Not yet examined')->assertDontSee('Examined by Dr Wong');
    }

    public function test_examiner_attribution_does_not_leak_between_patients(): void
    {
        $first = Patient::create(['subject_id' => 'REVIEWED-100']);
        Patient::create(['subject_id' => 'UNREVIEWED-200']);
        $doctor = $this->doctor('Wong');
        $this->recordFindings($first, $doctor);
        $this->actingAs($doctor)->get(route('doctor.patients', ['q' => 'UNREVIEWED-200']))->assertOk()
            ->assertSee('UNREVIEWED-200')->assertSee('Not yet examined')->assertDontSee('Examined by Dr Wong');
    }
}
