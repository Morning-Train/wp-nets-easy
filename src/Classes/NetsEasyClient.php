<?php namespace Morningtrain\WpNetsEasy\Classes;

use Morningtrain\WP\Database\Database;
use WP_Error;
use JsonSerializable;

class NetsEasyClient {

    const TEST_URL = 'https://test.api.dibspayment.eu/';
    const URL = 'https://api.dibspayment.eu/';

    protected static self $globalInstance;
    protected string $secretKey;
    protected bool $isTest = false;

    public function __construct(string $secretKey)
    {
        $this->setSecretKey($secretKey);
    }

    public function isTest() : static
    {
        return $this->setIsTest(true);
    }

    public function setIsTest(bool $isTest) : static
    {
        $this->isTest = $isTest;

        return $this;
    }

    public function setSecretKey(string $secretKey) : static
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getSecretKey() : string
    {
        return $this->secretKey;
    }

    public function getIsTest() : bool
    {
        return $this->isTest;
    }

    public function get(string $endpoint) : array|WP_Error
    {
        return wp_remote_get($this->getUrl($endpoint), [
            'headers' => $this->getRequestHeaders()
        ]);
    }

    public function post(string $endpoint, array|JsonSerializable $body = null) : array|WP_Error
    {
        $args = [
            'headers' => $this->getRequestHeaders()
        ];

        if($body !== null) {
            $args['body'] = json_encode($body);
        }

        return wp_remote_post($this->getUrl($endpoint), $args);
    }

    public function put(string $endpoint, array|JsonSerializable $body = null) : array|WP_Error
    {
        $args = [
            'headers' => $this->getRequestHeaders(),
            'method' => 'PUT'
        ];

        if($body !== null) {
            $args['body'] = json_encode($body);
        }

        return wp_remote_request($this->getUrl($endpoint), $args);
    }

    protected function getUrl(string $endpoint = '') : string
    {
        $url = static::URL;

        if($this->getIsTest()) {
            $url = static::TEST_URL;
        }

        return trailingslashit($url) . trim($endpoint, '/');
    }

    protected function getRequestHeaders() {
        return [
            'Authorization' => $this->getSecretKey(),
            'content-type' => 'application/*+json'
        ];
    }

    public static function create(string $secretKey) : static
    {
        return new static($secretKey);
    }

    public static function createGlobalInstance(string $secretKey) : static
    {
        $instance = static::getGlobalInstance();

        if(empty($instance)) {
            $instance = static::create($secretKey);

            static::$globalInstance = $instance;
        } else {
            $instance->setSecretKey($secretKey);
        }

        return $instance;
    }

    public static function init(string $secretKey) : static
    {
        $instance = static::createGlobalInstance($secretKey);

        if(str_contains($secretKey, 'test-secret-key')) {
            $instance->isTest();
        }

        return $instance;
    }

    public static function getGlobalInstance() : ?static
    {
        if(empty(static::$globalInstance)) {
            return null;
        }

        return static::$globalInstance;
    }
}