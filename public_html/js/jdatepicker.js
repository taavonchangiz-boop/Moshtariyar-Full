/* تقویم شمسی پاپ‌آپ سبک با انتخاب ماه و سال — برای ورودی‌های دارای کلاس .jdate */
(function () {
  'use strict';
  var EN = '0123456789', FA = '۰۱۲۳۴۵۶۷۸۹';
  function toFa(s){ return String(s).replace(/[0-9]/g, function(d){ return FA[d]; }); }
  function toEn(s){ return String(s).replace(/[۰-۹]/g, function(d){ return EN[FA.indexOf(d)]; }); }
  var MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  var WD = ['ش','ی','د','س','چ','پ','ج'];

  function g2j(gy, gm, gd){
    var g_d_m=[0,31,59,90,120,151,181,212,243,273,304,334];
    var gy2=(gm>2)?(gy+1):gy;
    var days=355666+(365*gy)+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400)+gd+g_d_m[gm-1];
    var jy=-1595+(33*Math.floor(days/12053)); days%=12053;
    jy+=4*Math.floor(days/1461); days%=1461;
    if(days>365){ jy+=Math.floor((days-1)/365); days=(days-1)%365; }
    if(days<186) return [jy,1+Math.floor(days/31),1+(days%31)];
    return [jy,7+Math.floor((days-186)/30),1+((days-186)%30)];
  }
  function j2g(jy, jm, jd){
    jy+=1595;
    var days=-355668+(365*jy)+(Math.floor(jy/33)*8)+Math.floor(((jy%33)+3)/4)+jd+((jm<7)?(jm-1)*31:((jm-7)*30)+186);
    var gy=400*Math.floor(days/146097); days%=146097;
    if(days>36524){ gy+=100*Math.floor(--days/36524); days%=36524; if(days>=365) days++; }
    gy+=4*Math.floor(days/1461); days%=1461;
    if(days>365){ gy+=Math.floor((days-1)/365); days=(days-1)%365; }
    var gd=days+1, sal_a=[0,31,((gy%4===0&&gy%100!==0)||(gy%400===0))?29:28,31,30,31,30,31,31,30,31,30,31];
    var gm; for(gm=0;gm<13&&gd>sal_a[gm];gm++) gd-=sal_a[gm];
    return [gy,gm,gd];
  }
  function jMonthLen(jy,jm){ if(jm<=6) return 31; if(jm<=11) return 30; var g=j2g(jy,12,1), gy=g[0]; return ((gy%4===0&&gy%100!==0)||(gy%400===0))?30:29; }
  function jWeekDay(jy,jm,jd){ var g=j2g(jy,jm,jd); return new Date(g[0],g[1]-1,g[2]).getDay(); }
  function shanbeIndex(jsDay){ return (jsDay+1)%7; }

  var pop=null, target=null, view={jy:1404,jm:1};

  function baseBtn(){ return 'background:#172033;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:4px 9px;cursor:pointer;font-family:inherit'; }
  function build(){
    pop=document.createElement('div');
    pop.style.cssText='position:absolute;z-index:99999;background:#1e293b;border:1px solid #334155;border-radius:14px;padding:10px;width:286px;box-shadow:0 14px 38px rgba(0,0,0,.55);font-family:inherit;color:#e2e8f0;direction:rtl';
    document.body.appendChild(pop);
    document.addEventListener('click',function(e){ if(pop && pop.style.display!=='none' && !pop.contains(e.target) && e.target!==target) hide(); });
  }
  function hide(){ if(pop) pop.style.display='none'; }

  function render(){
    var jy=view.jy, jm=view.jm, len=jMonthLen(jy,jm), first=shanbeIndex(jWeekDay(jy,jm,1));
    var html='<div style="display:flex;justify-content:space-between;align-items:center;gap:6px;margin-bottom:8px">'+
      '<button type="button" data-nav="-1" style="'+baseBtn()+'">‹</button>'+
      '<select data-month style="flex:1;background:#172033;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:5px;font-family:inherit">';
    for(var mi=1;mi<=12;mi++) html+='<option value="'+mi+'" '+(mi===jm?'selected':'')+'>'+MONTHS[mi-1]+'</option>';
    html+='</select><select data-year style="width:88px;background:#172033;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:5px;font-family:inherit;direction:ltr">';
    for(var y=jy-80;y<=jy+20;y++) html+='<option value="'+y+'" '+(y===jy?'selected':'')+'>'+toFa(y)+'</option>';
    html+='</select><button type="button" data-nav="1" style="'+baseBtn()+'">›</button></div>';
    html+='<div style="display:flex;gap:6px;margin-bottom:8px"><button type="button" data-today style="'+baseBtn()+';flex:1">امروز</button><button type="button" data-clear style="'+baseBtn()+';flex:1">پاک کردن</button></div>';
    html+='<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:.72rem;color:#94a3b8;margin-bottom:4px">';
    WD.forEach(function(d){ html+='<div>'+d+'</div>'; });
    html+='</div><div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;text-align:center">';
    for(var i=0;i<first;i++) html+='<div></div>';
    for(var d=1;d<=len;d++) html+='<button type="button" data-day="'+d+'" style="cursor:pointer;border:0;border-radius:8px;padding:7px 0;font-size:.82rem;background:#172033;color:#e2e8f0;font-family:inherit">'+toFa(d)+'</button>';
    html+='</div>';
    pop.innerHTML=html;
    pop.querySelectorAll('[data-nav]').forEach(function(b){ b.onclick=function(){ var n=+b.getAttribute('data-nav'); view.jm+=n; if(view.jm>12){view.jm=1;view.jy++;} if(view.jm<1){view.jm=12;view.jy--;} render(); }; });
    pop.querySelector('[data-month]').onchange=function(){ view.jm=+this.value; render(); };
    pop.querySelector('[data-year]').onchange=function(){ view.jy=+this.value; render(); };
    pop.querySelector('[data-today]').onclick=function(){ var t=new Date(), j=g2j(t.getFullYear(),t.getMonth()+1,t.getDate()); view.jy=j[0]; view.jm=j[1]; setDate(j[2]); };
    pop.querySelector('[data-clear]').onclick=function(){ target.value=''; hide(); target.dispatchEvent(new Event('change',{bubbles:true})); };
    pop.querySelectorAll('[data-day]').forEach(function(c){ c.onmouseenter=function(){c.style.background='#0ea5e9';c.style.color='#001018'}; c.onmouseleave=function(){c.style.background='#172033';c.style.color='#e2e8f0'}; c.onclick=function(){ setDate(+c.getAttribute('data-day')); }; });
  }
  function setDate(d){ var val=view.jy+'/'+String(view.jm).padStart(2,'0')+'/'+String(d).padStart(2,'0'); target.value=toFa(val); hide(); target.dispatchEvent(new Event('change',{bubbles:true})); }
  function open(input){
    target=input; if(!pop) build(); var v=toEn(input.value||''), m=v.match(/(\d{4})\D(\d{1,2})\D(\d{1,2})/);
    if(m){ view.jy=+m[1]; view.jm=+m[2]; } else { var t=new Date(), j=g2j(t.getFullYear(),t.getMonth()+1,t.getDate()); view.jy=j[0]; view.jm=j[1]; }
    var r=input.getBoundingClientRect(); pop.style.display='block'; pop.style.top=(window.scrollY+r.bottom+4)+'px'; pop.style.left=(window.scrollX+r.left)+'px'; render();
  }
  document.addEventListener('focus', function(e){ if(e.target && e.target.classList && e.target.classList.contains('jdate')) open(e.target); }, true);
})();
