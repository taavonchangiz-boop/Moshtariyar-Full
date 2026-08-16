{{-- partial: 360/timeline --}}
<div class="card">
    <h3 style="margin:0 0 12px;">📅 تایم‌لاین فعالیت‌ها</h3>

    @if(isset($timeline) && $timeline->isNotEmpty())
        <div style="max-height:340px; overflow-y:auto;">
            @foreach($timeline as $item)
                <div style="padding:9px 0; border-bottom:1px solid #334155; display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:32px; font-size:18px; opacity:0.9;">{{ $item['icon'] ?? '📌' }}</div>
                    <div style="flex:1;">
                        @php
                            $title = $item['title'] ?? '';
                            // اطمینان از فارسی بودن ارقام شماره سفارش و هر عدد دیگر
                            $title = \Modules\Core\Support\Num::fa($title);
                            // تبدیل # به عبارت فارسی واضح برای شماره سفارش
                            if (str_contains($title, 'سفارش #') || str_contains($title, 'سفارش#')) {
                                $title = str_replace(['سفارش #', 'سفارش#'], 'سفارش شماره ', $title);
                            }
                        @endphp
                        @if(!empty($item['link']))
                            <a href="{{ $item['link'] }}" style="font-weight:600; color:#e2e8f0;">
                                {{ $title }}
                            </a>
                        @else
                            <strong>{{ $title }}</strong>
                        @endif

                        @if(!empty($item['amount']))
                            <span style="margin-right:8px;">— @money($item['amount'])</span>
                        @endif

                        <div class="muted" style="font-size:12px; margin-top:1px;">
                            @php
                                // تاریخ و ساعت کاملاً جلالی و فارسی (دفاعی در برابر ورودی غیر Carbon یا انگلیسی)
                                $rawDate = $item['date'] ?? null;
                                $dateObj = null;
                                if ($rawDate) {
                                    if ($rawDate instanceof \DateTimeInterface) {
                                        $dateObj = $rawDate;
                                    } elseif (is_string($rawDate)) {
                                        try { $dateObj = \Carbon\Carbon::parse($rawDate); } catch (\Throwable $e) { $dateObj = null; }
                                    }
                                }
                                $dateStr = $dateObj ? \Modules\Core\Support\Jalali::datetime($dateObj) : '—';
                            @endphp
                            {{ $dateStr }}
                            @if(!empty($item['status']))
                                • 
                                @php
                                    $st = $item['status'] ?? '';
                                    $statusFa = [
                                        'completed' => 'تکمیل شده',
                                        'processing' => 'در حال پردازش',
                                        'shipped' => 'ارسال شده',
                                        'pending' => 'در انتظار',
                                        'cancelled' => 'لغو شده',
                                        'failed' => 'ناموفق',
                                        'done' => 'انجام شده',
                                        'active' => 'فعال',
                                        'new' => 'جدید',
                                        'open' => 'باز',
                                        'closed' => 'بسته شده',
                                        'ثبت شده' => 'ثبت شده',
                                    ][$st] ?? $st;
                                    // اگر وضعیت خام انگلیسی ماند، حداقل فارسی‌سازی ساده
                                    if (is_string($statusFa) && !preg_match('/[\x{0600}-\x{06FF}]/u', $statusFa)) {
                                        $statusFa = strtr($statusFa, [
                                            'completed' => 'تکمیل شده',
                                            'processing' => 'در حال پردازش',
                                            'shipped' => 'ارسال شده',
                                            'pending' => 'در انتظار',
                                        ]);
                                    }
                                @endphp
                                <span class="badge {{ ($item['status']==='completed' || $statusFa==='تکمیل شده') ? 'b-ok' : '' }}">{{ $statusFa }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="muted">هنوز فعالیتی ثبت نشده است.</p>
    @endif
</div>