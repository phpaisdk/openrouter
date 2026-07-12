<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter\Models;

use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Exceptions\InvalidArgumentException;
use AiSdk\Exceptions\InvalidResponseException;
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
        $format = $request->format ?? 'pcm';
        if (! in_array($format, ['mp3', 'pcm'], true)) {
            throw new InvalidArgumentException('OpenRouter speech only supports mp3 and pcm response formats.');
        }

        $body = SpeechRequestBuilder::build($this->modelId, $this->provider(), $request);
        $body['response_format'] = $format;
        $url = Url::joinPath($this->options->baseUrl, '/audio/speech');

        $response = $this->runner($this->options->sdk)
            ->postRaw($url, $body, array_replace(['Accept' => SpeechRequestBuilder::expectedMimeType($format)], $this->options->authHeaders()), $this->provider());

        $this->ensureAudioResponse($response->getHeaderLine('Content-Type'));

        return SpeechResponseParser::parse(
            $response,
            $this->provider(),
            SpeechRequestBuilder::expectedMimeType($format),
            ['model' => $this->modelId, 'format' => $format],
        );
    }

    private function ensureAudioResponse(string $contentType): void
    {
        $mimeType = strtolower(trim(explode(';', $contentType, 2)[0]));
        if (str_starts_with($mimeType, 'audio/')) {
            return;
        }

        throw InvalidResponseException::forProvider(
            $this->provider(),
            "Provider [{$this->provider()}] returned a non-audio speech response.",
            ['contentType' => $contentType],
        );
    }
}
