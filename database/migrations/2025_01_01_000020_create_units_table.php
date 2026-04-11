<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->string('size');
            $table->string('size_label')->nullable();
            $table->decimal('monthly_price', 10, 2);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'overdue'])->default('available');
            $table->unsignedInteger('position_x')->default(0);
            $table->unsignedInteger('position_y')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'unit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
