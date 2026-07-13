<?php

declare(strict_types=1);

use AiSdk\Content;
use AiSdk\Contracts\TranscriptionProviderInterface;
use AiSdk\Generate;
use AiSdk\OpenRouter;
use AiSdk\OpenRouter\Tests\Fakes\FakeHttpClient;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

afterEach(function () {
    Generate::reset();
    OpenRouter::reset();
});

it('uses OpenRouter native transcription endpoint', function () {
    $client = new FakeHttpClient(200, '{"text":"OpenRouter transcript.","usage":{"total_tokens":9}}');
    $factory = new Psr17Factory;
    Generate::configure(new Sdk($client, $factory, $factory));
    OpenRouter::create(['apiKey' => 'or-test']);

    expect(OpenRouter::default())->toBeInstanceOf(TranscriptionProviderInterface::class);

    $result = Generate::transcription(Content::audio('flac', 'audio/flac', 'clip.flac'))
        ->model(OpenRouter::transcription('openai/whisper-large-v3'))
        ->run();

    expect($result->output->text)->toBe('OpenRouter transcript.')
        ->and($result->usage->totalTokens)->toBe(9)
        ->and((string) $client->lastRequest?->getUri())->toBe('https://openrouter.ai/api/v1/audio/transcriptions')
        ->and((string) $client->lastRequest?->getBody())->toContain('openai/whisper-large-v3', 'clip.flac');
});
