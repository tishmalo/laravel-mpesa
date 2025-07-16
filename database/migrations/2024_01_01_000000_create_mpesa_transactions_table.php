<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_request_id')->unique();
            $table->string('merchant_request_id');
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('account_reference');
            $table->string('transaction_desc');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('response_code')->nullable();
            $table->string('response_description')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->json('callback_metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('phone_number');
            $table->index('mpesa_receipt_number');
            $table->index('account_reference');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};