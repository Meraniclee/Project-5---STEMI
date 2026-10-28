<?php

namespace App\Http\Requests;

use App\Support\EcgAnnotationSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveEcgInterpretationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'ecg_image_id' => ['required', 'integer', Rule::exists('ecg_images', 'id')->where('patient_id', $this->route('patient')->id)],
            'notes' => ['nullable', 'string', 'max:10000'],
            'complete' => ['nullable', 'boolean'],
            ...EcgAnnotationSchema::annotationRules($this->boolean('complete')),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach (EcgAnnotationSchema::consistencyErrors($this->all(), $this->boolean('complete')) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        }];
    }
    public function attributes(): array
    {
        $attributes = [];
        foreach (EcgAnnotationSchema::leads() as $lead) {
            foreach (EcgAnnotationSchema::leadFields() as $field => $definition) {
                $attributes["lead_findings.$lead.$field"] = "lead $lead: ".$definition['label'];
            }
        }
        foreach (['overall' => EcgAnnotationSchema::overallFields(), 'stemi' => EcgAnnotationSchema::stemiFields()] as $section => $fields) {
            foreach ($fields as $field => $definition) $attributes["$section.$field"] = "$section: ".$definition['label'];
        }
        return $attributes;
    }
}
