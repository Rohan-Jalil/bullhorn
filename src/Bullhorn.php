<?php

/**
 * Created by PhpStorm.
 * Date: 5/19/17
 * Time: 8:05 PM
 */

namespace NorthCreek\Bullhorn;

use NorthCreek\Bullhorn\BullhornOAuth\OAuth2\Service\Bullhorn as BullhornService;
use NorthCreek\Bullhorn\Config\BullhornConfig;

abstract class Bullhorn
{
    private $_service;
    private $_credentials;
    private $_storage;
    private $_httpClient;
    private $_scopes;
    private $_config;
    private $_sessionKey;
    private $_baseUrl;

    /**
     * @return mixed
     */
    public function getSessionKey ()
    {
        return $this->_sessionKey;
    }

    /**
     * @param mixed $sessionKey
     */
    public function setSessionKey ($sessionKey)
    {
        $this->_sessionKey = $sessionKey;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl ()
    {
        return $this->_baseUrl;
    }

    /**
     * @param mixed $baseUrl
     */
    public function setBaseUrl ($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * @return Bullhorn
     */
    public function getService ()
    {
        return $this->_service;
    }

    /**
     * @return mixed
     */
    public function getCredentials ()
    {
        return $this->_credentials;
    }

    /**
     * @return mixed
     */
    public function getStorage ()
    {
        return $this->_storage;
    }

    /**
     * @return mixed
     */
    public function getHttpClient ()
    {
        return $this->_httpClient;
    }

    /**
     * @return array
     */
    public function getScopes ()
    {
        return $this->_scopes;
    }

    /**
     * @return BullhornConfig
     */
    public function getConfig ()
    {
        return $this->_config;
    }

    /**
     * BullhornClient constructor.
     *
     * @param $credentials
     * @param $httpClient
     * @param $storage
     * @param array $scopes
     */
    public function __construct ($credentials, $httpClient, $storage, $scopes = array())
    {
        $this->_credentials = $credentials;
        $this->_httpClient = $httpClient;
        $this->_storage = $storage;
        $this->_scopes = $scopes;
        $this->_config = include (__DIR__.'/Config/BullhornConfig.php');
        $this->_service = new BullhornService($credentials, $httpClient, $storage, $scopes);
    }

    /**
     * @return mixed
     */
    protected function getCredentialsThroughFile()
    {
        try{

            $credentials = file_get_contents($this->_config['credentials_storage_path']);
            return json_decode($credentials, true);

        } catch (\Exception $e)
        {
            return [];
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    protected function extractJson($string) {
        return json_decode(trim(substr($string, strpos($string, '{'))), true); //}
    }

    /**
     * @param $container
     * @param $key
     * @param null $default
     *
     * @return null
     */
    private function get ($container, $key, $default = null)
    {
        return isset($container[$key]) ? $container[$key] : $default;
    }

    /**
     * @return bool
     */
    private function validatePreviousSession()
    {
        $servicesCredentials = $this->getCredentialsThroughFile();

        if (!empty($servicesCredentials['bullhorn']['BhRestToken']) &&  !empty($servicesCredentials['bullhorn']['base_url'])) {

            $this->setBaseUrl($servicesCredentials['bullhorn']['base_url']);

            $testURI = $this->getService()->getRestUri(
              $servicesCredentials['bullhorn']['base_url']."entity/Candidate/10809",
              $servicesCredentials['bullhorn']['BhRestToken'],
              ['fields'=>'id']
            );

            $response = $this->getHttpClient()->retrieveResponse($testURI, '',  [], 'GET');
            $decoded = $this->extractJson($response);

            if (array_key_exists("errorCode", $decoded)) {
                return false;
            } else {
                $this->setSessionKey($servicesCredentials['bullhorn']['BhRestToken']);
                return true;
            }
        }

        return false;

    }

    /**
     * @param $servicesCredentials
     */
    private function loadRefresh($servicesCredentials)
    {
        $refresh = $servicesCredentials['bullhorn']['lastRefreshToken'];

        $uri = $this->getService()->getAccessTokenUri($refresh);

        $response = $this->getHttpClient()->retrieveResponse($uri, '', []);

        $decoded = $this->extractJson($response);

        if (!$decoded || array_key_exists("error", $decoded)) {

            $this->authorize($servicesCredentials);

        } else {

            $ref = $decoded["refresh_token"];
            $token = $decoded["access_token"];

            $this->getLogin($ref, $token, $servicesCredentials);
        }
    }

    /**
     * @param $servicesCredentials
     */
    private function loadRefreshWithCode($servicesCredentials)
    {
        $code = $servicesCredentials['bullhorn']['code'];

        $uri = $this->getService()->getAccessTokenWithCodeUri($code);
        $response = $this->getHttpClient()->retrieveResponse($uri, '',  []); //supposed to be POST
        $decoded = $this->extractJson($response);

        if (array_key_exists("error", $decoded)) {

            die ("Problem with getting access via code $code\n");
            \Log::debug("Problem with getting access via code $code\n");

        } else {

            $ref = $decoded["refresh_token"];
            $token = $decoded["access_token"];
            $this->getLogin($ref, $token, $servicesCredentials);

        }
    }

    /**
     * @param $ref
     * @param $token
     * @param $servicesCredentials
     */
    private function getLogin($ref, $token, $servicesCredentials)
    {

        $servicesCredentials['bullhorn']['lastRefreshToken'] = $ref;

        $login = $this->getService()->getLoginUri($token);
        $response2 = $this->getHttpClient()->retrieveResponse($login, '', []);
        $decoded2 = $this->extractJson($response2);

        $this->setSessionKey($decoded2["BhRestToken"]);

        $servicesCredentials['bullhorn']['BhRestToken'] = $decoded2["BhRestToken"];
        $servicesCredentials['bullhorn']['base_url'] = $decoded2["restUrl"];

        file_put_contents($this->getConfig()['credentials_storage_path'], json_encode($servicesCredentials));

        $this->setBaseUrl($decoded2["restUrl"]);

    }

    private function authorize()
    {
        $httpClient = $this->getHttpClient();
        $servicesCredentials = $this->getCredentialsThroughFile();

        $userName = $this->get($this->getConfig(), 'username');
        $password = $this->get($this->getConfig(), 'password');

        $uri2 = $this->getService()->getAuthorizationUriWrapper($userName, $password);
        $httpClient->setMaxRedirects(0);
        $authResponse = $httpClient->retrieveResponse($uri2, '', [], 'GET');

        $html_start = strpos($authResponse, '<!DOCTYPE html>');

        $headers = $authResponse;

        if ($html_start>2) {
            $headers = substr($authResponse, 0, $html_start);
        }

        if (preg_match("|Location: (https?://\S+)|", $headers, $m)) {

            if (preg_match("|code=(\S+)\&client_id|", $m[1], $n)) {

                $code = urldecode($n[1]);
                $servicesCredentials['bullhorn']['code'] = $code;
                $this->loadRefreshWithCode($servicesCredentials);

            }

        }

        if (! $this->getBaseUrl()) {
            die("Unable to login\n\n");
            \Log::debug(" Unable to login. I should die !!!");
        }
    }

    protected function authenticate ()
    {

        if (! $this->validatePreviousSession()) {

            $servicesCredentials = $this->getCredentialsThroughFile();

            if (empty($servicesCredentials['bullhorn']['lastRefreshToken'])) {

                $this->authorize( $servicesCredentials );

            } else {

                $this->loadRefresh( $servicesCredentials );

            }

        }
    }

    /**
     * @param $url
     * @param $data
     * @param $httpVerb
     *
     * @return mixed
     */
    protected function makeCall ($url, $data, $httpVerb)
    {
        $submittedUrl = $this->getService()->getRestUri($url, $this->getSessionKey());
        $submittedReference = $this->getHttpClient()->retrieveResponse($submittedUrl, $data, [], $httpVerb);
        $response = $this->extractJson($submittedReference);
        return $response;
    }


}