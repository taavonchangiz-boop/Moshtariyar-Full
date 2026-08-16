<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Lead;

class LeadController extends Controller
{
    /** ثبت سرنخ از فرم سایت (عمومی) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'nullable|string|max:191',
            'email'  => 'nullable|email',
            'phone'  => 'nullable|string|max:32',
            'source' => 'nullable|string|max:50',
            'value'  => 'nullable|numeric',
        ]);
        $data['source'] = $data['source'] ?? 'website-form';

        return response()->json(Lead::create($data), 201);
    }
}
