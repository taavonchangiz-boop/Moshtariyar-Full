<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'type')) $table->string('type', 40)->default('main')->after('code');
            if (!Schema::hasColumn('warehouses', 'phone')) $table->string('phone', 30)->nullable()->after('location');
            if (!Schema::hasColumn('warehouses', 'manager_id')) $table->unsignedBigInteger('manager_id')->nullable()->after('phone');
            if (!Schema::hasColumn('warehouses', 'capacity')) $table->integer('capacity')->nullable()->after('manager_id');
            if (!Schema::hasColumn('warehouses', 'description')) $table->text('description')->nullable()->after('capacity');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'code')) $table->string('code', 40)->nullable()->after('name');
            if (!Schema::hasColumn('suppliers', 'category')) $table->string('category', 60)->default('material')->after('code');
            if (!Schema::hasColumn('suppliers', 'bank_account')) $table->string('bank_account', 100)->nullable()->after('address');
            if (!Schema::hasColumn('suppliers', 'payment_type')) $table->string('payment_type', 30)->default('cash')->after('bank_account');
            if (!Schema::hasColumn('suppliers', 'credit_limit')) $table->decimal('credit_limit', 15, 2)->default(0)->after('payment_type');
            if (!Schema::hasColumn('suppliers', 'description')) $table->text('description')->nullable()->after('credit_limit');
        });

        Schema::table('write_off_reasons', function (Blueprint $table) {
            if (!Schema::hasColumn('write_off_reasons', 'description')) $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['type', 'phone', 'manager_id', 'capacity', 'description']);
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['code', 'category', 'bank_account', 'payment_type', 'credit_limit', 'description']);
        });
        Schema::table('write_off_reasons', function (Blueprint $table) {
            $table->dropColumn(['description']);
        });
    }
};