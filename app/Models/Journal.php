<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'editor_id',
        'title',
        'abstract',
        'file_path',
        'status',
    ];

    // ── العلاقات ──────────────────────────────

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ── تسمية الحالة بالعربية ─────────────────
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'      => 'قيد الانتظار',
            'under_review' => 'قيد المراجعة',
            'accepted'     => 'مقبولة',
            'rejected'     => 'مرفوضة',
            default        => $this->status,
        };
    }
}