<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Automation\Entities\Workflow;
use Modules\Core\Entities\Segment;
use Modules\Core\Support\Num;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Money;

class JourneyController extends Controller
{
    public function index()
    {
        $workflows = Workflow::with('steps')
            ->withCount([
                'executions as active_executions_count' => fn($q) => $q->where('status', 'active'),
                'executions as completed_executions_count' => fn($q) => $q->where('status', 'completed'),
            ])
            ->orderByDesc('id')
            ->get();
        $html = $this->buildIndexHtml($workflows);
        return view('app.journeys.index', ['content' => $html, 'heading' => 'سفر مشتری', 'subtitle' => 'سناریوهای خودکار چندمرحله‌ای برای ارتباط هدفمند با مشتریان']);
    }

    public function create()
    {
        $segments = Segment::where('is_active', true)->get();
        $html = $this->buildFormHtml(null, $segments);
        return view('app.journeys.index', ['content' => $html, 'heading' => 'ساخت سفر جدید', 'subtitle' => 'تعریف مسیر خودکار برای مشتریان']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_segment_id' => 'nullable|exists:segments,id',
            'trigger_event' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.type' => 'required|in:wait,condition,action',
            'steps.*.label' => 'nullable|string',
            'steps.*.wait_hours' => 'nullable|integer',
            'steps.*.condition_field' => 'nullable|string',
            'steps.*.condition_value' => 'nullable|string',
            'steps.*.action_type' => 'nullable|string',
            'steps.*.action_template' => 'nullable|string',
        ]);

        $workflow = Workflow::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'type' => 'journey',
            'trigger_segment_id' => $validated['trigger_segment_id'] ?? null,
            'trigger_event' => $validated['trigger_event'] ?? null,
            'event' => $validated['trigger_event'] ?? 'journey.start',
            'action' => 'journey.action',
            'is_active' => true,
        ]);

        foreach ($validated['steps'] as $i => $step) {
            $workflow->steps()->create([
                'step_order' => $i,
                'type' => $step['type'],
                'label' => $step['label'] ?? '',
                'wait_hours' => $step['wait_hours'] ?? null,
                'condition_field' => $step['condition_field'] ?? null,
                'condition_value' => $step['condition_value'] ?? null,
                'action_type' => $step['action_type'] ?? null,
                'action_template' => $step['action_template'] ?? null,
                'action_channel' => $step['action_channel'] ?? null,
            ]);
        }

        return redirect('/app/journeys')->with('status', 'سفر مشتری با موفقیت ساخته شد.');
    }

    public function show(Workflow $workflow)
    {
        $workflow->load(['steps', 'executions.customer']);
        $html = $this->buildShowHtml($workflow);
        return view('app.journeys.index', ['content' => $html, 'heading' => $workflow->name, 'subtitle' => 'جزئیات سفر مشتری']);
    }

    public function edit(Workflow $workflow)
    {
        $workflow->load('steps');
        $segments = Segment::where('is_active', true)->get();
        $html = $this->buildFormHtml($workflow, $segments);
        return view('app.journeys.index', ['content' => $html, 'heading' => 'ویرایش سفر مشتری', 'subtitle' => $workflow->name]);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_segment_id' => 'nullable|exists:segments,id',
            'trigger_event' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.type' => 'required|in:wait,condition,action',
            'steps.*.label' => 'nullable|string',
            'steps.*.wait_hours' => 'nullable|integer',
            'steps.*.condition_field' => 'nullable|string',
            'steps.*.condition_value' => 'nullable|string',
            'steps.*.action_type' => 'nullable|string',
            'steps.*.action_template' => 'nullable|string',
        ]);

        $workflow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'trigger_segment_id' => $validated['trigger_segment_id'] ?? null,
            'trigger_event' => $validated['trigger_event'] ?? null,
        ]);

        $workflow->steps()->delete();
        foreach ($validated['steps'] as $i => $step) {
            $workflow->steps()->create([
                'step_order' => $i,
                'type' => $step['type'],
                'label' => $step['label'] ?? '',
                'wait_hours' => $step['wait_hours'] ?? null,
                'condition_field' => $step['condition_field'] ?? null,
                'condition_value' => $step['condition_value'] ?? null,
                'action_type' => $step['action_type'] ?? null,
                'action_template' => $step['action_template'] ?? null,
                'action_channel' => $step['action_channel'] ?? null,
            ]);
        }

        return redirect('/app/journeys')->with('status', 'سفر مشتری با موفقیت بروزرسانی شد.');
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();
        return redirect('/app/journeys')->with('status', 'سفر مشتری حذف شد.');
    }

    public function toggle(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);
        $label = $workflow->is_active ? 'فعال' : 'غیرفعال';
        return redirect('/app/journeys')->with('status', "سفر مشتری {$label} شد.");
    }

    // ─────── HTML Builders ───────

    private function buildIndexHtml($workflows): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $fa = fn($v) => Num::fa($v);
        $u = fn($path) => url($path);

        $total = $workflows->count();
        $active = $workflows->where('is_active', true)->count();
        $inactive = $workflows->where('is_active', false)->count();
        $running = $workflows->sum(fn($workflow) => (int) ($workflow->active_executions_count ?? 0));
        $completed = $workflows->sum(fn($workflow) => (int) ($workflow->completed_executions_count ?? 0));

        $groups = collect([
            'running' => [
                'title' => 'در حال اجرا',
                'hint' => 'سفرهایی که همین حالا مشتری فعال دارند',
                'color' => '#10b981',
                'items' => $workflows->filter(fn($workflow) => $workflow->is_active && (int) ($workflow->active_executions_count ?? 0) > 0)->values(),
            ],
            'ready' => [
                'title' => 'آماده اجرا',
                'hint' => 'فعال هستند ولی فعلاً اجرای زنده ندارند',
                'color' => '#0ea5e9',
                'items' => $workflows->filter(fn($workflow) => $workflow->is_active && (int) ($workflow->active_executions_count ?? 0) === 0)->values(),
            ],
            'inactive' => [
                'title' => 'غیرفعال',
                'hint' => 'سفرهایی که متوقف شده‌اند',
                'color' => '#f59e0b',
                'items' => $workflows->filter(fn($workflow) => ! $workflow->is_active)->values(),
            ],
        ]);

        $events = Workflow::EVENTS;
        if (! isset($events['rfm.changed'])) {
            $events['rfm.changed'] = 'تغییر گروه رفتاری مشتری';
        }

        $html = '';
        $html .= '<section class="journeys-hero">';
        $html .= '<div class="journeys-hero-main">';
        $html .= '<span class="journeys-eyebrow">اتوماسیون سفر مشتری</span>';
        $html .= '<h2>مسیرهای خودکار چندمرحله‌ای برای ارتباط هدفمند با مشتریان</h2>';
        $html .= '<p>سفر مشتری یک مسیر هوشمند است که با یک رویداد شروع می‌شود، منتظر می‌ماند، رفتار مشتری را بررسی می‌کند و اقدام مناسب را انجام می‌دهد؛ مثل خوش‌آمدگویی، بازگشت مشتری غیرفعال یا پیگیری سفارش.</p>';
        $html .= '<div class="journeys-hero-actions">';
        $html .= '<a class="btn" href="'.$u('/app/journeys/create').'">➕ ساخت سفر جدید</a>';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/workflows').'">⚙️ گردش‌کارهای ساده</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="journeys-hero-metrics">';
        $html .= '<div><span>کل سفرها</span><b>'.$fa(number_format($total)).'</b><small>مسیر ثبت‌شده</small></div>';
        $html .= '<div><span>سفر فعال</span><b>'.$fa(number_format($active)).'</b><small>آماده اجرا</small></div>';
        $html .= '<div><span>اجرای زنده</span><b>'.$fa(number_format($running)).'</b><small>مشتری در مسیر</small></div>';
        $html .= '<div><span>تکمیل‌شده</span><b>'.$fa(number_format($completed)).'</b><small>اجرای پایان‌یافته</small></div>';
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="journeys-template-strip">';
        $html .= '<a href="'.$u('/app/journeys/create?template=welcome').'"><i>👋</i><b>خوش‌آمدگویی</b><small>بعد از ثبت‌نام مشتری</small></a>';
        $html .= '<a href="'.$u('/app/journeys/create?template=cart').'"><i>🛒</i><b>سبد رهاشده</b><small>یادآوری خرید نیمه‌کاره</small></a>';
        $html .= '<a href="'.$u('/app/journeys/create?template=reengage').'"><i>🔄</i><b>بازگشت مشتری</b><small>فعال‌سازی مشتری خاموش</small></a>';
        $html .= '</section>';

        if ($workflows->isEmpty()) {
            $html .= '<section class="journeys-empty-state">';
            $html .= '<div>🗺️</div>';
            $html .= '<h3>هنوز هیچ سفری ساخته نشده است</h3>';
            $html .= '<p>با ساخت سفر مشتری، ارتباط خودکار و هدفمند با مشتریان را شروع کنید.</p>';
            $html .= '<a class="btn" href="'.$u('/app/journeys/create').'">ساخت اولین سفر</a>';
            $html .= '</section>';
            return $html;
        }

        $html .= '<section class="journeys-board-shell"><div class="journeys-board">';
        foreach ($groups as $group) {
            $items = $group['items'];
            $ratio = $total ? round($items->count() * 100 / max(1, $total)) : 0;
            $html .= '<article class="journeys-column" style="--journey-color: '.$group['color'].'; --column-ratio: '.$ratio.'%;">';
            $html .= '<header class="journeys-column-header"><div><span class="journeys-stage-mark"></span><h3>'.$h($group['title']).'</h3></div><span class="journeys-stage-count">'.$fa(number_format($items->count())).'</span></header>';
            $html .= '<div class="journeys-column-meta"><span>'.$h($group['hint']).'</span></div>';
            $html .= '<div class="journeys-column-progress"><span></span></div>';
            $html .= '<div class="journeys-drop-zone">';

            if ($items->isEmpty()) {
                $html .= '<div class="journeys-empty-column">در این وضعیت سفری وجود ندارد.</div>';
            }

            foreach ($items as $workflow) {
                $stepCount = $workflow->steps ? $workflow->steps->count() : 0;
                $eventLabel = $workflow->trigger_event ? ($events[$workflow->trigger_event] ?? $workflow->trigger_event) : 'شروع دستی یا زمان‌بندی‌شده';
                $activeCount = (int) ($workflow->active_executions_count ?? 0);
                $completedCount = (int) ($workflow->completed_executions_count ?? 0);

                $html .= '<details class="journey-card" style="--journey-color: '.$group['color'].';">';
                $html .= '<summary class="journey-card-summary">';
                $html .= '<span class="journey-summary-arrow">⌄</span>';
                $html .= '<span class="journey-summary-main"><b>'.$h($workflow->name).'</b><small>'.$h($eventLabel).'</small></span>';
                $html .= '<span class="journey-summary-count">'.$fa(number_format($stepCount)).'</span>';
                $html .= '</summary>';
                $html .= '<div class="journey-card-body">';
                if ($workflow->description) {
                    $html .= '<p>'.$h($workflow->description).'</p>';
                }
                $html .= '<div class="journey-info-box"><span>رویداد شروع</span><b>'.$h($eventLabel).'</b></div>';
                $html .= '<div class="journey-mini-grid">';
                $html .= '<div><span>مرحله</span><b>'.$fa(number_format($stepCount)).'</b></div>';
                $html .= '<div><span>اجرای زنده</span><b>'.$fa(number_format($activeCount)).'</b></div>';
                $html .= '<div><span>تکمیل‌شده</span><b>'.$fa(number_format($completedCount)).'</b></div>';
                $html .= '<div><span>وضعیت</span><b>'.($workflow->is_active ? 'فعال' : 'غیرفعال').'</b></div>';
                $html .= '</div>';
                $html .= '<div class="journey-actions-row">';
                $html .= '<a class="journey-small-action journey-small-action-primary" href="'.$u('/app/journeys/'.$workflow->id).'">مشاهده مسیر</a>';
                $html .= '<a class="journey-small-action" href="'.$u('/app/journeys/'.$workflow->id.'/edit').'">ویرایش</a>';
                $html .= '<a class="journey-small-action" href="'.$u('/app/journeys/'.$workflow->id.'/toggle').'">'.($workflow->is_active ? 'غیرفعال کردن' : 'فعال کردن').'</a>';
                $html .= '<form method="post" action="'.$u('/app/journeys/'.$workflow->id).'" onsubmit="return confirm(\'حذف شود؟\')"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button class="journey-small-action journey-small-action-danger">حذف</button></form>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</details>';
            }

            $html .= '</div></article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    private function buildFormHtml(?Workflow $workflow = null, $segments = null): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $u = fn($p) => url($p);
        $isEdit = $workflow !== null;
        $action = $isEdit ? $u('/app/journeys/'.$workflow->id) : $u('/app/journeys');
        $method = $isEdit ? 'PUT' : 'POST';

        $html = '';
        $html .= '<form method="post" action="'.$action.'" class="card" style="margin-bottom:20px;">';
        $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
        if ($isEdit) $html .= '<input type="hidden" name="_method" value="PUT">';

        // نام
        $html .= '<div style="margin-bottom:14px;"><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">نام سفر <span style="color:var(--bad);">*</span></label>';
        $html .= '<input name="name" value="'.($isEdit?$h($workflow->name):'').'" placeholder="مثلاً: بازگشت مشتریان غیرفعال" required style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;"></div>';

        // توضیحات
        $html .= '<div style="margin-bottom:14px;"><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">توضیحات</label>';
        $html .= '<textarea name="description" rows="2" placeholder="توضیح کوتاه..." style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;">'.($isEdit?$h($workflow->description):'').'</textarea></div>';

        // تریگر
        $html .= '<div style="margin-bottom:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">';
        // رویداد
        $html .= '<div><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">رویداد شروع</label>';
        $html .= '<select name="trigger_event" style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;">';
        $html .= '<option value="">-- دستی (با Cron اجرا می‌شود) --</option>';
        $events = Workflow::EVENTS;
        if (!isset($events['rfm.changed'])) {
            $events['rfm.changed'] = 'تغییر گروه رفتاری مشتری';
        }
        $selEvent = $isEdit ? $workflow->trigger_event : '';
        foreach ($events as $k => $lbl) {
            $html .= '<option value="'.$k.'"'.($selEvent===$k?' selected':'').'>'.$lbl.'</option>';
        }
        $html .= '</select></div>';
        // سگمنت
        $html .= '<div><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">محدود به گروه (اختیاری)</label>';
        $html .= '<select name="trigger_segment_id" style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;">';
        $html .= '<option value="">-- همه مشتریان --</option>';
        $selSeg = $isEdit ? $workflow->trigger_segment_id : '';
        if ($segments) foreach ($segments as $seg) {
            $html .= '<option value="'.$seg->id.'"'.($selSeg==$seg->id?' selected':'').'>'.$h($seg->name).' ('.$seg->members_count.' نفر)</option>';
        }
        $html .= '</select></div></div>';

        // مراحل
        $html .= '<div style="margin-bottom:14px;">';
        $html .= '<label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">مراحل سفر <span style="color:var(--bad);">*</span></label>';
        $html .= '<div id="stepsContainer" style="display:grid;gap:10px;">';

        $existingSteps = $isEdit ? $workflow->steps()->orderBy('step_order')->get() : collect([]);
        if ($existingSteps->isEmpty()) {
            $html .= $this->renderStepRow(0);
        } else {
            foreach ($existingSteps as $i => $st) {
                $html .= $this->renderStepRow($i, $st);
            }
        }

        $html .= '</div>';
        $html .= '<div style="display:flex;gap:8px;margin-top:10px;">';
        $html .= '<button type="button" class="btn btn-ghost" onclick="addStep(\'wait\')" style="font-size:.82rem;">⏱️ افزودن انتظار</button>';
        $html .= '<button type="button" class="btn btn-ghost" onclick="addStep(\'condition\')" style="font-size:.82rem;">🔍 افزودن بررسی</button>';
        $html .= '<button type="button" class="btn btn-ghost" onclick="addStep(\'action\')" style="font-size:.82rem;">⚡ افزودن اقدام</button>';
        $html .= '</div></div>';

        $html .= '<div style="display:flex;gap:10px;">';
        $html .= '<button type="submit" class="btn">'.($isEdit?'💾 بروزرسانی سفر':'✅ ساخت سفر').'</button>';
        $html .= '<a href="'.$u('/app/journeys').'" class="btn btn-ghost">انصراف</a>';
        $html .= '</div>';
        $html .= '</form>';

        // JS
        $html .= '<script>
let stepIdx = '.($existingSteps->count()?:1).';
function addStep(type) {
    var container = document.getElementById("stepsContainer");
    var row = document.createElement("div");
    row.innerHTML = '.json_encode($this->renderStepRow(999, null, 'wait')).'.replace(/999/g, stepIdx).replace(/\"wait\"/g, JSON.stringify(type));
    container.appendChild(row.firstElementChild);
    stepIdx++;
}
function removeStep(btn) {
    var rows = document.querySelectorAll("#stepsContainer > div");
    if (rows.length > 1) btn.closest("#stepsContainer > div").remove();
}
</script>';
        return $html;
    }

    private function renderStepRow(int $idx, $step = null, string $forceType = ''): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $type = $forceType ?: ($step ? $step->type : 'action');

        $colors = ['wait'=>'#f59e0b','condition'=>'#a78bfa','action'=>'#10b981'];
        $icons = ['wait'=>'⏱️','condition'=>'🔍','action'=>'⚡'];
        $labels = ['wait'=>'انتظار','condition'=>'بررسی','action'=>'اقدام'];
        $color = $colors[$type] ?? '#10b981';

        $html = '<div class="journey-step" style="background:var(--panel2);border:2px solid '.$color.'33;border-radius:14px;padding:14px;position:relative;">';
        $html .= '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">';
        $html .= '<span style="background:'.$color.';color:#fff;border-radius:999px;padding:4px 12px;font-size:.78rem;font-weight:800;">'.($icons[$type]??'⚡').' '.($labels[$type]??'اقدام').'</span>';
        $html .= '<span style="font-size:.78rem;color:var(--mut);">مرحله '.($idx+1).'</span>';
        $html .= '<input type="hidden" name="steps['.$idx.'][type]" value="'.$type.'">';
        $html .= '<div style="flex:1;"></div>';
        $html .= '<button type="button" class="btn btn-ghost" onclick="removeStep(this)" style="padding:4px 8px;font-size:.75rem;color:var(--bad);">✕</button>';
        $html .= '</div>';

        if ($type === 'wait') {
            $html .= '<div style="display:flex;align-items:center;gap:8px;">';
            $html .= '<span style="font-size:.82rem;color:var(--txt);">منتظر بمان به مدت</span>';
            $html .= '<input name="steps['.$idx.'][wait_hours]" value="'.($step?$step->wait_hours:'24').'" type="number" min="1" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;width:80px;font-family:inherit;font-size:13px;">';
            $html .= '<span style="font-size:.82rem;color:var(--txt);">ساعت</span>';
            $html .= '</div>';
        } elseif ($type === 'condition') {
            $html .= '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
            $html .= '<span style="font-size:.82rem;color:var(--txt);">بررسی کن آیا</span>';
            $html .= '<select name="steps['.$idx.'][condition_field]" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;font-family:inherit;font-size:13px;">';
            $selF = $step ? $step->condition_field : 'bought_after_trigger';
            foreach (['bought_after_trigger'=>'بعد از شروع سفر خرید کرده','in_segment'=>'در گروه خاصی قرار دارد','rfm_group'=>'گروه رفتاری مشخص'] as $k => $lbl) {
                $html .= '<option value="'.$k.'"'.($selF===$k?' selected':'').'>'.$lbl.'</option>';
            }
            $html .= '</select>';
            $html .= '<input name="steps['.$idx.'][condition_value]" value="'.($step?$h($step->condition_value):'').'" placeholder="مقدار..." style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;width:140px;font-family:inherit;font-size:13px;">';
            $html .= '</div>';
        } elseif ($type === 'action') {
            $html .= '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
            $html .= '<select name="steps['.$idx.'][action_type]" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;font-family:inherit;font-size:13px;max-width:160px;">';
            $selA = $step ? $step->action_type : 'sms';
            foreach (['sms'=>'ارسال پیامک','email'=>'ارسال ایمیل','messenger'=>'ارسال در پیام‌رسان','coupon'=>'ارسال کد تخفیف','add_segment'=>'افزودن به گروه','notify_admin'=>'اطلاع به مدیر'] as $k => $lbl) {
                $html .= '<option value="'.$k.'"'.($selA===$k?' selected':'').'>'.$lbl.'</option>';
            }
            $html .= '</select>';
            $html .= '<textarea name="steps['.$idx.'][action_template]" rows="2" placeholder="متن پیام... (می‌توانید از {نام}، {برند}، {امتیاز} استفاده کنید)" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;flex:1;min-width:200px;font-family:inherit;font-size:13px;">'.($step?$h($step->action_template):'').'</textarea>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function buildShowHtml(Workflow $workflow): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $fa = fn($v) => Num::fa($v);
        $u = fn($p) => url($p);

        $html = '';
        $html .= '<div class="card" style="margin-bottom:20px;">';
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:12px;">';
        $html .= '<div><h3 style="margin:0;">'.$h($workflow->name).'</h3>';
        if ($workflow->description) $html .= '<p class="muted" style="margin:4px 0 0;font-size:.85rem;">'.$h($workflow->description).'</p>';
        $html .= '</div>';
        $html .= '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/journeys/'.$workflow->id.'/toggle').'">'.($workflow->is_active?'⏸️ غیرفعال کردن':'▶️ فعال کردن').'</a>';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/journeys/'.$workflow->id.'/edit').'">✏️ ویرایش</a>';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/journeys').'">بازگشت</a>';
        $html .= '</div></div>';

        if ($workflow->trigger_event) {
            $events = Workflow::EVENTS;
        if (!isset($events['rfm.changed'])) {
            $events['rfm.changed'] = 'تغییر گروه رفتاری مشتری';
        }
            $html .= '<div style="font-size:.82rem;color:var(--mut);margin-bottom:12px;">شروع با: <b style="color:var(--acc);">'.($events[$workflow->trigger_event]??$workflow->trigger_event).'</b></div>';
        }
        $html .= '</div>';

        // نمایش مراحل به صورت زنجیره
        $steps = $workflow->steps()->orderBy('step_order')->get();
        if ($steps->isNotEmpty()) {
            $html .= '<div class="card" style="margin-bottom:14px;">';
            $html .= '<h3 style="margin:0 0 14px;">مسیر سفر</h3>';

            $colors = ['wait'=>'#f59e0b','condition'=>'#a78bfa','action'=>'#10b981'];
            $icons = ['wait'=>'⏱️','condition'=>'🔍','action'=>'⚡'];
            $labels = ['wait'=>'انتظار','condition'=>'بررسی','action'=>'اقدام'];
            $actionLabels = ['sms'=>'پیامک','email'=>'ایمیل','messenger'=>'پیام‌رسان','coupon'=>'کد تخفیف','add_segment'=>'افزودن به گروه','notify_admin'=>'اطلاع به مدیر'];

            foreach ($steps as $i => $st) {
                $color = $colors[$st->type] ?? '#10b981';
                $html .= '<div style="display:flex;gap:12px;align-items:stretch;margin-bottom:8px;">';
                $html .= '<div style="display:flex;flex-direction:column;align-items:center;">';
                $html .= '<div style="width:36px;height:36px;border-radius:50%;background:'.$color.';color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;">'.($i+1).'</div>';
                if ($i < $steps->count()-1) {
                    $html .= '<div style="width:2px;flex:1;background:'.$color.'33;margin:4px 0;"></div>';
                }
                $html .= '</div>';
                $html .= '<div style="flex:1;background:var(--panel2);border:1px solid '.$color.'33;border-radius:12px;padding:12px 14px;margin-bottom:4px;">';
                $html .= '<div style="font-weight:800;font-size:.85rem;color:'.$color.';margin-bottom:4px;">'.($icons[$st->type]??'').' '.($labels[$st->type]??'').(!empty($st->label)?': '.$h($st->label):'').'</div>';
                if ($st->type === 'wait') {
                    $html .= '<div class="muted" style="font-size:.8rem;">منتظر بمان به مدت <b style="color:var(--txt);">'.$fa($st->wait_hours ?? 24).' ساعت</b></div>';
                } elseif ($st->type === 'condition') {
                    $condLabels = ['bought_after_trigger'=>'بررسی خرید بعد از شروع','in_segment'=>'عضویت در گروه','rfm_group'=>'گروه رفتاری'];
                    $html .= '<div class="muted" style="font-size:.8rem;">'.($condLabels[$st->condition_field]??$st->condition_field).' = <b style="color:var(--txt);">'.$h($st->condition_value).'</b></div>';
                } elseif ($st->type === 'action') {
                    $html .= '<div class="muted" style="font-size:.8rem;">'.($actionLabels[$st->action_type]??$st->action_type);
                    if ($st->action_template) $html .= ': <b style="color:var(--txt);">'.$h(mb_substr($st->action_template,0,80)).'...</b>';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
            }
            $html .= '</div>';

            $html .= '<div class="card" style="margin-bottom:14px;background:rgba(56,189,248,.04);border-color:rgba(56,189,248,.12);">';
            $html .= '<div style="font-size:.82rem;color:var(--mut);">📝 <b style="color:var(--txt);">جای خالی‌های قابل استفاده در متن پیام:</b> {نام} نام مشتری · {برند} نام برند · {امتیاز} امتیاز · {کیف_پول} کیف پول · {لینک_دعوت} لینک معرفی · {گروه} گروه رفتاری · {سفارش} شماره سفارش · {مبلغ} مبلغ · {وضعیت} وضعیت</div>';
            $html .= '</div>';
        }

        $executions = $workflow->executions()->with('customer')->latest('id')->limit(12)->get();
        $html .= '<div class="card" style="margin-bottom:14px;">';
        $html .= '<h3 style="margin:0 0 10px;">اجرای واقعی این سفر</h3>';
        if ($executions->isEmpty()) {
            $html .= '<div class="empty">هنوز اجرای ثبت‌شده‌ای برای این سفر وجود ندارد.</div>';
        } else {
            $html .= '<div class="table-wrap"><table><thead><tr><th>مشتری</th><th>وضعیت</th><th>مرحله فعلی</th><th>نوبت بعدی</th><th>زمان شروع</th></tr></thead><tbody>';
            foreach ($executions as $ex) {
                $statusLabel = ['active'=>'در حال اجرا','completed'=>'به پایان رسیده','cancelled'=>'متوقف شده'][$ex->status] ?? $ex->status;
                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars((string) ($ex->customer?->full_name ?? '—'), ENT_QUOTES, 'UTF-8').'</td>';
                $html .= '<td>'.$statusLabel.'</td>';
                $html .= '<td>'.Num::fa($ex->current_step_order).'</td>';
                $html .= '<td>'.($ex->next_run_at ? Jalali::datetime($ex->next_run_at) : '—').'</td>';
                $html .= '<td>'.Jalali::datetime($ex->created_at).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</div>';

        return $html;
    }
}