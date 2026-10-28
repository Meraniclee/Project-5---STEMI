<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcgInterpretation extends Model
{
    protected $fillable = [
        'ecg_image_id', 'examination_id', 'doctor_id',
        'category', 'subcategory', 'rhythm', 'assessment', 'notes',
        'lead_findings', 'overall', 'stemi', 'extraction', 'annotation_complete',
    ];

    protected function casts(): array
    {
        return [
            'lead_findings' => 'array', 'overall' => 'array', 'stemi' => 'array',
            'extraction' => 'array', 'annotation_complete' => 'boolean',
        ];
    }

    public function image() { return $this->belongsTo(EcgImage::class, 'ecg_image_id'); }
    public function examination() { return $this->belongsTo(Examination::class); }
    public function doctor() { return $this->belongsTo(User::class, 'doctor_id'); }
}
