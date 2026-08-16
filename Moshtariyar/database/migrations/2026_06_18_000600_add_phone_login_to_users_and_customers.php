<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 32)->nullable()->unique()->after('email');
            }
        });
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'portal_password')) {
                $table->string('portal_password')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'portal_password')) $table->dropColumn('portal_password');
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
        });
    }
};
