<?php

namespace App\Support;

use App\Models\EcgInterpretation;
use App\Models\Patient;
use Illuminate\Support\Collection;

/** Shared, display-ready answers for the report page and all export formats. */
class ExaminationReport
{
    public static function forPatient(Patient $patient, Collection $findings): array
    {
        return [
            'patient' => [
                'id' => $patient->id,
                'label' => $patient->subject_id,
                'age' => self::answer($patient->age_category),
                'sex' => self::answer($patient->sex),
            ],
            'generatedAt' => now()->format('d M Y H:i').' '.config('app.timezone'),
            'records' => $findings->map(fn (EcgInterpretation $finding) => self::record($finding))->values()->all(),
        ];
    }

    public static function doctorName(?string $name): string
    {
        $name = trim($name ?? '');
        if ($name === '') return 'Doctor unavailable';
        return preg_match('/^Dr\.?\s/i', $name) ? $name : 'Dr '.$name;
    }

    private static function record(EcgInterpretation $finding): array
    {
        $sections = [];
        foreach (EcgAnnotationSchema::leads() as $lead) {
            $sections[] = ['title' => 'Lead Findings - '.$lead, 'rows' => self::rows(EcgAnnotationSchema::leadFields(), $finding->lead_findings[$lead] ?? [])];
        }
        $sections[] = ['title' => 'Overall ECG Interpretation', 'rows' => self::rows(EcgAnnotationSchema::overallFields(), $finding->overall ?? [])];
        $sections[] = ['title' => 'STEMI Specific Annotation', 'rows' => self::rows(EcgAnnotationSchema::stemiFields(), $finding->stemi ?? [])];
        $legacy = !$finding->lead_findings && !$finding->overall && !$finding->stemi;
        if ($legacy) {
            $sections[] = ['title' => 'Earlier Format Findings', 'rows' => [
                ['label' => 'ECG category', 'value' => self::answer($finding->category)],
                ['label' => 'ECG subcategory', 'value' => self::answer($finding->subcategory)],
                ['label' => 'ECG rhythm', 'value' => self::answer($finding->rhythm)],
                ['label' => 'Overall ECG assessment', 'value' => self::answer($finding->assessment)],
            ]];
        }
        $sections[] = ['title' => "Doctor's Notes", 'rows' => [['label' => 'ECG notes', 'value' => self::answer($finding->notes)]]];
        if (filled($finding->examination?->doctor_notes) && $finding->examination->doctor_notes !== $finding->notes) {
            $sections[] = ['title' => 'Examination Notes', 'rows' => [['label' => 'Examination notes', 'value' => $finding->examination->doctor_notes]]];
        }
        return [
            'id' => $finding->id,
            'doctor' => self::doctorName($finding->doctor?->name),
            'examinationId' => $finding->examination_id,
            'examinationStatus' => ucwords(str_replace('_', ' ', $finding->examination?->status ?? 'Not recorded')),
            'image' => $finding->image?->original_name ?? 'Image unavailable',
            'updatedAt' => $finding->updated_at->format('d M Y H:i').' '.config('app.timezone'),
            'status' => $finding->annotation_complete ? 'All required findings recorded' : ($legacy ? 'Earlier format' : 'Partial findings saved'),
            'sections' => $sections,
        ];
    }

    private static function rows(array $definitions, array $data): array
    {
        $rows = [];
        foreach ($definitions as $key => $definition) {
            $value = $data[$key] ?? null;
            if (($definition['type'] ?? '') === 'measurements') {
                $measurements = [];
                foreach (EcgAnnotationSchema::leads() as $lead) {
                    if (isset($value[$lead]) && $value[$lead] !== '') $measurements[] = $lead.': '.self::answer($value[$lead]).' mm';
                }
                $answer = $measurements ? implode('; ', $measurements) : 'Not recorded';
            } else {
                $answer = self::answer($value);
                if (($definition['type'] ?? '') === 'number' && $answer !== 'Not recorded') $answer .= ' mm';
            }
            $rows[] = ['label' => $definition['label'], 'value' => $answer];
        }
        return $rows;
    }

    private static function answer(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value, fn ($item) => $item !== null && $item !== ''));
        }
        return $value === null || trim((string) $value) === '' ? 'Not recorded' : (string) $value;
    }
}
