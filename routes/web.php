<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $authUrl = 'https://webauth.southernco.com/';
    $authPath = 'account/login';
    $authGetParams = [
        'WL_Type' => 'E',
        'WL_AppId' => 'OCCEvo',
        'Origin' => 'https://customerservice2.southerncompany.com',
        'WL_ReturnMethod' => 'FV',
        'WL_Expire' => '1',
        'ForgotInfoLink' => 'undefined',
        'ForgotPasswordLink' => 'undefined',
        'WL_ReturnUrl' => 'https://customerservice2.southerncompany.com:443/Account/LoginValidated?ReturnUrl=/Login',
        'WL_RegisterUrl' => 'https://customerservice2.southerncompany.com:443/MyProfile/Register?mnuopco=SCS',
    ];
    $webauthAftElId = 'webauth-aft';
    $webauthParamsElId = 'webauth-params';

    $loginForm = Http::get($authUrl . $authPath, $authGetParams)->body();
    $doc = new DOMDocument();
    $doc->loadHTML($loginForm);
    $webauthAft = $doc->getElementById($webauthAftElId);
    $webauthParams = $doc->getElementById($webauthParamsElId);
    $aft = $webauthAft->getAttribute('data-aft');
    $params = json_decode(urldecode($webauthParams->getAttribute('data-params')));

    $response = Http::asJson()->withHeaders([
        'RequestVerificationToken' => $aft,
    ])->post($authUrl . 'api/login', [
        'username' => '',
        'password' => '',
        'params' => $params,
        'targetPage' => 1,
    ]);

    ($form = new DOMDocument())->loadHTML('<div>'.$response->json('data.html').'</div>');
    $token = (new DOMXPath($form))->query('//input[@name="ScWebToken"]')->item(0)->attributes->getNamedItem('value')->nodeValue;

    $jwtRetrievalToken = Http::asForm()
        ->post($authGetParams['Origin'] . '/Account/LoginComplete?ReturnUrl=null', ['ScWebToken' => (string) $token])
        ->cookies()
        ->getCookieByName('SouthernJwtCookie')
        ->getValue();

    $jwt = Http::withCookies([
        'SouthernJwtCookie' => $jwtRetrievalToken,
    ], 'customerservice2.southerncompany.com')
        ->get($authGetParams['Origin'] . '/Account/LoginValidated/JwtToken')
        ->cookies()->getCookieByName('ScJwtToken')->getValue();

    return Http::withToken($jwt)
        ->get('https://customerservice2api.southerncompany.com/api/account/getAllAccounts')
        ->json();
});
