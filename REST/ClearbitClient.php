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

use CampaignChain\CoreBundle\Exception\ExternalApiException;
use CampaignChain\Location\ClearbitBundle\Entity\Clearbit;
use CampaignChain\Security\Authentication\Client\OAuthBundle\Entity\Token;
use CampaignChain\Security\Authentication\Client\OAuthBundle\EntityService\ApplicationService;
use CampaignChain\Security\Authentication\Client\OAuthBundle\EntityService\TokenService;
use Doctrine\Common\Persistence\ManagerRegistry;
use GuzzleHttp\Client;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ClearbitClient
{
    const RESOURCE_OWNER = 'Clearbit';

    protected $em;

    protected $validator;

    protected $accessToken;

    public function __construct(ManagerRegistry $managerRegistry, RecursiveValidator $validator)
    {
        $this->em = $managerRegistry->getManager();
        $this->validator = $validator;

        /** @var Clearbit $clearbitLocation */
        $clearbitLocation = $this->em->getRepository('CampaignChainLocationClearbitBundle:Clearbit')->findOneBy([], ['id' => 'ASC']);
        $this->accessToken = $clearbitLocation->getApiKey();
    }

    static private function getResponse($method, $url, $apiKey)
    {
        try {
            $client = new Client();
            $res = $client->request($method, 'https://' . $apiKey . '@' . str_replace('https://', '', $url));
            return json_decode($res->getBody(), true);
        } catch(\Exception $e){
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    static public function isValidApiKey($apiKey)
    {
        try {
            self::getResponse(
                'GET',
                'https://person-stream.clearbit.com/v2/combined/find?email=alex@alexmaccaw.com',
                $apiKey
            );
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function getEnrichmentCombined($email)
    {
        $emailConstraint = new Email();
        $errors = $this->validator->validate($email, $emailConstraint);

        if(count($errors) > 0){
            $errorsString = (string) $errors;
            throw new \Exception($errorsString);
        }

        return self::getResponse(
            'GET',
            'https://person-stream.clearbit.com/v2/combined/find?email='.$email,
            $this->accessToken
        );
    }

    public function getEnrichmentCompanyDomainLookup($domain)
    {
        return self::getResponse(
            'GET',
            'https://company.clearbit.com/v2/companies/find?domain='.$domain,
            $this->accessToken
        );
    }

    public function getReveal($ip)
    {
        $ipConstraint = new Ip();
        $errors = $this->validator->validate($ip, $ipConstraint);

        if(count($errors) > 0){
            $errorsString = (string) $errors;
            throw new \Exception($errorsString);
        }

        return self::getResponse(
            'GET',
            'https://reveal.clearbit.com/v1/companies/find?ip='.$ip,
            $this->accessToken
        );
    }
}