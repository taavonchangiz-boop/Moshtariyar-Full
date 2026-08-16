<?php

namespace Modules\Core\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Ticket;
use Modules\Core\Entities\TicketCannedResponse;
use Modules\Core\Entities\TicketReply;
use Modules\Loyalty\Entities\CustomerPortalNotification;

class TicketController extends Controller
{
    public const DEPARTMENTS = ['پشتیبانی', 'فروش', 'مالی', 'فنی'];
    public const PRIORITIES = ['low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری'];

    public function index(Request $request)
    {
        $statuses = collect([
            ['key' => 'open', 'label' => 'تازه', 'color' => '#3b82f6'],
            ['key' => 'pending', 'label' => 'در حال بررسی', 'color' => '#f59e0b'],
            ['key' => 'answered', 'label' => 'پاسخ داده‌شده', 'color' => '#10b981'],
            ['key' => 'closed', 'label' => 'بسته‌شده', 'color' => '#64748b'],
        ]);

        $query = Ticket::query()
            ->with([
                'customer',
                'assignee',
                // بارگذاری همه پاسخ‌ها به‌ترتیب زمانی؛ اولین ردیف = متن اصلی تیکت (پیام مشتری)
                'replies' => function ($q) {
                    $q->oldest();
                },
            ])
            ->withCount('replies')
            ->latest('updated_at');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($term) {
                        $customerQuery->where('full_name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    })
                    ->orWhereHas('assignee', function ($userQuery) use ($term) {
                        $userQuery->where('name', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->boolean('unread')) {
            $query->where('staff_unread', true);
        }

        if ($request->boolean('mine')) {
            $query->where('assigned_to', optional($request->user())->id);
        }

        if ($request->boolean('overdue')) {
            $query->whereNull('first_response_at')
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', now())
                ->whereNotIn('status', ['closed']);
        }

        $summary = [
            'total' => (clone $query)->count(),
            'unread' => (clone $query)->where('staff_unread', true)->count(),
            'overdue' => (clone $query)->whereNull('first_response_at')->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('status', ['closed'])->count(),
            'urgent' => (clone $query)->where('priority', 'urgent')->whereNotIn('status', ['closed'])->count(),
        ];

        $ticketsFlat = $query->limit(400)->get();
        $tickets = $ticketsFlat->groupBy('status');

        $totals = [];
        $maxColumnCount = 1;
        foreach ($statuses as $status) {
            $columnTickets = $tickets->get($status['key'], collect());
            $count = $columnTickets->count();
            $maxColumnCount = max($maxColumnCount, $count);

            $totals[$status['key']] = [
                'count' => $count,
                'unread' => $columnTickets->where('staff_unread', true)->count(),
                'overdue' => $columnTickets->filter(function ($ticket) {
                    return ! $ticket->first_response_at
                        && $ticket->sla_due_at
                        && now()->gt($ticket->sla_due_at)
                        && $ticket->status !== 'closed';
                })->count(),
            ];
        }

        $staff = User::where('is_active', true)->get(['id', 'name']);

        return view('app.tickets', [
            'tickets' => $tickets,
            'ticketsFlat' => $ticketsFlat,
            'statuses' => $statuses,
            'totals' => $totals,
            'summary' => $summary,
            'departments' => self::DEPARTMENTS,
            'priorities' => self::PRIORITIES,
            'staff' => $staff,
            'maxColumnCount' => $maxColumnCount,
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('full_name')->limit(200)->get(['id', 'full_name']);
        return view('app.ticket_create', [
            'customers' => $customers,
            'departments' => self::DEPARTMENTS,
            'priorities' => self::PRIORITIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'subject' => 'required|string|max:191',
            'priority' => 'required|in:low,normal,high,urgent',
            'department' => 'nullable|string|max:60',
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $data['customer_id'] ?? null,
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'department' => $data['department'] ?? 'پشتیبانی',
            'status' => 'open',
            'staff_unread' => true,
            'customer_unread' => false,
            'last_reply_at' => now(),
            'sla_due_at' => now()->addHours($this->slaHours($data['priority'])),
        ]);

        $ticket->replies()->create(array_merge([
            'author' => 'customer',
            'author_name' => optional($ticket->customer)->full_name ?? 'مشتری',
            'message' => $data['message'],
        ], $this->storeFile($request)));

        return redirect(url('/app/tickets/' . $ticket->id))->with('status', 'تیکت ایجاد شد.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['replies', 'customer', 'assignee']);
        $ticket->update(['staff_unread' => false]);
        $staff = User::where('is_active', true)->get(['id', 'name']);
        $cannedResponses = TicketCannedResponse::where('is_active', true)
            ->where(function ($q) use ($ticket) {
                $q->whereNull('department')->orWhere('department', $ticket->department);
            })
            ->orderBy('department')->orderBy('title')->get();
        return view('app.ticket_show', [
            'ticket' => $ticket,
            'priorities' => self::PRIORITIES,
            'cannedResponses' => $cannedResponses,
            'staff' => $staff,
        ]);
    }

    public function showModal(Ticket $ticket)
    {
        $ticket->load(['replies', 'customer', 'assignee']);
        $ticket->update(['staff_unread' => false]);
        $staff = User::where('is_active', true)->get(['id', 'name']);
        $cannedResponses = TicketCannedResponse::where('is_active', true)
            ->where(function ($q) use ($ticket) {
                $q->whereNull('department')->orWhere('department', $ticket->department);
            })
            ->orderBy('department')->orderBy('title')->get();
        return view('app.ticket_modal', [
            'ticket' => $ticket,
            'priorities' => self::PRIORITIES,
            'cannedResponses' => $cannedResponses,
            'staff' => $staff,
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);
        $isInternal = $request->boolean('is_internal');
        $ticket->replies()->create(array_merge([
            'author' => 'staff',
            'is_internal' => $isInternal,
            'author_name' => optional($request->user())->name ?? 'کارشناس پشتیبانی',
            'message' => $data['message'],
        ], $this->storeFile($request)));
        $ticket->update(array_filter([
            'status' => $isInternal ? $ticket->status : 'answered',
            'customer_unread' => ! $isInternal,
            'staff_unread' => false,
            'last_reply_at' => now(),
            'first_response_at' => $ticket->first_response_at ?: ($isInternal ? null : now()),
            'sla_breached_at' => (! $isInternal && ! $ticket->first_response_at && $ticket->sla_due_at && now()->gt($ticket->sla_due_at)) ? now() : $ticket->sla_breached_at,
        ], fn ($v) => $v !== null));
        if (! $isInternal && $ticket->customer_id) {
            CustomerPortalNotification::create([
                'customer_id' => $ticket->customer_id,
                'title' => 'پاسخ جدید به تیکت',
                'body' => 'برای تیکت «' . $ticket->subject . '» پاسخ جدید ثبت شد.',
                'type' => 'ticket',
                'url' => route('club.tickets.show', $ticket),
            ]);
        }

        return back()->with('status', 'پاسخ ثبت شد.');
    }

    public function responses()
    {
        $responses = TicketCannedResponse::latest('id')->paginate(30);
        return view('app.ticket_responses', [
            'responses' => $responses,
            'departments' => self::DEPARTMENTS,
        ]);
    }

    public function storeResponse(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'department' => 'nullable|string|max:60',
            'body' => 'required|string|max:5000',
        ]);
        TicketCannedResponse::create($data + ['is_active' => true]);
        return back()->with('status', 'پاسخ آماده ساخته شد.');
    }

    public function toggleResponse(TicketCannedResponse $response)
    {
        $response->update(['is_active' => ! $response->is_active]);
        return back()->with('status', 'وضعیت پاسخ آماده تغییر کرد.');
    }

    public function deleteResponse(TicketCannedResponse $response)
    {
        $response->delete();
        return back()->with('status', 'پاسخ آماده حذف شد.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:open,pending,answered,closed',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'department' => 'nullable|string|max:60',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(array_filter([
            'status' => $data['status'],
            'priority' => $data['priority'] ?? $ticket->priority,
            'department' => $data['department'] ?? $ticket->department,
            'assigned_to' => $data['assigned_to'] ?? null,
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ], fn ($v) => $v !== null));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'وضعیت تیکت به‌روزرسانی شد.',
                'ticket_id' => $ticket->id,
                'status' => $ticket->status,
            ]);
        }

        return back()->with('status', 'وضعیت تیکت به‌روزرسانی شد.');
    }

    public function slaReport()
    {
        $open = Ticket::whereNotIn('status', ['closed'])->count();
        $overdue = Ticket::whereNull('first_response_at')->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('status', ['closed'])->count();
        $answered = Ticket::whereNotNull('first_response_at')->count();
        $avgFirstMinutes = Ticket::whereNotNull('first_response_at')->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_min')->value('avg_min');
        $byPriority = Ticket::selectRaw('priority, COUNT(*) total, SUM(CASE WHEN first_response_at IS NULL AND sla_due_at < NOW() AND status != "closed" THEN 1 ELSE 0 END) overdue')->groupBy('priority')->get();
        $lateTickets = Ticket::with(['customer', 'assignee'])->whereNull('first_response_at')->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('status', ['closed'])->latest('sla_due_at')->limit(30)->get();
        return view('app.ticket_sla', compact('open', 'overdue', 'answered', 'avgFirstMinutes', 'byPriority', 'lateTickets'));
    }

    private function slaHours(string $priority): int
    {
        return match ($priority) {
            'urgent' => 2,
            'high' => 6,
            'normal' => 24,
            'low' => 48,
            default => 24,
        };
    }

    public function downloadAttachment(TicketReply $reply)
    {
        abort_unless($reply->attachment && Storage::disk('public')->exists($reply->attachment), 404);
        return Storage::disk('public')->download($reply->attachment, $reply->attachment_name ?: basename($reply->attachment));
    }

    public function deleteAttachment(TicketReply $reply)
    {
        if ($reply->attachment && Storage::disk('public')->exists($reply->attachment)) {
            Storage::disk('public')->delete($reply->attachment);
        }
        $reply->update(['attachment' => null, 'attachment_name' => null, 'attachment_mime' => null, 'attachment_size' => null]);
        return back()->with('status', 'فایل پیوست حذف شد.');
    }

    private function storeFile(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }
        $file = $request->file('attachment');
        return [
            'attachment' => $file->store('tickets', 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }
}