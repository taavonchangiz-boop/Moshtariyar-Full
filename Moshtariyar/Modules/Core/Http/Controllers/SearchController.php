<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Lead;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Product;
use Modules\Core\Entities\Ticket;
use Modules\Core\Entities\KbArticle;

class SearchController extends Controller
{
    public function advanced()
    {
        return view('app.advanced_search');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) return response()->json(['items' => []]);

        $items = [];
        if (Schema::hasTable('customers')) {
            foreach (Customer::where('full_name','like',"%{$q}%")->orWhere('phone','like',"%{$q}%")->orWhere('email','like',"%{$q}%")->limit(6)->get() as $c) {
                $items[] = ['type'=>'مشتری','title'=>$c->full_name,'desc'=>trim(($c->phone ?: '').' '.$c->email),'url'=>url('/app/customers/'.$c->id)];
            }
        }
        if (Schema::hasTable('orders')) {
            foreach (Order::where('number','like',"%{$q}%")->latest('id')->limit(4)->get() as $o) {
                $items[] = ['type'=>'سفارش','title'=>'سفارش '.$o->number,'desc'=>number_format((float)$o->total),'url'=>url('/app/orders')];
            }
        }
        if (Schema::hasTable('tickets')) {
            foreach (Ticket::where('subject','like',"%{$q}%")->latest('id')->limit(4)->get() as $t) {
                $items[] = ['type'=>'تیکت','title'=>$t->subject,'desc'=>$t->status,'url'=>url('/app/tickets/'.$t->id)];
            }
        }
        if (Schema::hasTable('products')) {
            foreach (Product::where('name','like',"%{$q}%")->orWhere('sku','like',"%{$q}%")->limit(4)->get() as $p) {
                $items[] = ['type'=>'محصول','title'=>$p->name,'desc'=>$p->sku,'url'=>url('/app/products/'.$p->id)];
            }
        }
        if (Schema::hasTable('leads')) {
            foreach (Lead::where('name','like',"%{$q}%")->orWhere('phone','like',"%{$q}%")->orWhere('email','like',"%{$q}%")->limit(4)->get() as $l) {
                $items[] = ['type'=>'سرنخ','title'=>$l->name ?: $l->phone,'desc'=>$l->email,'url'=>url('/app/pipeline/'.$l->id)];
            }
        }
        if (Schema::hasTable('kb_articles')) {
            foreach (KbArticle::where('title','like',"%{$q}%")->orWhere('question','like',"%{$q}%")->limit(4)->get() as $a) {
                $items[] = ['type'=>'دانش','title'=>$a->title,'desc'=>$a->question,'url'=>url('/app/kb/'.$a->id.'/edit')];
            }
        }

        return response()->json(['items' => array_slice($items, 0, 15)]);
    }
}
