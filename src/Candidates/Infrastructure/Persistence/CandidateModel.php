<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $years_of_experience
 * @property string $cv_content
 * @property string|null $cv_file_path
 * @property string|null $primary_specialty
 * @property string $created_at
 * @property string $updated_at
 */
class CandidateModel extends Model
{
    protected $table = 'candidates';

    protected $fillable = [
        'name',
        'email',
        'years_of_experience',
        'cv_content',
        'cv_file_path',
        'primary_specialty',
        'created_at'
    ];
}
