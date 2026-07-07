<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter;

use AiSdk\Support\Sdk;
use AiSdk\Utils\Support\Env;
use AiSdk\Utils\Support\Url;

final class OpenRouterOptions
{
    public const string DEFAULT_BASE_URL = 'https://openrouter.ai/api/v1';

    public const string PROVIDER_NAME = 'openrouter';

    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly array $headers = [],
        public readonly ?Sdk $sdk = null,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config = []): self
    {
        $apiKey = Env::loadApiKey(
            isset($config['apiKey']) ? (string) $config['apiKey'] : null,
            'OPENROUTER_API_KEY',
            self::PROVIDER_NAME,
        );

        $baseUrl = Url::withoutTrailingSlash(
            Env::loadOptionalSetting(isset($config['baseUrl']) ? (string) $config['baseUrl'] : null, 'OPENROUTER_BASE_URL')
                ?? self::DEFAULT_BASE_URL,
        );

        /** @var array<string, string> $headers */
        $headers = isset($config['headers']) && is_array($config['headers']) ? $config['headers'] : [];
        $sdk = $config['sdk'] ?? null;

        return new self(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            headers: $headers,
            sdk: $sdk instanceof Sdk ? $sdk : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function authHeaders(): array
    {
        return array_merge(['Authorization' => 'Bearer '.$this->apiKey], $this->headers);
    }
}
