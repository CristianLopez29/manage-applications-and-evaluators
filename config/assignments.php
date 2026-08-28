<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Assignment Lifecycle
|--------------------------------------------------------------------------
|
| Tunables for ProcessOverdueAssignmentsJob. Days an assignment may stay
| overdue before the scheduler escalates it to the admins by email.
|
*/

return [
    'overdue_escalation_days' => (int) env('OVERDUE_ESCALATION_DAYS', 3),
];
