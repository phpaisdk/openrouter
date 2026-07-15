<?php

declare(strict_types=1);

namespace AiSdk;

use AiSdk\Contracts\Model;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\OpenRouter\OpenRouterProvider;

final class OpenRouter
{
    private static ?OpenRouterProvider $default = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public static function create(array $config = []): OpenRouterProvider
    {
        return self::$default = new OpenRouterProvider(OpenRouterOptions::fromArray($config));
    }

    public static function default(): OpenRouterProvider
    {
        return self::$default ??= self::create();
    }

    public static function reset(): void
    {
        self::$default = null;
    }

    public static function model(string $modelId): Model
    {
        return self::default()->model($modelId);
    }
}
