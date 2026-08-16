<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Lead;

class LeadPipelineController extends Controller
{
    /** نمای قیف فروش */
    public function board(Request $request)
    {
        $statuses = DB::table('lead_statuses')->orderBy('order')->get();
        $firstStatusId = optional($statuses->first())->id;

        $query = Lead::query()->orderByDesc('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('source', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->input('status_id'));
        }

        if ($request->filled('source')) {
            $query->where('source', (string) $request->input('source'));
        }

        $leadsFlat = $query->get();
        $leadIds = $leadsFlat->pluck('id')->all();

        $latestActivities = collect();
        if (! empty($leadIds)) {
            $latestActivities = Activity::query()
                ->select('subject_id', DB::raw('MAX(created_at) as last_activity_at'))
                ->where('subject_type', 'lead')
                ->whereIn('subject_id', $leadIds)
                ->groupBy('subject_id')
                ->pluck('last_activity_at', 'subject_id');
        }

        $leadsFlat->each(function (Lead $lead) use ($latestActivities) {
            $lead->last_activity_at = $latestActivities->get($lead->id);
        });

        $leads = $leadsFlat->groupBy('status_id');

        $totals = [];
        $maxColumnCount = 1;
        foreach ($statuses as $status) {
            $columnLeads = $leads->get($status->id, collect());
            $count = $columnLeads->count();
            $maxColumnCount = max($maxColumnCount, $count);

            $totals[$status->id] = [
                'count' => $count,
                'value' => (float) $columnLeads->sum('value'),
                'score' => round((float) $columnLeads->avg('score')),
            ];
        }

        $summary = [
            'count' => $leadsFlat->count(),
            'value' => (float) $leadsFlat->sum('value'),
            'converted' => $leadsFlat->whereNotNull('converted_customer_id')->count(),
            'conversion_rate' => $leadsFlat->count() ? round(($leadsFlat->whereNotNull('converted_customer_id')->count() / $leadsFlat->count()) * 100) : 0,
            'average_score' => round((float) $leadsFlat->avg('score')),
            'stages' => $statuses->count(),
        ];

        $sources = Lead::query()
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('app.pipeline', compact(
            'statuses',
            'leads',
            'totals',
            'summary',
            'sources',
            'firstStatusId',
            'maxColumnCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:191',
            'value' => 'nullable|numeric|min:0',
            'status_id' => 'nullable|exists:lead_statuses,id',
            'source' => 'nullable|string|max:50',
        ]);

        $data['source'] = $data['source'] ?: 'manual';
        $data['status_id'] = $data['status_id'] ?: DB::table('lead_statuses')->orderBy('order')->value('id');
        $data['value'] = $data['value'] ?? 0;
        $data['score'] = $this->estimateScore($data);
        $data['assigned_to'] = optional($request->user())->id;

        $lead = Lead::create($data);

        Activity::create([
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'type' => 'note',
            'body' => 'سرنخ از بورد فروش ثبت شد.',
            'user_id' => optional($request->user())->id,
        ]);

        return back()->with('status', 'سرنخ جدید ثبت شد.');
    }

    /** جابه‌جایی سرنخ بین مراحل قیف */
    public function move(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status_id' => 'required|exists:lead_statuses,id',
        ]);

        $oldStatus = DB::table('lead_statuses')->where('id', $lead->status_id)->first();
        $newStatus = DB::table('lead_statuses')->where('id', $data['status_id'])->first();

        if ((int) $lead->status_id !== (int) $data['status_id']) {
            $lead->update(['status_id' => $data['status_id']]);

            Activity::create([
                'subject_type' => 'lead',
                'subject_id' => $lead->id,
                'type' => 'note',
                'body' => 'مرحله سرنخ از «' . ($oldStatus->name ?? 'نامشخص') . '» به «' . ($newStatus->name ?? 'نامشخص') . '» تغییر کرد.',
                'user_id' => optional($request->user())->id,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'مرحله سرنخ تغییر کرد.',
                'lead_id' => $lead->id,
                'status_id' => (int) $data['status_id'],
            ]);
        }

        return back()->with('status', 'مرحله سرنخ تغییر کرد.');
    }

    public function show(Lead $lead)
    {
        $statuses = DB::table('lead_statuses')->orderBy('order')->get();
        $activities = Activity::where('subject_type', 'lead')->where('subject_id', $lead->id)
            ->latest('id')->get();

        return view('app.lead_show', compact('lead', 'statuses', 'activities'));
    }

    /** تبدیل سرنخ به مشتری */
    public function convert(Lead $lead)
    {
        if ($lead->converted_customer_id) {
            return back()->with('status', 'این سرنخ قبلاً به مشتری تبدیل شده است.');
        }

        $customer = null;

        if ($lead->email) {
            $customer = Customer::where('email', $lead->email)->first();
        }

        if (! $customer && $lead->phone) {
            $customer = Customer::where('phone', $lead->phone)->first();
        }

        if (! $customer) {
            $customer = Customer::create([
                'full_name' => $lead->name ?: 'مشتری بدون نام',
                'email' => $lead->email ?: null,
                'phone' => $lead->phone ?: null,
                'source' => 'lead',
            ]);
        } else {
            $updates = [];
            if (! $customer->full_name && $lead->name) {
                $updates['full_name'] = $lead->name;
            }
            if (! $customer->email && $lead->email) {
                $updates['email'] = $lead->email;
            }
            if (! $customer->phone && $lead->phone) {
                $updates['phone'] = $lead->phone;
            }
            if (! $customer->source) {
                $updates['source'] = 'lead';
            }
            if ($updates) {
                $customer->update($updates);
            }
        }

        $lead->update(['converted_customer_id' => $customer->id]);

        Activity::create([
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'type' => 'note',
            'body' => 'سرنخ به مشتری تبدیل شد.',
            'user_id' => auth()->id(),
        ]);

        return redirect(url('/app/customers/' . $customer->id))
            ->with('status', 'سرنخ با موفقیت به مشتری تبدیل شد.');
    }

    private function estimateScore(array $data): int
    {
        $score = 20;

        if (! empty($data['phone'])) {
            $score += 20;
        }

        if (! empty($data['email'])) {
            $score += 15;
        }

        if (! empty($data['value']) && (float) $data['value'] > 0) {
            $score += 20;
        }

        if (! empty($data['source']) && $data['source'] !== 'manual') {
            $score += 10;
        }

        return min(100, $score);
    }
}