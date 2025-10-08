<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'buyer_id',
        'user_message',
        'ai_response',
        'extracted_data',
        'conversation_state',
        'rfq_id',
        'session_id',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'extracted_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the buyer that owns the conversation
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the RFQ associated with the conversation
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class);
    }

    /**
     * Scope for conversations by session
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for conversations by buyer
     */
    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope for active conversations (last 24 hours)
     */
    public function scopeActive($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Get formatted conversation for display
     */
    public function getFormattedConversation(): array
    {
        return [
            'user' => $this->user_message,
            'ai' => $this->ai_response,
            'data' => $this->extracted_data,
            'state' => $this->conversation_state,
            'timestamp' => $this->created_at->diffForHumans(),
        ];
    }
}
