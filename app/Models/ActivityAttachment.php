<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttachment extends Model
{
    protected $fillable = [
        'activity_id',
        'name',
        'description',
        'file_path',
        'mime',
        'size',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
