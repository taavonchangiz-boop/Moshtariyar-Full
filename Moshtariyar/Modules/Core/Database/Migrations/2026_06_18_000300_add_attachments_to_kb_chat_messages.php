<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kb_chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('kb_chat_messages', 'attachment')) $table->string('attachment')->nullable()->after('message');
            if (! Schema::hasColumn('kb_chat_messages', 'attachment_name')) $table->string('attachment_name')->nullable()->after('attachment');
            if (! Schema::hasColumn('kb_chat_messages', 'attachment_mime')) $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            if (! Schema::hasColumn('kb_chat_messages', 'attachment_size')) $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    public function down(): void
    {
        Schema::table('kb_chat_messages', function (Blueprint $table) {
            foreach (['attachment_size','attachment_mime','attachment_name','attachment'] as $col) {
                if (Schema::hasColumn('kb_chat_messages', $col)) $table->dropColumn($col);
            }
        });
    }
};
