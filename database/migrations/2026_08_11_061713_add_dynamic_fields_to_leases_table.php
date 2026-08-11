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
        Schema::table('leases', function (Blueprint $table) {
            $table->string('storage_type')->nullable()->after('notes');
            $table->string('goods_condition')->nullable()->after('storage_type');
            $table->longText('custom_terms')->nullable()->after('goods_condition');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['storage_type', 'goods_condition', 'custom_terms']);
        });
    }
};
