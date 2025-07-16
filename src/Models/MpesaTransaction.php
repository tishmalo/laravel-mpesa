<?php

namespace Tish\LaravelMpesa\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    protected $table = 'mpesa_transactions';
    
    protected $fillable = [
        'checkout_request_id',
        'merchant_request_id',
        'phone_number',
        'amount',
        'account_reference',
        'transaction_desc',
        'status',
        'response_code',
        'response_description',
        'result_code',
        'result_desc',
        'mpesa_receipt_number',
        'transaction_date',
        'callback_metadata',
    ];
    
    protected $casts = [
        'transaction_date' => 'datetime',
        'callback_metadata' => 'array',
        'amount' => 'decimal:2',
    ];
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    
    public function isPending()
    {
        return $this->status === 'pending';
    }
    
    public function isFailed()
    {
        return $this->status === 'failed';
    }
}