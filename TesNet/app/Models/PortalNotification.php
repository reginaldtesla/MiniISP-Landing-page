<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalNotification extends Model
{
    protected $table = 'portal_notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'is_global',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        return $this->isExpired() ? 'Expired' : 'Delivered';
    }

    public function formattedSentAt(): string
    {
        return $this->created_at->format('M j, g:i A');
    }
}
