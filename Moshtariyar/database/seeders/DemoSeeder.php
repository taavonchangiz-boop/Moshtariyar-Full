<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\OrderItem;

/**
 * دادهٔ نمونه برای مشاهدهٔ سریع رابط کاربری.
 * اجرا: php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['علی رضایی', 'ali@example.ir', '09121110001', 'woocommerce'],
            ['مریم احمدی', 'maryam@example.ir', '09121110002', 'woocommerce'],
            ['شرکت پارس‌تک', 'info@parstech.ir', '09121110003', 'manual'],
            ['نگار موسوی', 'negar@example.ir', '09121110004', 'website-form'],
            ['حسین کریمی', 'hossein@example.ir', '09121110005', 'woocommerce'],
        ];

        foreach ($samples as $i => [$name, $email, $phone, $source]) {
            $customer = Customer::updateOrCreate(
                ['email' => $email],
                ['full_name' => $name, 'phone' => $phone, 'source' => $source]
            );

            $ordersCount = rand(1, 4);
            for ($n = 1; $n <= $ordersCount; $n++) {
                $total = rand(2, 30) * 100000;
                $status = ['completed', 'completed', 'processing', 'pending', 'cancelled'][array_rand(['completed','completed','processing','pending','cancelled'])];
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'number'      => (1000 + $i * 10 + $n),
                    'status'      => $status,
                    'total'       => $total,
                    'tax_total'   => round($total * 0.09),
                    'currency'    => 'IRT',
                    'source'      => 'woocommerce',
                    'placed_at'   => now()->subDays(rand(0, 60)),
                ]);
                OrderItem::create([
                    'order_id'   => $order->id,
                    'name'       => 'محصول نمونه ' . $n,
                    'sku'        => 'SKU-' . rand(100, 999),
                    'qty'        => rand(1, 3),
                    'unit_price' => $total,
                    'line_total' => $total,
                ]);
            }

            $customer->recalcLifetimeValue();
        }


        // تیکت نمونه
        $firstCustomer = \Modules\Core\Entities\Customer::first();
        if ($firstCustomer && class_exists(\Modules\Core\Entities\Ticket::class)) {
            $ticket = \Modules\Core\Entities\Ticket::create([
                'customer_id' => $firstCustomer->id,
                'subject' => 'سوال دربارهٔ وضعیت سفارش',
                'priority' => 'normal',
                'status' => 'open',
                'last_reply_at' => now(),
            ]);
            $ticket->replies()->create([
                'author' => 'customer', 'author_name' => $firstCustomer->full_name,
                'message' => 'سلام، سفارش من چه زمانی ارسال می‌شود؟',
            ]);
        }


        // سرنخ‌های نمونه برای قیف فروش
        $statusIds = \Illuminate\Support\Facades\DB::table('lead_statuses')->orderBy('order')->pluck('id')->all();
        if ($statusIds) {
            $sampleLeads = [
                ['سارا تهرانی', '09120000011', 5000000],
                ['رضا کاظمی', '09120000012', 12000000],
                ['شرکت آرتا', '09120000013', 30000000],
                ['مینا رستمی', '09120000014', 7000000],
            ];
            foreach ($sampleLeads as $idx => [$nm, $ph, $val]) {
                \Modules\Core\Entities\Lead::create([
                    'name' => $nm, 'phone' => $ph, 'value' => $val, 'source' => 'website-form',
                    'status_id' => $statusIds[$idx % count($statusIds)],
                ]);
            }
        }


        // محصولات نمونه
        if (class_exists(\Modules\Core\Entities\Product::class)) {
            foreach ([['تیشرت نخی','TSH-01','پوشاک',450000,30,5],['کفش ورزشی','SHO-02','کفش',1800000,3,5],['کوله‌پشتی','BAG-03','اکسسوری',950000,12,4]] as [$nm,$sku,$cat,$pr,$stk,$min]) {
                \Modules\Core\Entities\Product::updateOrCreate(['sku'=>$sku],['name'=>$nm,'category'=>$cat,'price'=>$pr,'stock'=>$stk,'min_stock'=>$min,'is_active'=>true]);
            }
        }


        // قوانین اتوماسیون نمونه
        if (class_exists(\Modules\Automation\Entities\Workflow::class) && \Illuminate\Support\Facades\Schema::hasTable('workflows')) {
            \Modules\Automation\Entities\Workflow::updateOrCreate(
                ['name' => 'پیامک تشکر پس از تکمیل سفارش'],
                ['event' => 'order.completed', 'action' => 'sms', 'channel' => null, 'delay_min' => 0,
                 'template' => 'سلام {name}، سفارش {order} با موفقیت تکمیل شد. سپاس از خرید شما. {brand}', 'is_active' => true]
            );
            \Modules\Automation\Entities\Workflow::updateOrCreate(
                ['name' => 'اطلاع پرداخت موفق'],
                ['event' => 'payment.paid', 'action' => 'sms', 'channel' => null, 'delay_min' => 0,
                 'template' => 'پرداخت سفارش {order} با موفقیت انجام شد. {brand}', 'is_active' => true]
            );
        }

        $this->command->info('دادهٔ نمونه ساخته شد: ' . count($samples) . ' مشتری با سفارش.');
    }
}
