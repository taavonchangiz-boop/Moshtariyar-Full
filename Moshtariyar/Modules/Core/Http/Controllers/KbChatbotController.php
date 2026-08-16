<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Entities\KbArticle;
use Modules\Core\Entities\KbChatMessage;
use Modules\Core\Entities\KbChatSession;
use Modules\Core\Entities\Setting;
use Modules\Core\Services\AssistantBrainService;
use Modules\Core\Services\ActionExecutionService;
use Modules\Core\Services\ImageOptimizerService;
use Modules\Core\Support\Num;
use Modules\Loyalty\Entities\LoyaltyMember;

class KbChatbotController extends Controller
{
    protected AssistantBrainService $brain;
    protected ActionExecutionService $executor;

    public function __construct(
        AssistantBrainService $brain,
        ActionExecutionService $executor
    ) {
        $this->brain = $brain;
        $this->executor = $executor;
    }

    public function widgetScript(Request $request)
    {
        $base = url('/');
        $api = url('/api/v1/assistant/ask');
        $brand = Setting::get(
            'assistant_widget_title',
            config('brand.name', 'سامانه هوشمند مدیریت')
        );
        $welcome = Setting::get(
            'assistant_widget_welcome',
            'سلام! من دستیار هوشمند و مشاور آنلاین شما هستم. چطور می‌توانم راهنمایی‌تان کنم؟'
        );
        $color = Setting::get('assistant_widget_color', '#0ea5e9');
        $logo = Setting::get('assistant_widget_logo', '');
        $label = Setting::get('assistant_widget_label', 'مشاور آنلاین');
        $labelEnabled = (string) Setting::get(
            'assistant_widget_label_enabled',
            '1'
        ) === '1';

        $cfg = json_encode(
            compact(
                'base',
                'api',
                'brand',
                'welcome',
                'color',
                'logo',
                'label',
                'labelEnabled'
            ),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $js = <<<JS
(function(){
 if(window.MoshtariYarWidgetLoaded)return; window.MoshtariYarWidgetLoaded=true;
 var cfg={$cfg}; var base=cfg.base, api=cfg.api, brand=cfg.brand;
 var css='position:fixed;z-index:2147483647;font-family:Tahoma,Arial,sans-serif;direction:rtl';
 var wrap=document.createElement('div'); wrap.style.cssText=css+';left:22px;bottom:22px;display:flex;align-items:center;gap:8px';
 var label=document.createElement('div'); label.textContent=cfg.label||''; label.style.cssText='display:'+(cfg.labelEnabled?'block':'none')+';background:#0f172a;color:#fff;border:1px solid #334155;border-radius:14px;padding:8px 11px;box-shadow:0 10px 25px rgba(0,0,0,.22);font-size:13px;font-weight:bold';
 var btn=document.createElement('button'); btn.innerHTML=cfg.logo?'<img src="'+cfg.logo+'" style="width:28px;height:28px;object-fit:contain">':'🤖'; btn.title=brand; btn.style.cssText='width:60px;height:60px;border-radius:22px;border:0;background:linear-gradient(135deg,'+cfg.color+',#10b981);box-shadow:0 0 0 0 rgba(14,165,233,.55),0 12px 35px rgba(0,0,0,.32);cursor:pointer;font-size:26px;animation:myPulse 2.2s infinite';
 var style=document.createElement('style'); style.textContent='@keyframes myPulse{0%{box-shadow:0 0 0 0 rgba(14,165,233,.55),0 12px 35px rgba(0,0,0,.32)}70%{box-shadow:0 0 0 16px rgba(14,165,233,0),0 12px 35px rgba(0,0,0,.32)}100%{box-shadow:0 0 0 0 rgba(14,165,233,.55),0 12px 35px rgba(0,0,0,.32)}}'; document.head.appendChild(style);
 wrap.appendChild(label); wrap.appendChild(btn);
 var box=document.createElement('div'); box.style.cssText=css+';left:22px;bottom:92px;width:min(370px,calc(100vw - 28px));height:520px;display:none;background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:22px;box-shadow:0 24px 70px rgba(0,0,0,.38);overflow:hidden';
 box.innerHTML='<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#111827;border-bottom:1px solid #334155"><b>'+brand+'</b><button data-close style="background:transparent;border:0;color:#fff;font-size:20px;cursor:pointer">×</button></div><div data-msgs style="height:390px;overflow:auto;padding:12px;display:flex;flex-direction:column;gap:10px"><div style="background:#1e293b;border:1px solid #334155;border-radius:14px;padding:10px">'+cfg.welcome+'</div></div><form data-form style="display:flex;gap:6px;padding:10px;border-top:1px solid #334155"><input data-input style="flex:1;background:#111827;border:1px solid #334155;color:#fff;border-radius:12px;padding:10px" placeholder="سؤال شما..."><button style="background:'+cfg.color+';color:#001018;border:0;border-radius:12px;padding:10px 14px;font-weight:bold">ارسال</button></form>';
 document.body.appendChild(wrap); document.body.appendChild(box);
 var msgs=box.querySelector('[data-msgs]'), form=box.querySelector('[data-form]'), input=box.querySelector('[data-input]');
 function esc(s){return String(s||'').replace(/[<>&]/g,function(x){return {'<':'&lt;','>':'&gt;','&':'&amp;'}[x]})}
 function add(html,me){var d=document.createElement('div');d.style.cssText='border-radius:14px;padding:10px;white-space:pre-wrap;max-width:86%;'+(me?'align-self:flex-start;background:#0ea5e9;color:#001018':'align-self:flex-end;background:#1e293b;border:1px solid #334155');d.innerHTML=html;msgs.appendChild(d);msgs.scrollTop=msgs.scrollHeight;return d;}
 btn.onclick=function(){box.style.display=box.style.display==='none'?'block':'none'}; box.querySelector('[data-close]').onclick=function(){box.style.display='none'};
 var visitor=localStorage.getItem('my_widget_visitor'); if(!visitor){visitor=(crypto.randomUUID?crypto.randomUUID():String(Date.now())+Math.random());localStorage.setItem('my_widget_visitor',visitor)}
 form.onsubmit=function(e){e.preventDefault();var q=input.value.trim();if(!q)return;input.value='';add(esc(q),true);var l=add('در حال بررسی...',false);fetch(api,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:q,visitor_key:visitor,url:location.href,title:document.title})}).then(r=>r.json()).then(d=>{var html=esc(d.answer||'');if(d.articles&&d.articles.length){html+='<br><br><b>مقاله‌های آموزشی مرتبط:</b>';d.articles.forEach(function(a){html+='<br><a style="color:#7dd3fc" target="_blank" href="'+a.url+'">'+esc(a.title)+'</a>'})}if(d.needs_ticket){html+='<br><br><small>در صورتی که پاسخ کامل نبود، لطفاً درخواست پشتیبانی جدید ثبت کنید.</small>'}l.innerHTML=html}).catch(()=>{l.innerHTML='خطا در ارتباط با مشاور آنلاین.'});}
})();
JS;

        return response(
            $js,
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
            ]
        );
    }

    public function page()
    {
        return view('loyalty::portal.assistant');
    }

    public function adminPage()
    {
        return view('app.admin_assistant');
    }

    public function settingsPage()
    {
        $data = [
            'provider' => DB::table('settings')
                ->where('key', 'ai_provider')
                ->value('value') ?? 'openai',

            'model' => DB::table('settings')
                ->where('key', 'ai_model')
                ->value('value') ?? 'gpt-4-turbo',

            'api_key' => DB::table('settings')
                ->where('key', 'ai_api_key')
                ->value('value') ?? '',

            'total_tokens' => DB::table('settings')
                ->where('key', 'ai_total_tokens')
                ->value('value') ?? 0,
        ];

        return view('app.assistant_settings', compact('data'));
    }

    public function getModels(Request $request)
    {
        $provider = $request->query('provider');

        $models = match ($provider) {
            'openai' => ['gpt-4-turbo', 'gpt-4o', 'gpt-3.5-turbo'],
            'gemini' => ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro'],
            'claude' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
            'deepseek' => ['deepseek-chat', 'deepseek-coder'],
            'grok' => ['grok-1', 'grok-beta'],
            'qwen' => ['qwen-max', 'qwen-plus', 'qwen-turbo'],
            'mistral' => ['mistral-large-latest', 'mistral-medium', 'mistral-small'],
            default => [],
        };

        return response()->json([
            'models' => $models,
        ]);
    }

    public function testConnection(Request $request)
    {
        $provider = $request->input('provider');
        $apiKey = $request->input('api_key');

        if (! $apiKey) {
            return response()->json([
                'ok' => false,
                'message' => 'کلید دسترسی وارد نشده است.',
            ], 400);
        }

        try {
            $aiService = app(\Modules\Core\Services\AIService::class);
            $result = $aiService->testConnection($provider, $apiKey);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'خطا در اتصال: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function saveSettings(Request $request)
    {
        try {
            $provider = $request->input('provider', 'openai');
            $model = $request->input('model');
            $apiKey = $request->input('api_key', '');

            if (empty($model)) {
                $model = match ($provider) {
                    'openai' => 'gpt-4-turbo',
                    'gemini' => 'gemini-1.5-pro',
                    'claude' => 'claude-3-sonnet',
                    'deepseek' => 'deepseek-chat',
                    default => 'gpt-4-turbo',
                };
            }

            DB::table('settings')->updateOrInsert(
                ['key' => 'ai_provider'],
                ['value' => $provider]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'ai_model'],
                ['value' => $model]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'ai_api_key'],
                ['value' => $apiKey]
            );

            Cache::flush();

            $aiService = app(\Modules\Core\Services\AIService::class);
            $status = $aiService->testConnection($provider, $apiKey);

            return back()->with([
                'success' => 'تنظیمات با موفقیت ذخیره شد.',
                'conn_status' => $status,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'خطای دیتابیس: ' . $e->getMessage(),
            ]);
        }
    }

    public function widgetSettingsPage()
    {
        $data = [
            'title' => Setting::get(
                'assistant_widget_title',
                'مشاور آنلاین'
            ),
            'welcome' => Setting::get(
                'assistant_widget_welcome',
                'سلام! چطور می‌توانم کمکتان کنم؟'
            ),
            'color' => Setting::get(
                'assistant_widget_color',
                '#0ea5e9'
            ),
            'logo' => Setting::get('assistant_widget_logo', ''),
            'label' => Setting::get(
                'assistant_widget_label',
                'مشاور آنلاین'
            ),
            'label_enabled' => Setting::get(
                'assistant_widget_label_enabled',
                '1'
            ),
        ];

        return view('app.assistant_widget_settings', compact('data'));
    }

    public function saveWidgetSettings(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'welcome' => 'required|string',
            'color' => 'required|string',
            'logo_url' => 'nullable|string',
            'logo_file' => 'nullable|image|max:1024',
            'label' => 'required|string',
            'label_enabled' => 'required|string',
        ]);

        if ($request->hasFile('logo_file')) {
            $path = app(ImageOptimizerService::class)
                ->storeAsWebp(
                    $request->file('logo_file'),
                    'img/assistant',
                    82
                );

            Setting::updateOrCreate(
                ['key' => 'assistant_widget_logo'],
                ['value' => asset($path)]
            );
        } elseif ($request->filled('logo_url')) {
            Setting::updateOrCreate(
                ['key' => 'assistant_widget_logo'],
                ['value' => $request->logo_url]
            );
        }

        Setting::updateOrCreate(
            ['key' => 'assistant_widget_title'],
            ['value' => $data['title']]
        );

        Setting::updateOrCreate(
            ['key' => 'assistant_widget_welcome'],
            ['value' => $data['welcome']]
        );

        Setting::updateOrCreate(
            ['key' => 'assistant_widget_color'],
            ['value' => $data['color']]
        );

        Setting::updateOrCreate(
            ['key' => 'assistant_widget_label'],
            ['value' => $data['label']]
        );

        Setting::updateOrCreate(
            ['key' => 'assistant_widget_label_enabled'],
            ['value' => $data['label_enabled']]
        );

        return back()->with(
            'success',
            'تنظیمات ظاهر دستیار با موفقیت ذخیره شد.'
        );
    }

    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        $message = trim($data['message']);
        $session = $this->session($request);
        $member = $this->currentMember();

        KbChatMessage::create(array_merge([
            'session_id' => $session->id,
            'sender' => 'user',
            'message' => $message,
        ], $this->storeChatFile($request)));

        $result = $this->brain->process(
            $message,
            $member?->customer_id
        );

        KbChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'bot',
            'message' => $result['answer'],
            'matched_articles' => $result['articles'] ?? [],
            'needs_ticket' => $result['needs_ticket'] ?? false,
        ]);

        $session->update([
            'last_message_at' => now(),
            'customer_id' => $member?->customer_id ?: $session->customer_id,
        ]);

        return response()->json(array_merge([
            'ok' => true,
            'answer' => $result['answer'],
            'needs_ticket' => $result['needs_ticket'] ?? false,
            'articles' => $result['articles'] ?? [],
        ], $result));
    }

    public function adminAsk(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt',
            'context_type' => 'nullable|string|max:40',
            'context_id' => 'nullable|integer',
            'context_title' => 'nullable|string|max:191',
            'context_url' => 'nullable|string|max:500',
        ]);

        $message = trim($data['message']);

        $assistantContext = [
            'type' => $data['context_type'] ?? null,
            'id' => $data['context_id'] ?? null,
            'title' => $data['context_title'] ?? null,
            'url' => $data['context_url'] ?? null,
        ];

        $contextCustomerId = ($assistantContext['type'] ?? null) === 'customer'
            ? (int) ($assistantContext['id'] ?? 0)
            : null;

        $session = KbChatSession::firstOrCreate(
            [
                'visitor_key' => 'admin:' . optional($request->user())->id,
                'channel' => 'admin',
            ],
            [
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_message_at' => now(),
            ]
        );

        KbChatMessage::create(array_merge([
            'session_id' => $session->id,
            'sender' => 'user',
            'message' => $message,
        ], $this->storeChatFile($request)));

        $pending = $request->session()->get('admin_pending_action');

        if ($pending) {
            $normalizedMessage = mb_strtolower($message);

            $isYes = preg_match(
                '/(بله|تایید|تأیید|آره|انجام بده|بساز|ثبت کن|اوکی|ok|yes)/u',
                $normalizedMessage
            );

            $isNo = preg_match(
                '/(خیر|لغو|کنسل|نمیخوام|نه|توقف|پاک کن|cancel|no)/u',
                $normalizedMessage
            );

            if ($isYes && ! $isNo) {
                $request->session()->forget('admin_pending_action');

                $result = match ($pending['type']) {
                    'customer' => [
                        'answer' => $this->executor->executeCreateCustomer(
                            $pending['params']
                        ),
                        'url' => url('/app/customers'),
                    ],

                    'campaign' => [
                        'answer' => $this->executor->executeCreateCampaign(
                            $pending['params']
                        ),
                        'url' => url('/app/campaigns'),
                    ],

                    'coupon' => [
                        'answer' => $this->executor->executeCreateCoupon(
                            $pending['params']
                        ),
                        'url' => route('club.coupons'),
                    ],

                    'mission' => [
                        'answer' => $this->executor->executeCreateMission(
                            $pending['params']
                        ),
                        'url' => url('/app/loyalty/settings'),
                    ],

                    'workflow' => [
                        'answer' => $this->executor->executeToggleWorkflow(
                            $pending['params']['id'],
                            $pending['params']['status']
                        ),
                        'url' => url('/app/workflows'),
                    ],

                    'points' => [
                        'answer' => $this->executor->executeAdjustLoyaltyPoints(
                            $pending['params']
                        ),
                        'url' => null,
                    ],

                    'message' => [
                        'answer' => $this->executor->executeSendMessage(
                            $pending['params']
                        ),
                        'url' => null,
                    ],

                    'tag' => [
                        'answer' => $this->executor->executeUpdateCustomerTag(
                            $pending['params']
                        ),
                        'url' => null,
                    ],

                    'reminder' => [
                        'answer' => $this->executor->executeScheduleReminder(
                            $pending['params']
                        ),
                        'url' => null,
                    ],

                    'proforma' => [
                        'answer' => $this->executor->executeCreateProforma(
                            $pending['params']
                        ),
                        'url' => url('/app/tax-invoices?kind=proforma'),
                    ],

                    'ticket_create' => [
                        'answer' => $this->executor->executeCreateTicket(
                            $pending['params']
                        ),
                        'url' => url('/app/tickets'),
                    ],

                    'ticket_reply' => [
                        'answer' => $this->executor->executeReplyTicket(
                            $pending['params']
                        ),
                        'url' => url('/app/tickets/' . ($pending['params']['ticket_id'] ?? '')),
                    ],

                    'ticket_status' => [
                        'answer' => $this->executor->executeUpdateTicketStatus(
                            $pending['params']
                        ),
                        'url' => url('/app/tickets/' . ($pending['params']['ticket_id'] ?? '')),
                    ],

                    default => [
                        'answer' => 'خطا در اجرای دستور.',
                        'url' => null,
                    ],
                };

                KbChatMessage::create([
                    'session_id' => $session->id,
                    'sender' => 'bot',
                    'message' => $result['answer'],
                ]);

                return response()->json(array_merge([
                    'ok' => true,
                ], $result));
            }

            if ($isNo) {
                $request->session()->forget('admin_pending_action');

                $answer = '🛑 دستور لغو شد و هیچ تغییری اعمال نگردید.';

                KbChatMessage::create([
                    'session_id' => $session->id,
                    'sender' => 'bot',
                    'message' => $answer,
                ]);

                return response()->json([
                    'ok' => true,
                    'answer' => $answer,
                ]);
            }
        }

        $flow = $request->session()->get('admin_assistant_flow');

        if ($flow) {
            $flowResult = $this->continueAdminAssistantFlow(
                $request,
                $message,
                $flow
            );

            KbChatMessage::create([
                'session_id' => $session->id,
                'sender' => 'bot',
                'message' => $flowResult['answer'],
            ]);

            $session->update([
                'last_message_at' => now(),
            ]);

            return response()->json(array_merge([
                'ok' => true,
            ], $flowResult));
        }

        if ($this->shouldStartCampaignFlow($message)) {
            $flowResult = $this->startCampaignFlow(
                $request,
                $message
            );

            KbChatMessage::create([
                'session_id' => $session->id,
                'sender' => 'bot',
                'message' => $flowResult['answer'],
            ]);

            $session->update([
                'last_message_at' => now(),
            ]);

            return response()->json(array_merge([
                'ok' => true,
            ], $flowResult));
        }

        $result = $this->brain->process(
            $message,
            $contextCustomerId ?: null,
            [
                'page' => $assistantContext,
            ]
        );

        if (
            isset($result['intent'])
            && str_contains($result['intent'], 'pending_')
        ) {
            $type = str_replace('pending_', '', $result['intent']);

            $request->session()->put(
                'admin_pending_action',
                [
                    'type' => $type,
                    'params' => $result['params'] ?? [],
                ]
            );
        }

        KbChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'bot',
            'message' => $result['answer'],
        ]);

        $session->update([
            'last_message_at' => now(),
        ]);

        return response()->json(array_merge([
            'ok' => true,
        ], $result));
    }

    private function shouldStartCampaignFlow(string $message): bool
    {
        $text = $this->normalizePersianText($message);

        return str_contains($text, 'کمپین')
            && $this->containsAnyText(
                $text,
                [
                    'بساز',
                    'ایجاد کن',
                    'ثبت کن',
                    'طراحی کن',
                    'راه بنداز',
                    'راه‌انداز',
                ]
            );
    }

    private function startCampaignFlow(
        Request $request,
        string $message
    ): array {
        $data = $this->extractCampaignData($message, []);

        $flow = [
            'type' => 'campaign',
            'data' => $data,
            'created_at' => now()->toDateTimeString(),
        ];

        $request->session()->put(
            'admin_assistant_flow',
            $flow
        );

        return [
            'answer' => $this->campaignMissingQuestion($data, true),
            'intent' => 'campaign_flow_started',
            'suggestions' => [
                'هدف بازگرداندن مشتریان غیرفعال است، مخاطب مشتریان در خطر ریزش، کانال پیامک، پاداش ۱۵ درصد تخفیف، متن را خودت پیشنهاد بده',
                'لغو',
            ],
        ];
    }

    private function continueAdminAssistantFlow(
        Request $request,
        string $message,
        array $flow
    ): array {
        $text = $this->normalizePersianText($message);

        if ($this->containsAnyText(
            $text,
            ['لغو', 'انصراف', 'کنسل', 'نمیخوام', 'نمی‌خوام']
        )) {
            $request->session()->forget('admin_assistant_flow');

            return [
                'answer' => '🛑 ساخت مرحله‌ای لغو شد و هیچ تغییری اعمال نشد.',
                'intent' => 'flow_cancelled',
            ];
        }

        if (($flow['type'] ?? '') === 'campaign') {
            $data = $this->extractCampaignData(
                $message,
                $flow['data'] ?? []
            );

            $missing = $this->campaignMissingFields($data);

            if (count($missing)) {
                $flow['data'] = $data;

                $request->session()->put(
                    'admin_assistant_flow',
                    $flow
                );

                return [
                    'answer' => $this->campaignMissingQuestion($data, false),
                    'intent' => 'campaign_flow_collecting',
                    'suggestions' => [
                        'متن را خودت پیشنهاد بده',
                        'پاداش ۱۰ درصد تخفیف',
                        'کانال واتساپ',
                        'لغو',
                    ],
                ];
            }

            $params = $this->campaignParamsFromFlow($data);

            $request->session()->forget('admin_assistant_flow');

            $request->session()->put(
                'admin_pending_action',
                [
                    'type' => 'campaign',
                    'params' => $params,
                ]
            );

            return [
                'answer' => $this->campaignPreview($params),
                'intent' => 'pending_campaign',
                'params' => $params,
                'suggestions' => ['تأیید', 'لغو'],
            ];
        }

        $request->session()->forget('admin_assistant_flow');

        return [
            'answer' => 'جریان گفت‌وگو نامشخص بود و لغو شد. لطفاً دوباره دستور را بفرمایید.',
            'intent' => 'flow_cancelled',
        ];
    }

    private function extractCampaignData(
        string $message,
        array $data
    ): array {
        $text = $this->normalizePersianText($message);

        if (! isset($data['goal'])) {
            if ($this->containsAnyText(
                $text,
                ['بازگرداندن', 'بازگشت', 'غیرفعال', 'غیر فعال', 'ریزش', 'خفته']
            )) {
                $data['goal'] = 'بازگرداندن مشتریان غیرفعال';
                $data['rfm_group'] = 'at_risk';
                $data['segment'] = $data['segment'] ?? 'has_orders';
            } elseif ($this->containsAnyText(
                $text,
                ['خرید مجدد', 'تکرار خرید', 'وفادار']
            )) {
                $data['goal'] = 'افزایش خرید مجدد';
                $data['rfm_group'] = 'loyal';
                $data['segment'] = $data['segment'] ?? 'has_orders';
            } elseif ($this->containsAnyText(
                $text,
                ['معرفی دوستان', 'دعوت', 'رفرال', 'معرف']
            )) {
                $data['goal'] = 'افزایش معرفی دوستان';
                $data['rfm_group'] = 'all';
                $data['segment'] = $data['segment'] ?? 'all';
            } elseif ($this->containsAnyText(
                $text,
                ['فروش محصول', 'محصول خاص', 'فروش ویژه']
            )) {
                $data['goal'] = 'افزایش فروش محصول خاص';
                $data['rfm_group'] = 'all';
            }
        }

        if (! isset($data['segment'])) {
            if ($this->containsAnyText($text, ['همه مشتریان', 'همه'])) {
                $data['segment'] = 'all';
            } elseif ($this->containsAnyText($text, ['فروشگاه', 'ووکامرس'])) {
                $data['segment'] = 'woocommerce';
            } elseif ($this->containsAnyText(
                $text,
                ['سابقه خرید', 'خریدار', 'خرید کرده']
            )) {
                $data['segment'] = 'has_orders';
            } elseif ($this->containsAnyText(
                $text,
                ['باشگاه', 'اعضای باشگاه']
            )) {
                $data['segment'] = 'all';
            }
        }

        if (! isset($data['channel'])) {
            if ($this->containsAnyText($text, ['پیامک', 'sms'])) {
                $data['channel'] = 'sms';
            } elseif ($this->containsAnyText($text, ['واتساپ', 'whatsapp'])) {
                $data['channel'] = 'whatsapp';
            } elseif ($this->containsAnyText($text, ['ایمیل', 'email'])) {
                $data['channel'] = 'email';
            } elseif ($this->containsAnyText($text, ['تلگرام'])) {
                $data['channel'] = 'telegram';
            } elseif ($this->containsAnyText($text, ['بله'])) {
                $data['channel'] = 'bale';
            } elseif ($this->containsAnyText($text, ['ایتا'])) {
                $data['channel'] = 'eitaa';
            }
        }

        if (! isset($data['reward'])) {
            if (preg_match(
                '/(\d+)\s*درصد/u',
                $this->normalizeDigits($text),
                $match
            )) {
                $data['reward'] = $match[1] . ' درصد تخفیف';
                $data['reward_type'] = 'discount_percent';
                $data['reward_value'] = (int) $match[1];
            } elseif ($this->containsAnyText(
                $text,
                ['کد تخفیف', 'تخفیف']
            )) {
                $data['reward'] = 'کد تخفیف';
                $data['reward_type'] = 'discount_percent';
                $data['reward_value'] = 10;
            } elseif ($this->containsAnyText($text, ['امتیاز'])) {
                $data['reward'] = 'امتیاز باشگاه';
                $data['reward_type'] = 'points';
                $data['reward_value'] = 50;
            } elseif ($this->containsAnyText(
                $text,
                ['بدون پاداش']
            )) {
                $data['reward'] = 'بدون پاداش';
                $data['reward_type'] = 'none';
                $data['reward_value'] = 0;
            }
        }

        if (! isset($data['message'])) {
            if ($this->containsAnyText(
                $text,
                [
                    'خودت پیشنهاد بده',
                    'خودت بنویس',
                    'تو پیشنهاد بده',
                    'متن را خودت',
                    'متن رو خودت',
                ]
            )) {
                $data['message'] = $this->suggestCampaignMessage($data);
                $data['message_source'] = 'assistant';
            } elseif (preg_match(
                '/[«"](.+?)[»"]/u',
                $message,
                $match
            )) {
                $data['message'] = trim($match[1]);
                $data['message_source'] = 'manager';
            }
        }

        return $data;
    }

    private function campaignMissingFields(array $data): array
    {
        $missing = [];

        foreach (['goal', 'segment', 'channel', 'reward', 'message'] as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function campaignMissingQuestion(
        array $data,
        bool $first
    ): string {
        $missing = $this->campaignMissingFields($data);

        $labels = [
            'goal' => 'هدف کمپین چیست؟ مثل بازگرداندن مشتریان غیرفعال، افزایش خرید مجدد یا معرفی دوستان.',
            'segment' => 'مخاطب کمپین چه کسانی هستند؟ مثل همه مشتریان، مشتریان دارای خرید، اعضای باشگاه یا مشتریان فروشگاه.',
            'channel' => 'کانال ارسال چیست؟ مثل پیامک، واتساپ، ایمیل، تلگرام، بله یا ایتا.',
            'reward' => 'پاداش یا پیشنهاد کمپین چیست؟ مثل ۱۵ درصد تخفیف، امتیاز باشگاه، هدیه یا بدون پاداش.',
            'message' => 'متن پیام را خودتان می‌دهید یا من پیشنهاد بدهم؟ اگر متن دارید داخل گیومه بنویسید.',
        ];

        $answer = $first
            ? "حتماً. برای ساخت کمپین، من مرحله‌به‌مرحله اطلاعات لازم را جمع می‌کنم و قبل از ثبت، پیش‌نمایش می‌دهم.\n\n"
            : "بخشی از اطلاعات کمپین را گرفتم. هنوز این موارد لازم است:\n\n";

        foreach ($missing as $index => $field) {
            $answer .= Num::fa($index + 1) . '. ' . $labels[$field] . "\n";
        }

        $answer .= "\nمی‌توانید همه موارد را در یک پیام بفرستید. نمونه:\n"
            . "هدف بازگرداندن مشتریان غیرفعال است، مخاطب مشتریان دارای خرید، کانال پیامک، پاداش ۱۵ درصد تخفیف، متن را خودت پیشنهاد بده.";

        return $answer;
    }

    private function campaignParamsFromFlow(array $data): array
    {
        $goal = $data['goal'] ?? 'کمپین پیشنهادی دستیار';

        return [
            'name' => $goal,
            'referral_slug' => Str::slug(Str::random(8)),
            'channel' => $data['channel'] ?? 'sms',
            'segment' => $data['segment'] ?? 'all',
            'rfm_group' => $data['rfm_group'] ?? 'all',
            'message' => $data['message'] ?? $this->suggestCampaignMessage($data),
            'reward_rules' => [[
                'type' => $data['reward_type'] ?? 'none',
                'value' => $data['reward_value'] ?? 0,
                'label' => $data['reward'] ?? 'بدون پاداش',
            ]],
            'meta' => [
                'assistant_goal' => $goal,
                'assistant_reward' => $data['reward'] ?? null,
                'created_from_flow' => true,
            ],
        ];
    }

    private function campaignPreview(array $params): string
    {
        $channelLabels = [
            'sms' => 'پیامک',
            'whatsapp' => 'واتساپ',
            'email' => 'ایمیل',
            'telegram' => 'تلگرام',
            'bale' => 'بله',
            'eitaa' => 'ایتا',
        ];

        $segmentLabels = [
            'all' => 'همه مشتریان',
            'woocommerce' => 'مشتریان فروشگاه',
            'has_orders' => 'مشتریان دارای سابقه خرید',
        ];

        $reward = $params['reward_rules'][0]['label'] ?? 'بدون پاداش';

        return "پیش‌نمایش کمپین آماده شد:\n\n"
            . "• نام کمپین: " . ($params['name'] ?? 'کمپین پیشنهادی') . "\n"
            . "• مخاطب: " . (
                $segmentLabels[$params['segment'] ?? 'all']
                ?? ($params['segment'] ?? 'همه')
            ) . "\n"
            . "• کانال: " . (
                $channelLabels[$params['channel'] ?? 'sms']
                ?? ($params['channel'] ?? 'پیامک')
            ) . "\n"
            . "• پاداش: {$reward}\n"
            . "• متن پیام:\n" . ($params['message'] ?? '') . "\n\n"
            . "اگر تأیید می‌کنید، بنویسید: تأیید. اگر نمی‌خواهید اجرا شود، بنویسید: لغو.";
    }

    private function suggestCampaignMessage(array $data): string
    {
        $reward = $data['reward'] ?? 'پیشنهاد ویژه';
        $goal = $data['goal'] ?? '';

        if (
            str_contains($goal, 'غیرفعال')
            || str_contains($goal, 'بازگرداندن')
        ) {
            return "دلمان برای شما تنگ شده! با {$reward} خرید بعدی خود را جذاب‌تر شروع کنید.";
        }

        if (str_contains($goal, 'معرفی')) {
            return "دوستانتان را دعوت کنید و با هر معرفی موفق، از {$reward} بهره‌مند شوید.";
        }

        return "یک پیشنهاد ویژه برای شما داریم؛ همین امروز از {$reward} استفاده کنید.";
    }

    private function normalizePersianText(string $value): string
    {
        return mb_strtolower(
            str_replace(['ي', 'ك'], ['ی', 'ک'], $value)
        );
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    private function containsAnyText(
        string $text,
        array $needles
    ): bool {
        foreach ($needles as $needle) {
            if (
                $needle !== ''
                && str_contains($text, mb_strtolower($needle))
            ) {
                return true;
            }
        }

        return false;
    }

    public function publicAsk(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'visitor_key' => 'nullable|string|max:100',
        ]);

        $message = trim($data['message']);
        $visitor = $data['visitor_key'] ?? Str::uuid()->toString();

        $session = KbChatSession::firstOrCreate(
            [
                'visitor_key' => 'widget:' . $visitor,
                'channel' => 'widget',
            ],
            [
                'ip' => $request->ip(),
                'user_agent' => substr(
                    (string) $request->userAgent(),
                    0,
                    255
                ),
                'last_message_at' => now(),
            ]
        );

        KbChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'user',
            'message' => $message,
        ]);

        $result = $this->brain->process($message);

        KbChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'bot',
            'message' => $result['answer'],
            'matched_articles' => $result['articles'] ?? [],
            'needs_ticket' => $result['needs_ticket'] ?? false,
        ]);

        $session->update([
            'last_message_at' => now(),
        ]);

        return response()->json(array_merge([
            'ok' => true,
        ], $result));
    }

    private function storeChatFile(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment' => $file->store('chat', 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }

    public function logs()
    {
        $sessions = KbChatSession::withCount('messages')
            ->latest('last_message_at')
            ->paginate(30);

        return view('app.kb.chat_logs', compact('sessions'));
    }

    public function showLog(KbChatSession $session)
    {
        $session->load('messages');

        return view('app.kb.chat_log_show', compact('session'));
    }

    private function session(Request $request): KbChatSession
    {
        $customerId = $this->currentMember()?->customer_id;
        $visitor = $request->session()->get('kb_visitor_key');

        if (! $visitor) {
            $visitor = Str::uuid()->toString();
            $request->session()->put('kb_visitor_key', $visitor);
        }

        return KbChatSession::firstOrCreate(
            [
                'visitor_key' => $visitor,
                'channel' => 'club',
            ],
            [
                'customer_id' => $customerId,
                'ip' => $request->ip(),
                'user_agent' => substr(
                    (string) $request->userAgent(),
                    0,
                    255
                ),
                'last_message_at' => now(),
            ]
        );
    }

    public function importLegion()
    {
        if (! auth()->user() || auth()->user()->role !== 'admin') {
            abort(403);
        }

        $articles = [
            [
                'title' => 'معرفی سامانه لژیون (فیلیا)',
                'question' => 'لژیون چیست و چه کاربردی دارد؟',
                'answer' => 'لژیون یک پلتفرم ترکیبی CRM و باشگاه مشتریان است که داده‌های مشتری، ابزارهای وفادارسازی و اتوماسیون بازاریابی را در یک سیستم یکپارچه کنار هم می‌آورد. برخلاف سیستم‌های قدیمی، لژیون هیچ داده‌ای را دور نمی‌ریزد و هر خرید، ورود به کلاب یا کلیک کاربر را برای تحلیل‌های عمیق‌تر ثبت می‌کند.',
                'visibility' => 'public',
                'is_active' => true,
            ],
            [
                'title' => 'مفهوم عضو و پروفایل ۳۶۰ درجه',
                'question' => 'عضو در لژیون به چه معناست و پروفایل ۳۶۰ درجه چیست؟',
                'answer' => 'عضو، هر مشتری است که در سیستم ثبت شده باشد. هر عضو دارای یک پروفایل ۳۶۰ درجه است که شامل تاریخچه خرید، امتیازات، سطح، کیف پول و تمام رفتارهای دیجیتال اوست تا مدیران بتوانند دیدی جامع از هر مشتری داشته باشند.',
                'visibility' => 'public',
                'is_active' => true,
            ],
            [
                'title' => 'سیستم امتیازات و سکه‌ها',
                'question' => 'تفاوت امتیاز و سکه در لژیون چیست؟',
                'answer' => 'امتیاز و سکه واحدهای پاداش در لژیون هستند. سکه‌ها می‌توانند به صورت اعتبار در فروشگاه باشگاه خرج شوند، در حالی که امتیازات مبنای محاسبه رتبه مشتری در لیدربورد و تعیین سطح (Tier) او هستند.',
                'visibility' => 'public',
                'is_active' => true,
            ],
            [
                'title' => 'تعریف مأموریت‌ها (Missions)',
                'question' => 'مأموریت در لژیون چیست و چگونه کار می‌کند؟',
                'answer' => 'مأموریت در واقع یک قانون است که مشخص می‌کند چه رفتاری از سوی مشتری، چه پاداشی داشته باشد. برای مثال، می‌توان قانونی تعریف کرد که «خرید بالای ۵۰۰ هزار تومان منجر به دریافت ۵۰ سکه پاداش شود».',
                'visibility' => 'public',
                'is_active' => true,
            ],
            [
                'title' => 'سگمنت‌ها و سطح‌بندی مشتریان',
                'question' => 'تفاوت سگمنت و سطح (Level) چیست؟',
                'answer' => 'سطح (Tier) دسته‌بندی مشتریان بر اساس ارزش کلی آن‌هاست که در لحظه خرید به‌روز می‌شود. اما سگمنت (Segment) یک گروه پویای از مشتریان است که بر اساس ویژگی‌ها یا رفتارهای خاص تعریف می‌شوند و با تغییر رفتار کاربر، عضویت آن‌ها در سگمنت به صورت خودکار تغییر می‌کند.',
                'visibility' => 'public',
                'is_active' => true,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('kb_articles')->updateOrInsert(
                ['title' => $article['title']],
                [
                    'question' => $article['question'],
                    'answer' => $article['answer'],
                    'visibility' => $article['visibility'],
                    'is_active' => $article['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'اولین مرحله جذب محتوا با موفقیت انجام شد.',
        ]);
    }
}