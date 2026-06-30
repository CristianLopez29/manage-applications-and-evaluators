<?php

declare(strict_types=1);

namespace Src\Evaluators\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $candidate_id
 * @property int $evaluator_id
 * @property string|null $from_status
 * @property string $to_status
 * @property \Illuminate\Support\Carbon $occurred_at
 */
class AssignmentHistoryModel extends Model
{
    protected $table = 'assignment_history';

    protected $fillable = [
        'assignment_id',
        'candidate_id',
        'evaluator_id',
        'from_status',
        'to_status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
