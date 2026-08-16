<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('channel', ['sms', 'email'])->default('sms');
            $table->string('subject')->nullable();      // برای ایمیل
            $table->text('message');
            $table->string('segment', 40)->default('all'); // all / woocommerce / has_orders / leads
            $table->enum('status', ['draft', 'queued', 'sending', 'sent'])->default('draft');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
