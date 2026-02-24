<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;

use Src\Evaluators\Domain\Enums\Specialty;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Specialty $specialty
 * @property string $created_at
 * @property string $updated_at
 * @property float|null $avg_experience
 * @property int|null $total_candidates
 * @property string|null $candidate_emails
 */
class EvaluatorModel extends Model
{
    protected $table = 'evaluators';

    protected $fillable = [
        'name',
        'email',
        'specialty',
        'created_at'
    ];

    protected $casts = [
        'specialty' => Specialty::class,
    ];

    /**
     * Relation with assignments
     * @return HasMany<CandidateAssignmentModel, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CandidateAssignmentModel::class, 'evaluator_id');
    }

    /**
     * Relation with candidates through assignments
     * @return HasManyThrough<CandidateModel, CandidateAssignmentModel, $this>
     */
    public function candidates(): HasManyThrough
    {
        return $this->hasManyThrough(
            CandidateModel::class,
            CandidateAssignmentModel::class,
            'evaluator_id', // FK in candidate_assignments
            'id', // FK in candidates
            'id', // PK local en evaluators
            'candidate_id' // PK local en candidate_assignments
        );
    }
}
