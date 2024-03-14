<?php

declare(strict_types=1);

namespace SellingPartnerApi\Authentication;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Psr\Http\Client\ClientInterface;
use SellingPartnerApi\Enums\Endpoint;

class LWAAuthenticator extends AbstractAuthenticator
{
    /**
     * The authentication client, if any.
     *
     * @var GuzzleHttp\ClientInterface|null
     */
    protected ?ClientInterface $authenticationClient;

    /**
     * A map of LWA client IDs to access tokens. Used to cache access tokens
     * for multiple clients in a single spot.
     *
     * @var array[string => AccessToken]
     */
    private static array $accessTokens = [];

    public function __construct(
        protected readonly string $clientId,
        protected readonly string $clientSecret,
        protected readonly string $refreshToken,
        protected readonly Endpoint $endpoint,
        ?ClientInterface $authenticationClient = null,
    ) {
        $this->authenticationClient = $authenticationClient ?? new Client();
    }

    /**
     * Gets the access token for OAuth
     */
    protected function getAccessToken(): ?string
    {
        $accessToken = Arr::get(static::$accessTokens, $this->clientId);
        if (! $accessToken || $accessToken->expired()) {
            $jsonData = [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
            ];

            $data = $this->makeTokenRequest($jsonData);

            $accessToken = new AccessToken(
                $data['access_token'],
                new DateTime("+{$data['expires_in']} seconds")
            );

            $accessToken = static::$accessTokens[$this->clientId] = $accessToken;
        }

        return $accessToken->token;
    }
}
