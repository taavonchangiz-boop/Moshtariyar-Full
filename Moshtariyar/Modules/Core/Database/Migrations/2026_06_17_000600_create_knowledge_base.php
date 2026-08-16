<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('kb_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('question')->nullable();
            $table->longText('answer');
            $table->string('visibility', 30)->default('public'); // public / staff
            $table->json('tags')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['visibility','is_active']);
            $table->fullText(['title','question','answer']);
        });

        DB::table('kb_categories')->insert([
            ['title'=>'سوالات عمومی','slug'=>'general','description'=>'پرسش‌های پرتکرار عمومی','order'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'باشگاه مشتریان','slug'=>'loyalty','description'=>'امتیاز، کیف پول، رفرال و کوپن‌ها','order'=>2,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'ووکامرس','slug'=>'woocommerce','description'=>'اتصال فروشگاه و همگام‌سازی','order'=>3,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
    }
};
