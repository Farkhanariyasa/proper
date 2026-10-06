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
        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->string('provinsi_id', 2)->nullable();
            $table->string('regency_id', 4)->nullable();

            $table->foreign('provinsi_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('regency_id')->references('id')->on('regencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->dropForeign(['provinsi_id']);
            $table->dropForeign(['regency_id']);
            $table->dropColumn(['provinsi_id', 'regency_id']);
        });
    }
};
