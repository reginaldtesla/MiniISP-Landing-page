<?php

namespace App\Support;

use App\Models\Transaction;

class PaymentCompletionResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?Transaction $transaction = null,
        public bool $alreadyFulfilled = false,
    ) {}
}
