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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Entity\Location;

class ClearbitController extends Controller
{
    const RESOURCE_OWNER = 'Clearbit';

    public function createAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('access_token', 'text', array(
                'label' => 'API Key',
                'attr' => array('help_text' => 'Find your API key at https://dashboard.clearbit.com/api')
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * Check if API key is valid.
             */
            $apiKey = $form->get('access_token')->getData();
            $isValidApiKey = ClearbitClient::isValidApiKey($apiKey);

            if($isValidApiKey) {
                try {
                    $em = $this->getDoctrine()->getManager();
                    $em->getConnection()->beginTransaction();

                    $locationURL = 'https://dashboard.clearbit.com/';
                    $locationService = $this->get('campaignchain.core.location');
                    $locationModule = $locationService->getLocationModule('campaignchain/location-clearbit', 'campaignchain-clearbit-api');
                    $location = new Location();
                    $location->setLocationModule($locationModule);
                    $location->setName($locationUsername);
                    $location->setUrl($locationURL);
                    /*
                     * If user uploaded an image, use that as the Location image,
                     * otherwise, take the SlideShare default profile image.
                     */
                    $slideShareUserImage = 'http://cdn.slidesharecdn.com/profile-photo-'.$locationUsername.'-96x96.jpg';
                    try {
                        getimagesize($slideShareUserImage);
                    } catch (\Exception $e) {
                        $slideShareUserImage = 'http://public.slidesharecdn.com/b/images/user-96x96.png';
                    }
                    $location->setImage($slideShareUserImage);
                    $wizard = $this->get('campaignchain.core.channel.wizard');
                    $wizard->setName($location->getName());
                    $wizard->addLocation($location->getUrl(), $location);
                    $channel = $wizard->persist();
                    $wizard->end();

                    $slideshareUser = new SlideShareUser();
                    $slideshareUser->setLocation($channel->getLocations()[0]);
                    $slideshareUser->setIdentifier($locationUsername);
                    $slideshareUser->setPassword($locationPassword);
                    $slideshareUser->setDisplayName($locationUsername);
                    $em->persist($slideshareUser);
                    $em->flush();
                    $em->getConnection()->commit();
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'The Slideshare location <a href="#">'.$locationUsername.'</a> was connected successfully.'
                    );
                    return $this->redirect($this->generateUrl(
                        'campaignchain_core_location'));
                } catch (\Exception $e) {
                    $em->getConnection()->rollback();
                    throw $e;
                }

                $this->addFlash('success','Connected with Clearbit successfully');

                return $this->redirect(
                    $this->generateUrl('campaignchain_core_location')
                );
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