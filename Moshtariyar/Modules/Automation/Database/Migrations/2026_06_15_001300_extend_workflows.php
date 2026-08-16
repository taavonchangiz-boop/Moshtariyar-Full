<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (! Schema::hasColumn('workflows', 'channel'))     $table->string('channel', 30)->nullable()->after('action');  // sms/telegram/all
            if (! Schema::hasColumn('workflows', 'delay_min'))   $table->unsignedInteger('delay_min')->default(0)->after('channel'); // تأخیر به دقیقه
            if (! Schema::hasColumn('workflows', 'description'))  $table->string('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['channel', 'delay_min', 'description']);
        });
    }
};
