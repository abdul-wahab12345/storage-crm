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
        Schema::create('wa_chats', function (Blueprint $table) {
            $table->id();
            $table->string('contact_phone', 30)->unique();
            $table->string('contact_name', 150)->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_body', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_chats');
    }
};
