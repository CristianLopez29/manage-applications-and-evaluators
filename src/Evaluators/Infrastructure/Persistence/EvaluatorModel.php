<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $specialty
 * @property string $created_at
 * @property string $updated_at
 * @property float|null $avg_experience
 * @property int|null $total_candidates
 * @property string|null $candidate_emails
 */
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
