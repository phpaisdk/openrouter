<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter;

use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\ImageModelInterface;
use AiSdk\Contracts\ImageProviderInterface;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Contracts\SpeechProviderInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Contracts\TextProviderInterface;
use AiSdk\Contracts\TranscriptionModelInterface;
use AiSdk\Contracts\TranscriptionProviderInterface;
use AiSdk\Contracts\VideoModelInterface;
use AiSdk\Contracts\VideoProviderInterface;
use AiSdk\OpenRouter\Models\OpenRouterEmbeddingModel;
use AiSdk\OpenRouter\Models\OpenRouterImageModel;
use AiSdk\OpenRouter\Models\OpenRouterSpeechModel;
use AiSdk\OpenRouter\Models\OpenRouterTextModel;
use AiSdk\OpenRouter\Models\OpenRouterTranscriptionModel;
use AiSdk\OpenRouter\Models\OpenRouterVideoModel;

final class OpenRouterProvider extends BaseProvider implements EmbeddingProviderInterface, ImageProviderInterface, SpeechProviderInterface, TextProviderInterface, TranscriptionProviderInterface, VideoProviderInterface
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

    public function transcriptionModel(string $modelId): TranscriptionModelInterface
    {
        return new OpenRouterTranscriptionModel($modelId, $this->options);
    }

    public function embeddingModel(string $modelId): EmbeddingModelInterface
    {
        return new OpenRouterEmbeddingModel($modelId, $this->options);
    }

    public function videoModel(string $modelId): VideoModelInterface
    {
        return new OpenRouterVideoModel($modelId, $this->options);
    }
}
