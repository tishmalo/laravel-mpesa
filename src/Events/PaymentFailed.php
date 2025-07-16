<?php

namespace Tish\LaravelMpesa\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tish\LaravelMpesa\Models\MpesaTransaction;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public $transaction;

    public function __construct(MpesaTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}