<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamHistoryAnswer extends Model
{
    protected $guarded = [];

    // relasi inverse ke model ExamHistory
    public function examHistory(): BelongsTo
    {
        return $this->belongsTo(ExamHistory::class);
    }

    // relasi inverse ke model Question
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // relasi inverse ke model Answer (pilihan yang dipilih siswa)
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}