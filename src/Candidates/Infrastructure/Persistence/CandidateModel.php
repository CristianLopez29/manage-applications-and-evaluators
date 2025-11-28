<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CandidateModel extends Model
{
    use HasFactory;

    protected $table = 'candidates';

    protected $fillable = [
        'name',
        'email',
        'years_of_experience',
        'cv_content',
        'created_at'
    ];
}
