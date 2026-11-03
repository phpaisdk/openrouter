# PHP AI SDK — OpenRouter

Access multiple AI providers through OpenRouter using the PHP AI SDK generation API. Part of the framework-agnostic [PHP AI SDK](https://phpaisdk.com).

## Installation

```bash
composer require aisdk/openrouter
```

Set `OPENROUTER_API_KEY` in your environment.

## Usage

```php
use AiSdk\Generate;
use AiSdk\OpenRouter;

$result = Generate::text('Explain closures in PHP in one sentence.')
    ->model(OpenRouter::model('anthropic/claude-sonnet-5.5'))
    ->run();

echo $result->text;
```

## Documentation

[Read the documentation](https://phpaisdk.com/docs/0.x/providers/openrouter) for configuration, supported features, and more examples.

[Contributing](https://github.com/phpaisdk/.github/blob/main/CONTRIBUTING.md) · [Support](https://github.com/phpaisdk/.github/blob/main/SUPPORT.md)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
