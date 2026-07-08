<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter\Models;

use AiSdk\Capability;
use AiSdk\CapabilitySupport;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\OpenAICompatible\SpeechRequestBuilder;
use AiSdk\OpenAICompatible\SpeechResponseParser;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\Requests\SpeechRequest;
use AiSdk\Responses\SpeechResponse;
use AiSdk\Support\ModelCatalog;
use AiSdk\Support\ModelRegistry;
use AiSdk\Utils\Support\Url;

final class OpenRouterSpeechModel extends BaseModel implements SpeechModelInterface
{
    public function __construct(
        private readonly string $modelId,
        private readonly OpenRouterOptions $options,
        private readonly ?ModelRegistry $registry = null,
    ) {}

    public function provider(): string
    {
        return OpenRouterOptions::PROVIDER_NAME;
    }

    public function modelId(): string
    {
        return $this->modelId;
    }

    /**
     * @return array<int, Capability>
     */
    public function capabilities(): array
    {
        $definition = $this->registry?->resolve($this->provider(), $this->modelId);
        if ($definition !== null) {
            return $this->configuredCapabilities($definition->capabilities);
        }

        return $this->configuredCapabilities($this->catalog()->capabilities($this->modelId));
    }

    public function capability(Capability $capability): CapabilitySupport
    {
        $configured = $this->configuredCapability($capability);
        if ($configured !== null) {
            return $configured;
        }

        $registered = $this->registry?->capability($this->provider(), $this->modelId, $capability);
        if ($registered !== null) {
            return $registered;
        }

        return $this->catalog()->capability($this->modelId, $capability);
    }

    public function generate(SpeechRequest $request): SpeechResponse
    {
        $body = SpeechRequestBuilder::build($this->modelId, $this->provider(), $request);
        $format = (string) ($body['response_format'] ?? 'mp3');
        $url = Url::joinPath($this->options->baseUrl, '/audio/speech');

        $response = $this->runner($this->options->sdk)
            ->postRaw($url, $body, array_replace(['Accept' => SpeechRequestBuilder::expectedMimeType($format)], $this->options->authHeaders()), $this->provider());

        return SpeechResponseParser::parse(
            $response,
            $this->provider(),
            SpeechRequestBuilder::expectedMimeType($format),
            ['model' => $this->modelId, 'format' => $format],
        );
    }

    private function catalog(): ModelCatalog
    {
        return ModelCatalog::fromFile(dirname(__DIR__, 2).'/resources/models.json');
    }
}
