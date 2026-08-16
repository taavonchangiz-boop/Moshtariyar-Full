<?php

namespace Modules\Automation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'trigger_segment_id',
        'trigger_event',
        'event',
        'action',
        'channel',
        'delay_min',
        'template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(JourneyStep::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(JourneyExecution::class, 'workflow_id')->latest('id');
    }

    public const EVENTS = [
        'order.completed' => 'تکمیل سفارش',
        'order.processing' => 'در حال پردازش سفارش',
        'order.cancelled' => 'لغو سفارش',
        'payment.paid' => 'پرداخت موفق',
        'customer.registered' => 'ثبت نام مشتری جدید',
        'customer.created' => 'ثبت مشتری جدید',
        'lead.created' => 'ثبت سرنخ جدید',
        'ticket.created' => 'ثبت تیکت جدید',
        'ticket.opened' => 'باز شدن تیکت جدید',
        'cart.abandoned' => 'رها شدن سبد خرید',
        'no_purchase_30d' => '۳۰ روز بدون خرید',
        'no_purchase_45d' => '۴۵ روز بدون خرید',
        'no_purchase_60d' => '۶۰ روز بدون خرید',
        'birthday_soon' => 'نزدیک شدن تولد',
        'rfm.changed' => 'تغییر گروه رفتاری مشتری',
    ];

    public const ACTIONS = [
        'sms' => 'ارسال پیامک',
        'messenger' => 'ارسال در پیام‌رسان',
        'email' => 'ارسال ایمیل',
        'create_task' => 'ساخت وظیفه پیگیری',
    ];

    public const CHANNELS = [
        'all' => 'همه پیام‌رسان‌های فعال',
        'telegram' => 'تلگرام',
        'whatsapp' => 'واتساپ',
        'bale' => 'بله',
        'rubika' => 'روبیکا',
        'eitaa' => 'ایتا',
    ];
}