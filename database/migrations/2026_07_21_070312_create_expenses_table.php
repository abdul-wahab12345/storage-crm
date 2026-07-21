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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['rent', 'utilities', 'maintenance', 'supplies', 'marketing', 'payroll', 'other'])->default('other');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salary_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor', 150)->nullable();
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
