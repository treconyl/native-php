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
        Schema::table('proxy_keys', function (Blueprint $table) {
            $table->boolean('stop_requested')->default(false);
            $table->string('status', 32)->default('idle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxy_keys', function (Blueprint $table) {
            $table->dropColumn(['stop_requested', 'status']);
        });
    }
};
