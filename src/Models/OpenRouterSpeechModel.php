<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter\Models;

use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\OpenAICompatible\SpeechRequestBuilder;
use AiSdk\OpenAICompatible\SpeechResponseParser;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\Requests\SpeechRequest;
use AiSdk\Responses\SpeechResponse;
use AiSdk\Utils\Support\Url;

final class OpenRouterSpeechModel extends BaseModel implements SpeechModelInterface
{
    public function __construct(
        private readonly string $modelId,
        private readonly OpenRouterOptions $options,
    ) {}

    public function provider(): string
    {
        return OpenRouterOptions::PROVIDER_NAME;
    }

    public function modelId(): string
    {
        return $this->modelId;
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
}
