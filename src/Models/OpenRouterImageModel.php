<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter\Models;

use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\ImageModelInterface;
use AiSdk\OpenAICompatible\ImageRequestBuilder;
use AiSdk\OpenAICompatible\ImageResponseParser;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\Requests\ImageRequest;
use AiSdk\Responses\ImageResponse;
use AiSdk\Utils\Support\Url;

final class OpenRouterImageModel extends BaseModel implements ImageModelInterface
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

    public function generate(ImageRequest $request): ImageResponse
    {
        $body = ImageRequestBuilder::build(
            $this->modelId,
            $this->provider(),
            $request,
            [
                'aspectRatioParameter' => 'aspect_ratio',
                'inferSizeFromAspectRatio' => false,
                'includeResponseFormat' => false,
            ],
        );
        $url = Url::joinPath($this->options->baseUrl, '/images');

        $payload = $this->runner($this->options->sdk)
            ->postJson($url, $body, $this->options->authHeaders(), $this->provider());

        return ImageResponseParser::parse($payload, $this->provider());
    }
}
