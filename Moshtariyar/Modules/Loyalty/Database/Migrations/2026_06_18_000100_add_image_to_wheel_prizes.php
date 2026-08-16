<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wheel_prizes', function (Blueprint $table) {
            if (! Schema::hasColumn('wheel_prizes', 'image')) {
                $table->string('image')->nullable()->after('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wheel_prizes', function (Blueprint $table) {
            if (Schema::hasColumn('wheel_prizes', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
