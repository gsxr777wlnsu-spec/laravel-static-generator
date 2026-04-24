<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class AiContentService
{
    protected string $provider;
    protected string $apiKey;
    protected string $model;
    
    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'openai');
        $this->apiKey = config('services.ai.api_key', env('AI_API_KEY', ''));
        $this->model = config('services.ai.model', env('AI_MODEL', 'gpt-4o'));
    }

    /**
     * Generate structured content sections from text
     */
    public function generateContent(string $text): array
    {
        $prompt = "You are an expert web content structurer. Convert the following text into an array of webpage sections. Each section must have a 'type' (can be: faq, hero, text, list, table, gallery, cta) and a 'content' object containing the appropriate fields for that type. Return strictly valid JSON containing an array of section objects.\n\nText:\n" . $text;
        
        $response = $this->callAi($prompt);
        return $this->parseJson($response);
    }
    
    /**
     * Generate metadata including JSON-LD schema
     */
    public function generateMetadata(string $text): array
    {
        $prompt = "Extract and generate SEO metadata for the following text. Return strictly valid JSON with the following keys: 'meta_title' (max 60 chars), 'meta_description' (max 160 chars), 'meta_keywords' (comma separated string), 'json_ld' (valid Article schema markup as JSON string or object).\n\nText:\n" . $text;
        
        $response = $this->callAi($prompt);
        return $this->parseJson($response);
    }
    
    protected function callAi(string $prompt): string
    {
        if (empty($this->apiKey)) {
            Log::warning('AI API Key is missing, returning mock JSON array.');
            return '[]';
        }

        if ($this->provider === 'anthropic') {
            return $this->callAnthropic($prompt);
        }
        
        return $this->callOpenAI($prompt);
    }
    
    protected function callOpenAI(string $prompt): string
    {
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always respond with pure JSON, without markdown formatting blocks.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
            ]);
            
        if ($response->failed()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }
        
        return $response->json('choices.0.message.content') ?? '[]';
    }
    
    protected function callAnthropic(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'system' => 'You always respond with pure JSON, without markdown formatting blocks.',
        ]);
        
        if ($response->failed()) {
            throw new Exception('Anthropic API request failed: ' . $response->body());
        }
        
        return $response->json('content.0.text') ?? '[]';
    }

    protected function parseJson(string $jsonString): array
    {
        $jsonString = trim($jsonString);
        // Remove markdown json block if AI accidentally includes it
        if (str_starts_with($jsonString, '```json')) {
            $jsonString = substr($jsonString, 7);
            $jsonString = preg_replace('/```$/', '', trim($jsonString));
        } elseif (str_starts_with($jsonString, '```')) {
            $jsonString = substr($jsonString, 3);
            $jsonString = preg_replace('/```$/', '', trim($jsonString));
        }
        
        $data = json_decode(trim($jsonString), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AI JSON Parse Error: ' . json_last_error_msg() . ' String: ' . $jsonString);
            return [];
        }
        
        return is_array($data) ? $data : [];
    }
}
