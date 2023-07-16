<?php

use App\Traits\HasMemory;
use Illuminate\Support\Facades\Http;

test('it stores results in the cache array', function () {
    Http::fake(['*' => Http::response('yo')]);

    $rememberer = new class
    {
        use HasMemory;

        public function getResponse()
        {
            return $this->remember('response', fn () => Http::get('localhost'));
        }
    };

    $rememberer->getResponse();

    $cache = (new ReflectionClass($rememberer))
        ->getProperty('cache')
        ->getValue($rememberer);

    expect($cache['response'])->not->toBeNull();
    expect($cache['response']->body())->toBe('yo');
});

test('it does not callback if value exists in cache', function () {
    Http::fake(['*' => Http::response('yo')]);

    $rememberer = new class
    {
        use HasMemory;

        public function getResponse()
        {
            return $this->remember('response', fn () => Http::get('localhost'));
        }
    };

    (new ReflectionClass($rememberer))
        ->getProperty('cache')
        ->setValue($rememberer, ['response' => 'yo']);

    $response = $rememberer->getResponse();

    Http::assertNothingSent();
    expect($response)->toBe('yo');
});
