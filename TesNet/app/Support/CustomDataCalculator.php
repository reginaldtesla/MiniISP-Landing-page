<?php

namespace App\Support;

use App\Models\DataPackage;
use InvalidArgumentException;

/**
 * Prices custom data from standard packages only.
 * Special day offers are separate fixed packages and never affect this calculator.
 */
class CustomDataCalculator
{
    public static function sliderMinAmount(): float
    {
        return (float) config('custom_data.slider_min_amount', 1);
    }

    public static function sliderMaxAmount(): float
    {
        return (float) config('custom_data.slider_max_amount', 100);
    }

    public static function speedMbps(): int
    {
        return (int) config('custom_data.speed_mbps', 60);
    }

    public static function quote(float $amountGhs): CustomDataQuote
    {
        $points = self::calculatorPoints();

        if ($points->count() < 2) {
            throw new InvalidArgumentException('Custom purchase is unavailable (not enough fixed packages configured).');
        }

        $sliderMin = self::sliderMinAmount();
        $sliderMax = self::sliderMaxAmount();

        if ($amountGhs < $sliderMin - 0.001) {
            throw new InvalidArgumentException('Minimum custom purchase is GH¢'.number_format($sliderMin, 2).'.');
        }

        if ($amountGhs > $sliderMax + 0.001) {
            throw new InvalidArgumentException('Maximum custom purchase is GH¢'.number_format($sliderMax, 2).'.');
        }

        $dataGbDisplay = self::interpolateGb($amountGhs, $points);
        $bounds = self::resolveBounds($amountGhs, $points);

        if (! $bounds) {
            throw new InvalidArgumentException('Could not price custom purchase. Please try again.');
        }

        $lower = $bounds['lower'];
        $upper = $bounds['upper'];
        $bytes = (int) round($dataGbDisplay * 1024 * 1024 * 1024);
        $effectiveRate = $dataGbDisplay > 0 ? round($amountGhs / $dataGbDisplay, 4) : null;

        return new CustomDataQuote(
            $amountGhs,
            $dataGbDisplay,
            $bytes,
            $effectiveRate,
            self::speedMbps(),
            ['lower' => $lower, 'upper' => $upper]
        );
    }

    /**
     * Linear interpolation (and segment extrapolation) for GH¢1–GH¢100 against fixed packages.
     */
    public static function interpolateGb(float $amountGhs, $points): float
    {
        $bounds = self::resolveBounds($amountGhs, $points);

        if (! $bounds) {
            throw new InvalidArgumentException('Could not calculate data for this amount.');
        }

        $lower = $bounds['lower'];
        $upper = $bounds['upper'];

        if (abs($upper['price'] - $lower['price']) < 0.000001) {
            $gb = (float) $lower['gb'];
        } else {
            $t = ($amountGhs - $lower['price']) / ($upper['price'] - $lower['price']);
            $gb = (float) $lower['gb'] + ($t * ((float) $upper['gb'] - (float) $lower['gb']));
        }

        $gb = round($gb, 2);

        $minGb = (float) config('custom_data.min_allocatable_gb', 0.01);

        return max($minGb, $gb);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $points
     * @return array{lower: array<string, mixed>, upper: array<string, mixed>}|null
     */
    protected static function resolveBounds(float $amountGhs, $points): ?array
    {
        if ($points->count() < 2) {
            return null;
        }

        $first = $points->first();
        $second = $points->get(1);
        $last = $points->last();
        $secondLast = $points->get($points->count() - 2);

        if ($amountGhs < (float) $first['price']) {
            return [
                'lower' => ['price' => 0.0, 'gb' => 0.0, 'name' => ''],
                'upper' => $first,
                'mode' => 'below',
            ];
        }

        if ($amountGhs > (float) $last['price']) {
            return [
                'lower' => $secondLast,
                'upper' => $last,
                'mode' => 'above',
            ];
        }

        $lower = null;
        $upper = null;

        foreach ($points as $point) {
            if ($point['price'] <= $amountGhs + 0.000001) {
                $lower = $point;
            }
            if ($point['price'] >= $amountGhs - 0.000001) {
                $upper = $point;
                break;
            }
        }

        if (! $lower || ! $upper) {
            return null;
        }

        if ((float) $upper['price'] < (float) $lower['price']) {
            return null;
        }

        return ['lower' => $lower, 'upper' => $upper, 'mode' => 'between'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function frontendConfig(): array
    {
        return [
            'minAmount' => self::sliderMinAmount(),
            'maxAmount' => self::sliderMaxAmount(),
            'step' => 0.50,
            'speedMbps' => self::speedMbps(),
            'standardPackagesOnly' => true,
            'points' => self::calculatorPoints()
                ->map(fn (array $p) => [
                    'price' => $p['price'],
                    'gb' => $p['gb'],
                    'name' => $p['name'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Interpolation points from standard packages (never special day offers).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected static function calculatorPoints(): \Illuminate\Support\Collection
    {
        $sorted = DataPackage::forCustomCalculator()
            ->map(function (DataPackage $p) {
                return [
                    'slug' => $p->slug,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'gb' => round(((int) $p->data_limit_mb) / 1024, 4),
                ];
            })
            ->sortBy('price')
            ->values();

        return self::monotonicCalculatorPoints($sorted);
    }

    /**
     * Drop packages that break price→data scaling (e.g. 3GB @ GH¢3 then 1GB @ GH¢3.50).
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $sorted
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected static function monotonicCalculatorPoints(\Illuminate\Support\Collection $sorted): \Illuminate\Support\Collection
    {
        $filtered = collect();
        $lastGb = -1.0;

        foreach ($sorted as $point) {
            $gb = (float) $point['gb'];

            if ($gb >= $lastGb - 0.0001) {
                $filtered->push($point);
                $lastGb = $gb;
            }
        }

        return $filtered->values();
    }
}
