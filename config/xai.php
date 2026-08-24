<?php
declare(strict_types=1);

const XAI_MODEL = 'grok-4.5';
const XAI_ENDPOINT = 'https://api.x.ai/v1/responses';
const GROQ_MODEL = 'openai/gpt-oss-20b';
const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

// LOCAL XAMPP: paste your key between the quotes below. Keep this file private.
$XAI_API_KEY = '';

function xai_api_key(): string
{
    static $key = null;
    if ($key !== null) return $key;

    global $XAI_API_KEY;
    $direct = trim((string)$XAI_API_KEY);
    if ($direct !== '') { $key = $direct; return $key; }

    foreach (['XAI_API_KEY', 'GROK_API_KEY', 'GROQ_API_KEY'] as $name) {
        $candidate = getenv($name);
        if ($candidate === false || $candidate === '') $candidate = $_SERVER[$name] ?? $_ENV[$name] ?? '';
        $candidate = trim((string)$candidate);
        if ($candidate !== '') { $key = $candidate; return $key; }
    }

    $local = __DIR__ . '/xai.local.php';
    if (is_file($local)) {
        $config = require $local;
        if (is_array($config)) {
            $candidate = trim((string)($config['api_key'] ?? $config['XAI_API_KEY'] ?? $config['GROK_API_KEY'] ?? $config['GROQ_API_KEY'] ?? ''));
            if ($candidate !== '') { $key = $candidate; return $key; }
        }
    }

    $key = '';
    return $key;
}

function ai_provider(string $apiKey): string { return str_starts_with($apiKey, 'gsk_') ? 'groq' : 'xai'; }
function ai_endpoint(string $provider): string { return $provider === 'groq' ? GROQ_ENDPOINT : XAI_ENDPOINT; }
function ai_model(string $provider): string {
    $configured = trim((string)(getenv($provider === 'groq' ? 'GROQ_MODEL' : 'XAI_MODEL') ?: ''));
    return $configured !== '' ? $configured : ($provider === 'groq' ? GROQ_MODEL : XAI_MODEL);
}
