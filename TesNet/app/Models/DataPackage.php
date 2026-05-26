<?php

namespace App\Models;

use App\Support\PackageValidity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DataPackage extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'data_label',
        'data_limit_mb',
        'price',
        'speed_mbps',
        'validity_days',
        'validity_type',
        'description',
        'is_active',
        'is_special_offer',
        'special_starts_at',
        'special_ends_at',
        'promo_label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'data_limit_mb' => 'integer',
            'speed_mbps' => 'integer',
            'validity_days' => 'integer',
            'is_active' => 'boolean',
            'is_special_offer' => 'boolean',
            'special_starts_at' => 'datetime',
            'special_ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function amountPesewas(): int
    {
        return (int) round((float) $this->price * 100);
    }

    public function speedLabel(): string
    {
        return $this->speed_mbps
            ? 'Up to '.$this->speed_mbps.' Mbps'
            : 'Best effort speed';
    }

    public function isPurchasable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_special_offer) {
            return $this->isSpecialOfferVisible();
        }

        return true;
    }

    public function isSpecialOfferVisible(): bool
    {
        if (! $this->is_special_offer || ! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->special_starts_at && $this->special_starts_at->gt($now)) {
            return false;
        }

        if ($this->special_ends_at && $this->special_ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * @return null|'scheduled'|'live'|'ended'
     */
    public function specialOfferStatus(): ?string
    {
        if (! $this->is_special_offer) {
            return null;
        }

        $now = now();

        if ($this->special_starts_at && $this->special_starts_at->gt($now)) {
            return 'scheduled';
        }

        if ($this->special_ends_at && $this->special_ends_at->lt($now)) {
            return 'ended';
        }

        return 'live';
    }

    public function specialOfferEndsLabel(): ?string
    {
        if (! $this->special_ends_at) {
            return null;
        }

        return $this->special_ends_at->timezone(config('app.timezone'))->format('M j, Y g:i A');
    }

    public function purchaseExpiresAt(?Carbon $activatedAt = null): ?Carbon
    {
        return PackageValidity::purchaseExpiresAt($this, $activatedAt);
    }

    public function validityDaysLabel(): string
    {
        return PackageValidity::daysLabel($this->validity_days);
    }

    public function validityLabel(): string
    {
        return PackageValidity::labelForPackage($this);
    }

    public function adminDurationLabel(): string
    {
        return PackageValidity::adminDurationLabel($this);
    }

    public function hasUnlimitedData(): bool
    {
        return PackageValidity::isUnlimited($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlanArray(): array
    {
        return [
            'name' => $this->name,
            'data' => $this->data_label,
            'data_limit_mb' => $this->data_limit_mb,
            'price' => (float) $this->price,
            'amount_pesewas' => $this->amountPesewas(),
            'speed_mbps' => $this->speed_mbps,
        ];
    }

    public static function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'package';
        }

        $slug = $base;
        $counter = 1;

        while (
            static::query()
                ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Standard packages only — special day offers are excluded from the custom calculator.
     */
    public function scopeForCalculator(Builder $query): Builder
    {
        return $query->where('is_special_offer', false);
    }

    /** Same as forCalculator: regular store packages, not limited-time offers. */
    public function scopeRegular(Builder $query): Builder
    {
        return $query->forCalculator();
    }

    /**
     * Active standard packages used to price the custom “pay what you have” slider.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function forCustomCalculator(): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->active()->forCalculator()->ordered()->get();
    }

    public function scopeSpecialOffer(Builder $query): Builder
    {
        return $query->where('is_special_offer', true);
    }

    /** Special offers that are live right now for students. */
    public function scopeVisibleSpecialOffer(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_special_offer', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('special_starts_at')->orWhere('special_starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('special_ends_at')->orWhere('special_ends_at', '>=', $now);
            });
    }
}
