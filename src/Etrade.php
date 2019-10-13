<?php

namespace MichaelDrennen\Etrade;

use GuzzleHttp\Client;
use MichaelDrennen\Etrade\Traits\RequestTrait;

class Etrade {

    use RequestTrait;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    const PRODUCTION_URL = 'https://api.etrade.com/v1/';
    const SANDBOX_URL    = 'https://apisb.etrade.com/v1/';

    protected $consumerKey;


    protected $baseURL = '';

    public function __construct( string $consumerKey, bool $sandbox = FALSE ) {
        $this->consumerKey = $consumerKey;
        $this->setBaseURL( $sandbox );
        $this->client = new Client();
    }

    /**
     * @param bool $sandbox
     */
    protected function setBaseURL( bool $sandbox = FALSE ) {
        if ( $sandbox ):
            $this->baseURL = self::SANDBOX_URL;
        else:
            $this->baseURL = self::PRODUCTION_URL;
        endif;
    }


    /**
     * Set up a GuzzleHttp Client with some default settings.
     */
    protected function setClient() {
        $this->client = new Client( [
                                        'verify'   => FALSE,
                                        'base_uri' => $this->baseURL,
                                    ] );
    }


}