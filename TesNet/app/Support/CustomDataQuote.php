<?php

namespace App\Support;

class CustomDataQuote
{
    public function __construct(
        public readonly float $amountGhs,
        public readonly float $dataGb,
        public readonly int $dataLimitBytes,
        public readonly ?float $effectiveRatePerGb,
        public readonly ?int $speedMbps,
        public readonly ?array $bounds,
    ) {}

    public function dataLabel(): string
    {
        return number_format($this->dataGb, 2).' GB';
    }

    public function dataLimitMb(): int
    {
        return (int) ceil($this->dataLimitBytes / 1048576);
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadata(): array
    {
        return [
            'amount_ghs' => $this->amountGhs,
            'effective_rate_per_gb' => $this->effectiveRatePerGb,
            'data_gb' => $this->dataGb,
            'data_limit_bytes' => $this->dataLimitBytes,
            'data_label' => $this->dataLabel(),
            'speed_mbps' => $this->speedMbps,
            'bounds' => $this->bounds,
        ];
    }
}
