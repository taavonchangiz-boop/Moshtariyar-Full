<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;
use Modules\IranPack\FinTech\GoldPriceFeeder;

class Product extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'sku',
        'name',
        'price',
        'tax_rate',
        'stock',
        'is_formula_based',
        'formula_type',
        'formula',
        'formula_variables',
        'live_gold_weight',
        'live_gold_ajrat',
        'live_gold_profit',
        'is_active',
        'meta'
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'tax_rate'           => 'decimal:2',
        'is_formula_based'   => 'boolean',
        'formula_variables'  => 'array',
        'live_gold_weight'   => 'decimal:3',
        'live_gold_ajrat'    => 'decimal:2',
        'live_gold_profit'   => 'decimal:2',
        'is_active'          => 'boolean',
        'meta'               => 'array',
    ];

    /**
     * گردش‌های موجودی انبار برای این محصول
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * نمایش قیمت قابل نمایش محصول (قیمت فروش ویژه اگر موجود باشد، در غیر این صورت قیمت اصلی)
     */
    public function displayPrice(): float
    {
        // اگر محصول بر اساس فرمول است، قیمت نهایی را محاسبه کن
        if ($this->is_formula_based) {
            $result = $this->calculateFinalPrice();
            return (float) $result['final_price'];
        }

        // قیمت فروش ویژه اگر موجود باشد، در غیر این صورت قیمت اصلی
        $salePrice = (float) ($this->sale_price ?? 0);

        return $salePrice > 0 ? $salePrice : (float) ($this->price ?? 0);
    }

    /**
     * محاسبهٔ قیمت نهایی محصول با در نظر گرفتن فرمول، وزن طلا، اجرت و سود
     *
     * @param  array  $ورودی‌ها  مقادیر سفارشی برای متغیرهای فرمول
     * @return array             نتیجهٔ محاسبه
     */
    public function calculateFinalPrice(array $ورودی‌ها = []): array
    {
        $قیمت_پایه      = (float) ($this->price ?? 0);
        $نرخ_مالیات      = (float) ($this->tax_rate ?? 0);
        $بر_اساس_فرمول  = (bool) $this->is_formula_based;

        // اگر محصول بر اساس فرمول نیست، قیمت ساده را برگردان
        if (!$بر_اساس_فرمول) {
            $مالیات = $قیمت_پایه * $نرخ_مالیات;
            $قیمت_نهایی = $قیمت_پایه + $مالیات;

            return [
                'base_price'       => $قیمت_پایه,
                'final_price'      => round($قیمت_نهایی),
                'tax_amount'       => round($مالیات),
                'tax_rate'         => $نرخ_مالیات,
                'is_formula_based' => false,
                'details'          => 'قیمت ثابت',
            ];
        }

        // ── محصول بر اساس فرمول (مخصوص طلافروشی و محصولات ترکیبی) ──

        $قیمت_زنده_طلا = 0;
        try {
            $قیمت_زنده_طلا = GoldPriceFeeder::getLiveGoldPrice18k() ?: 0;
        } catch (\Throwable $e) {
            // قیمت زنده در دسترس نیست — با صفر ادامه بده
        }

        $وزن_طلا  = (float) ($this->live_gold_weight ?? 0);
        $اجرت      = (float) ($this->live_gold_ajrat ?? 0);
        $سود       = (float) ($this->live_gold_profit ?? 0.07);

        // محاسبهٔ قیمت طلای خام بر اساس وزن
        $قیمت_طلای_خام = $وزن_طلا * $قیمت_زنده_طلا;

        // افزودن اجرت
        $قیمت_با_اجرت = $قیمت_طلای_خام + $اجرت;

        // افزودن سود فروشنده
        $قیمت_با_سود = $قیمت_با_اجرت * (1 + $سود);

        // اعمال مقادیر سفارشی از ورودی
        $متغیرهای_فرمول = is_array($this->formula_variables) ? $this->formula_variables : [];

        foreach ($ورودی‌ها as $کلید => $مقدار) {
            if (array_key_exists($کلید, $متغیرهای_فرمول)) {
                $متغیرهای_فرمول[$کلید] = (float) $مقدار;
            }
        }

        // اگر وزن طلا از ورودی آمده باشد، بازمحاسبه کن
        if (!empty($ورودی‌ها['live_gold_weight']) && $ورودی‌ها['live_gold_weight'] > 0) {
            $وزن_طلای_ورودی = (float) $ورودی‌ها['live_gold_weight'];
            $قیمت_طلای_خام   = $وزن_طلای_ورودی * $قیمت_زنده_طلا;
            $قیمت_با_اجرت   = $قیمت_طلای_خام + $اجرت;
            $قیمت_با_سود     = $قیمت_با_اجرت * (1 + $سود);
        }

        // محاسبهٔ مالیات
        $مالیات = $قیمت_با_سود * $نرخ_مالیات;
        $قیمت_نهایی = $قیمت_با_سود + $مالیات;

        return [
            'base_price'        => $قیمت_پایه,
            'gold_raw_price'    => round($قیمت_طلای_خام),
            'gold_weight'       => $وزن_طلا,
            'live_gold_price'   => $قیمت_زنده_طلا,
            'ajrat'             => round($اجرت),
            'profit_rate'       => $سود,
            'price_after_ajrat' => round($قیمت_با_اجرت),
            'price_after_profit'=> round($قیمت_با_سود),
            'final_price'       => round($قیمت_نهایی),
            'tax_amount'        => round($مالیات),
            'tax_rate'          => $نرخ_مالیات,
            'is_formula_based'  => true,
            'details'           => 'قیمت بر اساس وزن طلا و اجرت و سود',
            'variables'         => $متغیرهای_فرمول,
        ];
    }

    /**
     * ثبت یک گردش موجودی (ورود، خروج، یا اصلاح) در انبار
     *
     * @param  string     $نوع     in | out | adjust
     * @param  int|float  $تعداد   مقدار ورود/خروج
     * @param  string|null $دلیل   توضیح دلیل گردش
     * @param  int|null   $کاربر   شناسهٔ کاربر ثبت‌کننده
     * @return StockMovement
     */
    public function applyMovement(string $نوع, $تعداد, ?string $دلیل = null, ?int $کاربر = null): StockMovement
    {
        $تعداد = (int) $تعداد;
        $موجودی_فعلی = (int) ($this->stock ?? 0);

        // محاسبهٔ موجودی پس از گردش
        $موجودی_بعد = match ($نوع) {
            'in'     => $موجودی_فعلی + $تعداد,
            'out'    => max(0, $موجودی_فعلی - $تعداد),
            'adjust' => $تعداد,
            default  => $موجودی_فعلی,
        };

        // ثبت گردش در جدول stock_movements
        $گردش = StockMovement::create([
            'product_id'    => $this->id,
            'type'          => $نوع,
            'qty'           => $تعداد,
            'balance_after' => $موجودی_بعد,
            'reason'        => $دلیل,
            'user_id'       => $کاربر,
        ]);

        // به‌روزرسانی موجودی محصول
        $this->update(['stock' => $موجودی_بعد]);

        return $گردش;
    }
}