<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('move_in_date');
            $table->date('move_out_date')->nullable();
            $table->decimal('monthly_rate', 10, 2);
            $table->unsignedTinyInteger('billing_day');
            $table->enum('status', ['active', 'terminated', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'billing_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
