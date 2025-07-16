<?php

namespace Tish\LaravelMpesa;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $client;
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $businessShortCode;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = config('mpesa.sandbox', true) ? 
            'https://sandbox.safaricom.co.ke' : 
            'https://api.safaricom.co.ke';
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->passkey = config('mpesa.passkey');
        $this->businessShortCode = config('mpesa.business_short_code');
    }

    public function getAccessToken()
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret),
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'];
        } catch (RequestException $e) {
            Log::error('M-Pesa Access Token Error: ' . $e->getMessage());
            throw new \Exception('Failed to get access token: ' . $e->getMessage());
        }
    }

    public function stkPush($phoneNumber, $amount, $accountReference, $transactionDesc)
    {
        $accessToken = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';

        // Ensure phone number is in correct format
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $postData = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->businessShortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => config('mpesa.callback_url'),
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $postData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage());
            throw new \Exception('STK Push failed: ' . $e->getMessage());
        }
    }

    public function stkQuery($checkoutRequestId)
    {
        $accessToken = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';

        $postData = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $postData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('M-Pesa STK Query Error: ' . $e->getMessage());
            throw new \Exception('STK Query failed: ' . $e->getMessage());
        }
    }

    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Convert to 254 format
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 1) === '+') {
            $phoneNumber = substr($phoneNumber, 1);
        }
        
        return $phoneNumber;
    }
}