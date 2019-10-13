<?php

namespace MichaelDrennen\Etrade\Traits;

use GuzzleHttp\Client;

use Exception;
use GuzzleHttp\Exception\ClientException;
use MichaelDrennen\OAuth1Signature\OAuth1Signature;

trait RequestTrait {

    protected $oauthToken             = '';
    protected $oauthTokenSecret       = '';
    protected $oauthCallbackConfirmed = FALSE;


    protected function makeRequest( string $method, string $uri, array $headers = [], array $queryParameters = [], array $formParameters = [], array $guzzleRequestOptions = [] ) {


        $options = [];
        $options = $this->setHeaders( $options, $headers );
        $options = $this->setAdditionalQueryParameters( $options, $queryParameters );
        $options = $this->setAdditionalFormParams( $options, $formParameters );
        $options = $this->setGuzzleRequestOptions( $options, $guzzleRequestOptions );


        try {
            return $this->client->request( $method, $uri, $options );
        } catch ( ClientException $clientException ) {

            var_dump( $clientException->getMessage() );

            // @codeCoverageIgnoreStart
            throw $clientException;
        } catch ( Exception $exception ) {
            throw $exception;
        }
        // @codeCoverageIgnoreEnd

    }

    protected function setHeaders( array $options, array $headers ): array {
        foreach ( $headers as $key => $value ):
            $options[ 'headers' ][ $key ] = $value;
        endforeach;

        return $options;
    }


    /**
     *
     * @param array $options The existing $options array used by the Guzzle client. It gets added to and returned by this function.
     * @param array $additionalQueryParameters An array of name => value pairs that will get added to the query string sent to the server.
     * @return array The modified $options array to be used by the Guzzle client.
     */
    protected function setAdditionalQueryParameters( array $options, array $additionalQueryParameters = [] ): array {
        foreach ( $additionalQueryParameters as $key => $value ):
            $options[ 'query' ][ $key ] = $value;
        endforeach;

        return $options;
    }


    /**
     * @param array $options
     * @param array $additionalFormParameters
     * @return array
     */
    protected function setAdditionalFormParams( array $options, array $additionalFormParameters = [] ): array {
        foreach ( $additionalFormParameters as $key => $value ):
            $options[ 'form_params' ][ $key ] = $value;
        endforeach;

        return $options;
    }


    /**
     * I originally created this method to pass the debug flag into the GuzzleHTTP
     * request options for development and testing.
     * @param array $options
     * @param array $guzzleRequestOptions
     * @return array
     * @see http://docs.guzzlephp.org/en/stable/request-options.html
     */
    protected function setGuzzleRequestOptions( array $options, array $guzzleRequestOptions ): array {
        foreach ( $guzzleRequestOptions as $key => $value ):
            $options[ $key ] = $value;
        endforeach;
        return $options;
    }


    /**
     * @see https://apisb.etrade.com/docs/api/authorization/request_token.html
     * @throws Exception
     */
    public function getRequestToken() {

        /**
         * These all go in the request header
         */
        $oathTimestamp       = time();          // The date and time of the request, in epoch time. Must be accurate within five minutes.
        $oathNonce           = md5( time() . 'requestToken' );   // A nonce, as described in the authorization guide - roughly, an arbitrary or random value that cannot be used again with the same timestamp.
        $oathSignatureMethod = 'HMAC-SHA1';     // The signature method used by the consumer to sign the request. The only supported value is HMAC-SHA1.
        $oathCallback        = 'oob';           // Callback information, as described elsewhere. Must always be set to 'oob', whether using a callback or not.

        $uri        = 'oauth/request_token';
        $requestURL = $this->baseURL . $uri;

        $headers = [
            'oauth_consumer_key'     => $this->consumerKey,
            'oauth_timestamp'        => $oathTimestamp,
            'oauth_nonce'            => $oathNonce,
            'oauth_signature_method' => $oathSignatureMethod,
            'oauth_callback'         => $oathCallback,
        ];

        $postParams  = [];
        $queryParams = [];

        // Signature generated with the shared secret and token secret using the specified oauth_signature_method, as described in OAuth documentation.
        $oathSignature = OAuth1Signature::signature( $this->consumerKey,
                                                     'GET',
                                                     $requestURL,
                                                     $headers,
                                                     $postParams,
                                                     $queryParams );

        $headers[ 'oauth_signature' ] = $oathSignature;

        $response       = $this->makeRequest( 'GET', $uri, $headers );
        $stringResponse = (string)$response->getBody();

        /**
         * @TODO make sure this all works including the signature code once I have a key to test with.
         */
        parse_str( $stringResponse, $arrayResponse );

        $this->oauthToken             = $arrayResponse[ 'oauth_token' ];
        $this->oauthTokenSecret       = $arrayResponse[ 'oauth_token_secret' ];
        $this->oauthCallbackConfirmed = $arrayResponse[ 'oauth_callback_confirmed' ];
    }


    /**
     * @see https://apisb.etrade.com/docs/api/authorization/authorize.html
     */
    public function authorizeApplication() {
        /**
         * @see https://stackoverflow.com/questions/28277889/guzzlehttp-client-change-base-url-dynamically
         */
        $URL     = 'https://us.etrade.com/e/t/etws/authorize';
        $headers = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token'        => $this->oauthToken,
        ];

        $response       = $this->makeRequest( 'GET', $URL, $headers );
        $stringResponse = (string)$response->getBody();

        /**
         * @TODO Parse oauth_verifier out of the 302 redirect response.
         */
    }


    public function getAccessToken() {
        /**
         * These all go in the request header
         */
        $oathTimestamp       = time();          // The date and time of the request, in epoch time. Must be accurate within five minutes.
        $oathNonce           = md5( time() . 'accessToken' );   // A nonce, as described in the authorization guide - roughly, an arbitrary or random value that cannot be used again with the same timestamp.
        $oathSignatureMethod = 'HMAC-SHA1';     // The signature method used by the consumer to sign the request. The only supported value is HMAC-SHA1.
        $oathCallback        = 'oob';           // Callback information, as described elsewhere. Must always be set to 'oob', whether using a callback or not.

        $uri        = 'oauth/access_token';
        $requestURL = $this->baseURL . $uri;

        $headers = [
            'oauth_consumer_key'     => $this->consumerKey,
            'oauth_timestamp'        => $oathTimestamp,
            'oauth_nonce'            => $oathNonce,
            'oauth_signature_method' => $oathSignatureMethod,
            'oauth_callback'         => $oathCallback,
        ];

        $postParams  = [];
        $queryParams = [];

        // Signature generated with the shared secret and token secret using the specified oauth_signature_method, as described in OAuth documentation.
        $oathSignature = OAuth1Signature::signature( $this->consumerKey,
                                                     'GET',
                                                     $requestURL,
                                                     $headers,
                                                     $postParams,
                                                     $queryParams );

        $headers[ 'oauth_signature' ] = $oathSignature;

        $response       = $this->makeRequest( 'GET', $uri, $headers );
        $stringResponse = (string)$response->getBody();

        /**
         * @TODO make sure this all works including the signature code once I have a key to test with.
         */
        parse_str( $stringResponse, $arrayResponse );

        $this->oauthToken             = $arrayResponse[ 'oauth_token' ];
        $this->oauthTokenSecret       = $arrayResponse[ 'oauth_token_secret' ];
        $this->oauthCallbackConfirmed = $arrayResponse[ 'oauth_callback_confirmed' ];
    }
}