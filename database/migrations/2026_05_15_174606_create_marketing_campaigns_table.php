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
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('template_name');
            $table->string('language_code')->default('en_US');
            $table->enum('audience_type', ['all', 'all_except', 'selected'])->default('all');
            $table->json('tenant_ids')->nullable();       // selected or excluded IDs
            $table->json('body_variables')->nullable();   // [{position,type,value}, ...]
            $table->string('header_url')->nullable();     // public URL for document/image header
            $table->enum('status', ['draft', 'sending', 'completed', 'failed'])->default('draft');
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
