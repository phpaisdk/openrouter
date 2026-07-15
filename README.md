# aisdk/openrouter

Official OpenRouter provider for the PHP AI SDK. Uses the shared OpenAI-compatible wire adapter.

## Installation

```bash
composer require aisdk/openrouter
```

## Basic Usage

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::text()
    ->model(OpenRouter::model('openai/gpt-4o'))
    ->instructions('Write short, clear answers.')
    ->prompt('Explain closures in PHP.')
    ->run();

echo $result->text;
```

Routed model IDs pass through unchanged and do not need to be registered. This package does not ship a model inventory; the SDK performs internal adapter validation before OpenRouter validates support for the selected route.

## Embeddings

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::embedding(['Search query', 'Document text'])
    ->model(OpenRouter::model('openai/text-embedding-3-small'))
    ->dimensions(256)
    ->run();

$queryVector = $result->embeddings[0]->vector;
$documentVector = $result->embeddings[1]->vector;
```

OpenRouter embedding model IDs pass through unchanged. The portable embedding API accepts text or a list of texts; provider-specific multimodal embedding inputs are outside this contract.

## Image Generation

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::image()
    ->model(OpenRouter::model('openai/gpt-image-1'))
    ->prompt('A clean app icon for a PHP AI SDK')
    ->size('1024x1024')
    ->run();

$result->output->save(__DIR__.'/icon.png');
```

## Speech Generation

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::speech()
    ->model(OpenRouter::model('microsoft/mai-voice-2'))
    ->input('Welcome to the release.')
    ->voice('en-US-Harper:MAI-Voice-2')
    ->format('mp3')
    ->run();

$result->output->save(__DIR__.'/speech.mp3');
```

## Transcription

```php
use AiSdk\Content;
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::transcription(Content::audio(__DIR__.'/meeting.mp3'))
    ->model(OpenRouter::model('openai/whisper-large-v3'))
    ->run();

echo $result->output->text;
```

## Video Generation

```php
$result = Generate::video('A time-lapse of a flower blooming')
    ->model(OpenRouter::model('google/veo-3.1'))
    ->resolution('1080p')
    ->aspectRatio('16:9')
    ->run(timeout: 600);
```

## Configuration

| Variable | Description | Default |
|---|---|---|
| `OPENROUTER_API_KEY` | API key for authentication | Required |
| `OPENROUTER_BASE_URL` | Base URL for API requests | `https://openrouter.ai/api/v1` |

```php
OpenRouter::create([
    'apiKey' => 'or-...',
    'headers' => [
        'HTTP-Referer' => 'https://example.com',
        'X-OpenRouter-Title' => 'Example App',
    ],
]);
```

## Provider-Specific Options

```php
$result = Generate::text('Hello')
    ->model(OpenRouter::model('anthropic/claude-sonnet-4'))
    ->providerOptions('openrouter', [
        'raw' => [
            'provider' => ['order' => ['Anthropic']],
            'reasoning' => ['effort' => 'medium'],
        ],
    ])
    ->run();
```

Embedding-specific fields use the same provider namespace. For example, models that distinguish queries from documents can receive OpenRouter's documented `input_type` field:

```php
$result = Generate::embedding('Document text')
    ->model(OpenRouter::model('provider/embedding-model'))
    ->providerOptions('openrouter', ['input_type' => 'search_document'])
    ->run();
```

## Testing

```bash
composer test
```

## Links

- [OpenRouter Embeddings API](https://openrouter.ai/docs/api/reference/embeddings)
- [OpenRouter Speech-to-Text Guide](https://openrouter.ai/docs/guides/overview/multimodal/stt)
- [Core Package](https://github.com/phpaisdk/core)
