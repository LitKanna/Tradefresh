<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $fillable = [
        'quote_id',
        'conversation_id',
        'sender_id',
        'sender_type',
        'recipient_id',
        'recipient_type',
        'message',
        'content',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically sync message <-> content fields on create AND update
        static::saving(function ($message) {
            // Always sync both fields to maintain consistency
            if ($message->message && !$message->content) {
                $message->content = $message->message;
            }
            if ($message->content && !$message->message) {
                $message->message = $message->content;
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get unread messages
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get messages between two users
     */
    public function scopeBetweenUsers(Builder $query, int $senderId, string $senderType, int $recipientId, string $recipientType): Builder
    {
        return $query->where(function ($q) use ($senderId, $senderType, $recipientId, $recipientType) {
            $q->where(function ($subQ) use ($senderId, $senderType, $recipientId, $recipientType) {
                $subQ->where('sender_id', $senderId)
                    ->where('sender_type', $senderType)
                    ->where('recipient_id', $recipientId)
                    ->where('recipient_type', $recipientType);
            })->orWhere(function ($subQ) use ($senderId, $senderType, $recipientId, $recipientType) {
                $subQ->where('sender_id', $recipientId)
                    ->where('sender_type', $recipientType)
                    ->where('recipient_id', $senderId)
                    ->where('recipient_type', $senderType);
            });
        });
    }

    /**
     * Scope to get conversation for a specific user
     */
    public function scopeForUser(Builder $query, int $userId, string $userType): Builder
    {
        return $query->where(function ($q) use ($userId, $userType) {
            $q->where(function ($subQ) use ($userId, $userType) {
                $subQ->where('sender_id', $userId)
                    ->where('sender_type', $userType);
            })->orWhere(function ($subQ) use ($userId, $userType) {
                $subQ->where('recipient_id', $userId)
                    ->where('recipient_type', $userType);
            });
        });
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
