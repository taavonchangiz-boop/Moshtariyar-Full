<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 50);   // order.completed / order.processing / payment.paid
            $table->string('action', 30);  // sms / email
            $table->text('template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
