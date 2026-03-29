<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conference extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'submission_deadline',
        'status',
        'max_attendees',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    // ── العلاقات ──────────────────────────────

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attendees()
    {
        return $this->hasMany(ConferenceAttendee::class);
    }

    public function registeredUsers()
    {
        return $this->belongsToMany(
            User::class,
            'conference_attendees'
        )->withPivot('status', 'certificate_path')
         ->withTimestamps();
    }

    // ── هل المستخدم مسجل ─────────────────────
    public function isRegistered(int $userId): bool
    {
        return $this->attendees()
            ->where('user_id', $userId)
            ->exists();
    }

    // ── عدد المسجلين ─────────────────────────
    public function getAttendeesCountAttribute(): int
    {
        return $this->attendees()
            ->where('status', '!=', 'cancelled')
            ->count();
    }
}