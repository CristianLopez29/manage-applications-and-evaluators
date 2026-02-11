<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $years_of_experience
 * @property string $cv_content
 * @property string $created_at
 * @property string $updated_at
 */
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
