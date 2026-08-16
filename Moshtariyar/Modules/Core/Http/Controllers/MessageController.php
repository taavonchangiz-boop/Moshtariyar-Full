<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\CustomerMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $segments = $this->getSegments();
        $selectedSegment = $request->get('segment', 'all');

        if ($selectedSegment === 'selected') {
            $recipientCount = 0;
            $preSelectedCustomers = [];
            $ids = $request->get('ids', '');
            if (!empty($ids)) {
                $idArray = array_filter(array_map('intval', explode(',', $ids)));
                if (!empty($idArray)) {
                    $customers = Customer::whereIn('id', $idArray)->get(['id', 'full_name', 'phone']);
                    foreach ($customers as $c) {
                        $preSelectedCustomers[] = [
                            'id' => $c->id,
                            'name' => $c->full_name,
                            'phone' => $c->phone ?? '',
                        ];
                    }
                    $recipientCount = count($preSelectedCustomers);
                }
            }
        } else {
            $recipientCount = $segments[$selectedSegment]['count'] ?? $segments['all']['count'] ?? 0;
            $preSelectedCustomers = [];
        }

        $templates = $this->getTemplates();
        $recentMessages = $this->getRecentMessages();
        $smsCost = $this->getSmsCost();
        $sentToday = $this->getSentToday();

        return view('core::messages.create', compact(
            'segments', 'selectedSegment', 'recipientCount',
            'templates', 'recentMessages', 'smsCost', 'sentToday',
            'preSelectedCustomers'
        ));
    }

    /**
     * جستجوی مشتری برای انتخاب دستی (API)
     */
    public function searchCustomers(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = Customer::where('full_name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(12)
            ->get(['id', 'full_name', 'phone', 'email']);

        return response()->json(['customers' => $customers]);
    }

    /**
     * ارسال تکی پیام به مشتری (از صفحه ۳۶۰)
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'message'     => 'required|string|max:2000',
            'channel'     => 'required|string|in:sms,email,messenger',
        ]);

        try {
            $customer = Customer::findOrFail($data['customer_id']);
            $personalizedMsg = $this->personalize($data['message'], $customer);

            CustomerMessage::create([
                'customer_id' => $customer->id,
                'sender'      => 'admin',
                'message'     => $personalizedMsg,
                'channel'     => $data['channel'],
                'sent_at'     => now(),
            ]);

            $this->dispatchSend($customer, $personalizedMsg, $data['channel']);

            return back()->with('success', '✅ پیام با موفقیت ارسال شد.');
        } catch (\Exception $e) {
            Log::error('Message Send Error: ' . $e->getMessage());
            return back()->with('error', '❌ خطا در ارسال پیام: ' . $e->getMessage());
        }
    }

    /**
     * ارسال گروهی پیام (از مرکز پیام‌رسانی)
     */
    public function sendBulk(Request $request)
    {
        $data = $request->validate([
            'segment'   => 'required|string|in:all,club,recent,inactive,vip,selected',
            'channels'  => 'required|array|min:1',
            'channels.*' => 'string|in:sms,email,telegram,bale,whatsapp,eitaa,rubika',
            'message'   => 'required|string|max:5000',
            'selected_ids' => 'nullable|string|max:5000',
        ]);

        if ($data['segment'] === 'selected') {
            $ids = array_filter(array_map('intval', explode(',', $data['selected_ids'] ?? '')));
            if (empty($ids)) {
                return back()->with('error', '❌ حداقل یک مشتری را برای ارسال انتخاب کنید.');
            }
            $customers = Customer::whereIn('id', $ids)->get();
        } else {
            $customers = $this->getCustomersBySegment($data['segment']);
        }

        $channels = $data['channels'];
        $template = $data['message'];
        $sent = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            foreach ($channels as $channel) {
                try {
                    $personalizedMsg = $this->personalize($template, $customer);

                    CustomerMessage::create([
                        'customer_id' => $customer->id,
                        'sender'      => 'admin',
                        'message'     => $personalizedMsg,
                        'channel'     => $channel,
                        'sent_at'     => now(),
                    ]);

                    $this->dispatchSend($customer, $personalizedMsg, $channel);
                    $sent++;
                } catch (\Exception $e) {
                    Log::error("Bulk send failed for customer {$customer->id}, channel {$channel}: " . $e->getMessage());
                    $failed++;
                }
            }
        }

        return redirect('/app/messages')
            ->with('success', "✅ پیام به {$sent} مخاطب ارسال شد." . ($failed > 0 ? " ({$failed} خطا)" : ""));
    }

    // ═══════════ متدهای کمکی ═══════════

    private function getSegments(): array
    {
        $allCount = Customer::count();
        $clubCount = DB::table('loyalty_members')->count() ?: 0;
        $recentCount = Customer::whereHas('orders', function ($q) {
            $q->where('placed_at', '>=', now()->subDays(30));
        })->count();
        $inactiveCount = Customer::whereDoesntHave('orders', function ($q) {
            $q->where('placed_at', '>=', now()->subDays(90));
        })->count();
        $vipCount = Customer::where('lifetime_value', '>=', 5000000)->count();

        return [
            'all'      => ['label' => 'همه مشتریان',        'count' => $allCount],
            'club'     => ['label' => 'اعضای باشگاه',       'count' => $clubCount],
            'recent'   => ['label' => 'خریداران ۳۰ روز اخیر', 'count' => $recentCount],
            'inactive' => ['label' => 'مشتریان کم‌فعال',    'count' => $inactiveCount],
            'vip'      => ['label' => 'مشتریان VIP',         'count' => $vipCount],
            'selected' => ['label' => 'مشتریان انتخابی',    'count' => 0],
        ];
    }

    private function getCustomersBySegment(string $segment)
    {
        $query = Customer::query();

        return match ($segment) {
            'club'     => $query->whereHas('loyalty')->get(),
            'recent'   => $query->whereHas('orders', fn($q) => $q->where('placed_at', '>=', now()->subDays(30)))->get(),
            'inactive' => $query->whereDoesntHave('orders', fn($q) => $q->where('placed_at', '>=', now()->subDays(90)))->get(),
            'vip'      => $query->where('lifetime_value', '>=', 5000000)->get(),
            default    => $query->get(),
        };
    }

    private function personalize(string $message, Customer $customer): string
    {
        $replacements = [
            '{نام}'    => $customer->full_name ?? 'مشتری عزیز',
            '{موبایل}' => $customer->phone ?? '—',
            '{کد}'     => strtoupper('MY-' . Str::random(6)),
            '{امتیاز}' => '۰',
            '{سطح}'    => 'عادی',
        ];

        try {
            $member = DB::table('loyalty_members')->where('customer_id', $customer->id)->first();
            if ($member) {
                $replacements['{امتیاز}'] = $member->points ?? '۰';
                $tier = DB::table('loyalty_tiers')->where('id', $member->tier_id)->first();
                if ($tier) {
                    $replacements['{سطح}'] = $tier->name ?? 'عادی';
                }
            }
        } catch (\Throwable $e) {}

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    private function dispatchSend(Customer $customer, string $message, string $channel): void
    {
        // برای نسخه آینده: اتصال به سرویس‌های واقعی
    }

    private function getTemplates(): array
    {
        return [];
    }

    private function getRecentMessages()
    {
        try {
            return CustomerMessage::with('customer')->latest('id')->limit(15)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function getSmsCost(): string
    {
        return '۱۵';
    }

    private function getSentToday(): int
    {
        try {
            return CustomerMessage::whereDate('created_at', today())->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}