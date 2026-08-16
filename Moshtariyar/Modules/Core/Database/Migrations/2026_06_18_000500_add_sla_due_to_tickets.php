<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'sla_due_at')) $table->timestamp('sla_due_at')->nullable()->after('first_response_at');
            if (! Schema::hasColumn('tickets', 'sla_breached_at')) $table->timestamp('sla_breached_at')->nullable()->after('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['sla_breached_at','sla_due_at'] as $col) if (Schema::hasColumn('tickets', $col)) $table->dropColumn($col);
        });
    }
};
