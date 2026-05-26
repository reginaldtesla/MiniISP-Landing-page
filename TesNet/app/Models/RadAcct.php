<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    public $timestamps = false;

    protected $table = 'radacct';

    protected $primaryKey = 'radacctid';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'acctstarttime' => 'datetime',
            'acctupdatetime' => 'datetime',
            'acctstoptime' => 'datetime',
            'acctinputoctets' => 'integer',
            'acctoutputoctets' => 'integer',
            'acctsessiontime' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereNull('acctstoptime');
    }

    public function totalBytes(): int
    {
        return (int) ($this->acctinputoctets ?? 0) + (int) ($this->acctoutputoctets ?? 0);
    }

    public function formattedDataUsed(): string
    {
        $bytes = $this->totalBytes();

        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        return round($bytes / 1024, 2).' KB';
    }
}
