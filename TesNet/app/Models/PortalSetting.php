<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $table = 'portal_settings';

    protected $fillable = [
        'outage_enabled',
        'outage_message',
        'block_purchases',
        'block_connect',
    ];

    protected function casts(): array
    {
        return [
            'outage_enabled' => 'boolean',
            'block_purchases' => 'boolean',
            'block_connect' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'outage_enabled' => false,
            'outage_message' => null,
            'block_purchases' => false,
            'block_connect' => false,
        ]);
    }
}

