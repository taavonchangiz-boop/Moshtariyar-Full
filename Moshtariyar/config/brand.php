<?php

/*
|--------------------------------------------------------------------------
| تنظیمات مرکزی برند
|--------------------------------------------------------------------------
| محصول: «مشتری‌یار» — CRM، باشگاه مشتریان و اتوماسیون فروش
*/
return [
    'name'      => env('BRAND_NAME', 'مشتری‌یار'),
    'name_en'   => env('BRAND_NAME_EN', 'MoshtariYar'),
    'tagline'   => env('BRAND_TAGLINE', 'مدیریت هوشمند مشتریان، فروش و وفاداری'),

    'owner'     => env('BRAND_OWNER', 'هومن‌وب'),
    'owner_en'  => env('BRAND_OWNER_EN', 'HoomanWeb'),
    'domain'    => env('BRAND_DOMAIN', 'hoomanweb.ir'),
    'url'       => env('BRAND_URL', 'https://hoomanweb.ir'),
    'support'   => env('BRAND_SUPPORT_EMAIL', 'support@hoomanweb.ir'),
    'phone'     => env('SUPPORT_PHONE', ''),

    'logo'        => env('BRAND_LOGO', 'img/moshtariyar-logo.svg'),
    'logo_svg'    => env('BRAND_LOGO_SVG', 'img/moshtariyar-logo.svg'),
    'owner_logo'  => env('BRAND_OWNER_LOGO', 'img/hoomanweb-logo.svg'),
];
