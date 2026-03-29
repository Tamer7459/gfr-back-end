<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'reviewer_id',
        'feedback',
        'decision',
    ];

    // ── العلاقات ──────────────────────────────

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}