<?php
/**
 * Created by PhpStorm.
 * User: rohan
 * Date: 5/19/17
 * Time: 8:14 PM
 */

namespace NorthCreek\Bullhorn;

use NorthCreek\Bullhorn\Config\BullhornConfig as BullhornConfig;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

/**
 * Class BullhornFactory
 * @package App\Http\Bullhorn
 */

final class BullhornFactory
{

    private $_dispatcher;
    private $_extractor;
    private $_httpClient;
    private $_config;
    private $_storage;
    private $_uri;
    private $_credentials;
    private $_scopes;

    /**
     * @return mixed
     */
    public function getScopes ()
    {
        return $this->_scopes;
    }

    /**
     * @param mixed $scopes
     */
    public function setScopes ($scopes)
    {
        $this->_scopes = $scopes;
    }

    /**
     * @return mixed
     */
    public function getUri ()
    {
        return $this->_uri;
    }

    /**
     * @param UriInterface $uri
     */
    public function setUri (UriInterface $uri)
    {
        $this->_uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getStorage ()
    {
        return $this->_storage;
    }

    /**
     * @param TokenStorageInterface $storage
     */
    public function setStorage (TokenStorageInterface $storage)
    {
        $this->_storage = $storage;
    }

    /**
     * @return mixed
     */
    public function getCredentials ()
    {
        return $this->_credentials;
    }

    /**
     * @param CredentialsInterface $credentials
     */
    public function setCredentials (CredentialsInterface $credentials)
    {
        $this->_credentials = $credentials;
    }


    /**
     * @return mixed
     */
    public function getHttpClient ()
    {
        return $this->_httpClient;
    }

    /**
     * @param ClientInterface $_httpClient
     */
    public function setHttpClient (ClientInterface $_httpClient)
    {
        $this->_httpClient = $_httpClient;
    }

    /**
     * BullhornFactory constructor.
     */
    public function __construct ()
    {
        $this->_config = include (__DIR__.'/Config/BullhornConfig.php');
        $this->_httpClient = $this->_config['oauth']['http_client'];
        $this->_storage = $this->_config['oauth']['storage'];
        $this->_credentials = $this->_config['oauth']['credentials'];
        $this->_scopes = [];
        ini_set('date.timezone', $this->_config['timezone']['date']);
    }

    /**
     * @return mixed
     */
    private function getCredentialsThroughFile()
    {
        try{

            $credentials = file_get_contents($this->_config['credentials_storage_path']);
            return json_decode($credentials, true);

        } catch (\Exception $e)
        {
            return [];
        }
    }

    private function makeHttpClientObject()
    {
        $httpClient = $this->getHttpClient();
        $httpClient = new $httpClient();
        $httpClient->setCurlParameters([CURLOPT_HEADER=>true]);
        $httpClient->setTimeout(60);
        $this->setHttpClient($httpClient);
    }

    private function makeQueryObject()
    {
        $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
        $currentUri = $uriFactory->createFromAbsolute("http://localhost");
        $currentUri->setQuery('');
        $this->setUri($currentUri);

    }

    private function makeStorageObject ()
    {
        $storage = $this->getStorage();
        $storage = new $storage();
        $storage = new $storage(false);
        $this->setStorage($storage);
    }

    private function makeCredentialsObject ()
    {
        $credentials = $this->getCredentials();
        $servicesCredentials = $this->getCredentialsThroughFile();
        if ($servicesCredentials)
        {
            $credentials = new $credentials(
              $servicesCredentials['bullhorn']['key'],
              $servicesCredentials['bullhorn']['secret'],
              $this->getUri()->getAbsoluteUri()
            );
        } else {

            $credentials = new $credentials('', '', $this->getUri()->getAbsoluteUri());

        }

        $this->setCredentials($credentials);
    }

    private function init() {

        $this->makeHttpClientObject();

        $this->makeQueryObject();

        $this->makeStorageObject();

        $this->makeCredentialsObject();
    }

    /**
     * @param $entity
     *
     * @return null
     */
    public function makeDispatcher($entity)
    {

        $className = $this->prepareEntity($entity, 'Dispatcher');

        if ($className)
        {
            $this->init();

            if (!$this->_dispatcher)
            {
                $this->_dispatcher = new $className($this->getCredentials(), $this->getHttpClient(), $this->getStorage(), $this->getScopes());
            }

            return $this->_dispatcher;

        }

        return null;
    }

    /**
     * @param $entity
     *
     * @return null
     */
    public function makeExtractor($entity)
    {

        $className = $this->prepareEntity($entity, 'Extractor');

        if ($className)
        {
            $this->init();

            if (!$this->_extractor)
            {
                $this->_extractor = new $className($this->getCredentials(), $this->getHttpClient(), $this->getStorage(), $this->getScopes());
            }

            return $this->_extractor;

        }

        return null;

    }

    /**
     * @param $entity
     * @param $type
     *
     * @return null|string
     */
    private function prepareEntity($entity, $type)
    {

        $entity = ucfirst($entity);

        $className = "Bullhorn{$entity}{$type}";

        if(class_exists($instance = '\NorthCreek\Bullhorn\\' . $type . '\\' . $className))
        {
            return $instance;
        }

        return null;
    }

}