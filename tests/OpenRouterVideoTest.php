<?php

declare(strict_types=1);

use AiSdk\Generate;
use AiSdk\OpenRouter;
use AiSdk\OpenRouter\Models\OpenRouterVideoModel;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\OpenRouter\Tests\Fakes\FakeHttpClient;
use AiSdk\Responses\VideoJob;
use AiSdk\Responses\VideoJobStatus;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

function openRouterVideoOptions(FakeHttpClient $c): OpenRouterOptions
{
    $f = new Psr17Factory;

    return new OpenRouterOptions('key', sdk: new Sdk($c, $f, $f));
}
it('starts OpenRouter video jobs', function () {
    $c = new FakeHttpClient(202, json_encode(['id' => 'job-1', 'polling_url' => 'https://openrouter.ai/api/v1/videos/job-1', 'status' => 'pending']));
    $f = new Psr17Factory;
    OpenRouter::create([
        'apiKey' => 'key',
        'sdk' => new Sdk($c, $f, $f),
    ]);

    $j = Generate::video('Flower')
        ->model(OpenRouter::model('google/veo-3.1'))
        ->job();

    expect($j->id)->toBe('job-1')->and($c->lastRequest?->getUri()->getPath())->toBe('/api/v1/videos');
});
it('polls completed OpenRouter video jobs', function () {
    $c = new FakeHttpClient(200, json_encode(['id' => 'job-1', 'status' => 'completed', 'unsigned_urls' => ['https://openrouter.ai/content.mp4']]));
    $m = new OpenRouterVideoModel('google/veo-3.1', openRouterVideoOptions($c));
    $j = $m->poll(new VideoJob('job-1', 'openrouter', 'google/veo-3.1'));
    expect($j->status)->toBe(VideoJobStatus::Succeeded)->and($j->result?->url)->toBe('https://openrouter.ai/content.mp4');
});
