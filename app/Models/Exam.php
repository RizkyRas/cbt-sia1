<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $guarded = [];

    protected $casts = [
        'threshold' => 'decimal:2',
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
        'exact_time' => 'boolean',
        'is_available' => 'boolean',
    ];

    // relasi many-to-many dengan model Subject
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)->withPivot('qty');
    }

    // relasi 1 to many dengan model ExamHistory
    public function histories(): HasMany
    {
        return $this->hasMany(ExamHistory::class);
    }
}