<?php

namespace Modules\WooBridge\Services;

use Modules\Core\Entities\Customer;
use Modules\Core\Entities\CustomerAddress;
use Modules\WooBridge\Entities\IdMap;

class CustomerSync
{
    /**
     * upsert مشتری از payload ووکامرس با منطق ضدتکرار سه‌مرحله‌ای:
     *  1) جستجو در wb_id_map با woo_id
     *  2) جستجو با کلید یکتا (email یا phone)
     *  3) ساخت رکورد جدید
     */
    public function upsert(int $connectionId, array $woo): Customer
    {
        $wooId = (int) ($woo['id'] ?? 0);

        $billing  = $woo['billing'] ?? [];
        $shipping = $woo['shipping'] ?? [];

        $email = $this->normalizeEmail($woo['email'] ?? ($billing['email'] ?? null));
        $phone = $this->normalizePhone($billing['phone'] ?? ($woo['phone'] ?? null));
        $name  = trim(($woo['first_name'] ?? ($billing['first_name'] ?? '')) . ' '
                    . ($woo['last_name'] ?? ($billing['last_name'] ?? '')));
        $name  = $name !== '' ? $name : ($woo['username'] ?? 'مشتری ووکامرس');

        // 1) از روی نگاشت شناسه
        $customer = null;
        if ($wooId) {
            $crmId = IdMap::resolveCrmId($connectionId, 'customer', $wooId);
            if ($crmId) {
                $customer = Customer::find($crmId);
            }
        }

        // 2) از روی کلید یکتا
        if (! $customer && $email) {
            $customer = Customer::where('email', $email)->first();
        }
        if (! $customer && $phone) {
            $customer = Customer::where('phone', $phone)->first();
        }

        // 3) ساخت
        if (! $customer) {
            $customer = new Customer();
            $customer->source = 'woocommerce';
        }

        $customer->full_name = $name;
        if ($email && ! $customer->email) $customer->email = $email;
        if ($phone && ! $customer->phone) $customer->phone = $phone;
        if (! empty($billing['company'])) {
            $customer->type = 'company';
            $customer->company_name = $billing['company'];
        }
        $customer->save();

        // آدرس‌ها
        $this->syncAddress($customer, 'billing', $billing);
        $this->syncAddress($customer, 'shipping', $shipping);

        // ثبت نگاشت
        if ($wooId) {
            IdMap::link($connectionId, 'customer', $wooId, $customer->id);
        }

        return $customer;
    }

    private function syncAddress(Customer $customer, string $kind, array $data): void
    {
        if (empty($data['address_1']) && empty($data['city'])) {
            return;
        }
        CustomerAddress::updateOrCreate(
            ['customer_id' => $customer->id, 'kind' => $kind],
            [
                'province'    => $data['state'] ?? null,
                'city'        => $data['city'] ?? null,
                'address'     => trim(($data['address_1'] ?? '') . ' ' . ($data['address_2'] ?? '')),
                'postal_code' => $data['postcode'] ?? null,
            ],
        );
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) return null;
        // تبدیل ارقام فارسی/عربی به انگلیسی و حذف کاراکترهای اضافه
        $phone = strtr($phone, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '98'))  $phone = '0' . substr($phone, 2);
        if (str_starts_with($phone, '9') && strlen($phone) === 10) $phone = '0' . $phone;
        return $phone ?: null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        return $email !== '' ? mb_strtolower($email) : null;
    }
}
