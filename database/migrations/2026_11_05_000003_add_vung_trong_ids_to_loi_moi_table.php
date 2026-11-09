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
        Schema::table('loi_moi', function (Blueprint $table) {
            $table->json('vung_trong_ids')->nullable()->after('vung_trong_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loi_moi', function (Blueprint $table) {
            $table->dropColumn('vung_trong_ids');
        });
    }
};

