<?php

namespace App;

use App\Models\ScCredentials;
use DOMDocument;
use DOMException;
use DOMXPath;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScClient
{
    const AUTH_BASE_URL = 'https://webauth.southernco.com';
    const SC_WEB_BASE_URL = 'https://customerservice2.southerncompany.com';
    const SC_API_BASE_URL = 'https://customerservice2api.southerncompany.com';

    public function __construct(
        public ScCredentials $credentials,
        protected Http $client = new Http(),
    ) {
    }

    public function getAccounts(): array
    {
        return $this->client::withToken($this->credentials->jwt)
            ->get(self::SC_API_BASE_URL . '/api/account/getAllAccounts')
            ->throw()
            ->json();
    }

    public function getJwt(): string
    {
        [$aft, $authParams] = $this->getTempTokenAndParams();
        $loginResponse = $this->getLoginResponse($aft, $authParams);
        $tempToken = $this->getTempTokenFromLoginResponse($loginResponse);
        $jwtRetrievalToken = $this->getJwtRetrievalToken($tempToken);

        $token = $this->client::withCookies([
            'SouthernJwtCookie' => $jwtRetrievalToken,
        ], Str::after(static::SC_WEB_BASE_URL, 'https://'))
            ->get(static::SC_WEB_BASE_URL . '/Account/LoginValidated/JwtToken')
            ->throw()
            ->cookies()
            ->getCookieByName('ScJwtToken')
            ?->getValue();

        throw_if(!$token, new DOMException('Missing ScJwtToken cookie.'));

        return $token;
    }

    private function getTempTokenAndParams(): array
    {
        $authGetParams = [
            'WL_Type' => 'E',
            'WL_AppId' => 'OCCEvo',
            'Origin' => static::SC_WEB_BASE_URL,
            'WL_ReturnMethod' => 'FV',
            'WL_Expire' => '1',
            'ForgotInfoLink' => 'undefined',
            'ForgotPasswordLink' => 'undefined',
            'WL_ReturnUrl' => static::SC_WEB_BASE_URL . ':443/Account/LoginValidated?ReturnUrl=/Login',
            'WL_RegisterUrl' => static::SC_WEB_BASE_URL . ':443/MyProfile/Register?mnuopco=SCS',
        ];

        $loginForm = $this->client::get(static::AUTH_BASE_URL . '/account/login', $authGetParams)->throw()->body();
        $doc = new DOMDocument();
        $doc->loadHTML($loginForm);
        $aft = $doc->getElementById('webauth-aft')?->getAttribute('data-aft');
        $params = json_decode(
            urldecode($doc->getElementById('webauth-params')?->getAttribute('data-params')),
            true,
        );

        throw_if(!$aft || !$params, new DOMException('Missing AFT token or params.'));

        return [$aft, $params];
    }

    private function getLoginResponse(string $requestVerificationToken, array $params): Response
    {
        return $this->client::asJson()->withHeaders([
            'RequestVerificationToken' => $requestVerificationToken,
        ])->post(static::AUTH_BASE_URL . '/api/login', [
            'username' => $this->credentials->username,
            'password' => $this->credentials->password,
            'params' => $params,
            'targetPage' => 1,
        ])->throw();
    }

    private function getTempTokenFromLoginResponse(Response $response): string
    {
        ($form = new DOMDocument())->loadHTML('<div>' . $response->json('data.html') . '</div>');

        $token = (new DOMXPath($form))
            ->query('//input[@name="ScWebToken"]')
            ->item(0)
            ?->attributes
            ->getNamedItem('value')
            ?->nodeValue;

        throw_if(!$token, new DOMException('Missing ScWebToken input.'));

        return $token;
    }

    private function getJwtRetrievalToken(string $tempToken): string
    {
        $token = $this->client::asForm()
            ->post(static::SC_WEB_BASE_URL . '/Account/LoginComplete?ReturnUrl=null', ['ScWebToken' => (string) $tempToken])
            ->throw()
            ->cookies()
            ->getCookieByName('SouthernJwtCookie')
            ?->getValue();

        throw_if(!$token, new DOMException('Missing SouthernJwtCookie value.'));

        return $token;
    }
}