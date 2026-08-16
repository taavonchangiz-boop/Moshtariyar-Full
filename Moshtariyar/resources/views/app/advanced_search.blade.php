@extends('layouts.app')
@section('title','جستجوی پیشرفته')
@section('heading','جستجوی پیشرفته')
@section('subtitle','پیدا کردن سریع در کل سامانه')
@section('content')
<div class="card">
    <h3 style="margin-top:0">پیدا کردن هوشمند</h3>
    <p class="muted">نام مشتری، موبایل، شماره سفارش، موضوع تیکت، محصول یا سرنخ را وارد کنید.</p>
    <input id="advSearch" placeholder="مثلاً: علی، 0912، سفارش 1001، تیکت پرداخت..." autofocus>
</div>
<div class="card" id="advResults"><div class="empty">برای شروع، حداقل دو حرف وارد کنید.</div></div>
<script>
const input=document.getElementById('advSearch'), box=document.getElementById('advResults'); let to=null;
input.addEventListener('input',()=>{clearTimeout(to); const q=input.value.trim(); if(q.length<2){box.innerHTML='<div class="empty">برای شروع، حداقل دو حرف وارد کنید.</div>';return;} box.innerHTML='<div class="empty">در حال جستجو...</div>'; to=setTimeout(()=>fetch('{{ url('/app/search') }}?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{const items=d.items||[]; box.innerHTML=items.length?('<div class="grid grid-2">'+items.map(i=>`<a class="card" style="margin:0" href="${i.url}"><span class="badge b-ok">${i.type}</span><h3>${i.title}</h3><p class="muted">${i.desc||''}</p></a>`).join('')+'</div>'):'<div class="empty">نتیجه‌ای یافت نشد.</div>'; }),250);});
</script>
@endsection
