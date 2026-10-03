<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('last_login_at');
            $table->softDeletes()->after('updated_at');

            // email sekarang nullable karena bisa login dengan username
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['username', 'is_active', 'last_login_at', 'created_by']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
