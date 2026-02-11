<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $candidate_id
 * @property int $evaluator_id
 * @property string $status
 * @property \DateTimeInterface $assigned_at
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class CandidateAssignmentModel extends Model
{
    use HasFactory;

    protected $table = 'candidate_assignments';

    protected $fillable = [
        'candidate_id',
        'evaluator_id',
        'status',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Relation with the candidate
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(\Src\Candidates\Infrastructure\Persistence\CandidateModel::class, 'candidate_id');
    }

    /**
     * Relation with the evaluator
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(EvaluatorModel::class, 'evaluator_id');
    }
}
