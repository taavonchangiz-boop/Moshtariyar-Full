<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;
use Modules\Core\Services\Insights;

class InsightsController extends Controller
{
    /** داشبورد هوش مصنوعی: توزیع سگمنت‌ها + مشتریان در خطر ریزش + VIPها */
    public function index()
    {
        $distribution = Insights::segmentDistribution();

        // مشتریان در خطر ریزش و VIP
        $atRisk = [];
        $vip = [];
        Customer::with('orders')->chunk(200, function ($chunk) use (&$atRisk, &$vip) {
            foreach ($chunk as $c) {
                $ins = Insights::forCustomer($c);
                if ($ins['churn_risk'] >= 60 && $ins['rfm']['frequency'] > 0) {
                    $atRisk[] = ['c' => $c, 'i' => $ins];
                }
                if (str_contains($ins['segment'], 'وفادار')) {
                    $vip[] = ['c' => $c, 'i' => $ins];
                }
            }
        });
        usort($atRisk, fn ($a, $b) => $b['i']['churn_risk'] <=> $a['i']['churn_risk']);
        usort($vip, fn ($a, $b) => $b['i']['rfm']['monetary'] <=> $a['i']['rfm']['monetary']);

        return view('app.insights', [
            'distribution' => $distribution,
            'atRisk'       => array_slice($atRisk, 0, 10),
            'vip'          => array_slice($vip, 0, 10),
        ]);
    }
}
