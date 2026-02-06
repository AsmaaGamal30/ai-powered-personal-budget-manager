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
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');
        $this->model = config('services.deepseek.model', 'deepseek-chat');
    }

    /**
     * Send a chat message to DeepSeek AI
     *
     * @param string $message User's message
     * @param array $context Financial context data
     * @param array $options Additional options
     * @return array Response from DeepSeek
     */
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

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? null,
                    'model' => $data['model'] ?? $this->model,
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                ];
            }

            Log::error('DeepSeek API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('DeepSeek API request failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('DeepSeek Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }


    private function buildSystemPrompt(array $context): string
    {
        $basePrompt = "You are an expert financial advisor and budgeting assistant. Your role is to help users manage their personal finances, understand their spending patterns, and make informed financial decisions.

You should:
- Provide clear, actionable financial advice
- Be specific with numbers and recommendations
- Highlight both positive habits and areas for improvement
- Warn about potential budget overruns or concerning spending patterns
- Suggest realistic savings strategies
- Be encouraging and supportive while being honest about financial realities
- Use clear, non-technical language that anyone can understand";

        if (empty($context)) {
            return $basePrompt;
        }

        $contextPrompt = "\n\nCurrent Financial Context:\n";

        if (isset($context['analysis_period'])) {
            $contextPrompt .= "Analysis Period: {$context['analysis_period']}\n";
        }

        if (isset($context['date_range'])) {
            $contextPrompt .= "Date Range: {$context['date_range']['start']} to {$context['date_range']['end']}\n";
        }

        if (isset($context['total_budget']) && isset($context['total_spent'])) {
            $remaining = $context['total_budget'] - $context['total_spent'];
            $percentageUsed = $context['total_budget'] > 0
                ? round(($context['total_spent'] / $context['total_budget']) * 100, 2)
                : 0;

            $contextPrompt .= "\nOverall Budget Summary:\n";
            $contextPrompt .= "- Total Budget: \${$context['total_budget']}\n";
            $contextPrompt .= "- Total Spent: \${$context['total_spent']}\n";
            $contextPrompt .= "- Remaining: \${$remaining}\n";
            $contextPrompt .= "- Percentage Used: {$percentageUsed}%\n";
        }

        if (isset($context['categories_summary']) && !empty($context['categories_summary'])) {
            $contextPrompt .= "\nCategory Breakdown:\n";
            foreach ($context['categories_summary'] as $category) {
                $contextPrompt .= "- {$category['category']}: Budget \${$category['budget']}, ";
                $contextPrompt .= "Spent \${$category['spent']} ({$category['percentage_used']}%), ";
                $contextPrompt .= "Remaining \${$category['remaining']}, ";
                $contextPrompt .= "{$category['transaction_count']} transactions, ";
                $contextPrompt .= "Avg \${$category['average_transaction']} per transaction\n";
            }
        }

        if (isset($context['daily_breakdown']) && !empty($context['daily_breakdown'])) {
            $contextPrompt .= "\nDaily Spending Pattern:\n";
            foreach ($context['daily_breakdown'] as $day) {
                $contextPrompt .= "- {$day['date']}: \${$day['amount']} ({$day['transactions']} transactions)\n";
            }
        }

        if (isset($context['previous_period'])) {
            $contextPrompt .= "\nPrevious Period Comparison:\n";
            $contextPrompt .= "- Period: {$context['previous_period']['date_range']['start']} to {$context['previous_period']['date_range']['end']}\n";
            $contextPrompt .= "- Total Spent: \${$context['previous_period']['total_spent']}\n";

            if (isset($context['total_spent'])) {
                $change = $context['total_spent'] - $context['previous_period']['total_spent'];
                $percentageChange = $context['previous_period']['total_spent'] > 0
                    ? round(($change / $context['previous_period']['total_spent']) * 100, 2)
                    : 0;
                $contextPrompt .= "- Change: \${$change} ({$percentageChange}%)\n";
            }
        }

        if (isset($context['target_savings'])) {
            $contextPrompt .= "\nSavings Goal: \${$context['target_savings']}\n";
        }

        return $basePrompt . $contextPrompt;
    }

    /**
     * Stream chat responses (for real-time responses)
     */
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
            ])->timeout(60)
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
            throw new \Exception('DeepSeek API key is not configured');
        }

        if (empty($this->baseUrl)) {
            throw new \Exception('DeepSeek base URL is not configured');
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
}