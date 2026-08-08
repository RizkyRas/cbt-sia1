<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    // relasi inverse ke model Subject
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // relas 1 to many dengan model Answer
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}