<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/** Shared vocabulary for clinician-entered annotations; no diagnosis is inferred. */
class EcgAnnotationSchema
{
    public static function leads(): array
    {
        return ['I', 'II', 'III', 'aVR', 'aVL', 'aVF', 'V1', 'V2', 'V3', 'V4', 'V5', 'V6'];
    }

    public static function leadFields(): array
    {
        return [
            'signal_quality' => ['label' => 'Signal quality', 'options' => ['Adequate', 'Limited but interpretable', 'Uninterpretable']],
            'p_wave' => ['label' => 'P-wave finding', 'options' => ['Normal', 'Abnormal morphology', 'Absent', 'Not assessable']],
            'pr_interval' => ['label' => 'PR interval', 'options' => ['Normal', 'Prolonged', 'Short', 'Not assessable, where measurable']],
            'qrs_morphology' => ['label' => 'QRS morphology', 'options' => ['No significant abnormality', 'Wide QRS', 'Low voltage', 'Bundle branch block pattern', 'Other abnormality', 'Not assessable']],
            'qrs_duration' => ['label' => 'QRS Duration', 'options' => ['Normal', 'Prolonged', 'Not assessable']],
            'pathological_q' => ['label' => 'Pathological Q wave', 'options' => ['Absent', 'Present', 'Borderline / uncertain', 'Not assessable']],
            'st_segment' => ['label' => 'ST-segment finding', 'options' => ['No significant deviation', 'ST elevation', 'ST depression', 'Elevation and depression / complex pattern', 'Not assessable']],
            'st_elevation_mm' => ['label' => 'ST Elevation Measurement (mm)', 'type' => 'number'],
            'st_depression_mm' => ['label' => 'ST Depression Measurement (mm)', 'type' => 'number'],
            't_wave' => ['label' => 'T-wave finding', 'options' => ['Normal', 'Inverted', 'Hyperacute or unusually prominent', 'Flattened', 'Other abnormality', 'Not assessable']],
            'confidence' => ['label' => "Doctor's confidence for this lead", 'options' => ['High', 'Moderate', 'Low']],
        ];
    }

    public static function localisations(): array
    {
        return ['Anterior', 'Anteroseptal', 'Anterolateral', 'Inferior', 'Inferolateral', 'Lateral', 'Posterior', 'Right ventricular', 'Multiple regions', 'Indeterminate'];
    }

    public static function contiguousGroups(): array
    {
        return ['Anterior (V1–V4)', 'Septal (V1–V2)', 'Lateral (I, aVL, V5–V6)', 'Inferior (II, III, aVF)', 'Other / multiple groups', 'Not applicable', 'Uncertain'];
    }

    public static function overallFields(): array
    {
        return [
            'primary_interpretation' => ['label' => 'Primary ECG interpretation', 'options' => ['Normal ECG', 'Myocardial infarction pattern', 'Ischemic changes without definite MI pattern', 'Other abnormal ECG', 'Nondiagnostic / insufficient quality']],
            'mi_localisation' => ['label' => 'MI localisation (select all that apply)', 'options' => self::localisations(), 'multiple' => true],
            'stemi_interpretation' => ['label' => 'STEMI interpretation', 'options' => ['STEMI pattern present', 'No STEMI pattern', 'Uncertain / needs clinical correlation', 'Not assessable']],
            'nstemi_interpretation' => ['label' => 'NSTEMI Interpretation', 'options' => ['Present', 'Not Present']],
            'mi_evidence' => ['label' => 'Evidence of MI', 'options' => ['Acute MI suspected', 'Old infarction pattern', 'Acute vs old cannot be determined from ECG alone', 'No MI pattern identified']],
            'confidence' => ['label' => 'Overall confidence', 'options' => ['High', 'Moderate', 'Low']],
        ];
    }

    public static function stemiFields(): array
    {
        return [
            'st_elevation_present' => ['label' => 'ST elevation present', 'options' => ['Yes', 'No', 'Uncertain']],
            'elevation_leads' => ['label' => 'Leads with ST elevation', 'options' => self::leads(), 'multiple' => true],
            'elevation_measurements' => ['label' => 'ST elevation measurement (mm per lead)', 'type' => 'measurements'],
            'contiguous_groups' => ['label' => 'Contiguous leads involved', 'options' => self::contiguousGroups(), 'multiple' => true],
            'reciprocal_depression' => ['label' => 'Reciprocal ST depression', 'options' => ['Present', 'Absent', 'Uncertain']],
            'reciprocal_leads' => ['label' => 'Leads with reciprocal ST depression', 'options' => self::leads(), 'multiple' => true],
            'new_lbbb' => ['label' => 'New or presumed-new LBBB', 'options' => ['Yes', 'No', 'Unknown']],
            'criteria_met' => ['label' => 'STEMI ECG criteria met', 'options' => ['Yes', 'No', 'Uncertain']],
            'occlusion_suspected' => ['label' => 'Acute coronary occlusion suspected', 'options' => ['Yes', 'No', 'Uncertain']],
            'interpretation' => ['label' => 'STEMI interpretation', 'options' => ['Present', 'Absent', 'Uncertain', 'Not assessable']],
        ];
    }

    /** Validate crop geometry always; require consistent clinical fields on completion. */
    public static function consistencyErrors(array $data, bool $complete): array
    {
        $errors = [];
        $crop = $data['extraction'] ?? [];
        if (is_array($crop)) {
            foreach ([['left', 'width'], ['top', 'height']] as [$offset, $size]) {
                if (is_numeric($crop[$offset] ?? null) && is_numeric($crop[$size] ?? null)
                    && (float) $crop[$offset] + (float) $crop[$size] > 1.000001) {
                    $errors["extraction.$size"] = 'The ECG crop must remain inside the source image.';
                }
            }
            foreach (['column_breaks', 'row_breaks'] as $axis) {
                $previous = 0;
                foreach (is_array($crop[$axis] ?? null) ? $crop[$axis] : [] as $point) {
                    if (!is_numeric($point) || (float) $point <= $previous) {
                        $errors["extraction.$axis"] = 'Lead boundaries must be in increasing order.';
                        break;
                    }
                    $previous = (float) $point;
                }
            }
        }
        // Autosave must preserve intermediate choices while the doctor works
        // through related fields. Completeness and clinical consistency are
        // assessed separately and enforced only when completing an examination.
        if (!$complete) {
            return $errors;
        }
        foreach (self::leads() as $lead) {
            $leadMm = data_get($data, "lead_findings.$lead.st_elevation_mm");
            $stemiMm = data_get($data, "stemi.elevation_measurements.$lead");
            if (is_numeric($leadMm) && is_numeric($stemiMm) && abs((float) $leadMm - (float) $stemiMm) > 0.000001) {
                $errors["stemi.elevation_measurements.$lead"] = "The ST elevation measurement for $lead must match its lead findings.";
            }
        }
        foreach ([['st_elevation_present', 'Yes', 'No', 'elevation_leads'], ['reciprocal_depression', 'Present', 'Absent', 'reciprocal_leads']] as [$flag, $positive, $negative, $leads]) {
            $selected = data_get($data, "stemi.$leads", []);
            if (data_get($data, "stemi.$flag") === $negative && !empty($selected)) {
                $errors["stemi.$leads"] = 'Selected leads conflict with the absence of this finding.';
            }
            if ($complete && data_get($data, "stemi.$flag") === $positive && empty($selected)) {
                $errors["stemi.$leads"] = 'Specify at least one applicable lead.';
            }
        }
        $groups = data_get($data, 'stemi.contiguous_groups', []);
        if (is_array($groups) && count($groups) > 1 && (in_array('Not applicable', $groups, true) || in_array('Uncertain', $groups, true))) {
            $errors['stemi.contiguous_groups'] = 'Choose lead groups, Not applicable, or Uncertain without combining these alternatives.';
        }
        if ($complete && data_get($data, 'overall.primary_interpretation') === 'Myocardial infarction pattern' && !data_get($data, 'overall.mi_localisation')) {
            $errors['overall.mi_localisation'] = 'Select the applicable MI locations or Indeterminate.';
        }
        return $errors;
    }
    public static function annotationRules(bool $complete): array
    {
        $required = $complete ? 'required' : 'nullable';
        $rules = ['lead_findings' => [$required, 'array:'.implode(',', self::leads())]];
        foreach (self::leads() as $lead) {
            $rules["lead_findings.$lead"] = [$required, 'array:'.implode(',', array_keys(self::leadFields()))];
            foreach (self::leadFields() as $field => $definition) {
                $rules["lead_findings.$lead.$field"] = isset($definition['options'])
                    ? [$required, 'string', Rule::in($definition['options'])]
                    : ['nullable', 'numeric', 'between:0,100'];
            }
        }
        foreach (['overall' => self::overallFields(), 'stemi' => self::stemiFields()] as $section => $fields) {
            $rules[$section] = [$required, 'array:'.implode(',', array_keys($fields))];
            foreach ($fields as $field => $definition) {
                $key = "$section.$field";
                if (($definition['type'] ?? '') === 'measurements') {
                    $rules[$key] = ['nullable', 'array:'.implode(',', self::leads())];
                    $rules["$key.*"] = ['nullable', 'numeric', 'between:0,100'];
                } elseif ($definition['multiple'] ?? false) {
                    $rules[$key] = [($complete && $field === 'contiguous_groups') ? 'required' : 'nullable', 'array', 'max:12'];
                    $rules["$key.*"] = ['required', 'string', 'distinct', Rule::in($definition['options'])];
                } else {
                    $rules[$key] = [$required, 'string', Rule::in($definition['options'])];
                }
            }
        }
        $rules['extraction'] = [$required, 'array:left,top,width,height,column_breaks,row_breaks,confirmed'];
        foreach (['left', 'top'] as $field) $rules["extraction.$field"] = [$required, 'numeric', 'between:0,0.99'];
        foreach (['width', 'height'] as $field) $rules["extraction.$field"] = [$required, 'numeric', 'between:0.01,1'];
        $rules['extraction.column_breaks'] = [$required, 'array', 'size:3', 'list'];
        $rules['extraction.row_breaks'] = [$required, 'array', 'size:2', 'list'];
        $rules['extraction.column_breaks.*'] = ['required', 'numeric', 'between:0.01,0.99'];
        $rules['extraction.row_breaks.*'] = ['required', 'numeric', 'between:0.01,0.99'];
        $rules['extraction.confirmed'] = $complete ? ['required', 'accepted'] : ['nullable', 'boolean'];
        return $rules;
    }
}
