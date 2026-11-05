<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'subtotal',
        'shipping_cost',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'carrier',
        'tracking_number',
        'tracking_url',
        'shipped_at',
        'delivered_at',
        'estimated_delivery',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'estimated_delivery' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match (strtolower($this->payment_method ?? 'cod')) {
            'card', 'stripe' => 'Credit / Debit Card',
            'bank_transfer' => 'Direct Bank Transfer',
            default => 'Cash on Delivery',
        };
    }

    public function getStatusStepAttribute(): int
    {
        return match (strtolower($this->status)) {
            'completed' => 4,
            'shipped' => 3,
            'processing' => 2,
            'cancelled' => -1,
            default => 1,
        };
    }

    public function getLiveTrackingUrlAttribute(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }

        if (! $this->tracking_number) {
            return null;
        }

        $carrier = strtolower($this->carrier ?? '');
        $tracking = urlencode($this->tracking_number);

        if (str_contains($carrier, 'fedex')) {
            return "https://www.fedex.com/fedextrack/?trknbr={$tracking}";
        }
        if (str_contains($carrier, 'dhl')) {
            return "https://www.dhl.com/en/express/tracking.html?AWB={$tracking}";
        }
        if (str_contains($carrier, 'ups')) {
            return "https://www.ups.com/track?tracknum={$tracking}";
        }
        if (str_contains($carrier, 'usps')) {
            return "https://tools.usps.com/go/TrackConfirmAction?tLabels={$tracking}";
        }

        return 'https://www.google.com/search?q='.urlencode(($this->carrier ?? 'courier').' tracking '.$this->tracking_number);
    }
}
