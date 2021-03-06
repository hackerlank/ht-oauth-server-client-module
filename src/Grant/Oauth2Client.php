<?php

namespace HtOauth\Server\ClientModule\Grant;

use Psr\Http\Message\ServerRequestInterface;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use League\OAuth2\Client\Exception\IDPException;
use HtLeagueOauthClientModule\Model\Oauth2User;
use League\OAuth2\Client\Token\AccessToken as ProviderAccessToken;
use Hrevert\OauthClient\Model\ProviderInterface;

class Oauth2Client extends AbstractOauthClientGrant
{
    const GRANT_TYPE          = 'oauth2_client';
    const GRANT_RESPONSE_TYPE = null;

    /**
     * {@inheritdoc}
     */
    protected function findProviderUserFromRequest(ServerRequestInterface $request, ProviderInterface $provider)
    {
        $postParams                = $request->getParsedBody();
        $providerAuthorizationCode = isset($postParams['provider_authorization_code']) ? $postParams['provider_authorization_code'] : null;
        $providerAccessToken       = isset($postParams['provider_access_token']) ? $postParams['provider_access_token'] : null;

        /* @var \League\OAuth2\Client\Provider\ProviderInterface */
        $providerClient = $this->providerClients->get($provider->getName());

        if ($providerAuthorizationCode) {
            // Try to get an access token (using the authorization code grant)
            try {
                /** @var ProviderAccessToken  $providerAccessToken*/
                $providerAccessToken = $providerClient->getAccessToken('authorization_code', ['code' => $providerAuthorizationCode]);
            } catch (IDPException $e) {
                // @todo decide what is the best thing to do here???
                throw OAuth2Exception::invalidRequest('Provider authorization code is invalid');
            }
        } else {
            $providerAccessToken = new ProviderAccessToken(['access_token' => $providerAccessToken]);
        }

        /* @var \League\OAuth2\Client\Entity\User */
        $userDetails = $providerClient->getUserDetails($providerAccessToken);

        return new Oauth2User($userDetails);
    }
}
