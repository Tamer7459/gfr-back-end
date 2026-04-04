<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'specialty',
        'bio',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ── العلاقات ──────────────────────────────

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // ── الوظائف ───────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function journals()
{
    return $this->hasMany(Journal::class);
}

public function reviews()
{
    return $this->hasMany(Review::class, 'reviewer_id');
}

public function conferences()
{
    return $this->belongsToMany(
        Conference::class,
        'conference_attendees'
    )->withPivot('status')->withTimestamps();
}

public function organizedConferences()
{
    return $this->hasMany(Conference::class, 'organizer_id');
}

// أضف هذه الدالة
public function getRoleLabelAttribute(): string
{
    return match($this->role) {
        'admin'      => 'مسؤول',
        'researcher' => 'باحث',
        'professor'  => 'أستاذ',
        'reviewer'   => 'مراجع',
        default      => 'مستخدم',
    };
}

}