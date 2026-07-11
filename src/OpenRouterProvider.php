<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter;

use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\ImageModelInterface;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\OpenRouter\Models\OpenRouterImageModel;
use AiSdk\OpenRouter\Models\OpenRouterSpeechModel;
use AiSdk\OpenRouter\Models\OpenRouterTextModel;

final class OpenRouterProvider extends BaseProvider
{
    public function __construct(public readonly OpenRouterOptions $options) {}

    public function name(): string
    {
        return OpenRouterOptions::PROVIDER_NAME;
    }

    public function textModel(string $modelId): TextModelInterface
    {
        return new OpenRouterTextModel($modelId, $this->options);
    }

    public function imageModel(string $modelId): ImageModelInterface
    {
        return new OpenRouterImageModel($modelId, $this->options);
    }

    public function speechModel(string $modelId): SpeechModelInterface
    {
        return new OpenRouterSpeechModel($modelId, $this->options);
    }
}
