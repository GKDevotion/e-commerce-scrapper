<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\ProductImport;
use App\Models\PromptTemplate;
use Illuminate\Support\Facades\Log;

class AiGenerationService
{
    private string $apiKey;
    private string $model;
    private string $apiBase = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    /**
     * Generate AI content for a product import
     */
    public function generateListing(ProductImport $import, array $options = []): AiGeneration
    {
        // Create generation record
        $generation = AiGeneration::create([
            'user_id' => $import->user_id,
            'product_import_id' => $import->id,
            'generation_method' => 'ai',
            'status' => 'generating',
            'brand_name' => $import->target_brand_name,
            'manufacturer' => $import->target_manufacturer,
        ]);

        try {
            $template = PromptTemplate::getDefault();
            $prompt = $this->buildPrompt($import, $template);

            $startTime = microtime(true);
            $response = $this->callOpenAI($prompt, $template);
            $elapsedMs = (int)((microtime(true) - $startTime) * 1000);

            $parsed = $this->parseAiResponse($response['content']);

            // Apply brand substitution
            $parsed = self::applyBrandSubstitution(
                $parsed,
                $import->original_brand,
                $import->original_manufacturer,
                $import->target_brand_name,
                $import->target_manufacturer
            );

            $generation->update([
                'status' => 'completed',
                'generated_title' => $parsed['title'],
                'generated_bullet_points' => $parsed['bullet_points'],
                'generated_description' => $parsed['description'],
                'generated_search_terms' => $parsed['search_terms'],
                'generated_seo_keywords' => $parsed['seo_keywords'],
                'generated_highlights' => $parsed['highlights'],
                'generated_aplus_content' => $parsed['aplus_content'],
                'ai_model' => $response['model'],
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                'ai_cost' => $this->calculateCost($response['usage'] ?? [], $response['model']),
                'prompt_used' => $prompt,
                'generated_at' => now(),
                'generation_name' => $parsed['suggested_name'] ?? null,
            ]);

            // Update user's usage count
            $import->user->increment('ai_generations_used');
            $import->update(['status' => 'completed']);

        } catch (\Exception $e) {
            $generation->update([
                'status' => 'failed',
                'generation_error' => $e->getMessage(),
            ]);
            Log::error('AI Generation failed: ' . $e->getMessage(), [
                'import_id' => $import->id,
                'user_id' => $import->user_id,
            ]);
            throw $e;
        }

        return $generation->fresh();
    }

    /**
     * Build the AI prompt from product data
     */
    private function buildPrompt(ProductImport $import, ?PromptTemplate $template): array
    {
        $systemPrompt = $template?->system_prompt ?? $this->getDefaultSystemPrompt();
        $userPrompt = $this->buildUserPrompt($import, $template?->user_prompt_template);

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    private function getDefaultSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert Amazon listing copywriter specializing in creating unique, compelling, and Amazon-compliant product listings. 

Your role is to:
1. Create completely ORIGINAL content that does NOT copy the source listing
2. Rewrite all content in a fresh, unique voice while preserving factual accuracy
3. Optimize content for Amazon's A9/A10 search algorithm
4. Create listings that convert browsers into buyers
5. Replace ALL references to the original brand/manufacturer with the new brand provided
6. Ensure all bullet points and descriptions are unique and don't mirror the original
7. Follow Amazon's content guidelines strictly

CRITICAL RULES:
- Never copy original text verbatim
- Never mention competitor brand names
- Never make false or unverifiable claims
- Keep titles under 200 characters
- Write 5 bullet points, each under 200 characters starting with a capital letter
- Descriptions should be 200-400 words
- Search terms should be comma-separated, under 250 bytes total
- All claims must be factually supportable from the product specs

Respond ONLY with valid JSON in the exact format specified.
PROMPT;
    }

    private function buildUserPrompt(ProductImport $import, ?string $template): string
    {
        $bulletPoints = implode("\n- ", $import->original_bullet_points ?? []);
        $specs = collect($import->original_specifications ?? [])->map(fn($v, $k) => "{$k}: {$v}")->implode("\n");

        return <<<PROMPT
Generate a complete Amazon product listing for the following product.

NEW BRAND NAME: {$import->target_brand_name}
NEW MANUFACTURER: {$import->target_manufacturer}
TARGET KEYWORDS: {$import->target_keywords}

ORIGINAL PRODUCT DATA (for reference only - do NOT copy):
Title: {$import->original_title}
Brand: {$import->original_brand}
Category: {$import->original_category}
Description: {$import->original_description}
Bullet Points:
- {$bulletPoints}
Specifications:
{$specs}
Weight: {$import->product_weight}
Dimensions: {$import->product_dimensions}

Generate UNIQUE content and respond with this EXACT JSON format:
{
  "title": "Complete product title with brand, key features, up to 200 chars",
  "bullet_points": [
    "FEATURE 1: Detailed benefit explanation...",
    "FEATURE 2: Detailed benefit explanation...",
    "FEATURE 3: Detailed benefit explanation...",
    "FEATURE 4: Detailed benefit explanation...",
    "FEATURE 5: Detailed benefit explanation..."
  ],
  "description": "Full 200-400 word product description with HTML formatting allowed",
  "search_terms": "keyword1, keyword2, keyword3, ... (comma separated, total under 250 bytes)",
  "seo_keywords": "primary keyword, secondary keyword, long tail keywords...",
  "highlights": "3-5 key selling points in plain text, newline separated",
  "aplus_content": "Suggested A+ content modules with headers and body text",
  "suggested_name": "Short internal name for this listing draft"
}
PROMPT;
    }

    /**
     * Call OpenAI API directly
     */
    private function callOpenAI(array $prompt, ?PromptTemplate $template): array
    {
        $model = $template?->ai_model ?? $this->model;
        $maxTokens = $template?->max_tokens ?? 4000;
        $temperature = (float)($template?->temperature ?? 0.7);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $prompt['user']],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init($this->apiBase);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new \Exception("OpenAI API connection error: {$error}");
        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            throw new \Exception("OpenAI API error {$httpCode}: " . ($body['error']['message'] ?? $response));
        }

        $body = json_decode($response, true);
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI API response format');
        }

        return [
            'content' => $body['choices'][0]['message']['content'],
            'model' => $body['model'] ?? $model,
            'usage' => $body['usage'] ?? [],
        ];
    }

    /**
     * Parse AI JSON response
     */
    private function parseAiResponse(string $content): array
    {
        // Strip markdown code blocks if present
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        $data = json_decode(trim($content), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('AI returned invalid JSON: ' . json_last_error_msg());
        }

        return [
            'title' => $data['title'] ?? null,
            'bullet_points' => $data['bullet_points'] ?? [],
            'description' => $data['description'] ?? null,
            'search_terms' => $data['search_terms'] ?? null,
            'seo_keywords' => $data['seo_keywords'] ?? null,
            'highlights' => $data['highlights'] ?? null,
            'aplus_content' => $data['aplus_content'] ?? null,
            'suggested_name' => $data['suggested_name'] ?? null,
        ];
    }

    /**
     * Replace original brand/manufacturer with user-provided values
     */
    /**
     * Replace original brand/manufacturer with user-provided values.
     * Public + static so it can be reused by manual (non-AI) listing
     * creation without instantiating the full AI service.
     */
    public static function applyBrandSubstitution(
        array $content,
        ?string $originalBrand,
        ?string $originalManufacturer,
        ?string $newBrand,
        ?string $newManufacturer
    ): array {
        if (!$newBrand && !$newManufacturer) return $content;

        $searchTerms = array_filter([$originalBrand, $originalManufacturer]);
        $replacements = [];
        foreach ($searchTerms as $term) {
            $replacements[$term] = $newBrand ?? $newManufacturer;
        }

        array_walk_recursive($content, function (&$value) use ($searchTerms, $newBrand, $newManufacturer) {
            if (is_string($value) && !empty($searchTerms)) {
                foreach ($searchTerms as $term) {
                    if ($term && strlen($term) > 2) {
                        $value = str_ireplace($term, $newBrand ?? $newManufacturer, $value);
                    }
                }
            }
        });

        return $content;
    }

    /**
     * Calculate approximate API cost
     */
    private function calculateCost(array $usage, string $model): float
    {
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        // Approximate pricing per 1K tokens
        $pricing = match(true) {
            str_contains($model, 'gpt-4o') => ['input' => 0.005, 'output' => 0.015],
            str_contains($model, 'gpt-4') => ['input' => 0.03, 'output' => 0.06],
            str_contains($model, 'gpt-3.5') => ['input' => 0.001, 'output' => 0.002],
            default => ['input' => 0.005, 'output' => 0.015],
        };

        return (($inputTokens / 1000) * $pricing['input']) + (($outputTokens / 1000) * $pricing['output']);
    }
}
