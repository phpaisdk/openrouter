<?php

declare(strict_types=1);

use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\ImageProviderInterface;
use AiSdk\Contracts\SpeechProviderInterface;
use AiSdk\Contracts\TextProviderInterface;
use AiSdk\Contracts\VideoProviderInterface;
use AiSdk\Exceptions\InvalidArgumentException;
use AiSdk\Exceptions\InvalidResponseException;
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

it('normalizes provider-neutral text usage fields', function () {
    $client = new FakeHttpClient(200, json_encode([
        'choices' => [['index' => 0, 'message' => ['content' => 'Hello from OpenRouter'], 'finish_reason' => 'stop']],
        'usage' => ['input_tokens' => 11, 'output_tokens' => 5, 'total_tokens' => 16],
    ]));
    configureOpenRouterWith($client);

    OpenRouter::create(['apiKey' => 'or-test']);

    $result = Generate::text('Hi')->model(OpenRouter::model('openai/gpt-4o'))->run();

    expect($result->usage->inputTokens)->toBe(11)
        ->and($result->usage->outputTokens)->toBe(5)
        ->and($result->usage->totalTokens)->toBe(16);
});

it('generates images through the OpenRouter vertical', function () {
    $client = new FakeHttpClient(200, json_encode([
        'created' => 1710000000,
        'data' => [['b64_json' => base64_encode('<svg></svg>'), 'media_type' => 'image/svg+xml']],
        'usage' => ['prompt_tokens' => 6, 'completion_tokens' => 8, 'total_tokens' => 14],
    ]));
    configureOpenRouterWith($client);

    OpenRouter::create(['apiKey' => 'or-test']);

    $result = Generate::image()
        ->model(OpenRouter::image('recraft/recraft-v4.1-vector'))
        ->prompt('A vector bird')
        ->count(1)
        ->aspectRatio('1:1')
        ->providerOptions('openrouter', ['raw' => ['provider' => ['options' => ['recraft' => ['style' => 'vector']]]]])
        ->run();

    expect($result->output->base64)->toBe(base64_encode('<svg></svg>'))
        ->and($result->output->mimeType)->toBe('image/svg+xml')
        ->and($result->usage->totalTokens)->toBe(14);

    $body = $client->sentBody();
    expect($body['model'])->toBe('recraft/recraft-v4.1-vector')
        ->and($body['prompt'])->toBe('A vector bird')
        ->and($body['n'])->toBe(1)
        ->and($body['aspect_ratio'])->toBe('1:1')
        ->and($body['provider']['options']['recraft']['style'])->toBe('vector')
        ->and($body)->not->toHaveKey('response_format');

    expect($client->lastRequest->getUri()->getPath())->toBe('/api/v1/images')
        ->and($client->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer or-test');
});

it('generates speech through the OpenRouter vertical', function () {
    $client = new FakeHttpClient(200, 'audio-bytes', 'audio/pcm');
    configureOpenRouterWith($client);

    OpenRouter::create(['apiKey' => 'or-test']);

    $result = Generate::speech()
        ->model(OpenRouter::speech('microsoft/mai-voice-2'))
        ->input('Welcome to the release.')
        ->voice('en-US-Harper:MAI-Voice-2')
        ->format('pcm')
        ->providerOptions('openrouter', ['raw' => ['speed' => 1.2]])
        ->run();

    expect($result->output->data)->toBe('audio-bytes')
        ->and($result->output->mimeType)->toBe('audio/pcm')
        ->and($result->providerMetadata['openrouter']['model'])->toBe('microsoft/mai-voice-2');

    $body = $client->sentBody();
    expect($body)->toMatchArray([
        'model' => 'microsoft/mai-voice-2',
        'input' => 'Welcome to the release.',
        'voice' => 'en-US-Harper:MAI-Voice-2',
        'response_format' => 'pcm',
        'speed' => 1.2,
    ]);

    expect($client->lastRequest->getUri()->getPath())->toBe('/api/v1/audio/speech')
        ->and($client->lastRequest->getHeaderLine('Accept'))->toBe('audio/pcm')
        ->and($client->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer or-test');
});

it('generates embeddings through the OpenRouter vertical', function () {
    $client = new FakeHttpClient(200, json_encode([
        'object' => 'list',
        'model' => 'openai/text-embedding-3-small',
        'data' => [['object' => 'embedding', 'index' => 0, 'embedding' => [0.1, 0.2]]],
        'usage' => ['prompt_tokens' => 4, 'total_tokens' => 4],
    ]));
    configureOpenRouterWith($client);
    OpenRouter::create(['apiKey' => 'or-test']);

    $result = Generate::embedding('A document')
        ->model(OpenRouter::embedding('openai/text-embedding-3-small'))
        ->dimensions(256)
        ->run();

    expect($result->output->vector)->toBe([0.1, 0.2])
        ->and($result->usage->inputTokens)->toBe(4)
        ->and($client->sentBody()['dimensions'])->toBe(256)
        ->and($client->lastRequest?->getUri()->getPath())->toBe('/api/v1/embeddings');
});

it('accepts opaque routed model ids for every implemented modality', function () {
    OpenRouter::create(['apiKey' => 'or-test']);

    expect(OpenRouter::model('vendor/future-text-model')->modelId())->toBe('vendor/future-text-model')
        ->and(OpenRouter::image('vendor/future-image-model')->modelId())->toBe('vendor/future-image-model')
        ->and(OpenRouter::speech('vendor/future-speech-model')->modelId())->toBe('vendor/future-speech-model')
        ->and(OpenRouter::embedding('vendor/future-embedding-model')->modelId())->toBe('vendor/future-embedding-model');
});

it('declares contracts for every implemented modality', function () {
    $provider = OpenRouter::create(['apiKey' => 'or-test']);

    expect($provider)->toBeInstanceOf(TextProviderInterface::class)
        ->and($provider)->toBeInstanceOf(ImageProviderInterface::class)
        ->and($provider)->toBeInstanceOf(SpeechProviderInterface::class)
        ->and($provider)->toBeInstanceOf(EmbeddingProviderInterface::class)
        ->and($provider)->toBeInstanceOf(VideoProviderInterface::class);
});

it('rejects an empty OpenRouter image response', function () {
    configureOpenRouterWith(new FakeHttpClient(200, json_encode([])));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::image('A red cube')
        ->model(OpenRouter::image('openai/gpt-image-1'))
        ->run();
})->throws(InvalidResponseException::class, 'no generated images');

it('rejects OpenRouter image entries without valid image data', function () {
    configureOpenRouterWith(new FakeHttpClient(200, json_encode([
        'data' => [['b64_json' => 'not-base64', 'url' => 'not-a-url']],
    ])));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::image('A red cube')
        ->model(OpenRouter::image('openai/gpt-image-1'))
        ->run();
})->throws(InvalidResponseException::class, 'invalid image data');

it('rejects an empty OpenRouter speech response', function () {
    configureOpenRouterWith(new FakeHttpClient(200, '', 'audio/mpeg'));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::speech('Read this aloud.')
        ->model(OpenRouter::speech('openai/gpt-4o-mini-tts'))
        ->run();
})->throws(InvalidResponseException::class, 'empty speech response');

it('rejects JSON responses from the OpenRouter speech endpoint', function () {
    configureOpenRouterWith(new FakeHttpClient(200, json_encode(['error' => ['message' => 'Unexpected success payload']])));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::speech('Read this aloud.')
        ->model(OpenRouter::speech('openai/gpt-4o-mini-tts'))
        ->run();
})->throws(InvalidResponseException::class, 'non-audio speech response');

it('defaults OpenRouter speech requests to pcm', function () {
    $client = new FakeHttpClient(200, 'audio-bytes', 'audio/pcm');
    configureOpenRouterWith($client);
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::speech('Read this aloud.')
        ->model(OpenRouter::speech('openai/gpt-4o-mini-tts'))
        ->run();

    expect($client->sentBody()['response_format'])->toBe('pcm')
        ->and($client->lastRequest?->getHeaderLine('Accept'))->toBe('audio/pcm');
});

it('rejects undocumented OpenRouter speech formats', function () {
    configureOpenRouterWith(new FakeHttpClient(200, 'audio-bytes', 'audio/pcm'));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::speech('Read this aloud.')
        ->model(OpenRouter::speech('openai/gpt-4o-mini-tts'))
        ->format('wav')
        ->run();
})->throws(InvalidArgumentException::class, 'only supports mp3 and pcm');

it('rejects an empty OpenRouter embedding response', function () {
    configureOpenRouterWith(new FakeHttpClient(200, json_encode([])));
    OpenRouter::create(['apiKey' => 'or-test']);

    Generate::embedding('A document')
        ->model(OpenRouter::embedding('openai/text-embedding-3-small'))
        ->run();
})->throws(InvalidResponseException::class, 'no valid embeddings');
