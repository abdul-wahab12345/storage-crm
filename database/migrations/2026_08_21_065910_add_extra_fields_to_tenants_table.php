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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('emirates_id')->nullable()->after('last_name');
            $table->string('company_name')->nullable()->after('emirates_id');
            $table->string('trade_license_number')->nullable()->after('company_name');
            $table->string('passport_number')->nullable()->after('trade_license_number');
            $table->string('alt_phone')->nullable()->after('phone');
            $table->string('logo')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'emirates_id',
                'company_name',
                'trade_license_number',
                'passport_number',
                'alt_phone',
                'logo',
            ]);
        });
    }
};
