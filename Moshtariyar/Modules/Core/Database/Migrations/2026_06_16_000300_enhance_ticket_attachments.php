<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_replies', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment');
            }
            if (! Schema::hasColumn('ticket_replies', 'attachment_mime')) {
                $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            }
            if (! Schema::hasColumn('ticket_replies', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            foreach (['attachment_size', 'attachment_mime', 'attachment_name'] as $col) {
                if (Schema::hasColumn('ticket_replies', $col)) $table->dropColumn($col);
            }
        });
    }
};
