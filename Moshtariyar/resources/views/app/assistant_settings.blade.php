@extends('layouts.app')
@section('title','تنظیمات مغز هوشمند')
@section('heading','تنظیمات مغز هوشمند')
@section('subtitle','مدیریت اتصال هوش مصنوعی، مدل پاسخ‌گویی، مصرف و حالت پشتیبان داخلی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/assistant-settings-pro.css') }}">

@php
    $providerLabels = [
        'openai' => 'OpenAI',
        'gemini' => 'Google Gemini',
        'claude' => 'Anthropic Claude',
        'deepseek' => 'DeepSeek',
        'grok' => 'xAI Grok',
        'qwen' => 'Alibaba Qwen',
        'mistral' => 'Mistral AI',
    ];
@endphp

<div class="assistant-settings-page">
    @if(session('success'))
        <div class="assistant-alert is-ok">{{ session('success') }}</div>
    @endif

    @if(session('conn_status'))
        <div class="assistant-alert {{ session('conn_status')['ok'] ? 'is-ok' : 'is-bad' }}">
            <b>وضعیت اتصال:</b> {{ session('conn_status')['message'] }}
        </div>
    @endif

    @if($errors->any())
        <div class="assistant-alert is-bad">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="assistant-settings-hero">
        <div class="assistant-settings-hero-main">
            <span class="assistant-settings-eyebrow">مغز پاسخ‌گویی دستیار</span>
            <h2>دستیار باید حتی بدون اتصال بیرونی، با نقشه داخلی سامانه پاسخ‌گو بماند</h2>
            <p>در این بخش اتصال مدل زبانی، کلید دسترسی، تست اتصال و وضعیت مصرف را مدیریت می‌کنید. پاسخ‌های عملیاتی مهم مثل فروش، مشتری، تیکت، باشگاه، وظایف و کمپین‌ها از داده‌های داخلی سامانه پشتیبانی می‌شوند.</p>
            <div class="assistant-settings-actions">
                <a class="btn" href="{{ url('/app/assistant') }}">رفتن به چت دستیار</a>
                <a class="btn btn-ghost" href="{{ url('/app/assistant/widget-settings') }}">ظاهر ویجت</a>
                <a class="btn btn-ghost" href="{{ url('/app/kb') }}">پایگاه دانش</a>
            </div>
        </div>
        <div class="assistant-settings-metrics">
            <div class="assistant-settings-metric" style="--metric-color:#2563eb;"><span>ارائه‌دهنده فعلی</span><strong>{{ $providerLabels[$data['provider']] ?? $data['provider'] }}</strong><small>قابل تغییر</small></div>
            <div class="assistant-settings-metric" style="--metric-color:#8b5cf6;"><span>مدل فعال</span><strong>{{ $data['model'] ?: 'انتخاب نشده' }}</strong><small>مدل پاسخ‌گویی</small></div>
            <div class="assistant-settings-metric" style="--metric-color:#10b981;"><span>مصرف ثبت‌شده</span><strong>@fa(number_format($data['total_tokens'] ?? 0))</strong><small>توکن</small></div>
            <div class="assistant-settings-metric" style="--metric-color:#f59e0b;"><span>حالت داخلی</span><strong>فعال</strong><small>پشتیبان بدون وابستگی</small></div>
        </div>
    </section>

    <section class="assistant-settings-layout">
        <article class="assistant-settings-card is-accent" style="--card-color:#2563eb;">
            <header>
                <div><span>اتصال مدل زبانی</span><h2>تنظیم ارائه‌دهنده، مدل و کلید دسترسی</h2></div>
            </header>
            <form action="{{ route('assistant.settings.save') }}" method="POST" id="aiSettingsForm" class="assistant-settings-form">
                @csrf
                <div class="assistant-form-grid">
                    <div>
                        <label>ارائه‌دهنده هوش مصنوعی</label>
                        <select name="provider" id="ai_provider" onchange="updateModels()">
                            @foreach($providerLabels as $key => $label)
                                <option value="{{ $key }}" @selected($data['provider'] == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>مدل مورد استفاده</label>
                        <select name="model" id="ai_model"></select>
                    </div>
                </div>

                <div>
                    <label>کلید دسترسی</label>
                    <div class="assistant-key-row">
                        <input type="password" name="api_key" id="ai_api_key" value="{{ $data['api_key'] }}" placeholder="کلید دسترسی را وارد کنید">
                        <button type="button" class="btn btn-ghost" id="btn_test_conn" onclick="testConnection()">تست اتصال</button>
                    </div>
                    <small id="conn_status" class="assistant-inline-status">برای بررسی ارتباط، بعد از وارد کردن کلید روی تست اتصال بزنید.</small>
                </div>

                <div class="assistant-form-actions">
                    <button type="submit" class="btn">ذخیره تنظیمات</button>
                </div>
            </form>
        </article>

        <aside class="assistant-settings-side">
            <article class="assistant-settings-card is-accent" style="--card-color:#10b981;">
                <header><div><span>حالت پشتیبان داخلی</span><h2>دستیار وابسته مطلق به سرویس بیرونی نیست</h2></div></header>
                <div class="assistant-guide-list">
                    <div><b>فروش و سفارش</b><p>گزارش سفارش، فروش، سفارش‌های باز و میانگین ارزش سفارش.</p></div>
                    <div><b>مشتری و باشگاه</b><p>تحلیل مشتری، امتیاز، کیف پول، سطح، ریزش و پیگیری.</p></div>
                    <div><b>پشتیبانی</b><p>تیکت‌های فوری، پاسخ پیشنهادی، وضعیت تیکت و مهلت پاسخ.</p></div>
                    <div><b>اقدام تأییدشده</b><p>ساخت کمپین، پیش‌فاکتور، تیکت و یادآور فقط پس از تأیید مدیر.</p></div>
                </div>
            </article>

            <article class="assistant-settings-card is-accent" style="--card-color:#f59e0b;">
                <header><div><span>راهنمای انتخاب</span><h2>چطور مدل را انتخاب کنیم؟</h2></div></header>
                <div class="assistant-provider-list">
                    <div><b>تحلیل مدیریتی</b><span>مدل‌های قوی‌تر برای گزارش‌های طولانی و تصمیم‌سازی بهتر هستند.</span></div>
                    <div><b>پاسخ سریع</b><span>مدل‌های سبک‌تر برای گفت‌وگوی سریع و هزینه کمتر مناسب‌اند.</span></div>
                    <div><b>حریم دسترسی</b><span>کلید دسترسی را فقط در همین بخش ذخیره کنید و در متن چت نفرستید.</span></div>
                </div>
            </article>
        </aside>
    </section>
</div>

<script>
const API_MODELS_URL = "{{ url('/app/assistant/models') }}";
const API_TEST_CONN_URL = "{{ url('/app/assistant/test-connection') }}";
const CURRENT_MODEL = "{{ $data['model'] }}";

async function updateModels() {
    const provider = document.getElementById('ai_provider').value;
    const modelSelect = document.getElementById('ai_model');
    modelSelect.innerHTML = '<option>در حال بارگذاری...</option>';

    try {
        const response = await fetch(`${API_MODELS_URL}?provider=${encodeURIComponent(provider)}`);
        if (!response.ok) throw new Error('خطا در پاسخ سرور');
        const data = await response.json();
        modelSelect.innerHTML = '';
        if (data.models && data.models.length > 0) {
            data.models.forEach(function (model) {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                if (model === CURRENT_MODEL) option.selected = true;
                modelSelect.appendChild(option);
            });
        } else {
            modelSelect.innerHTML = '<option value="">مدلی یافت نشد</option>';
        }
    } catch (error) {
        modelSelect.innerHTML = '<option value="">خطا در دریافت مدل‌ها</option>';
    }
}

async function testConnection() {
    const btn = document.getElementById('btn_test_conn');
    const status = document.getElementById('conn_status');
    const provider = document.getElementById('ai_provider').value;
    const apiKey = document.getElementById('ai_api_key').value;

    if (!apiKey) {
        status.textContent = 'لطفاً ابتدا کلید دسترسی را وارد کنید.';
        status.className = 'assistant-inline-status is-bad';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'در حال تست...';
    status.textContent = 'در حال بررسی اتصال...';
    status.className = 'assistant-inline-status';

    try {
        const response = await fetch(API_TEST_CONN_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({provider: provider, api_key: apiKey})
        });
        const data = await response.json();
        status.textContent = data.message || 'پاسخ نامشخص دریافت شد.';
        status.className = data.ok ? 'assistant-inline-status is-ok' : 'assistant-inline-status is-bad';
    } catch (error) {
        status.textContent = 'خطای سیستمی هنگام تست اتصال.';
        status.className = 'assistant-inline-status is-bad';
    } finally {
        btn.disabled = false;
        btn.textContent = 'تست اتصال';
    }
}

document.addEventListener('DOMContentLoaded', updateModels);
</script>
@endsection