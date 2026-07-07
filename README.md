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

## Image Generation

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::image()
    ->model(OpenRouter::image('openai/gpt-image-1'))
    ->prompt('A clean app icon for a PHP AI SDK')
    ->size('1024x1024')
    ->run();

$result->output->save(__DIR__.'/icon.png');
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

## Testing

```bash
composer test
```
