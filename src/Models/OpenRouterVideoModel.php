<?php

declare(strict_types=1);

namespace AiSdk\OpenRouter\Models;

use AiSdk\Content;
use AiSdk\ContentSource;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\VideoModelInterface;
use AiSdk\Exceptions\InvalidArgumentException;
use AiSdk\Exceptions\InvalidResponseException;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\Requests\VideoRequest;
use AiSdk\Responses\VideoJob;
use AiSdk\Responses\VideoJobStatus;
use AiSdk\Results\VideoData;
use AiSdk\Support\Usage;
use AiSdk\Utils\Support\Url;

final class OpenRouterVideoModel extends BaseModel implements VideoModelInterface
{
    public function __construct(private readonly string $modelId, private readonly OpenRouterOptions $options) {}

    public function provider(): string
    {
        return OpenRouterOptions::PROVIDER_NAME;
    }

    public function modelId(): string
    {
        return $this->modelId;
    }

    public function generate(VideoRequest $r): VideoJob
    {
        if ($r->video !== null) {
            throw new InvalidArgumentException('OpenRouter video generation accepts image references, not a source video.');
        }
        $o = $r->providerOptionsFor($this->provider());
        $body = array_filter(['model' => $this->modelId, 'prompt' => $r->prompt, 'duration' => $r->output?->duration, 'resolution' => $r->output?->resolution, 'aspect_ratio' => $r->output?->aspectRatio, 'seed' => $r->output?->seed], fn ($v) => $v !== null);
        if ($r->image) {
            $body['frame_images'] = [['type' => 'image_url', 'image_url' => ['url' => $this->media($r->image)], 'frame_type' => 'first_frame']];
        }if (is_array($o['frameImages'] ?? null)) {
            $body['frame_images'] = $o['frameImages'];
        }if (is_array($o['inputReferences'] ?? null)) {
            $body['input_references'] = $o['inputReferences'];
        }$body = array_replace($body, array_diff_key($o, array_flip(['pollIntervalMs', 'pollTimeoutMs', 'frameImages', 'inputReferences'])));
        $p = $this->runner($this->options->sdk)->postJson(Url::joinPath($this->options->baseUrl, '/videos'), $body, $this->options->authHeaders(), $this->provider());
        $id = $p['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw InvalidResponseException::forProvider($this->provider(), 'OpenRouter returned no video job id.', ['body' => $p]);
        }

        return new VideoJob($id, $this->provider(), $this->modelId, rawResponse: $p, providerMetadata: [$this->provider() => ['jobId' => $id, 'pollingUrl' => $p['polling_url'] ?? null, 'pollIntervalMs' => (int) ($o['pollIntervalMs'] ?? 5000), 'pollTimeoutMs' => (int) ($o['pollTimeoutMs'] ?? 600000)]]);
    }

    public function poll(VideoJob $job): VideoJob
    {
        $url = $job->providerMetadata[$this->provider()]['pollingUrl'] ?? Url::joinPath($this->options->baseUrl, '/videos/'.rawurlencode($job->id));
        $p = $this->runner($this->options->sdk)->getJson((string) $url, $this->options->authHeaders(), $this->provider());
        $s = (string) ($p['status'] ?? 'pending');
        if ($s === 'completed') {
            $url = $p['unsigned_urls'][0] ?? Url::joinPath($this->options->baseUrl, '/videos/'.rawurlencode($job->id).'/content?index=0');

            return new VideoJob($job->id, $job->provider, $job->modelId, VideoJobStatus::Succeeded, new VideoData(url: (string) $url), usage: Usage::empty(), rawResponse: $p, providerMetadata: $job->providerMetadata);
        } $status = match ($s) {
            'failed' => VideoJobStatus::Failed,'cancelled' => VideoJobStatus::Canceled,'expired' => VideoJobStatus::Expired,default => VideoJobStatus::Running
        };

        return new VideoJob($job->id, $job->provider, $job->modelId, $status, errorMessage: $status === VideoJobStatus::Running ? null : (string) ($p['error'] ?? 'OpenRouter video generation '.$s.'.'), rawResponse: $p, providerMetadata: $job->providerMetadata);
    }

    private function media(Content $c): string
    {
        return $c->source() === ContentSource::Url ? (string) $c->url() : 'data:'.$c->mimeType().';base64,'.$c->base64Data();
    }
}
