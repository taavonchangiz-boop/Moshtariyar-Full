@extends('layouts.app')

@section('title', $heading ?? 'سفر مشتری')
@section('heading', $heading ?? 'سفر مشتری')
@section('subtitle', $subtitle ?? '')

@section('content')
<link rel="stylesheet" href="{{ asset('css/journeys-admin-pro.css') }}">
<div class="journeys-admin-page">
    {!! $content !!}
</div>
@endsection