<?php

namespace Tests\Unit;

use App\Services\CaptchaService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaServiceTest extends TestCase
{
    public function test_recaptcha_token_is_verified_with_google(): void
    {
        config([
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.expected_hostname' => 'papertrailpsu.com',
            'services.recaptcha.testing_bypass' => false,
        ]);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'hostname' => 'papertrailpsu.com',
            ]),
        ]);
        $request = Request::create('/login', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        $this->assertTrue(app(CaptchaService::class)->verify($request, 'browser-token'));
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'browser-token'
            && $request['remoteip'] === '127.0.0.1'
        );
    }

    public function test_recaptcha_failure_is_rejected(): void
    {
        config([
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.expected_hostname' => null,
            'services.recaptcha.testing_bypass' => false,
        ]);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);
        $request = Request::create('/login', 'POST');

        $this->assertFalse(app(CaptchaService::class)->verify($request, 'invalid-token'));
    }
}
