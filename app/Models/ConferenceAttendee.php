<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenceAttendee extends Model
{
    protected $fillable = [
        'conference_id',
        'user_id',
        'status',
        'certificate_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }
}