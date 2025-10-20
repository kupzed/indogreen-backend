<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttachment extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivityName(): string
    {
        return $this->name ?: 'Activity Attachment #' . $this->id;
    }

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
