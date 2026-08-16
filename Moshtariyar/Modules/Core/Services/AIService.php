<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Setting;
use Exception;

/**
 * AIService: لایه ارتباطی با مدل‌های هوش مصنوعی
 * بهینه‌شده برای سرعت پاسخ‌دهی بالا، مدیریت توکن‌ها و تشخیص خطاهای شبکه.
 */
class AIService
{
    protected string $provider;
    protected string $model;
    protected string $apiKey;

    public function __construct()
    {
        $this->provider = Setting::get('ai_provider', 'openai');
        $this->model    = Setting::get('ai_model', 'gpt-4-turbo');
        $this->apiKey   = Setting::get('ai_api_key', '');
    }

    /**
     * تست اتصال به API با تشخیص دقیق دلیل خطا
     */
    public function testConnection(string $provider, string $apiKey): array
    {
        try {
            $result = match ($provider) {
                'openai'    => $this->testOpenAI($apiKey),
                'gemini'    => $this->testGemini($apiKey),
                'claude'    => $this->testClaude($apiKey),
                'deepseek'  => $this->testDeepSeek($apiKey),
                'grok'      => $this->testGrok($apiKey),
                default     => throw new Exception("ارائه دهنده {$provider} پشتیبانی نمی‌شود."),
            };

            return ['ok' => true, 'message' => "✅ اتصال با موفقیت برقرار شد. مدل آماده است."];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '403')) {
                return ['ok' => false, 'message' => "❌ خطای ۴۰۳: دسترسی مسدود است. احتمالاً IP سرور شما توسط این سرویس محدود شده است."];
            }
            if (str_contains($msg, 'timed out') || str_contains($msg, 'cURL error 28')) {
                return ['ok' => false, 'message' => "❌ خطای Timeout: سرور پاسخ نمی‌دهد. احتمالاً ارتباط شبکه با این API مسدود است."];
            }
            return ['ok' => false, 'message' => "❌ خطا در اتصال: " . $msg];
        }
    }

    private function testOpenAI($key) {
        return Http::withToken($key)->timeout(5)->get('https://api.openai.com/v1/models')->throw();
    }
    private function testGemini($key) {
        return Http::timeout(5)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}")->throw();
    }
    private function testClaude($key) {
        return Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(5)->get('https://api.anthropic.com/v1/messages', [])->throw();
    }
    private function testDeepSeek($key) {
        return Http::withToken($key)->timeout(5)->post('https://api.deepseek.com/chat/completions', [
            'model' => 'deepseek-chat', 'messages' => [['role' => 'user', 'content' => 'hi']], 'max_tokens' => 1
        ])->throw();
    }
    private function testGrok($key) {
        return Http::withToken($key)->timeout(5)->post('https://api.x.ai/v1/chat/completions', [
            'model' => 'grok-beta', 'messages' => [['role' => 'user', 'content' => 'hi']], 'max_tokens' => 1
        ])->throw();
    }

    public function chat(array $messages, float $temperature = 0.7): ?string
    {
        if (empty($this->apiKey)) return $this->simulateResponse($messages);

        try {
            return match ($this->provider) {
                'openai'    => $this->callOpenAI($messages, $temperature),
                'gemini'    => $this->callGemini($messages, $temperature),
                'claude'    => $this->callClaude($messages, $temperature),
                'deepseek'  => $this->callDeepSeek($messages, $temperature),
                'grok'      => $this->callGrok($messages, $temperature),
                default     => $this->callOpenAI($messages, $temperature),
            };
        } catch (Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return $this->simulateResponse($messages);
        }
    }

    protected function recordTokens(int $tokens): void
    {
        $current = (int) Setting::get('ai_total_tokens', 0);
        Setting::updateOrCreate(['key' => 'ai_total_tokens'], ['value' => $current + $tokens]);
    }

    protected function simulateResponse(array $messages): string
    {
        $userMsg = collect($messages)->last()['content'] ?? '';
        $userMsg = mb_strtolower($userMsg);
        if (str_contains($userMsg, 'فروش') || str_contains($userMsg, 'درآمد')) return '{"tool": "get_revenue_report", "params": {}, "explanation": "دارم گزارش فروش امروز رو آماده می‌کنم."}';
        if (str_contains($userMsg, 'مشتری') && (str_contains($userMsg, 'تحلیل') || str_contains($userMsg, 'بررسی'))) return '{"tool": "analyze_customer_behavior", "params": {"customer_id": 1}, "explanation": "در حال تحلیل رفتار مشتری هستم."}';
        if (str_contains($userMsg, 'امتیاز') || str_contains($userMsg, 'پاداش')) return '{"tool": "adjust_loyalty_points", "params": {"customer_id": 1, "amount": 100, "reason": "هدیه"}, "explanation": "پیشنهاد تغییر امتیاز."}';
        if (str_contains($userMsg, 'پیام') || str_contains($userMsg, 'ارسال')) return '{"tool": "send_message", "params": {"customer_id": 1, "message": "سلام!"}, "explanation": "متن پیام آماده است."}';
        return "من در حالت «شبیه‌ساز» هستم چون ارتباط با سرور AI برقرار نشد. اما می‌توانم تحلیل‌های بیزینسی را انجام دهم.";
    }

    protected function callOpenAI(array $messages, float $temperature): string
    {
        $response = Http::withToken($this->apiKey)->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model, 'messages' => $messages, 'temperature' => $temperature,
        ]);
        if ($response->failed()) throw new Exception('OpenAI Error');
        $this->recordTokens($response->json('usage.total_tokens', 0));
        return $response->json('choices.0.message.content');
    }

    protected function callGemini(array $messages, float $temperature): string
    {
        $contents = collect($messages)->filter(fn($m) => $m['role'] !== 'system')->map(fn($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]],
        ])->values()->all();
        $systemInstruction = collect($messages)->firstWhere('role', 'system')['content'] ?? '';
        $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => $contents, 'system_instruction' => $systemInstruction ? ['parts' => [['text' => $systemInstruction]]] : null, 'generationConfig' => ['temperature' => $temperature],
        ]);
        if ($response->failed()) throw new Exception('Gemini Error');
        $this->recordTokens($response->json('usageMetadata.totalTokenCount', 0));
        return $response->json('candidates.0.content.parts.0.text');
    }

    protected function callClaude(array $messages, float $temperature): string
    {
        $system = collect($messages)->firstWhere('role', 'system')['content'] ?? '';
        $userMsgs = collect($messages)->filter(fn($m) => $m['role'] !== 'system')->values()->all();
        $response = Http::withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])->timeout(10)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model, 'max_tokens' => 2000, 'system' => $system, 'messages' => $userMsgs, 'temperature' => $temperature,
        ]);
        if ($response->failed()) throw new Exception('Claude Error');
        $this->recordTokens($response->json('usage.input_tokens', 0) + $response->json('usage.output_tokens', 0));
        return $response->json('content.0.text');
    }

    protected function callDeepSeek(array $messages, float $temperature): string
    {
        $response = Http::withToken($this->apiKey)->timeout(10)->post('https://api.deepseek.com/chat/completions', [
            'model' => $this->model, 'messages' => $messages, 'temperature' => $temperature,
        ]);
        if ($response->failed()) throw new Exception('DeepSeek Error');
        $this->recordTokens($response->json('usage.total_tokens', 0));
        return $response->json('choices.0.message.content');
    }

    protected function callGrok(array $messages, float $temperature): string
    {
        $response = Http::withToken($this->apiKey)->timeout(10)->post('https://api.x.ai/v1/chat/completions', [
            'model' => $this->model, 'messages' => $messages, 'temperature' => $temperature,
        ]);
        if ($response->failed()) throw new Exception('Grok Error');
        $this->recordTokens($response->json('usage.total_tokens', 0));
        return $response->json('choices.0.message.content');
    }
}