/*
 * ═══════════════════════════════════════════════════════
 *  اسکریپت‌های اصلی — مشتری‌یار
 *  شامل: مدیریت تم، ساعت، جستجوی سراسری، جدول واکنش‌گرا،
 *  انتخاب فایل، پنل دستیار، کشوی جزئیات، پرش به بالا
 *  آخرین به‌روزرسانی: ۱۴۰۵/۰۵/۰۲
 * ═══════════════════════════════════════════════════════
 */

function اعمال_تم_مدیریتی() {
    try {
        var تم_ذخیره = localStorage.getItem('adminTheme');
        var تم = تم_ذخیره === 'dark' ? 'dark' : 'light';
        document.body.classList.toggle('light', تم === 'light');
        document.body.classList.toggle('dark', تم === 'dark');
        if (!تم_ذخیره) localStorage.setItem('adminTheme', 'light');
        document.cookie = "theme=" + تم + ";path=/;max-age=31536000";
        var متای_تم = document.querySelector('meta[name="theme-color"]');
        if (متای_تم) متای_تم.setAttribute('content', تم === 'light' ? '#f4f7fa' : '#05070a');
    } catch(خطا) {
        document.body.classList.add('light');
        document.body.classList.remove('dark');
    }
}
function تغییر_تم() {
    var تم_فعلی = document.body.classList.contains('light') ? 'dark' : 'light';
    localStorage.setItem('adminTheme', تم_فعلی);
    اعمال_تم_مدیریتی();
}
اعمال_تم_مدیریتی();

function به‌روزرسانی_ساعت() {
    var المان = document.getElementById('adminClock');
    if (!المان) return;
    var اکنون = new Date();
    var تاریخ = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(اکنون);
    var زمان = new Intl.DateTimeFormat('fa-IR', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(اکنون);
    المان.textContent = تاریخ + '، ساعت ' + زمان;
}
به‌روزرسانی_ساعت();
setInterval(به‌روزرسانی_ساعت, 1000);

function بازکردن_بازشو(شناسه) {
    document.querySelectorAll('.drop-panel').forEach(function(پ) {
        if (پ.id !== شناسه) پ.classList.remove('show');
    });
    document.getElementById(شناسه).classList.toggle('show');
}
document.addEventListener('click', function(رویداد) {
    if (!رویداد.target.closest('.drop-wrap')) {
        document.querySelectorAll('.drop-panel').forEach(function(پ) {
            پ.classList.remove('show');
        });
    }
});

(function() {
    var ورودی = document.getElementById('globalSearch');
    var نتایج = document.getElementById('globalResults');
    var زمان‌سنج = null;
    if (!ورودی) return;
    ورودی.addEventListener('input', function() {
        clearTimeout(زمان‌سنج);
        var پرس‌وجو = ورودی.value.trim();
        if (پرس‌وجو.length < 2) {
            نتایج.style.display = 'none';
            return;
        }
        زمان‌سنج = setTimeout(function() {
            fetch('/app/search?q=' + encodeURIComponent(پرس‌وجو))
                .then(function(پاسخ) { return پاسخ.json(); })
                .then(function(داده) {
                    نتایج.innerHTML = (داده.items || []).map(function(آیتم) {
                        return '<a href=\"' + آیتم.url + '\"><b>' + آیتم.title + '</b><small>' + آیتم.type + ' · ' + (آیتم.desc || '') + '</small></a>';
                    }).join('') || '<a>نتیجه‌ای یافت نشد</a>';
                    نتایج.style.display = 'block';
                });
        }, 250);
    });
    document.addEventListener('click', function(رویداد) {
        if (!رویداد.target.closest('.global-search')) نتایج.style.display = 'none';
    });
})();

function اعمال_برچسب‌های_جدول() {
    document.querySelectorAll('.table-wrap table').forEach(function(جدول) {
        var سربرگ‌ها = [].slice.call(جدول.querySelectorAll('thead th')).map(function(سلول) {
            return سلول.textContent.trim();
        });
        جدول.querySelectorAll('tbody tr').forEach(function(ردیف) {
            [].slice.call(ردیف.children).forEach(function(سلول, شماره) {
                if (!سلول.getAttribute('data-label')) {
                    سلول.setAttribute('data-label', سربرگ‌ها[شماره] || '');
                }
            });
        });
    });
}
document.addEventListener('DOMContentLoaded', اعمال_برچسب‌های_جدول);
اعمال_برچسب‌های_جدول();

function بهبود_فیلدهای_فایل() {
    document.querySelectorAll('input[type="file"]:not([data-file-enhanced])').forEach(function(ورودی) {
        ورودی.setAttribute('data-file-enhanced', '1');
        if (!ورودی.id) ورودی.id = 'file_' + Math.random().toString(36).slice(2);
        var لفاف = document.createElement('div');
        لفاف.className = 'file-picker';
        var برچسب = document.createElement('label');
        برچسب.className = 'file-picker-btn';
        برچسب.setAttribute('for', ورودی.id);
        برچسب.innerHTML = '📎 انتخاب فایل';
        var نام = document.createElement('span');
        نام.className = 'file-picker-name';
        نام.textContent = 'فایلی انتخاب نشده است';
        var راهنما = document.createElement('div');
        راهنما.className = 'file-picker-hint';
        راهنما.textContent = ورودی.dataset.hint || 'فرمت‌های مجاز را از توضیح کنار فیلد ببینید';
        ورودی.parentNode.insertBefore(لفاف, ورودی);
        لفاف.appendChild(ورودی);
        لفاف.appendChild(برچسب);
        لفاف.appendChild(نام);
        لفاف.appendChild(راهنما);
        ورودی.addEventListener('change', function() {
            نام.textContent = ورودی.files && ورودی.files[0] ? ورودی.files[0].name : 'فایلی انتخاب نشده است';
        });
    });
}
document.addEventListener('DOMContentLoaded', بهبود_فیلدهای_فایل);
بهبود_فیلدهای_فایل();

function ساخت_نشانی_دستیار() {
    var بافت_پویا = (typeof window.getMoshtariyarAssistantContext === 'function')
        ? window.getMoshtariyarAssistantContext()
        : null;
    var بافت = بافت_پویا || window.MoshtariyarAssistantContext || {};
    window.MoshtariyarAssistantContext = بافت;
    var پارامترها = new URLSearchParams({ embed: '1' });
    if (بافت.type) پارامترها.set('context_type', بافت.type);
    if (بافت.id !== null && بافت.id !== undefined && بافت.id !== '') پارامترها.set('context_id', بافت.id);
    if (بافت.title) پارامترها.set('context_title', بافت.title);
    if (بافت.url) پارامترها.set('context_url', بافت.url);
    return '/app/assistant?' + پارامترها.toString();
}
function بازکردن_دستیار() {
    var قاب = document.getElementById('adminAssistantFrame');
    if (قاب) { قاب.src = ساخت_نشانی_دستیار(); }
    document.body.classList.add('admin-assistant-open');
}
function بستن_دستیار() {
    document.body.classList.remove('admin-assistant-open');
}
function تغییر_وضعیت_دستیار(رویداد) {
    if (رویداد) رویداد.stopPropagation();
    if (document.body.classList.contains('admin-assistant-open')) {
        بستن_دستیار();
        return;
    }
    بازکردن_دستیار();
}
document.addEventListener('click', function(رویداد) {
    if (!document.body.classList.contains('admin-assistant-open')) return;
    if (رویداد.target.closest('#adminAssistantPanel') || رویداد.target.closest('#adminAssistantFloat')) return;
    بستن_دستیار();
});
document.addEventListener('keydown', function(رویداد) {
    if (رویداد.key === 'Escape') بستن_دستیار();
});

function بازکردن_کشو(نشانی, عنوان) {
    document.getElementById('drawerTitle').textContent = عنوان;
    document.getElementById('drawerIframe').src = نشانی;
    document.getElementById('sideDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
}
function بستن_کشو() {
    document.getElementById('sideDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.getElementById('drawerIframe').src = '';
}
function ارقام_فارسی(رشته) {
    return رشته.replace(/\d/g, function(رقم) {
        return '۰۱۲۳۴۵۶۷۸۹'[رقم];
    });
}
function پیمایش_متن(گره) {
    if (گره.nodeType === 3) {
        var متن = گره.data;
        var فارسی = ارقام_فارسی(متن);
        if (متن !== فارسی) گره.data = فارسی;
    } else if (
        گره.nodeType === 1 &&
        گره.nodeName !== "SCRIPT" &&
        گره.nodeName !== "STYLE" &&
        !گره.classList.contains('ltr') &&
        گره.nodeName !== "INPUT" &&
        گره.nodeName !== "TEXTAREA"
    ) {
        for (var i = 0; i < گره.childNodes.length; i++) {
            پیمایش_متن(گره.childNodes[i]);
        }
    }
}
document.addEventListener("DOMContentLoaded", function() {
    var محفظه = document.querySelector('.main');
    if (محفظه) { پیمایش_متن(محفظه); }
    var ناظر = new MutationObserver(function(جهش‌ها) {
        جهش‌ها.forEach(function(جهش) {
            if (جهش.addedNodes && جهش.addedNodes.length > 0) {
                جهش.addedNodes.forEach(function(گره) {
                    if (گره.nodeType === 1 || گره.nodeType === 3) پیمایش_متن(گره);
                });
            }
        });
    });
    if (محفظه) { ناظر.observe(محفظه, { childList: true, subtree: true }); }
    document.querySelectorAll('[data-count]').forEach(function(المان) {
        var شمارش = المان.getAttribute('data-count');
        if (شمارش && شمارش !== '') المان.setAttribute('data-count', ارقام_فارسی(شمارش));
    });
});
window.addEventListener('scroll', function() {
    var دکمه = document.getElementById('btnScrollToTop');
    if (!دکمه) return;
    var موقعیت = window.scrollY;
    var ارتفاع_کل = document.documentElement.scrollHeight - window.innerHeight;
    var درصد_اسکرول = ارتفاع_کل > 0 ? (موقعیت / ارتفاع_کل) : 0;
    if (موقعیت > 150) {
        دکمه.style.visibility = 'visible';
        دکمه.classList.add('is-visible');
        var شفافیت_هدف = 0.3 + (درصد_اسکرول * 0.7);
        دکمه.style.opacity = Math.min(شفافیت_هدف, 1).toString();
    } else {
        دکمه.classList.remove('is-visible');
        دکمه.style.opacity = '0';
        setTimeout(function() {
            if (window.scrollY <= 150) دکمه.style.visibility = 'hidden';
        }, 400);
    }
});