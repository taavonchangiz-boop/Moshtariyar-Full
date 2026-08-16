<?php

namespace Modules\Automation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Automation\Entities\Workflow;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $query = Workflow::query()->latest('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('event', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%")
                    ->orWhere('channel', 'like', "%{$term}%");
            });
        }

        if ($request->filled('event')) {
            $query->where('event', (string) $request->input('event'));
        }

        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $workflows = $query->get();
        $workflows->each(function (Workflow $workflow) {
            $workflow->board_bucket = $this->workflowBoardBucket($workflow);
        });

        $workflowGroups = $workflows->groupBy('board_bucket');

        $summary = [
            'total' => Workflow::count(),
            'active' => Workflow::where('is_active', true)->count(),
            'inactive' => Workflow::where('is_active', false)->count(),
            'delayed' => Workflow::where('is_active', true)->where('delay_min', '>', 0)->count(),
            'messenger' => Workflow::where('is_active', true)->where('action', 'messenger')->count(),
        ];

        return view('app.workflows', [
            'workflows' => $workflows,
            'workflowGroups' => $workflowGroups,
            'summary' => $summary,
            'events' => Workflow::EVENTS,
            'actions' => Workflow::ACTIONS,
            'channels' => Workflow::CHANNELS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:191',
            'event' => 'required|string|max:50',
            'action' => 'required|string|max:30',
            'channel' => 'nullable|string|max:30',
            'delay_min' => 'nullable|integer|min:0|max:10080',
            'template' => 'nullable|string',
        ]);

        $data['is_active'] = true;
        $data['delay_min'] = $data['delay_min'] ?? 0;
        Workflow::create($data);

        return back()->with('status', 'قانون اتوماسیون ایجاد شد.');
    }

    public function toggle(Workflow $workflow)
    {
        $workflow->update(['is_active' => ! $workflow->is_active]);
        return back()->with('status', 'وضعیت قانون تغییر کرد.');
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();
        return back()->with('status', 'قانون حذف شد.');
    }

    private function workflowBoardBucket(Workflow $workflow): string
    {
        if (! $workflow->is_active) {
            return 'inactive';
        }

        if ($workflow->action === 'messenger') {
            return 'messenger';
        }

        if ((int) $workflow->delay_min > 0) {
            return 'delayed';
        }

        return 'instant';
    }
}