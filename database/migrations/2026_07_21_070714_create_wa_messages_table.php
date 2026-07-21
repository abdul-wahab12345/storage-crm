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
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_chat_id')->constrained()->cascadeOnDelete();
            $table->string('wa_message_id', 100)->unique();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'image', 'document', 'audio', 'video', 'template', 'other'])->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_filename', 150)->nullable();
            $table->enum('status', ['received', 'sent', 'delivered', 'read', 'failed'])->default('received');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};
