<?php
namespace Germania\AuthApiClient;

use Psr\Http\Message\ResponseInterface;
use Germania\JsonDecoder\JsonDecoder;
use Germania\AuthApiClient\Exceptions\AuthApiResponseException;

trait ResponseDecoderTrait
{


    /**
     * Decodes the JSON response and checks for "access_token" and "expires_in" elements.
     * If one of these is missing, or the JSON could not be decoded, an AuthApiResponseException
     * will be thrown.
     *
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @return array
     * @throws \Germania\AuthApiClient\Exceptions\AuthApiResponseException
     */
    protected function decodeResponse(ResponseInterface $response) : array
    {
        try {
            $response_decoded = (new JsonDecoder)( $response, (bool) "assoc" );
        }
        catch( \JsonException $e) {
            throw new AuthApiResponseException( "Could not decode AuthApi response", 0, $e );
        }

        if (empty($response_decoded['access_token'])) {
            throw new AuthApiResponseException("AuthApi response: Access token missing");
        }

        if (!isset($response_decoded['expires_in'])) {
            throw new AuthApiResponseException("AuthApi response: Expiration lifetime missing");
        }

        return $response_decoded;
    }
}
