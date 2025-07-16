<?php

namespace Tish\LaravelMpesa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tish\LaravelMpesa\MpesaService;
use Tish\LaravelMpesa\Models\MpesaTransaction;
use Tish\LaravelMpesa\Events\PaymentCompleted;
use Tish\LaravelMpesa\Events\PaymentFailed;

class MpesaController extends Controller
{
    protected $mpesa;

    public function __construct(MpesaService $mpesa)
    {
        $this->mpesa = $mpesa;
    }

    public function stkPush(Request $request)
    {

        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string',
            'transaction_desc' => 'required|string',
        ]);

        try {

            $response = $this->mpesa->stkPush(
                $request->phone,
                $request->amount,
                $request->account_reference,
                $request->transaction_desc
            );

            \Log::info('storing the initial payment transaction', [$response]);

            // Store initial transaction record
            if (isset($response['CheckoutRequestID'])) {
                MpesaTransaction::create([
                    'checkout_request_id' => $response['CheckoutRequestID'],
                    'merchant_request_id' => $response['MerchantRequestID'],
                    'phone_number' => $request->phone,
                    'amount' => $request->amount,
                    'account_reference' => $request->account_reference,
                    'transaction_desc' => $request->transaction_desc,
                    'status' => 'pending',
                    'response_code' => $response['ResponseCode'],
                    'response_description' => $response['ResponseDescription'],
                ]);
            }

            \Log::info('storing the later payment transaction', [$response]);


            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        // Handle M-Pesa callback
        $data = $request->all();
        
        // Log the callback data for debugging
        \Log::info('M-Pesa Callback Received:', $data);
        
        try {
            // Extract the callback data
            $stkCallback = $data['Body']['stkCallback'];
            $checkoutRequestId = $stkCallback['CheckoutRequestID'];
            $merchantRequestId = $stkCallback['MerchantRequestID'];
            $resultCode = $stkCallback['ResultCode'];
            $resultDesc = $stkCallback['ResultDesc'];
            
            // Find the transaction record
            $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();
            
            if (!$transaction) {
                \Log::error('Transaction not found for CheckoutRequestID: ' . $checkoutRequestId);
                return $this->callbackResponse();
            }
            
            // Update transaction with callback data
            $transaction->update([
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'status' => $resultCode == 0 ? 'completed' : 'failed',
            ]);
            
            if ($resultCode == 0) {
                // Payment successful - extract metadata
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];
                $metadata = [];
                
                foreach ($callbackMetadata as $item) {
                    $metadata[$item['Name']] = $item['Value'] ?? null;
                }
                
                // Update transaction with payment details
                $transaction->update([
                    'mpesa_receipt_number' => $metadata['MpesaReceiptNumber'] ?? null,
                    'transaction_date' => isset($metadata['TransactionDate']) ? 
                        \Carbon\Carbon::createFromFormat('YmdHis', $metadata['TransactionDate']) : null,
                    'phone_number' => $metadata['PhoneNumber'] ?? $transaction->phone_number,
                    'amount' => $metadata['Amount'] ?? $transaction->amount,
                    'callback_metadata' => json_encode($metadata),
                ]);
                
                // Dispatch payment completed event
                event(new PaymentCompleted($transaction));
                
                \Log::info('Payment completed successfully', [
                    'transaction_id' => $transaction->id,
                    'receipt_number' => $metadata['MpesaReceiptNumber'] ?? 'N/A',
                    'amount' => $metadata['Amount'] ?? 'N/A',
                ]);
                
            } else {
                // Payment failed
                event(new PaymentFailed($transaction));
                
                \Log::warning('Payment failed', [
                    'transaction_id' => $transaction->id,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error processing M-Pesa callback: ' . $e->getMessage(), [
                'callback_data' => $data,
                'exception' => $e->getTraceAsString(),
            ]);
        }
        
        return $this->callbackResponse();
    }
    
    /**
     * Standard callback response that M-Pesa expects
     */
    private function callbackResponse()
    {
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback processed successfully'
        ]);
    }
}