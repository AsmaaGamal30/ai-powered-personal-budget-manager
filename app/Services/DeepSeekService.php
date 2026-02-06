<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->baseUrl = config('services.deepseek.base_url', 'https://openrouter.ai/api/v1');
        $this->model = config('services.deepseek.model', 'openai/gpt-oss-120b:free');
    }

    public function chat(string $message, array $context = [], array $options = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $message,
            ],
        ];

        if (isset($options['history']) && is_array($options['history'])) {
            array_splice($messages, 1, 0, $options['history']);
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ];

        if (isset($options['reasoning']) && $options['reasoning']) {
            $payload['reasoning'] = ['enabled' => true];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])->timeout(120)
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $result = [
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? null,
                    'model' => $data['model'] ?? $this->model,
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                ];

                if (isset($data['choices'][0]['message']['reasoning_details'])) {
                    $result['reasoning_details'] = $data['choices'][0]['message']['reasoning_details'];
                }

                return $result;
            }

            $errorBody = $response->json();

            if (isset($errorBody['error'])) {
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';
                $errorCode = $errorBody['error']['code'] ?? $response->status();

                if ($errorCode == 404 && str_contains($errorMessage, 'data policy')) {
                    throw new \Exception(
                        "OpenRouter Privacy Settings Error: Please configure your data policy at https://openrouter.ai/settings/privacy. " .
                        "For free models, you may need to allow 'Free model publication' in your privacy settings."
                    );
                }

                throw new \Exception("OpenRouter API Error ({$errorCode}): {$errorMessage}");
            }

            Log::error('DeepSeek API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('DeepSeek API request failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('DeepSeek Service Exception', [
                'message' => $e->getMessage(),
                'context' => $context,
                'userId' => auth()->id() ?? null,
            ]);

            throw $e;
        }
    }

    private function buildSystemPrompt(array $context): string
    {
        $basePrompt = "You are an expert financial advisor and budgeting assistant. Your role is to help users manage their personal finances, understand their spending patterns, and make informed financial decisions.

You should:
- Provide clear, actionable financial advice in natural prose
- Be specific with numbers and recommendations
- Highlight both positive habits and areas for improvement
- Warn about potential budget overruns or concerning spending patterns
- Suggest realistic savings strategies
- Be encouraging and supportive while being honest about financial realities
- Use clear, non-technical language that anyone can understand

CRITICAL FORMATTING RULES:
- Write in natural paragraphs and sentences, NOT lists or bullet points
- Do NOT use tables, headers, or markdown formatting
- Do NOT use bold text, italics, or any emphasis markers
- Write lists in natural language like 'some areas include: x, y, and z' with no bullet points or numbered lists
- Keep responses conversational and flowing, as if speaking to someone
- Avoid over-formatting with excessive structure
- Present information in prose form only";

        if (empty($context)) {
            return $basePrompt;
        }

        $contextPrompt = "\n\nCurrent Financial Context: ";

        if (isset($context['analysis_period'])) {
            $contextPrompt .= "You're analyzing the {$context['analysis_period']} period. ";
        }

        if (isset($context['date_range'])) {
            $contextPrompt .= "The date range covers {$context['date_range']['start']} to {$context['date_range']['end']}. ";
        }

        if (isset($context['total_budget']) && isset($context['total_spent'])) {
            $remaining = $context['total_budget'] - $context['total_spent'];
            $percentageUsed = $context['total_budget'] > 0
                ? round(($context['total_spent'] / $context['total_budget']) * 100, 2)
                : 0;

            $contextPrompt .= "The total budget is \${$context['total_budget']}, with \${$context['total_spent']} spent so far, leaving \${$remaining} remaining. This represents {$percentageUsed}% of the budget used. ";
        }

        if (isset($context['categories_summary']) && !empty($context['categories_summary'])) {
            $contextPrompt .= "Looking at category breakdown: ";
            $categoryDescriptions = [];
            foreach ($context['categories_summary'] as $category) {
                $categoryDescriptions[] = "{$category['category']} has a budget of \${$category['budget']} with \${$category['spent']} spent ({$category['percentage_used']}% used), leaving \${$category['remaining']} remaining across {$category['transaction_count']} transactions averaging \${$category['average_transaction']} each";
            }
            $contextPrompt .= implode('; ', $categoryDescriptions) . ". ";
        }

        if (isset($context['daily_breakdown']) && !empty($context['daily_breakdown'])) {
            $contextPrompt .= "Daily spending shows: ";
            $dailyDescriptions = [];
            foreach ($context['daily_breakdown'] as $day) {
                $dailyDescriptions[] = "\${$day['amount']} on {$day['date']} ({$day['transactions']} transactions)";
            }
            $contextPrompt .= implode(', ', $dailyDescriptions) . ". ";
        }

        if (isset($context['previous_period'])) {
            $contextPrompt .= "Compared to the previous period ({$context['previous_period']['date_range']['start']} to {$context['previous_period']['date_range']['end']}), which had \${$context['previous_period']['total_spent']} in spending";

            if (isset($context['total_spent'])) {
                $change = $context['total_spent'] - $context['previous_period']['total_spent'];
                $percentageChange = $context['previous_period']['total_spent'] > 0
                    ? round(($change / $context['previous_period']['total_spent']) * 100, 2)
                    : 0;
                $direction = $change >= 0 ? 'increase' : 'decrease';
                $contextPrompt .= ", there's a \${$change} {$direction} ({$percentageChange}%)";
            }
            $contextPrompt .= ". ";
        }

        if (isset($context['target_savings'])) {
            $contextPrompt .= "The savings goal is \${$context['target_savings']}. ";
        }

        return $basePrompt . $contextPrompt;
    }

    public function streamChat(string $message, array $context = [], callable $callback): void
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
            'stream' => true,
            'temperature' => 0.7,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])->timeout(120)
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->successful()) {
                $stream = $response->body();
                $lines = explode("\n", $stream);

                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);
                        if ($data === '[DONE]') {
                            break;
                        }

                        $json = json_decode($data, true);
                        if (isset($json['choices'][0]['delta']['content'])) {
                            $callback($json['choices'][0]['delta']['content']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('DeepSeek Stream Exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function validateConfiguration(): bool
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenRouter API key is not configured. Please set DEEPSEEK_API_KEY in your .env file');
        }

        if (empty($this->baseUrl)) {
            throw new \Exception('OpenRouter base URL is not configured');
        }

        return true;
    }

    public function testConnection(): array
    {
        try {
            $this->validateConfiguration();

            $response = $this->chat('Hello', [], ['max_tokens' => 10]);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'model' => $response['model'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available models from OpenRouter
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch available models', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
