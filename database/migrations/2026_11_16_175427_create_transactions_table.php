<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('invoice_id');
            $table->foreignId('purchase_id')->constrained('course_purchases')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('payment_method', ['credit_card', 'wallet', 'paypal', 'bank_transfer'])->default('credit_card');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
