<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_no')->unique(); // Contoh: INV-20240130-001
        $table->integer('total_amount');
        $table->dateTime('transaction_date');
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
