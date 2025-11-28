<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluatorModel extends Model
{
    use HasFactory;

    protected $table = 'evaluators';

    protected $fillable = [
        'name',
        'email',
        'specialty',
        'created_at'
    ];

    /**
     * Relation with assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CandidateAssignmentModel::class, 'evaluator_id');
    }

    /**
     * Relation with candidates through assignments
     */
    public function candidates()
    {
        return $this->hasManyThrough(
            \Src\Candidates\Infrastructure\Persistence\CandidateModel::class,
            CandidateAssignmentModel::class,
            'evaluator_id', // FK in candidate_assignments
            'id', // FK in candidates
            'id', // PK local en evaluators
            'candidate_id' // PK local en candidate_assignments
        );
    }
}
