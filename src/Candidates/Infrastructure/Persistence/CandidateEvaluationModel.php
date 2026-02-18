<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $candidate_id
 * @property string|null $summary
 * @property array|null $skills
 * @property int|null $years_experience
 * @property string|null $seniority_level
 * @property array|null $raw_response
 * @property string|null $created_at
 */
class CandidateEvaluationModel extends Model
{
    protected $table = 'candidate_evaluations';
    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'summary',
        'skills',
        'years_experience',
        'seniority_level',
        'raw_response',
        'created_at',
    ];

    protected $casts = [
        'skills' => 'array',
        'raw_response' => 'array',
    ];
}

