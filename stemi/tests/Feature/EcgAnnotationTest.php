<?php

namespace Tests\Feature;

use App\Models\EcgImage;
use App\Models\EcgInterpretation;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use App\Support\EcgAnnotationSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcgAnnotationTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'status' => 'active']);
        $patient = Patient::create(['subject_id' => 'TEST-'.uniqid(), 'case_status' => 'in_progress']);
        $exam = Examination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
        return [$doctor, $patient, $exam, $this->image($patient, $doctor)];
    }

    private function image(Patient $patient, User $uploader): EcgImage
    {
        return EcgImage::create([
            'patient_id' => $patient->id, 'uploaded_by' => $uploader->id,
            'original_name' => 'ecg.jpg', 'storage_path' => 'test/ecg.jpg',
            'mime_type' => 'image/jpeg', 'file_size' => 100, 'width' => 1600, 'height' => 1200,
        ]);
    }

    private function payload(EcgImage $image): array
    {
        $leads = [];
        foreach (EcgAnnotationSchema::leads() as $lead) {
            foreach (EcgAnnotationSchema::leadFields() as $key => $definition) {
                $leads[$lead][$key] = $definition['options'][0] ?? null;
            }
        }
        return [
            'ecg_image_id' => $image->id,
            'lead_findings' => $leads,
            'overall' => [
                'primary_interpretation' => 'Normal ECG', 'mi_localisation' => [],
                'stemi_interpretation' => 'No STEMI pattern', 'nstemi_interpretation' => 'Not Present',
                'mi_evidence' => 'No MI pattern identified', 'confidence' => 'High',
            ],
            'stemi' => [
                'st_elevation_present' => 'No', 'elevation_leads' => [], 'elevation_measurements' => [],
                'contiguous_groups' => ['Not applicable'], 'reciprocal_depression' => 'Absent',
                'reciprocal_leads' => [], 'new_lbbb' => 'Unknown', 'criteria_met' => 'No',
                'occlusion_suspected' => 'No', 'interpretation' => 'Absent',
            ],
            'extraction' => ['left' => 0.05, 'top' => 0.2, 'width' => 0.9, 'height' => 0.6,
                'column_breaks' => [0.25, 0.5, 0.75], 'row_breaks' => [0.3333, 0.6667], 'confirmed' => 1],
            'notes' => 'Clinician-entered test interpretation.', 'complete' => 0,
        ];
    }

    public function test_annotations_round_trip_per_image_with_multiple_regions_and_decimal_measurements(): void
    {
        [$doctor, $patient, $exam, $image] = $this->scenario();
        $payload = $this->payload($image);
        $payload['overall']['primary_interpretation'] = 'Myocardial infarction pattern';
        $payload['overall']['mi_localisation'] = ['Anterior', 'Lateral'];
        $payload['lead_findings']['V2']['st_elevation_mm'] = 2.75;
        $payload['stemi']['elevation_measurements']['V2'] = 2.75;
        $payload['stemi']['st_elevation_present'] = 'Yes';
        $payload['stemi']['elevation_leads'] = ['V2'];
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $payload)->assertSessionHasNoErrors();
        $finding = EcgInterpretation::sole();
        $this->assertSame($exam->id, $finding->examination_id);
        $this->assertSame(['Anterior', 'Lateral'], $finding->overall['mi_localisation']);
        $this->assertEquals(2.75, $finding->lead_findings['V2']['st_elevation_mm']);
        $this->assertTrue($finding->annotation_complete);
        $this->assertSame('in_progress', $exam->fresh()->status);

        $payload['notes'] = 'Updated after review';
        $this->post(route('doctor.interpretations.store', $patient), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ecg_interpretations', 1);
        $this->assertSame('Updated after review', $finding->fresh()->notes);
    }

    public function test_json_save_returns_the_selected_image_without_losing_other_image_drafts(): void
    {
        [$doctor, $patient, $exam, $first] = $this->scenario();
        $second = $this->image($patient, $doctor);
        $this->actingAs($doctor)->postJson(route('doctor.interpretations.store', $patient), $this->payload($first))
            ->assertOk()->assertJsonPath('completed', false)
            ->assertJsonPath('interpretation.ecg_image_id', $first->id)
            ->assertJsonPath('interpretation.extraction.confirmed', 1)
            ->assertJsonPath('interpretation.annotation_complete', true);
        $this->postJson(route('doctor.interpretations.store', $patient), ['ecg_image_id' => $second->id, 'notes' => 'Second draft'])
            ->assertOk()->assertJsonPath('interpretation.notes', 'Second draft')
            ->assertJsonPath('interpretation.annotation_complete', false);
        $this->assertDatabaseCount('ecg_interpretations', 2);
        $this->assertSame('Clinician-entered test interpretation.', EcgInterpretation::where('ecg_image_id', $first->id)->sole()->notes);
    }
    public function test_partial_draft_is_allowed_but_cannot_complete_the_examination(): void
    {
        [$doctor, $patient, $exam, $image] = $this->scenario();
        $payload = ['ecg_image_id' => $image->id, 'lead_findings' => ['I' => ['signal_quality' => 'Adequate']]];
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $payload)->assertSessionHasNoErrors();
        $this->assertFalse(EcgInterpretation::sole()->annotation_complete);
        $payload['complete'] = 1;
        $this->post(route('doctor.interpretations.store', $patient), $payload)
            ->assertSessionHasErrors(['lead_findings.II', 'overall', 'stemi', 'extraction']);
        $this->assertSame('in_progress', $exam->fresh()->status);
    }

    public function test_every_image_must_have_complete_annotations_before_case_completion(): void
    {
        [$doctor, $patient, $exam, $first] = $this->scenario();
        $second = $this->image($patient, $doctor);
        $firstPayload = $this->payload($first);
        $firstPayload['complete'] = 1;
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $firstPayload)->assertSessionHasErrors('complete');
        $this->assertDatabaseCount('ecg_interpretations', 0);
        $firstPayload['complete'] = 0;
        $this->post(route('doctor.interpretations.store', $patient), $firstPayload)->assertSessionHasNoErrors();
        $secondPayload = $this->payload($second);
        $secondPayload['complete'] = 1;
        $this->post(route('doctor.interpretations.store', $patient), $secondPayload)->assertSessionHasNoErrors();
        $this->assertSame('completed', $exam->fresh()->status);
        $this->assertNotNull($exam->fresh()->completed_at);
        $this->assertSame('completed', $patient->fresh()->case_status);
        $this->assertDatabaseCount('ecg_interpretations', 2);
    }

    public function test_read_only_roles_and_other_doctors_cannot_save_an_examination(): void
    {
        [$doctor, $patient, $exam, $image] = $this->scenario();
        foreach (['consultant', 'admin', 'doctor'] as $role) {
            $other = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($other)->post(route('doctor.interpretations.store', $patient), $this->payload($image))->assertForbidden();
        }
        $this->assertDatabaseCount('ecg_interpretations', 0);
        $exam->update(['status' => 'completed']);
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $this->payload($image))->assertForbidden();
    }

    public function test_image_must_belong_to_the_patient_being_examined(): void
    {
        [$doctor, $patient] = $this->scenario();
        [, , , $otherImage] = $this->scenario();
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $this->payload($otherImage))->assertSessionHasErrors('ecg_image_id');
        $this->assertDatabaseCount('ecg_interpretations', 0);
    }

    public function test_invalid_options_leads_measurements_and_crop_boundaries_are_rejected(): void
    {
        [$doctor, $patient, , $image] = $this->scenario();
        $payload = $this->payload($image);
        $payload['lead_findings']['I']['signal_quality'] = 'Automatically normal';
        $payload['lead_findings']['II']['st_elevation_mm'] = -1;
        $payload['lead_findings']['III']['st_depression_mm'] = 101;
        $payload['stemi']['elevation_leads'] = ['V99'];
        $payload['extraction']['left'] = 0.8;
        $payload['extraction']['column_breaks'] = [0.6, 0.5, 0.75];
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $payload)
            ->assertSessionHasErrors(['lead_findings.I.signal_quality', 'lead_findings.II.st_elevation_mm',
                'lead_findings.III.st_depression_mm', 'stemi.elevation_leads.0', 'extraction.width', 'extraction.column_breaks']);
        $this->assertDatabaseCount('ecg_interpretations', 0);
    }

    public function test_completion_rejects_conflicting_measurements_and_absent_findings_with_selected_leads(): void
    {
        [$doctor, $patient, , $image] = $this->scenario();
        $payload = $this->payload($image);
        $payload['complete'] = 1;
        $payload['lead_findings']['I']['st_elevation_mm'] = 1;
        $payload['stemi']['elevation_measurements']['I'] = 2;
        $payload['stemi']['elevation_leads'] = ['I'];
        $payload['stemi']['reciprocal_leads'] = ['II'];
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $payload)
            ->assertSessionHasErrors(['stemi.elevation_measurements.I', 'stemi.elevation_leads', 'stemi.reciprocal_leads']);
    }

    public function test_autosave_preserves_a_single_lead_field_then_updates_and_clears_it(): void
    {
        [$doctor, $patient, $exam, $image] = $this->scenario();
        $payload = [
            'ecg_image_id' => $image->id, 'complete' => 0,
            'lead_findings' => ['V2' => ['signal_quality' => 'Adequate']],
            'overall' => [], 'stemi' => [], 'extraction' => null,
        ];
        $this->actingAs($doctor)->postJson(route('doctor.interpretations.store', $patient), $payload)
            ->assertOk()->assertJsonPath('interpretation.lead_findings.V2.signal_quality', 'Adequate')
            ->assertJsonPath('interpretation.annotation_complete', false);

        $payload['lead_findings']['V2']['signal_quality'] = 'Limited but interpretable';
        $payload['overall']['mi_localisation'] = ['Anterior', 'Lateral'];
        $this->postJson(route('doctor.interpretations.store', $patient), $payload)
            ->assertOk()->assertJsonPath('interpretation.lead_findings.V2.signal_quality', 'Limited but interpretable')
            ->assertJsonPath('interpretation.overall.mi_localisation', ['Anterior', 'Lateral']);

        $payload['lead_findings']['V2']['signal_quality'] = null;
        $payload['overall']['mi_localisation'] = [];
        $this->postJson(route('doctor.interpretations.store', $patient), $payload)
            ->assertOk()->assertJsonPath('interpretation.lead_findings.V2.signal_quality', null)
            ->assertJsonPath('interpretation.overall.mi_localisation', [])
            ->assertJsonPath('interpretation.annotation_complete', false);
        $this->assertDatabaseCount('ecg_interpretations', 1);
        $this->assertSame('in_progress', $exam->fresh()->status);
    }

    public function test_autosave_preserves_intermediate_cross_field_choices_without_marking_them_complete(): void
    {
        [$doctor, $patient, , $image] = $this->scenario();
        $payload = $this->payload($image);
        $payload['lead_findings']['I']['st_elevation_mm'] = 1;
        $payload['stemi']['elevation_measurements']['I'] = 2;
        $payload['stemi']['elevation_leads'] = ['I'];
        $payload['stemi']['reciprocal_leads'] = ['II'];
        $payload['stemi']['contiguous_groups'] = ['Not applicable', 'Inferior (II, III, aVF)'];
        $this->actingAs($doctor)->postJson(route('doctor.interpretations.store', $patient), $payload)
            ->assertOk()->assertJsonPath('interpretation.annotation_complete', false)
            ->assertJsonPath('interpretation.stemi.elevation_leads', ['I'])
            ->assertJsonPath('interpretation.stemi.reciprocal_leads', ['II']);

        $payload['stemi']['elevation_leads'] = [];
        $payload['stemi']['reciprocal_leads'] = [];
        $payload['stemi']['elevation_measurements'] = [];
        $payload['stemi']['contiguous_groups'] = ['Not applicable'];
        $this->postJson(route('doctor.interpretations.store', $patient), $payload)
            ->assertOk()->assertJsonPath('interpretation.annotation_complete', true)
            ->assertJsonPath('interpretation.stemi.elevation_leads', [])
            ->assertJsonPath('interpretation.stemi.reciprocal_leads', []);
    }

    public function test_mi_completion_requires_location_and_crop_confirmation(): void
    {
        [$doctor, $patient, $exam, $image] = $this->scenario();
        $payload = $this->payload($image);
        $payload['complete'] = 1;
        $payload['overall']['primary_interpretation'] = 'Myocardial infarction pattern';
        $payload['extraction']['confirmed'] = 0;
        $this->actingAs($doctor)->post(route('doctor.interpretations.store', $patient), $payload)
            ->assertSessionHasErrors(['overall.mi_localisation', 'extraction.confirmed']);
        $payload['overall']['mi_localisation'] = ['Indeterminate'];
        $payload['extraction']['confirmed'] = 1;
        $this->post(route('doctor.interpretations.store', $patient), $payload)->assertSessionHasNoErrors();
        $this->assertSame('completed', $exam->fresh()->status);
    }
}
