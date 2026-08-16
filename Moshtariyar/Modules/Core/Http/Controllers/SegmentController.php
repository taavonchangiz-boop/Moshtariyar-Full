<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Segment;
use Modules\Core\Entities\SegmentCondition;
use Modules\Core\Services\SegmentEvaluator;
use Modules\Core\Support\Num;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Money;

class SegmentController extends Controller
{
    public function index()
    {
        $segments = Segment::with('conditions')->orderByDesc('id')->get();
        $html = $this->buildIndexHtml($segments);
        return view('app.segments.index', ['content' => $html, 'heading' => 'بخش‌بندی مشتریان', 'subtitle' => 'گروه‌بندی هوشمند مشتریان برای کمپین‌ها و اقدامات هدفمند']);
    }

    public function create()
    {
        $html = $this->buildFormHtml();
        return view('app.segments.index', ['content' => $html, 'heading' => 'ساخت گروه جدید', 'subtitle' => 'تعریف گروه جدید از مشتریان']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logic' => 'required|in:and,or',
            'description' => 'nullable|string',
            'conditions' => 'required|array|min:1',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|string',
        ]);

        $segment = Segment::create([
            'name' => $validated['name'],
            'type' => 'dynamic',
            'logic' => $validated['logic'],
            'description' => $validated['description'] ?? '',
            'is_active' => true,
        ]);

        foreach ($validated['conditions'] as $i => $cond) {
            $segment->conditions()->create([
                'field' => $cond['field'],
                'operator' => $cond['operator'],
                'value' => $cond['value'],
                'value2' => $cond['value2'] ?? null,
                'sort_order' => $i,
            ]);
        }

        try {
            $evaluator = new SegmentEvaluator();
        try { $evaluator->evaluate($segment); } catch (\Throwable $e) { \Log::warning('Segment update evaluate: ' . $e->getMessage()); }
        } catch (\Throwable $e) {
            // Prevent 500 on shared hosting - evaluation can be done later
            \Log::warning('Segment auto-evaluate failed: ' . $e->getMessage());
        }

        return redirect('/app/segments')->with('status', 'گروه با موفقیت ساخته شد.');
    }

    public function show(Segment $segment, Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $customers = $segment->customers()
            ->withCount('orders')
            ->orderByDesc('lifetime_value')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        $hasNext = $customers->count() > $perPage;
        $customers = $customers->take($perPage);

        $html = $this->buildShowHtml($segment, $customers, $page, $hasNext);
        return view('app.segments.index', ['content' => $html, 'heading' => $segment->name, 'subtitle' => $segment->description ?: 'اعضای این گروه']);
    }

    public function edit(Segment $segment)
    {
        $segment->load('conditions');
        $html = $this->buildFormHtml($segment);
        return view('app.segments.index', ['content' => $html, 'heading' => 'ویرایش گروه', 'subtitle' => $segment->name]);
    }

    public function update(Request $request, Segment $segment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logic' => 'required|in:and,or',
            'description' => 'nullable|string',
            'conditions' => 'required|array|min:1',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|string',
        ]);

        $segment->update([
            'name' => $validated['name'],
            'logic' => $validated['logic'],
            'description' => $validated['description'] ?? '',
        ]);

        $segment->conditions()->delete();
        foreach ($validated['conditions'] as $i => $cond) {
            $segment->conditions()->create([
                'field' => $cond['field'],
                'operator' => $cond['operator'],
                'value' => $cond['value'],
                'value2' => $cond['value2'] ?? null,
                'sort_order' => $i,
            ]);
        }

        $evaluator = new SegmentEvaluator();
        try { $evaluator->evaluate($segment); } catch (\Throwable $e) { \Log::warning('Segment update evaluate: ' . $e->getMessage()); }

        return redirect('/app/segments')->with('status', 'گروه با موفقیت بروزرسانی شد.');
    }

    public function destroy(Segment $segment)
    {
        $segment->delete();
        return redirect('/app/segments')->with('status', 'گروه حذف شد.');
    }

    public function evaluate(Segment $segment)
    {
        try {
            $evaluator = new SegmentEvaluator();
            $count = $evaluator->evaluate($segment);
            return redirect('/app/segments/'.$segment->id)->with('status', "ارزیابی انجام شد. {$count} مشتری در این گروه قرار دارند.");
        } catch (\Throwable $e) {
            \Log::warning('SEGMENT EVALUATE 500: ' . $e->getMessage());
            return redirect('/app/segments/'.$segment->id)->with('status', 'ارزیابی با مشکل مواجه شد. گروه ساخته شد. بعداً دوباره امتحان کنید.');
        }
    }

    public function evaluateAll()
    {
        $segments = Segment::where('is_active', true)->get();
        $evaluator = new SegmentEvaluator();
        $total = 0;
        foreach ($segments as $seg) { try { $total += $evaluator->evaluate($seg); } catch (\Throwable $e) { \Log::warning('evaluateAll: ' . $e->getMessage()); } }
        return redirect('/app/segments')->with('status', "همه گروه‌ها بروزرسانی شدند. مجموع اعضا: {$total}");
    }

    // ─────── HTML Builders ───────

    private function buildIndexHtml($segments): string
    {
        $h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $fa = fn($value) => Num::fa($value);
        $u = fn($path) => url($path);
        $unit = Money::unitLabel();

        $totalSegments = $segments->count();
        $activeSegments = $segments->where('is_active', true)->count();
        $totalMembers = (int) $segments->sum('members_count');
        $evaluatedSegments = $segments->filter(fn($segment) => ! empty($segment->evaluated_at))->count();

        $segmentGroups = collect([
            'ready' => $segments->filter(fn($segment) => $segment->is_active && (int) $segment->members_count > 0)->values(),
            'empty' => $segments->filter(fn($segment) => $segment->is_active && (int) $segment->members_count === 0)->values(),
            'not_evaluated' => $segments->filter(fn($segment) => $segment->is_active && empty($segment->evaluated_at))->values(),
            'inactive' => $segments->filter(fn($segment) => ! $segment->is_active)->values(),
        ]);

        $groupCards = collect([
            ['key' => 'ready', 'label' => 'آماده استفاده', 'color' => '#10b981', 'hint' => 'گروه‌هایی که عضو دارند و برای کمپین آماده‌اند'],
            ['key' => 'empty', 'label' => 'بدون عضو', 'color' => '#f59e0b', 'hint' => 'گروه‌هایی که شرط دارند اما عضوی ندارند'],
            ['key' => 'not_evaluated', 'label' => 'نیازمند بروزرسانی', 'color' => '#3b82f6', 'hint' => 'گروه‌هایی که باید دوباره ارزیابی شوند'],
            ['key' => 'inactive', 'label' => 'غیرفعال', 'color' => '#64748b', 'hint' => 'گروه‌هایی که فعلاً استفاده نمی‌شوند'],
        ]);

        $html = '';
        $html .= '<div class="segments-page">';
        $html .= '<section class="segments-hero">';
        $html .= '<div class="segments-hero-main">';
        $html .= '<div class="segments-eyebrow">مرکز گروه‌بندی هوشمند مشتریان</div>';
        $html .= '<h2>گروه‌های هدفمند برای کمپین، سفر مشتری و فروش دقیق‌تر</h2>';
        $html .= '<p>با بخش‌بندی پویا می‌توانید مشتریان را بر اساس خرید، ارزش، منبع جذب، شهر و رفتار خرید جدا کنید و برای هر گروه، کمپین یا اقدام اختصاصی بسازید. واحد پول فعال: <b>'.$h($unit).'</b></p>';
        $html .= '</div>';
        $html .= '<div class="segments-hero-metrics">';
        $html .= '<div class="segments-metric-card"><span>کل گروه‌ها</span><strong>'.$fa($totalSegments).'</strong></div>';
        $html .= '<div class="segments-metric-card"><span>گروه فعال</span><strong>'.$fa($activeSegments).'</strong></div>';
        $html .= '<div class="segments-metric-card"><span>مجموع اعضا</span><strong>'.$fa($totalMembers).'</strong></div>';
        $html .= '<div class="segments-metric-card"><span>ارزیابی‌شده</span><strong>'.$fa($evaluatedSegments).'</strong></div>';
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="segments-toolbar">';
        $html .= '<div class="segments-toolbar-info"><b>بورد بخش‌بندی مشتریان</b><span>گروه‌ها را مرتب، قابل اقدام و آماده استفاده در کمپین‌ها ببینید.</span></div>';
        $html .= '<div class="segments-toolbar-actions">';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/segments/evaluate-all').'" onclick="return confirm(\'همه گروه‌ها دوباره ارزیابی شوند؟\')">بروزرسانی همه</a>';
        $html .= '<a class="btn" href="'.$u('/app/segments/create').'">ساخت گروه جدید</a>';
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="segments-insight-strip">';
        $html .= '<div><span class="segments-dot segments-dot-green"></span><b>'.$fa($segmentGroups['ready']->count()).'</b><small>گروه آماده اجرای کمپین</small></div>';
        $html .= '<div><span class="segments-dot segments-dot-orange"></span><b>'.$fa($segmentGroups['empty']->count()).'</b><small>گروه بدون عضو</small></div>';
        $html .= '<div><span class="segments-dot segments-dot-blue"></span><b>'.$fa($segmentGroups['not_evaluated']->count()).'</b><small>گروه نیازمند بروزرسانی</small></div>';
        $html .= '</section>';

        if ($segments->isEmpty()) {
            $html .= '<section class="segments-empty-state">';
            $html .= '<div>🎯</div><h3>هنوز هیچ گروهی ساخته نشده است</h3>';
            $html .= '<p>با ساخت اولین گروه، مشتریان را هدفمند کنید و پایه کمپین‌های حرفه‌ای را بسازید.</p>';
            $html .= '<a class="btn" href="'.$u('/app/segments/create').'">ساخت اولین گروه</a>';
            $html .= '</section>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<section class="segments-board-shell"><div class="segments-board">';
        foreach ($groupCards as $group) {
            $items = $segmentGroups->get($group['key'], collect());
            $count = $items->count();
            $members = (int) $items->sum('members_count');
            $conditionCount = (int) $items->sum(fn($segment) => $segment->conditions ? $segment->conditions->count() : 0);
            $ratio = $totalSegments ? round(($count / max(1, $totalSegments)) * 100) : 0;

            $html .= '<section class="segments-column" style="--segment-status-color: '.$group['color'].'; --column-ratio: '.$ratio.'%;">';
            $html .= '<header class="segments-column-header"><div><span class="segments-stage-mark"></span><h3>'.$h($group['label']).'</h3></div><span class="segments-stage-count">'.$fa($count).'</span></header>';
            $html .= '<div class="segments-column-meta"><span>'.$h($group['hint']).'</span><span>اعضا: <b>'.$fa($members).'</b> · شرط‌ها: <b>'.$fa($conditionCount).'</b></span></div>';
            $html .= '<div class="segments-column-progress"><span></span></div>';
            $html .= '<div class="segments-drop-zone">';

            if ($items->isEmpty()) {
                $html .= '<div class="segments-empty-column">در این بخش گروهی وجود ندارد.</div>';
            } else {
                foreach ($items as $segment) {
                    $conditionTotal = $segment->conditions ? $segment->conditions->count() : 0;
                    $logicLabel = $segment->logic === 'and' ? 'همه شرط‌ها' : 'حداقل یکی از شرط‌ها';
                    $evalTime = $segment->evaluated_at ? Jalali::datetime($segment->evaluated_at) : 'ارزیابی نشده';
                    $activeLabel = $segment->is_active ? 'فعال' : 'غیرفعال';

                    $html .= '<details class="segment-card" style="--segment-status-color: '.$group['color'].';">';
                    $html .= '<summary class="segment-card-summary"><span class="segment-summary-arrow">⌄</span><span class="segment-summary-main"><b>'.$h($segment->name).'</b><small>'.$h($logicLabel).' · '.$h($activeLabel).'</small></span><span class="segment-summary-count">'.$fa($segment->members_count).'</span></summary>';
                    $html .= '<div class="segment-card-body">';
                    $html .= '<div class="segment-card-top"><h4>'.$h($segment->name).'</h4><span>'.($segment->description ? $h($segment->description) : 'توضیحی برای این گروه ثبت نشده است.').'</span></div>';
                    $html .= '<div class="segment-info-box"><span>آخرین ارزیابی</span><b>'.$evalTime.'</b></div>';
                    $html .= '<div class="segment-mini-grid"><div><span>اعضا</span><b>'.$fa($segment->members_count).'</b></div><div><span>شرط‌ها</span><b>'.$fa($conditionTotal).'</b></div></div>';
                    $html .= '<div class="segment-condition-list">';
                    if ($segment->conditions && $segment->conditions->count()) {
                        $fields = SegmentEvaluator::fieldLabels();
                        foreach ($segment->conditions as $condition) {
                            $fieldLabel = $fields[$condition->field] ?? $condition->field;
                            $operatorLabel = SegmentEvaluator::OPERATOR_LABELS[$condition->operator] ?? $condition->operator;
                            $value = $condition->field === 'rfm_group' ? (SegmentEvaluator::RFM_GROUPS[$condition->value] ?? $condition->value) : $condition->value;
                            $html .= '<span>'.$h($fieldLabel).' '.$h($operatorLabel).' <b>'.$h($value).'</b></span>';
                        }
                    } else {
                        $html .= '<span>شرطی ثبت نشده است.</span>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="segment-actions-row">';
                    $html .= '<a class="segment-small-action segment-small-action-primary" href="'.$u('/app/segments/'.$segment->id).'">اعضا</a>';
                    $html .= '<a class="segment-small-action" href="'.$u('/app/segments/'.$segment->id.'/evaluate').'">بروزرسانی</a>';
                    $html .= '<a class="segment-small-action" href="'.$u('/app/segments/'.$segment->id.'/edit').'">ویرایش</a>';
                    $html .= '<form method="post" action="'.$u('/app/segments/'.$segment->id).'" onsubmit="return confirm(\'حذف شود؟\')"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button class="segment-small-action segment-small-action-danger">حذف</button></form>';
                    $html .= '</div></div></details>';
                }
            }

            $html .= '</div></section>';
        }
        $html .= '</div></section></div>';

        return $html;
    }

    private function buildFormHtml(?Segment $segment = null): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $u = fn($p) => url($p);
        $isEdit = $segment !== null;
        $action = $isEdit ? $u('/app/segments/'.$segment->id) : $u('/app/segments');
        $method = $isEdit ? 'PUT' : 'POST';
        $unit = Money::unitLabel();

        $html = '';
        $html .= '<form method="post" action="'.$action.'" class="card" style="margin-bottom:20px;">';
        $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
        if ($isEdit) $html .= '<input type="hidden" name="_method" value="PUT">';

        // توضیح واحد پول
        $html .= '<div class="card" style="margin-bottom:16px;padding:14px 18px;background:rgba(56,189,248,.06);border-color:rgba(56,189,248,.15);">';
        $html .= '<span style="color:var(--txt);font-size:.88rem;">💰 واحد پول فعال: <b style="color:var(--acc);">'.$unit.'</b> — همه مبالغ بر اساس این واحد محاسبه و نمایش داده می‌شوند.</span>';
        $html .= '</div>';

        // نام
        $html .= '<div style="margin-bottom:14px;"><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">نام گروه <span style="color:var(--bad);">*</span></label>';
        $html .= '<input name="name" value="'.($isEdit ? $h($segment->name) : '').'" placeholder="مثلاً: تهرانی‌های پولدار در خطر ریزش" required style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;"></div>';

        // توضیحات
        $html .= '<div style="margin-bottom:14px;"><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">توضیحات</label>';
        $html .= '<textarea name="description" rows="2" placeholder="توضیح کوتاه درباره این گروه..." style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;">'.($isEdit ? $h($segment->description) : '').'</textarea></div>';

        // منطق
        $html .= '<div style="margin-bottom:14px;"><label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">روش ترکیب شرط‌ها</label>';
        $html .= '<select name="logic" style="background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px;">';
        $html .= '<option value="and"'.(($isEdit && $segment->logic==='and')?' selected':'').'>همه شرط‌ها با هم — مشتری باید تمام شرط‌ها را داشته باشد</option>';
        $html .= '<option value="or"'.(($isEdit && $segment->logic==='or')?' selected':'').'>حداقل یکی — مشتری باید یکی از شرط‌ها را داشته باشد</option>';
        $html .= '</select></div>';

        // شرط‌ها
        $html .= '<div style="margin-bottom:14px;">';
        $html .= '<label style="display:block;color:var(--mut);margin-bottom:6px;font-size:.85rem;">شرط‌ها <span style="color:var(--bad);">*</span></label>';
        $html .= '<div id="conditionsContainer" style="display:grid;gap:10px;">';

        $existingConditions = $isEdit ? $segment->conditions : collect([]);
        if ($existingConditions->isEmpty()) {
            $html .= $this->renderConditionRow(0);
        } else {
            foreach ($existingConditions as $i => $cond) {
                $html .= $this->renderConditionRow($i, $cond);
            }
        }

        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-ghost" onclick="addCondition()" style="margin-top:10px;font-size:.85rem;">➕ افزودن شرط</button>';
        $html .= '</div>';

        $html .= '<div style="display:flex;gap:10px;">';
        $html .= '<button type="submit" class="btn">'.($isEdit ? '💾 بروزرسانی گروه' : '✅ ساخت گروه').'</button>';
        $html .= '<a href="'.$u('/app/segments').'" class="btn btn-ghost">انصراف</a>';
        $html .= '</div>';
        $html .= '</form>';

        // JS
        $html .= '<script>
let condIdx = '.($existingConditions->count() ?: 1).';
function addCondition() {
    var container = document.getElementById("conditionsContainer");
    var row = document.createElement("div");
    row.innerHTML = '.json_encode($this->renderConditionRow(999)).'.replace(/999/g, condIdx);
    container.appendChild(row.firstElementChild);
    condIdx++;
}
function removeCondition(btn) {
    var rows = document.querySelectorAll("#conditionsContainer > div");
    if (rows.length > 1) btn.closest("#conditionsContainer > div").remove();
}
</script>';
        return $html;
    }

    private function renderConditionRow(int $idx, $cond = null): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $fields = SegmentEvaluator::fieldLabels(); // ← پویا از روی تنظیمات
        $operators = SegmentEvaluator::OPERATOR_LABELS;

        $selField = $cond ? $cond->field : 'total_spent';
        $selOp = $cond ? $cond->operator : '>=';
        $selVal = $cond ? $cond->value : '';
        $selVal2 = $cond ? ($cond->value2 ?? '') : '';

        $html = '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:var(--panel2);border:1px solid var(--line);border-radius:12px;padding:10px;">';

        $html .= '<select name="conditions['.$idx.'][field]" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;font-family:inherit;font-size:13px;max-width:180px;">';
        foreach ($fields as $k => $lbl) {
            $html .= '<option value="'.$k.'"'.($selField===$k?' selected':'').'>'.$h($lbl).'</option>';
        }
        $html .= '</select>';

        $html .= '<select name="conditions['.$idx.'][operator]" style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;font-family:inherit;font-size:13px;max-width:160px;">';
        foreach ($operators as $k => $lbl) {
            $html .= '<option value="'.$k.'"'.($selOp===$k?' selected':'').'>'.$h($lbl).'</option>';
        }
        $html .= '</select>';

        $html .= '<input name="conditions['.$idx.'][value]" value="'.$h($selVal).'" placeholder="مقدار..." style="background:var(--bg);border:1px solid var(--line);color:var(--txt);border-radius:8px;padding:8px;font-family:inherit;font-size:13px;max-width:150px;">';

        $html .= '<button type="button" class="btn btn-ghost" onclick="removeCondition(this)" style="padding:6px 10px;font-size:.8rem;color:var(--bad);">✕</button>';
        $html .= '</div>';

        return $html;
    }

    private function buildShowHtml(Segment $segment, $customers, int $page, bool $hasNext): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $fa = fn($v) => Num::fa($v);
        $u = fn($p) => url($p);
        $unit = Money::unitLabel();

        $html = '';

        $html .= '<div class="card" style="margin-bottom:20px;">';
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:12px;">';
        $html .= '<div><h3 style="margin:0;">'.$h($segment->name).'</h3>';
        if ($segment->description) $html .= '<p class="muted" style="margin:4px 0 0;font-size:.85rem;">'.$h($segment->description).'</p>';
        $html .= '</div>';
        $html .= '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/segments/'.$segment->id.'/evaluate').'">🔄 بروزرسانی اعضا</a>';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/segments/'.$segment->id.'/edit').'">✏️ ویرایش</a>';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/segments').'">بازگشت</a>';
        $html .= '</div></div>';

        // شرط‌ها
        $html .= '<div style="font-size:.82rem;color:var(--mut);">';
        $glue = $segment->logic === 'and' ? ' <b style="color:var(--txt);">و</b> ' : ' <b style="color:var(--txt);">یا</b> ';
        $condParts = [];
        $fields = SegmentEvaluator::fieldLabels();
        foreach ($segment->conditions as $cond) {
            $fieldLabel = $fields[$cond->field] ?? $cond->field;
            $opLabel = SegmentEvaluator::OPERATOR_LABELS[$cond->operator] ?? $cond->operator;
            $val = $cond->field === 'rfm_group' ? (SegmentEvaluator::RFM_GROUPS[$cond->value] ?? $cond->value) : $cond->value;
            $condParts[] = '<span style="background:var(--panel2);padding:4px 10px;border-radius:8px;display:inline-block;">'.$fieldLabel.' '.$opLabel.' <b style="color:var(--acc);">'.$h($val).'</b></span>';
        }
        $html .= implode($glue, $condParts);
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="card">';
        $html .= '<h3 style="margin:0 0 4px;">اعضای گروه ('.$fa($segment->members_count).' نفر)</h3>';
        $html .= '<p class="muted" style="font-size:.78rem;margin-bottom:4px;">واحد پول: <b style="color:var(--acc);">'.$unit.'</b></p>';

        if ($customers->isEmpty()) {
            $html .= '<div class="empty" style="padding:30px;">هنوز عضوی در این گروه قرار ندارد. روی «بروزرسانی اعضا» کلیک کنید.</div>';
        } else {
            $html .= '<div class="table-wrap" style="margin-top:12px;"><table>';
            $html .= '<thead><tr><th>نام</th><th>موبایل</th><th>تعداد خرید</th><th>مجموع خرید ('.$unit.')</th><th>آخرین خرید</th></tr></thead><tbody>';
            foreach ($customers as $c) {
                $html .= '<tr>';
                $html .= '<td><a href="'.$u('/app/customers/'.$c->id).'" style="color:var(--acc);">'.$h($c->full_name ?: '—').'</a></td>';
                $html .= '<td>'.$h($c->phone ?: '—').'</td>';
                $html .= '<td>'.$fa($c->orders_count).'</td>';
                $html .= '<td>'.Money::show($c->lifetime_value ?? 0).'</td>';
                $lastOrder = $c->orders()->latest('placed_at')->first();
                $html .= '<td>'.($lastOrder?Jalali::date($lastOrder->placed_at):'—').'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            $html .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid var(--line);">';
            $html .= '<div>'.($page>1?'<a class="btn btn-ghost" href="'.$u('/app/segments/'.$segment->id.'?page='.($page-1)).'">→ قبلی</a>':'').'</div>';
            $html .= '<div class="muted">صفحه '.$fa($page).'</div>';
            $html .= '<div>'.($hasNext?'<a class="btn btn-ghost" href="'.$u('/app/segments/'.$segment->id.'?page='.($page+1)).'">بعدی ←</a>':'').'</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}