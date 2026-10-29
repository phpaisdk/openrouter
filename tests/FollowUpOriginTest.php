<?php

declare(strict_types=1);

use AiSdk\Exceptions\InvalidResponseException;
use AiSdk\OpenRouter\Models\OpenRouterVideoModel;
use AiSdk\OpenRouter\OpenRouterOptions;
use AiSdk\OpenRouter\Tests\Fakes\SequenceHttpClient;
use AiSdk\Requests\VideoRequest;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

require_once __DIR__ . '/Fakes/SequenceHttpClient.php';

function followUp(string $url, string $baseUrl = 'https://openrouter.ai/api/v1'): array
{
    $client = new SequenceHttpClient([
        new Response(200, [], json_encode(['id' => 'job', 'polling_url' => $url], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['status' => 'completed', 'unsigned_urls' => ['https://media.example/video.mp4']], JSON_THROW_ON_ERROR)),
    ]);
    $factory = new Psr17Factory();
    $model = new OpenRouterVideoModel('video', new OpenRouterOptions(apiKey: 'secret', baseUrl: $baseUrl, sdk: new Sdk($client, $factory, $factory)));
    $run = fn() => $model->poll($model->generate(new VideoRequest('Hello')));

    return [$run, $client];
}

it('permits follow-ups only on the configured origin', function (string $url) {
    [$run, $client] = followUp($url);
    $result = $run();
    expect($result->status)->toBe(\AiSdk\Responses\VideoJobStatus::Succeeded);
    expect($client->requests)->toHaveCount(2);
    expect(end($client->requests)->getHeaderLine('Authorization'))->toBe('Bearer secret');
})->with([
    'https://openrouter.ai/result?token=signed',
    'https://OPENROUTER.AI:443/result',
]);

it('rejects untrusted follow-up origins before sending credentials', function (string $url) {
    [$run, $client] = followUp($url);

    try {
        $run();
        test()->fail('Expected origin rejection.');
    } catch (InvalidResponseException $error) {
        expect($error->getMessage())->toBe('Provider follow-up URL has an untrusted origin.');
        expect((string) $error)->not->toContain('signed-secret');
    }
    expect($client->requests)->toHaveCount(1);
})->with([
    'https://evil.test/result?token=signed-secret',
    'http://openrouter.ai/result',
    'https://openrouter.ai:444/result',
    'https://openrouter.ai@evil.test/result',
    'https://user:password@openrouter.ai/result',
    'https://openrouter.ai.evil.test/result',
    'https://openrouter.ai\\@evil.test/result',
    '/relative/result',
    '//openrouter.ai/result',
    'https://openrouter.ai:99999/result',
    'https://openrouter.ai:0/result',
    "https://openrouter.ai/result\n",
    'file:///result',
]);

it('honors an explicitly configured non-default port', function () {
    [$run, $client] = followUp('https://custom.example:8443/result', 'https://custom.example:8443/v1');
    $result = $run();
    expect($result->status)->toBe(\AiSdk\Responses\VideoJobStatus::Succeeded);
    expect($client->requests)->toHaveCount(2);
});

it('validates persisted polling metadata before any request', function (mixed $url) {
    $client = new SequenceHttpClient([]);
    $factory = new Psr17Factory();
    $model = new OpenRouterVideoModel('video', new OpenRouterOptions(apiKey: 'secret', sdk: new Sdk($client, $factory, $factory)));
    $job = new \AiSdk\Responses\VideoJob('job', 'openrouter', 'video', providerMetadata: ['openrouter' => ['pollingUrl' => $url]]);
    expect(fn() => $model->poll($job))->toThrow(InvalidResponseException::class);
    expect($client->requests)->toHaveCount(0);
})->with(['https://openrouter.ai@evil.test/', '', 42, [['https://openrouter.ai/result']]]);

it('builds fallback polling URLs from the configured base and encoded job id', function () {
    $client = new SequenceHttpClient([new Response(200, [], '{"status":"pending"}')]);
    $factory = new Psr17Factory();
    $model = new OpenRouterVideoModel('video', new OpenRouterOptions(apiKey: 'secret', baseUrl: 'https://custom.example:8443/api/v1', sdk: new Sdk($client, $factory, $factory)));
    $model->poll(new \AiSdk\Responses\VideoJob('../https://evil.test', 'openrouter', 'video'));
    expect((string) $client->requests[0]->getUri())->toBe('https://custom.example:8443/api/v1/videos/..%2Fhttps%3A%2F%2Fevil.test');
    expect($client->requests[0]->getHeaderLine('Authorization'))->toBe('Bearer secret');
});

it('does not allow the default origin for a custom configuration', function () {
    [$run, $client] = followUp('https://openrouter.ai/api/v1/videos/job', 'https://custom.example/api/v1');
    expect($run)->toThrow(InvalidResponseException::class);
    expect($client->requests)->toHaveCount(1);
});
