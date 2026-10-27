<?php

namespace Tests\Feature;

use App\Models\EcgImage;
use App\Models\EcgInterpretation;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExaminationReportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'doctor', string $name = 'Wong'): User
    {
        return User::factory()->create(['role' => $role, 'name' => $name, 'status' => 'active']);
    }

    private function record(Patient $patient, User $doctor, array $attributes = []): EcgInterpretation
    {
        $exam = Examination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
        $image = EcgImage::create(['patient_id' => $patient->id, 'uploaded_by' => $doctor->id, 'original_name' => 'report-ecg.png', 'storage_path' => 'test/report.png', 'mime_type' => 'image/png', 'file_size' => 100]);
        return EcgInterpretation::create(array_merge([
            'ecg_image_id' => $image->id, 'examination_id' => $exam->id, 'doctor_id' => $doctor->id,
            'category' => 'Normal', 'rhythm' => 'Sinus rhythm', 'assessment' => 'Normal',
            'lead_findings' => ['I' => ['signal_quality' => 'Adequate', 't_wave' => 'Normal', 'st_elevation_mm' => 0]],
            'overall' => ['primary_interpretation' => 'Normal ECG', 'mi_localisation' => ['Anterior', 'Lateral']],
            'stemi' => ['st_elevation_present' => 'Yes', 'elevation_leads' => ['I', 'II'], 'elevation_measurements' => ['I' => 0, 'II' => 1.25], 'reciprocal_leads' => ['I', 'II', 'aVR']],
            'notes' => 'Review saved before completion.', 'annotation_complete' => false,
        ], $attributes));
    }

    public function test_doctor_report_list_includes_partial_saved_records_but_not_another_doctors_patients_or_claims(): void
    {
        $doctor = $this->user();
        $other = $this->user('doctor', 'Private Doctor');
        $mine = Patient::create(['subject_id' => 'REPORT-MINE']);
        $theirs = Patient::create(['subject_id' => 'REPORT-OTHER']);
        $claimed = Patient::create(['subject_id' => 'CLAIMED-ONLY']);
        $this->record($mine, $doctor);
        $this->record($mine, $other);
        $this->record($theirs, $other);
        Examination::create(['patient_id' => $claimed->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
        $this->actingAs($doctor)->get(route('doctor.reports'))->assertOk()
            ->assertSee('REPORT-MINE')->assertSee('Examined by Dr Wong')->assertDontSee('Private Doctor')->assertDontSee('REPORT-OTHER')->assertDontSee('CLAIMED-ONLY')
            ->assertViewHas('patients', fn ($patients) => $patients->total() === 1 && $patients->first()->ecgImages->flatMap->interpretations->count() === 1);
        $this->get(route('doctor.reports', ['q' => 'absent']))->assertOk()->assertDontSee('REPORT-MINE');
        $this->get(route('doctor.reports.show', $theirs))->assertNotFound();
        $this->get(route('doctor.reports.show', $claimed))->assertNotFound();
    }

    public function test_patient_report_preserves_all_saved_examinations_and_images_and_unanswered_fields(): void
    {
        $doctor = $this->user();
        $patient = Patient::create(['subject_id' => 'REPORT-DETAIL', 'age_category' => 'Adult', 'sex' => 'Female']);
        $first = $this->record($patient, $doctor);
        $second = $this->record($patient, $doctor, ['ecg_image_id' => $first->ecg_image_id, 'notes' => 'Second examination notes.']);
        $third = $this->record($patient, $doctor, ['examination_id' => $second->examination_id, 'notes' => 'Another ECG in the second examination.']);
        $response = $this->actingAs($doctor)->get(route('doctor.reports.show', $patient))->assertOk()
            ->assertSee('Lead Findings - I')->assertSee('Lead Findings - V6')->assertSee('Adequate')->assertSee('Not recorded')
            ->assertSee('0 mm')->assertSee('I: 0 mm; II: 1.25 mm')->assertSee('I, II, aVR')->assertSee('Anterior, Lateral')
            ->assertSee('Overall ECG Interpretation')->assertSee('STEMI Specific Annotation')->assertSee('Review saved before completion.')
            ->assertSee('Second examination notes.')->assertSee('Another ECG in the second examination.')->assertSee('Partial findings saved')->assertSee('Download Excel (.xlsx)')->assertSee('Download PDF')->assertSee('Download PNG')->assertDontSee('Download JPG');
        $response->assertViewHas('report', function ($report) use ($first, $second, $third) {
            $this->assertSame([$second->id, $third->id, $first->id], array_column($report['records'], 'id'));
            $this->assertCount(15, $report['records'][0]['sections']);
            $this->assertCount(11, $report['records'][0]['sections'][0]['rows']);
            return $report['patient']['label'] === 'REPORT-DETAIL';
        });
    }

    public function test_report_page_and_export_payload_exclude_other_doctors_answers_for_shared_patient(): void
    {
        $doctor = $this->user();
        $patient = Patient::create(['subject_id' => 'REPORT-SHARED']);
        $mine = $this->record($patient, $doctor);
        $this->record($patient, $this->user('doctor', 'Dr Hidden'), ['notes' => 'Hidden private report notes']);
        $this->actingAs($doctor)->get(route('doctor.reports.show', $patient))->assertOk()
            ->assertDontSee('Hidden private report notes')->assertDontSee('Dr Hidden')
            ->assertViewHas('report', fn ($report) => array_column($report['records'], 'id') === [$mine->id]);
    }

    public function test_admin_and_consultant_can_preview_all_doctor_records_but_cannot_post_to_report_routes(): void
    {
        $patient = Patient::create(['subject_id' => 'REPORT-ALL']);
        $this->record($patient, $this->user('doctor', 'Dr Wong'));
        $this->record($patient, $this->user('doctor', 'Dr Lim'));
        foreach (['admin', 'consultant'] as $role) {
            $this->actingAs($this->user($role))->get(route($role.'.reports'))->assertOk()->assertSee('REPORT-ALL')->assertSee('Dr Wong')->assertSee('Dr Lim');
            $this->get(route($role.'.reports.show', $patient))->assertOk()->assertViewHas('report', fn ($report) => count($report['records']) === 2);
            $this->post(route($role.'.reports.show', $patient))->assertStatus(405);
            $this->get(route('doctor.reports.show', $patient))->assertForbidden();
        }
        $this->assertDatabaseCount('ecg_interpretations', 2);
    }

    public function test_earlier_format_findings_and_literal_notes_are_preserved_safely_in_export_data(): void
    {
        $doctor = $this->user();
        $patient = Patient::create(['subject_id' => 'REPORT-LEGACY']);
        $note = '</script><script>alert("unsafe")</script> Clinical note';
        $this->record($patient, $doctor, ['lead_findings' => null, 'overall' => null, 'stemi' => null, 'subcategory' => 'Earlier subtype', 'notes' => $note]);
        $response = $this->actingAs($doctor)->get(route('doctor.reports.show', $patient))->assertOk()
            ->assertSee('Earlier Format Findings')->assertSee('Earlier subtype')->assertSee($note)->assertDontSee($note, false);
        preg_match('/<script type="application\/json" id="examinationReportData">(.*?)<\/script>/s', $response->getContent(), $matches);
        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($note, $data['records'][0]['sections'][15]['rows'][0]['value']);
    }

    public function test_reports_require_login(): void
    {
        $this->get(route('doctor.reports'))->assertRedirect(route('login'));
        $this->get(route('consultant.reports.show', 1))->assertRedirect(route('login'));
    }
}
