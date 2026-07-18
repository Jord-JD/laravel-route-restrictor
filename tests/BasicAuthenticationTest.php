<?php

namespace Tests;

use Illuminate\Http\Request;
use JordJD\LaravelRouteRestrictor\Http\Middleware\BasicAuthentication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicAuthenticationTest extends TestCase
{
    public function testRouteCredentialsAuthenticateFromTheRequest()
    {
        $request = Request::create('/', 'GET', array(), array(), array(), array(
            'PHP_AUTH_USER' => 'preview',
            'PHP_AUTH_PW' => 'secret',
        ));

        $result = (new BasicAuthentication())->handle($request, function () {
            return 'allowed';
        }, 'preview', 'secret');

        $this->assertSame('allowed', $result);
    }

    public function testIncorrectRouteCredentialsAreRejectedEvenUnderCli()
    {
        $request = Request::create('/', 'GET', array(), array(), array(), array(
            'PHP_AUTH_USER' => 'preview',
            'PHP_AUTH_PW' => 'wrong',
        ));

        try {
            (new BasicAuthentication())->handle($request, function () {
                return 'allowed';
            }, 'preview', 'secret');
            $this->fail('Incorrect credentials should be rejected.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Basic', $exception->getHeaders()['WWW-Authenticate']);
        }
    }

    public function testRawAuthorizationHeaderSupportsPasswordsContainingColons()
    {
        $request = Request::create('/', 'GET', array(), array(), array(), array(
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('preview:secret:part'),
        ));

        $result = (new BasicAuthentication())->handle($request, function () {
            return 'allowed';
        }, 'preview', 'secret:part');

        $this->assertSame('allowed', $result);
    }

    public function testDisableParameterBypassesRestriction()
    {
        $request = Request::create('/');

        $result = (new BasicAuthentication())->handle($request, function () {
            return 'allowed';
        }, 'disable');

        $this->assertSame('allowed', $result);
    }
}
