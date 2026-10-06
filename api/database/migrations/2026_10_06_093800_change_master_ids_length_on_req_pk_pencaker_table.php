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
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->string('regency_id', 10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->string('regency_id', 4)->nullable()->change();
        });
    }
};
