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
        Schema::table('goals', function (Blueprint $table) {
            $table->boolean('is_exceeded_notified')->default(false)->after('end_date');
            $table->boolean('is_completed_notified')->default(false)->after('is_exceeded_notified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['is_exceeded_notified', 'is_completed_notified']);
        });
    }
};
