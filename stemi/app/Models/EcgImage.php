<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcgImage extends Model
{
    protected $fillable = [
        'patient_id', 'uploaded_by', 'original_name', 'storage_path',
        'mime_type', 'file_size', 'width', 'height',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function interpretations() { return $this->hasMany(EcgInterpretation::class); }
}
