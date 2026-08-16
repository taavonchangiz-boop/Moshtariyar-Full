<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general'); // brand/payment/sms/tax/general
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_secret')->default(false);     // رمزنگاری مقدار حساس
            $table->timestamps();
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
