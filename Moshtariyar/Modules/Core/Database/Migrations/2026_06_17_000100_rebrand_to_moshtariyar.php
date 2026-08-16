<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) return;
        $now = now();
        $rows = [
            ['group'=>'brand','key'=>'brand_name','value'=>'مشتری‌یار','is_secret'=>0,'created_at'=>$now,'updated_at'=>$now],
            ['group'=>'brand','key'=>'brand_tagline','value'=>'مدیریت هوشمند مشتریان، فروش و وفاداری','is_secret'=>0,'created_at'=>$now,'updated_at'=>$now],
            ['group'=>'brand','key'=>'brand_owner','value'=>'هومن‌وب','is_secret'=>0,'created_at'=>$now,'updated_at'=>$now],
        ];
        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(['key'=>$row['key']], $row);
        }
    }

    public function down(): void
    {
        // بازگشت اجباری انجام نمی‌دهیم تا تنظیمات سفارشی کاربر حفظ شود.
    }
};
