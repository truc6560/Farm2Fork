<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chuyen_van_chuyen', function (Blueprint $table) {
            $table->unsignedBigInteger('nguoi_lap_id')->nullable()->after('tai_xe_id');
            $table->foreign('nguoi_lap_id')
                ->references('tai_khoan_id')
                ->on('tai_khoan_he_thong')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chuyen_van_chuyen', function (Blueprint $table) {
            $table->dropForeign(['nguoi_lap_id']);
            $table->dropColumn('nguoi_lap_id');
        });
    }
};
