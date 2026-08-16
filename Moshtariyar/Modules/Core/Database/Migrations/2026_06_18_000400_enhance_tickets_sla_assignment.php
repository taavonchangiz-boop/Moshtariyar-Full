<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'assigned_to')) $table->unsignedBigInteger('assigned_to')->nullable()->after('department');
            if (! Schema::hasColumn('tickets', 'customer_unread')) $table->boolean('customer_unread')->default(false)->after('status');
            if (! Schema::hasColumn('tickets', 'staff_unread')) $table->boolean('staff_unread')->default(true)->after('customer_unread');
            if (! Schema::hasColumn('tickets', 'first_response_at')) $table->timestamp('first_response_at')->nullable()->after('last_reply_at');
            if (! Schema::hasColumn('tickets', 'closed_at')) $table->timestamp('closed_at')->nullable()->after('first_response_at');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_replies', 'is_internal')) $table->boolean('is_internal')->default(false)->after('author');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_replies', 'is_internal')) $table->dropColumn('is_internal');
        });
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['closed_at','first_response_at','staff_unread','customer_unread','assigned_to'] as $col) {
                if (Schema::hasColumn('tickets', $col)) $table->dropColumn($col);
            }
        });
    }
};
