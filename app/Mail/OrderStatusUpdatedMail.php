<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $previousStatus = null
    ) {
        $this->order->loadMissing('items.product');
    }

    public function envelope(): Envelope
    {
        $statusLabel = match (strtolower($this->order->status)) {
            'shipped' => 'Shipped',
            'completed' => 'Delivered',
            'processing' => 'Processing',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->order->status),
        };

        return new Envelope(
            subject: "Order #{$this->order->order_number} Update: {$statusLabel} | ".config('app.name', 'MONO'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
