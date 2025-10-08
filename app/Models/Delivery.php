<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'driver_id',
        'parking_location_id',
        'delivery_zone_id',
        'tracking_code',
        'priority',
        'scheduled_date',
        'time_slot',
        'pickup_time',
        'delivered_time',
        'estimated_minutes',
        'route_waypoints',
        'total_distance_km',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'proof_type',
        'proof_signature',
        'proof_photo',
        'proof_pin',
        'received_by',
        'requires_cold_chain',
        'temperature_log',
        'min_temperature',
        'max_temperature',
        'notification_settings',
        'notification_log',
        'sms_enabled',
        'whatsapp_enabled',
        'email_enabled',
        'delivery_rating',
        'delivery_feedback',
        'driver_notes',
        'internal_notes',
        'has_issues',
        'issues_log',
        'delay_minutes',
        'delay_reason',
        'status',
        'delivery_address',
        'delivery_instructions',
        'contact_name',
        'contact_phone',
        'delivery_fee'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'pickup_time' => 'datetime',
        'delivered_time' => 'datetime',
        'location_updated_at' => 'datetime',
        'route_waypoints' => 'array',
        'temperature_log' => 'array',
        'notification_settings' => 'array',
        'notification_log' => 'array',
        'issues_log' => 'array',
        'requires_cold_chain' => 'boolean',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'has_issues' => 'boolean',
        'total_distance_km' => 'decimal:2',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'delivery_fee' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (empty($delivery->tracking_code)) {
                $delivery->tracking_code = static::generateTrackingCode();
            }
            
            if (empty($delivery->notification_settings)) {
                $delivery->notification_settings = [
                    'on_pickup' => true,
                    'on_way' => true,
                    'near_arrival' => true,
                    'delivered' => true,
                    'issues' => true
                ];
            }
        });

        static::updating(function ($delivery) {
            // Log status changes
            if ($delivery->isDirty('status')) {
                $delivery->logStatusChange($delivery->getOriginal('status'), $delivery->status);
            }

            // Update timestamps based on status
            if ($delivery->status === 'picked_up' && !$delivery->pickup_time) {
                $delivery->pickup_time = now();
            }
            
            if ($delivery->status === 'delivered' && !$delivery->delivered_time) {
                $delivery->delivered_time = now();
            }
        });
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'SM' . strtoupper(Str::random(8));
        } while (static::where('tracking_code', $code)->exists());
        
        return $code;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function parkingLocation(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'location_updated_at' => now()
        ]);
    }

    public function addTemperatureReading(float $temperature): void
    {
        if (!$this->requires_cold_chain) {
            return;
        }

        $log = $this->temperature_log ?? [];
        $log[] = [
            'temperature' => $temperature,
            'timestamp' => now()->toIso8601String(),
            'is_valid' => $this->isTemperatureValid($temperature)
        ];

        $this->update(['temperature_log' => $log]);

        // Check for temperature breach
        if (!$this->isTemperatureValid($temperature)) {
            $this->reportIssue('temperature_breach', "Temperature reading of {$temperature}°C is outside acceptable range");
        }
    }

    public function isTemperatureValid(float $temperature): bool
    {
        if (!$this->min_temperature || !$this->max_temperature) {
            return true;
        }

        return $temperature >= $this->min_temperature && $temperature <= $this->max_temperature;
    }

    public function reportIssue(string $type, string $description, array $data = []): void
    {
        $issues = $this->issues_log ?? [];
        $issues[] = [
            'type' => $type,
            'description' => $description,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'resolved' => false
        ];

        $this->update([
            'has_issues' => true,
            'issues_log' => $issues
        ]);

        // Send notification about the issue
        $this->sendNotification('issue', $description);
    }

    public function resolveIssue(int $issueIndex, string $resolution): void
    {
        $issues = $this->issues_log ?? [];
        
        if (isset($issues[$issueIndex])) {
            $issues[$issueIndex]['resolved'] = true;
            $issues[$issueIndex]['resolution'] = $resolution;
            $issues[$issueIndex]['resolved_at'] = now()->toIso8601String();

            $hasUnresolvedIssues = collect($issues)->contains('resolved', false);

            $this->update([
                'issues_log' => $issues,
                'has_issues' => $hasUnresolvedIssues
            ]);
        }
    }

    public function sendNotification(string $event, string $message = null): void
    {
        $log = $this->notification_log ?? [];
        $notification = [
            'event' => $event,
            'message' => $message ?? $this->getNotificationMessage($event),
            'timestamp' => now()->toIso8601String(),
            'channels' => []
        ];

        // Send via enabled channels
        if ($this->sms_enabled) {
            // SMS sending logic would go here
            $notification['channels'][] = 'sms';
        }

        if ($this->whatsapp_enabled) {
            // WhatsApp sending logic would go here
            $notification['channels'][] = 'whatsapp';
        }

        if ($this->email_enabled) {
            // Email sending logic would go here
            $notification['channels'][] = 'email';
        }

        $log[] = $notification;
        $this->update(['notification_log' => $log]);
    }

    protected function getNotificationMessage(string $event): string
    {
        $messages = [
            'assigned' => "Your order #{$this->order->order_number} has been assigned to a driver",
            'picked_up' => "Your order #{$this->order->order_number} has been picked up and is on the way",
            'on_way' => "Your delivery is on the way! Track it here: " . $this->getTrackingUrl(),
            'near_arrival' => "Your delivery will arrive in approximately 10 minutes",
            'delivered' => "Your order #{$this->order->order_number} has been delivered successfully",
            'delayed' => "Your delivery has been delayed by approximately {$this->delay_minutes} minutes",
            'issue' => "There's an issue with your delivery. We're working to resolve it"
        ];

        return $messages[$event] ?? "Delivery update for order #{$this->order->order_number}";
    }

    public function getTrackingUrl(): string
    {
        return route('delivery.track', $this->tracking_code);
    }

    public function getEstimatedArrival(): ?Carbon
    {
        if ($this->delivered_time) {
            return Carbon::parse($this->delivered_time);
        }

        if ($this->pickup_time) {
            return Carbon::parse($this->pickup_time)->addMinutes($this->estimated_minutes + $this->delay_minutes);
        }

        if ($this->scheduled_date) {
            return Carbon::parse($this->scheduled_date);
        }

        return null;
    }

    public function canBeDelivered(): bool
    {
        return in_array($this->status, ['pending', 'assigned', 'picked_up', 'in_transit']);
    }

    public function isLate(): bool
    {
        if ($this->delivered_time) {
            return false;
        }

        $estimatedArrival = $this->getEstimatedArrival();
        
        if (!$estimatedArrival) {
            return false;
        }

        return now()->greaterThan($estimatedArrival);
    }

    protected function logStatusChange(string $from, string $to): void
    {
        $log = $this->notification_log ?? [];
        $log[] = [
            'event' => 'status_change',
            'from' => $from,
            'to' => $to,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->notification_log = $log;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'assigned' => 'blue',
            'picked_up' => 'indigo',
            'in_transit' => 'yellow',
            'delivered' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'bg-red-100 text-red-800',
            'express' => 'bg-orange-100 text-orange-800',
            'scheduled' => 'bg-blue-100 text-blue-800',
            'standard' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}