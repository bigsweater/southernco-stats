<?php

namespace App;

use App\Exceptions\DataMissingFromSuccessfulResponse;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use DOMDocument;
use DOMException;
use DOMXPath;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Support\Traits\ForwardsCalls;

class ScClient
{
    use ForwardsCalls;

    const AUTH_BASE_URL = 'https://webauth.southernco.com';
    const SC_WEB_BASE_URL = 'https://customerservice2.southerncompany.com';
    const SC_API_BASE_URL = 'https://customerservice2api.southerncompany.com';

    public function __construct(
        public ScCredentials $credentials,
        protected Http|Factory $client = new Http(),
    ) {
    }

    public function authenticatedClient(): PendingRequest
    {
        if ($this->credentials->updated_at->diffInHours(now()) > 2) {
            $this->credentials->jwt = $this->getJwt();
            $this->credentials->save();
        }

        return $this->client::withToken($this->credentials->fresh()->jwt);
    }

    public function getAccounts(): array
    {
        return $this->authenticatedClient()
            ->get(self::SC_API_BASE_URL . '/api/account/getAllAccounts')
            ->throw()
            ->json('Data');
    }

    public function getServicePointNumber(ScAccount $account): array
    {
        return $this->authenticatedClient()
            ->get(self::SC_API_BASE_URL . "/api/MyPowerUsage/getMPUBasicAccountInformation/{$account->account_number}/GPC")
            ->throw()
            ->json('Data.meterAndServicePoints.0');
    }

    public function getHourly(
        ScAccount $account,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $response = $this->authenticatedClient()
            ->get(self::SC_API_BASE_URL . "/api/MyPowerUsage/MPUData/{$account->account_number}/Hourly", [
                'StartDate' => $startDate->format('m/d/Y'),
                'EndDate' => $endDate->format('m/d/Y'),
                'ServicePointNumber' => $account->service_point_number,
                'intervalBehavior' => 'Automatic',
                'OPCO' => $account->company->name,
            ])
            ->throw();

        return $this->getDataFromResponse($response);
    }

    public function getDaily(
        ScAccount $account,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $response = $this->authenticatedClient()
            ->get(self::SC_API_BASE_URL . "/api/MyPowerUsage/MPUData/{$account->account_number}/Daily", [
                'StartDate' => $startDate->format('m/d/Y'),
                'EndDate' => $endDate->format('m/d/Y'),
                'ServicePointNumber' => $account->service_point_number,
                'intervalBehavior' => 'Automatic',
                'OPCO' => $account->company->name,
            ])
            ->throw();

        return $this->getDataFromResponse($response);
    }

    public function getMonthly(
        ScAccount $account,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): array {
        $startDate = $startDate ?? now()->subYears(1);
        $endDate = $endDate ?? now();

        $response = $this->authenticatedClient()
            ->get(self::SC_API_BASE_URL . "/api/MyPowerUsage/MPUData/{$account->account_number}/Monthly", [
                'StartDate' => $startDate->format('m/d/Y'),
                'EndDate' => $endDate->format('m/d/Y'),
                'ServicePointNumber' => $account->service_point_number,
                'intervalBehavior' => 'Automatic',
                'OPCO' => $account->company->name,
            ])
            ->throw();

        return $this->getDataFromResponse($response);
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

    public static function fake(ScCredentials $credentials): static
    {
        return new static($credentials, Http::fake());
    }

    private function getDataFromResponse(Response $response): array
    {
        return json_decode(
            Str::of($response->json('Data.Data', '{}'))
                ->whenEmpty(fn () => '{}'),
            associative: true
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardCallTo($this->client, $name, $arguments);
    }
}
