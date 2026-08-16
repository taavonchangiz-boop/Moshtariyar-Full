@extends('layouts.app')

@section('title', $heading ?? 'بخش‌بندی مشتریان')
@section('heading', $heading ?? 'بخش‌بندی مشتریان')
@section('subtitle', $subtitle ?? '')

@section('content')
<link rel="stylesheet" href="{{ asset('css/segments-board.css') }}">
{!! $content !!}
@endsection