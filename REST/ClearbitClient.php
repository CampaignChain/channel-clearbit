<?php
/*
 * Copyright 2017 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Channel\ClearbitBundle\REST;

use CampaignChain\Security\Authentication\Client\OAuthBundle\Entity\Token;
use CampaignChain\Security\Authentication\Client\OAuthBundle\EntityService\ApplicationService;
use CampaignChain\Security\Authentication\Client\OAuthBundle\EntityService\TokenService;
use Guzzle\Http\Client;

class ClearbitClient
{
    const RESOURCE_OWNER = 'Clearbit';

    /** @var  ApplicationService */
    protected $oauthAppService;

    /** @var  TokenService */
    protected $oauthTokenService;

    protected $accessToken;

    public function __construct(
        ApplicationService $oauthAppService,
        TokenService $oauthTokenService
    )
    {
        $this->oauthAppService = $oauthAppService;
        $this->oauthTokenService = $oauthTokenService;
    }

    public function connect(){
        $application = $this->oauthAppService->getApplication(self::RESOURCE_OWNER);

        // Get Access Token and Token Secret
        /** @var Token $token */
        $token = $this->oauthTokenService->getTokenByApplication($application);
        $this->accessToken = $token->getAccessToken();
    }

    static private function getResponse($method, $url, $apiKey)
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request($method, 'https://'.$apiKey.'@'.str_replace('https://', '', $url));

        return $res;
    }

    static public function isValidApiKey($apiKey)
    {
        try {
            $client = self::getResponse(
                'GET',
                'https://person-stream.clearbit.com/v2/combined/find?email=alex@alexmaccaw.com',
                $apiKey
            );
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }
}