<?php

declare(strict_types=1);

use AiSdk\Capability;
use AiSdk\Generate;
use AiSdk\OpenRouter;
use AiSdk\OpenRouter\Tests\Fakes\FakeHttpClient;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

afterEach(function () {
    Generate::reset();
    OpenRouter::reset();
});

function configureOpenRouterWith(FakeHttpClient $client): void
{
    $factory = new Psr17Factory;
    Generate::configure(new Sdk(
        httpClient: $client,
        requestFactory: $factory,
        streamFactory: $factory,
    ));
}

it('generates text end to end through the OpenRouter vertical', function () {
    $client = new FakeHttpClient(200, json_encode([
        'id' => 'chatcmpl_openrouter',
        'object' => 'chat.completion',
        'created' => 1710000000,
        'model' => 'openai/gpt-4o',
        'choices' => [['index' => 0, 'message' => ['content' => 'Hello from OpenRouter'], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 9, 'completion_tokens' => 3],
    ]));
    configureOpenRouterWith($client);

    OpenRouter::create([
        'apiKey' => 'or-test',
        'headers' => ['HTTP-Referer' => 'https://example.com'],
    ]);

    $result = Generate::text('Hi')->model(OpenRouter::model('openai/gpt-4o'))->run();

    expect($result->text)->toBe('Hello from OpenRouter')
        ->and($result->usage->inputTokens)->toBe(9)
        ->and($result->providerMetadata['openrouter']['id'])->toBe('chatcmpl_openrouter')
        ->and($result->providerMetadata['openrouter']['model'])->toBe('openai/gpt-4o');

    $body = $client->sentBody();
    expect($body['model'])->toBe('openai/gpt-4o')
        ->and($body['messages'][0]['role'])->toBe('user')
        ->and($body['stream'])->toBeFalse();

    expect($client->lastRequest->getUri()->getPath())->toBe('/api/v1/chat/completions')
        ->and($client->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer or-test')
        ->and($client->lastRequest->getHeaderLine('HTTP-Referer'))->toBe('https://example.com');
});

it('loads routed model capabilities from resources models json', function () {
    OpenRouter::create(['apiKey' => 'or-test']);

    expect(OpenRouter::model('anthropic/claude-sonnet-4')->supports(Capability::Reasoning))->toBeTrue()
        ->and(OpenRouter::model('x-ai/grok-4.3')->supports(Capability::ImageInput))->toBeTrue();
});
