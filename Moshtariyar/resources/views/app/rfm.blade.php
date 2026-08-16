@extends('layouts.app')

@section('title', 'تحلیل رفتار مشتریان')
@section('heading', '📊 تحلیل رفتار مشتریان')
@section('subtitle', 'گروه‌بندی مشتریان بر اساس تازگی خرید، تعداد خرید، ارزش خرید و طول رابطه')

@section('content')
<link rel="stylesheet" href="{{ asset('css/rfm-pro.css') }}">
<div class="rfm-pro-page" dir="rtl">
    {!! $content !!}
</div>
@endsection