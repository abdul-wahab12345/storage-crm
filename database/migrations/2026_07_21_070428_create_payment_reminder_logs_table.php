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
        Schema::create('payment_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('days_before');
            $table->enum('channel', ['whatsapp', 'email']);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['invoice_id', 'days_before', 'channel'], 'payment_reminder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_logs');
    }
};
