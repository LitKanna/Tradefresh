<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'previous_status',
        'notes',
        'user_id',
        'metadata',
        'notified_at',
        'notification_type',
        'notification_recipient'
    ];

    protected $casts = [
        'metadata' => 'array',
        'notified_at' => 'datetime'
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods
    public function getDurationFromPreviousAttribute(): ?string
    {
        $previousStatus = self::where('order_id', $this->order_id)
            ->where('created_at', '<', $this->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($previousStatus) {
            $diff = $this->created_at->diff($previousStatus->created_at);
            
            if ($diff->days > 0) {
                return $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
            } elseif ($diff->h > 0) {
                return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            } else {
                return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
            }
        }

        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            Order::STATUS_DRAFT => 'Draft',
            Order::STATUS_SUBMITTED => 'Submitted',
            Order::STATUS_CONFIRMED => 'Confirmed',
            Order::STATUS_PREPARING => 'Preparing',
            Order::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            Order::STATUS_IN_TRANSIT => 'In Transit',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_COMPLETED => 'Completed',
            Order::STATUS_CANCELLED => 'Cancelled',
            Order::STATUS_REFUNDED => 'Refunded'
        ];

        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            Order::STATUS_DRAFT => 'gray',
            Order::STATUS_SUBMITTED => 'blue',
            Order::STATUS_CONFIRMED => 'indigo',
            Order::STATUS_PREPARING => 'yellow',
            Order::STATUS_READY_FOR_PICKUP => 'orange',
            Order::STATUS_IN_TRANSIT => 'purple',
            Order::STATUS_DELIVERED => 'green',
            Order::STATUS_COMPLETED => 'green',
            Order::STATUS_CANCELLED => 'red',
            Order::STATUS_REFUNDED => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusIconAttribute(): string
    {
        $icons = [
            Order::STATUS_DRAFT => 'document-text',
            Order::STATUS_SUBMITTED => 'paper-airplane',
            Order::STATUS_CONFIRMED => 'check-circle',
            Order::STATUS_PREPARING => 'clock',
            Order::STATUS_READY_FOR_PICKUP => 'shopping-bag',
            Order::STATUS_IN_TRANSIT => 'truck',
            Order::STATUS_DELIVERED => 'home',
            Order::STATUS_COMPLETED => 'badge-check',
            Order::STATUS_CANCELLED => 'x-circle',
            Order::STATUS_REFUNDED => 'receipt-refund'
        ];

        return $icons[$this->status] ?? 'question-mark-circle';
    }

    public function isTransitionAllowed(string $newStatus): bool
    {
        $allowedTransitions = [
            Order::STATUS_DRAFT => [
                Order::STATUS_SUBMITTED,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_SUBMITTED => [
                Order::STATUS_CONFIRMED,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_CONFIRMED => [
                Order::STATUS_PREPARING,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_PREPARING => [
                Order::STATUS_READY_FOR_PICKUP,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_READY_FOR_PICKUP => [
                Order::STATUS_IN_TRANSIT,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_IN_TRANSIT => [
                Order::STATUS_DELIVERED
            ],
            Order::STATUS_DELIVERED => [
                Order::STATUS_COMPLETED,
                Order::STATUS_REFUNDED
            ],
            Order::STATUS_COMPLETED => [
                Order::STATUS_REFUNDED
            ],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => []
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    public function sendNotification(): void
    {
        // Implementation would depend on notification system
        // This is a placeholder for notification logic
        $this->notified_at = now();
        $this->save();
    }
}