# Laravel M-Pesa Package

A Laravel package for integrating M-Pesa Lipa Na M-Pesa STK Push payments.

## Installation

### For Development/Testing (Package not yet published)

1. **Create the package structure in your Laravel project:**
```bash
# In your Laravel project root
mkdir -p packages/tish/laravel-mpesa
```

2. **Copy all package files to `packages/tish/laravel-mpesa/`** (see file structure below)

3. **Add the local package repository to your main `composer.json`:**
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/tish/laravel-mpesa"
        }
    ],
    "require": {
        "tish/laravel-mpesa": "dev-main"
    }
}
```

4. **Install the local package:**
```bash
composer require tish/laravel-mpesa:dev-main
```

### For Production (Once Published to Packagist)

1. **Install the package via Composer:**
```bash
composer require tish/laravel-mpesa
```

2. **Publish the config file and migrations:**
```bash
php artisan vendor:publish --provider="Tish\LaravelMpesa\MpesaServiceProvider"
php artisan migrate
```

3. **Add your M-Pesa credentials to your `.env` file:**
```env
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_PASSKEY=your_passkey
MPESA_BUSINESS_SHORT_CODE=your_shortcode
MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback
MPESA_SANDBOX=true
```

4. **Create listeners to handle payment events:**
```bash
php artisan make:listener HandleSuccessfulPayment
php artisan make:listener HandleFailedPayment
```

5. **Register the event listeners in your `app/Providers/EventServiceProvider.php`:**
```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Tish\LaravelMpesa\Events\PaymentCompleted;
use Tish\LaravelMpesa\Events\PaymentFailed;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentCompleted::class => [
            \App\Listeners\HandleSuccessfulPayment::class,
        ],
        PaymentFailed::class => [
            \App\Listeners\HandleFailedPayment::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
```

6. **Implement your listeners:**

**`app/Listeners/HandleSuccessfulPayment.php`:**
```php
<?php

namespace App\Listeners;

use Tish\LaravelMpesa\Events\PaymentCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleSuccessfulPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentCompleted $event)
    {
        $transaction = $event->transaction;
        
        Log::info('Payment completed', [
            'account_reference' => $transaction->account_reference,
            'receipt_number' => $transaction->mpesa_receipt_number,
            'amount' => $transaction->amount,
            'phone' => $transaction->phone_number,
        ]);

        // Update your database here
        // Example: Find order by account_reference and mark as paid
        /*
        $order = Order::where('order_number', $transaction->account_reference)->first();
        if ($order) {
            $order->update([
                'status' => 'paid',
                'mpesa_receipt' => $transaction->mpesa_receipt_number,
                'payment_date' => $transaction->transaction_date,
            ]);
        }
        */
    }
}
```

**`app/Listeners/HandleFailedPayment.php`:**
```php
<?php

namespace App\Listeners;

use Tish\LaravelMpesa\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleFailedPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentFailed $event)
    {
        $transaction = $event->transaction;
        
        Log::warning('Payment failed', [
            'account_reference' => $transaction->account_reference,
            'result_desc' => $transaction->result_desc,
            'amount' => $transaction->amount,
            'phone' => $transaction->phone_number,
        ]);

        // Handle failed payment
        // Example: Update order status, send notification, etc.
        /*
        $order = Order::where('order_number', $transaction->account_reference)->first();
        if ($order) {
            $order->update(['status' => 'payment_failed']);
        }
        */
    }
}
```

## Usage

### Initiate STK Push Payment

```php
use Tish\LaravelMpesa\Facades\Mpesa;

$response = Mpesa::stkPush(
    '254712345678',           // Phone number
    100,                      // Amount
    'ORDER-12345',           // Account reference (your order ID)
    'Payment for Order 12345' // Transaction description
);

if (isset($response['CheckoutRequestID'])) {
    // Payment initiated successfully
    // Store the CheckoutRequestID for tracking
    $checkoutRequestId = $response['CheckoutRequestID'];
} else {
    // Handle error
}
```

### Check Payment Status

```php
// Get transaction status
$status = file_get_contents("http://yourapp.com/mpesa/status/{$checkoutRequestId}");
$statusData = json_decode($status, true);

if ($statusData['status'] === 'completed') {
    echo "Payment successful! Receipt: " . $statusData['mpesa_receipt_number'];
} elseif ($statusData['status'] === 'failed') {
    echo "Payment failed: " . $statusData['result_desc'];
} else {
    echo "Payment pending...";
}
```

### Available Transaction Data in Events

When handling the `PaymentCompleted` or `PaymentFailed` events, you have access to:

```php
$transaction = $event->transaction;

// Available properties:
$transaction->checkout_request_id      // M-Pesa checkout request ID
$transaction->merchant_request_id      // M-Pesa merchant request ID  
$transaction->phone_number            // Customer phone number
$transaction->amount                  // Transaction amount
$transaction->account_reference       // Your reference (order ID, etc.)
$transaction->transaction_desc        // Transaction description
$transaction->status                  // 'pending', 'completed', 'failed'
$transaction->mpesa_receipt_number    // M-Pesa receipt (if successful)
$transaction->transaction_date        // When payment was made
$transaction->result_code             // M-Pesa result code
$transaction->result_desc             // M-Pesa result description
```

## Important Notes

1. **Callback URL**: Make sure your `MPESA_CALLBACK_URL` is publicly accessible and points to `https://yourdomain.com/mpesa/callback`

2. **Queue Workers**: Since the listeners implement `ShouldQueue`, make sure you have queue workers running:
   ```bash
   php artisan queue:work
   ```

3. **Logging**: All M-Pesa transactions are logged for debugging. Check your Laravel logs.

4. **Testing**: Use the sandbox environment for testing by setting `MPESA_SANDBOX=true`

## Security

The package handles M-Pesa callback validation and ensures only valid callbacks are processed. Always use HTTPS in production.

## Support

For support, please open an issue on [GitHub](https://github.com/tishmalo/laravel-mpesa). 
