<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'channel',
        'priority',
        'category',
        'action_url',
        'action_text',
        'icon',
        'is_actionable',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_actionable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the notifiable entity that the notification belongs to.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread.
     *
     * @return void
     */
    public function markAsUnread()
    {
        if (! is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if a notification has been read.
     *
     * @return bool
     */
    public function read()
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read.
     *
     * @return bool
     */
    public function unread()
    {
        return $this->read_at === null;
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Create a new notification for a buyer
     *
     * @param  \App\Models\Buyer  $buyer
     * @param  string  $type
     * @param  array  $data
     * @return static
     */
    public static function createForBuyer($buyer, $type, array $data)
    {
        return static::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => $type,
            'notifiable_type' => get_class($buyer),
            'notifiable_id' => $buyer->id,
            'data' => $data,
        ]);
    }

    /**
     * Get formatted notification message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->data['message'] ?? 'You have a new notification';
    }

    /**
     * Get notification title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get notification icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->data['icon'] ?? 'info';
    }

    /**
     * Check if notification is read
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }

    /**
     * Get priority color
     *
     * @return string
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority ?? 'normal') {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'blue'
        };
    }

    /**
     * Get category label
     *
     * @return string
     */
    public function getCategoryLabelAttribute()
    {
        return match($this->category ?? 'system') {
            'order' => 'Order',
            'rfq' => 'RFQ',
            'quote' => 'Quote',
            'payment' => 'Payment',
            'delivery' => 'Delivery',
            'system' => 'System',
            default => ucfirst($this->category ?? 'System')
        };
    }

    /**
     * Get notification content/message
     *
     * @return string
     */
    public function getContentAttribute()
    {
        return $this->data['message'] ?? 'You have a new notification';
    }

    /**
     * Get notification title
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get icon attribute
     *
     * @return string
     */
    public function getIconAttribute()
    {
        // Return the icon from data or from the icon field
        if (isset($this->data['icon'])) {
            return $this->data['icon'];
        }
        
        // Return based on category/type
        return match($this->category ?? $this->type) {
            'order', 'order_shipped' => 'shopping-cart',
            'rfq', 'rfq_response' => 'file-text',
            'quote', 'quote_accepted' => 'check-circle',
            'payment' => 'dollar-sign',
            'delivery', 'delivery_update' => 'truck',
            'price_alert' => 'tag',
            'new_supplier' => 'store',
            default => $this->attributes['icon'] ?? 'bell'
        };
    }
}