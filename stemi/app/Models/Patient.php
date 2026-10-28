<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = ['subject_id','age_category','sex','case_status'];

    public function ecgImages() { return $this->hasMany(EcgImage::class); }
    public function examinations() { return $this->hasMany(Examination::class); }
    public function examinedExaminations()
    {
        return $this->hasMany(Examination::class)->whereHas('ecgInterpretations');
    }
    public function latestExamination() { return $this->hasOne(Examination::class)->latestOfMany(); }
}
