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

namespace CampaignChain\Channel\ClearbitBundle\Controller;

use CampaignChain\Channel\ClearbitBundle\REST\ClearbitClient;
use CampaignChain\Location\ClearbitBundle\Entity\Clearbit;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Entity\Location;

class ClearbitController extends Controller
{
    const LOCATION_NAME = 'Clearbit';
    const LOCATION_URL = 'https://dashboard.clearbit.com/';

    public function createAction(Request $request)
    {
        /*
         * Has an API key already been provided?
         */
        $em = $this->getDoctrine();
        $clearbitLocation = $em->getRepository('CampaignChainLocationClearbitBundle:Clearbit')->findOneBy([], ['id' => 'ASC']);

        if(is_null($clearbitLocation)){
            $isNew = true;
            $clearbitLocation = new Clearbit();
        } else {
            $isNew = false;
        }

        $form = $this->createFormBuilder($clearbitLocation)
            ->add('apiKey', 'text', array(
                'label' => 'API Key',
                'attr' => array('help_text' => 'Find your API key at https://dashboard.clearbit.com/api')
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * Check if API key is valid.
             */
            $isValidApiKey = ClearbitClient::isValidApiKey($clearbitLocation->getApiKey());

            if($isValidApiKey) {
                $em = $this->getDoctrine()->getManager();

                try {
                    $em->getConnection()->beginTransaction();

                    if($isNew) {
                        $locationService = $this->get('campaignchain.core.location');
                        $locationModule = $locationService->getLocationModule('campaignchain/location-clearbit', 'campaignchain-clearbit-api');
                        $location = new Location();
                        $location->setLocationModule($locationModule);
                        $location->setName(self::LOCATION_NAME);
                        $location->setUrl(self::LOCATION_URL);

                        $wizard = $this->get('campaignchain.core.channel.wizard');
                        $wizard->setName($location->getName());
                        $wizard->addLocation($location->getUrl(), $location);
                        $channel = $wizard->persist();
                        $wizard->end();

                        $clearbitLocation->setLocation($channel->getLocations()[0]);
                    }

                    $em->persist($clearbitLocation);
                    $em->flush();
                    $em->getConnection()->commit();

                    $this->addFlash('success','Connected with Clearbit successfully');

                    return $this->redirect(
                        $this->generateUrl('campaignchain_core_location')
                    );
                } catch (\Exception $e) {
                    $em->getConnection()->rollback();
                    throw $e;
                }
            } else {
                $this->addFlash('warning','Invalid API key');
            }
        }
        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Connect with Clearbit',
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_location',
            ));
    }

/*

Below code is for Clearbit OAuth authentification. Yet, it's only available to
partners.

    private $applicationInfo = array(
        'key_labels' => array('id', 'Client ID'),
        'secret_labels' => array('secret', 'Client Secret'),
        'config_url' => 'https://clearbit.com/docs#oauth',
        'parameters' => array(),
        'wrapper' => array(
            'class'=>'Hybrid_Providers_Clearbit',
            //'path' => 'vendor/campaignchain/channel-clearbit/REST/ClearbitOAuth.php'
            'path' => 'src/CampaignChain/Channel/ClearbitBundle/REST/ClearbitOAuth.php'
        ),
    );

    public function createAction()
    {
        $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);

        if(!$application){
            return $oauthApp->newApplicationTpl(self::RESOURCE_OWNER, $this->applicationInfo);
        }
        else {
            return $this->render(
                'CampaignChainChannelClearbitBundle:Create:index.html.twig',
                array(
                    'page_title' => 'Connect with Clearbit',
                    'app_id' => $application->getKey(),
                )
            );
        }
    }

    public function loginAction(Request $request){
        $oauth = $this->get('campaignchain.security.authentication.client.oauth.authentication');
        $status = $oauth->authenticate(self::RESOURCE_OWNER, $this->applicationInfo);

        if($status){
            $this->get('session')->getFlashBag()->add(
                'success',
                'The MailChimp location <a href="#">'.$profile->displayName.'</a> was connected successfully.'
            );
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'A location has already been connected for this Clearbit account.'
            );
        }

        return $this->render(
            'CampaignChainChannelClearbitBundle:Create:login.html.twig',
            array(
                'redirect' => $this->generateUrl('campaignchain_core_location')
            )
        );
    }
*/

}