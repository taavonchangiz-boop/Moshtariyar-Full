<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Lead;
use Modules\Core\Support\Jalali;

class ActivityController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_type' => 'required|in:customer,lead',
            'subject_id' => 'required|integer',
            'type' => 'required|in:note,call,meeting,task,email,sms',
            'body' => 'nullable|string',
            'due' => 'nullable|string',
        ]);

        Activity::create([
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'type' => $data['type'],
            'body' => $data['body'] ?? null,
            'user_id' => optional($request->user())->id,
            'due_at' => ! empty($data['due'])
                ? Jalali::parse($data['due'])
                : null,
        ]);

        return back()->with('status', 'وظیفه یا یادآوری با موفقیت ثبت شد.');
    }

    public function toggle(Activity $activity)
    {
        $activity->update([
            'done' => ! $activity->done,
        ]);

        return back()->with(
            'status',
            $activity->done
                ? 'وضعیت انجام‌شده ثبت شد.'
                : 'وظیفه دوباره فعال شد.'
        );
    }

    /** مرکز وظایف و پیگیری‌های هوشمند مدیر */
    public function reminders(Request $request)
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $query = Activity::query()
            ->latest('due_at')
            ->latest('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));

            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhere('subject_type', 'like', "%{$term}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('subject_type')) {
            $query->where(
                'subject_type',
                (string) $request->input('subject_type')
            );
        }

        if ($request->filled('customer_id')) {
            $query->where('subject_type', 'customer')
                ->where('subject_id', (int) $request->input('customer_id'));
        }

        if ($request->boolean('mine')) {
            $query->where(
                'user_id',
                optional($request->user())->id
            );
        }

        if ($request->boolean('assistant')) {
            $query->where(function ($q) {
                $q->where('body', 'like', '%دستیار%')
                    ->orWhereNull('user_id');
            });
        }

        if ($request->input('scope') === 'overdue') {
            $query->where('done', false)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $todayStart);
        } elseif ($request->input('scope') === 'today') {
            $query->where('done', false)
                ->whereBetween('due_at', [$todayStart, $todayEnd]);
        } elseif ($request->input('scope') === 'waiting') {
            $query->where('done', false)
                ->whereNull('due_at');
        } elseif ($request->input('scope') === 'upcoming') {
            $query->where('done', false)
                ->where('due_at', '>', $todayEnd);
        } elseif ($request->input('scope') === 'done') {
            $query->where('done', true);
        } else {
            $query->where(function ($q) {
                $q->where('done', false)
                    ->orWhere('updated_at', '>=', now()->subDays(14));
            });
        }

        $items = $query->limit(400)->get();

        $this->hydrateSubjects($items);

        $grouped = collect([
            'overdue' => $items
                ->filter(
                    fn ($item) =>
                        ! $item->done
                        && $item->due_at
                        && $item->due_at->lt($todayStart)
                )
                ->values(),

            'today' => $items
                ->filter(
                    fn ($item) =>
                        ! $item->done
                        && $item->due_at
                        && $item->due_at->betweenIncluded(
                            $todayStart,
                            $todayEnd
                        )
                )
                ->values(),

            'waiting' => $items
                ->filter(
                    fn ($item) =>
                        ! $item->done
                        && is_null($item->due_at)
                )
                ->values(),

            'upcoming' => $items
                ->filter(
                    fn ($item) =>
                        ! $item->done
                        && $item->due_at
                        && $item->due_at->gt($todayEnd)
                )
                ->values(),

            'done' => $items
                ->filter(fn ($item) => (bool) $item->done)
                ->values(),
        ]);

        $summary = [
            'total' => Activity::where('done', false)->count(),

            'overdue' => Activity::where('done', false)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $todayStart)
                ->count(),

            'today' => Activity::where('done', false)
                ->whereBetween('due_at', [$todayStart, $todayEnd])
                ->count(),

            'waiting' => Activity::where('done', false)
                ->whereNull('due_at')
                ->count(),

            'upcoming' => Activity::where('done', false)
                ->where('due_at', '>', $todayEnd)
                ->count(),

            'done' => Activity::where('done', true)
                ->where('updated_at', '>=', now()->subDays(14))
                ->count(),

            'mine' => Activity::where('done', false)
                ->where('user_id', optional($request->user())->id)
                ->count(),
        ];

        $customers = Customer::orderBy('full_name')
            ->limit(250)
            ->get(['id', 'full_name', 'phone']);

        $leads = Lead::latest('id')
            ->limit(250)
            ->get(['id', 'name', 'phone']);

        return view(
            'app.reminders',
            compact(
                'items',
                'grouped',
                'summary',
                'customers',
                'leads'
            )
        );
    }

    private function hydrateSubjects($items): void
    {
        $customerIds = $items
            ->where('subject_type', 'customer')
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        $leadIds = $items
            ->where('subject_type', 'lead')
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        $customerMap = Customer::whereIn('id', $customerIds)
            ->get(['id', 'full_name', 'phone'])
            ->keyBy('id');

        $leadMap = Lead::whereIn('id', $leadIds)
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        $items->each(function (Activity $activity) use ($customerMap, $leadMap) {
            if ($activity->subject_type === 'customer') {
                $subject = $customerMap->get($activity->subject_id);

                $activity->subject_title = $subject?->full_name
                    ?: 'مشتری حذف‌شده';

                $activity->subject_phone = $subject?->phone;
                $activity->subject_url = $subject
                    ? url('/app/customers/' . $subject->id)
                    : null;

                return;
            }

            $subject = $leadMap->get($activity->subject_id);

            $activity->subject_title = $subject?->name
                ?: 'سرنخ حذف‌شده';

            $activity->subject_phone = $subject?->phone;
            $activity->subject_url = $subject
                ? url('/app/pipeline/leads/' . $subject->id)
                : null;
        });
    }
}