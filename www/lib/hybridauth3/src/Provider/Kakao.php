<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\User;

class Kakao extends OAuth2
{
    protected $scope = '';
    protected $apiBaseUrl = 'https://kapi.kakao.com/v2/';
    protected $authorizeUrl = 'https://kauth.kakao.com/oauth/authorize';
    protected $accessTokenUrl = 'https://kauth.kakao.com/oauth/token';
    protected $apiDocumentation = 'https://developers.kakao.com/docs/latest/ko/kakaologin/common';

    protected function initialize()
    {
        parent::initialize();
    }

    /**
    * load the user profile
    */
    function getUserProfile()
    {
        //$params = array('property_keys'=>'kaccount_email');   // v1 parameter
        $params = array('property_keys'=>array('kakao_account.email'));     // v2 parameter

        $requestHeader = array( 'Authorization: Bearer ' . $this->getStoredData('access_token') );

        $response = $this->apiRequest("user/me", "POST", $params, $requestHeader);
        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }
        # store the user profile.
        $userProfile = new User\Profile();
        $userProfile->identifier  = @$data->get('id');
        $userProfile->displayName = @$data->get('properties')->nickname;
        $userProfile->photoURL    = @$data->get('properties')->thumbnail_image;
        //$email = @ $data->properties->kaccount_email; // v1 version

        $phone_number = @$data->get('kakao_account')->phone_number;       // 010-1111-2222의 경우, +82 10-1111-2222
        if ($phone_number) {
            // 국제번호 를 0 으로 변경
            if (strpos($phone_number, '+82 ') === 0) $phone_number = str_replace('+82 ', '0', $phone_number);
            $userProfile->phone = preg_replace('/[^0-9]+/', '', $phone_number);
        }

        $birthyear = @ $data->get('kakao_account')->birthyear;         // 1990
        if ($birthyear) {
            $userProfile->birthYear = $birthyear;
            $userProfile->age = date('Y') - $birthyear + 1;
        }

        $birthday = @ $data->get('kakao_account')->birthday;               // 0708
        if ($birthday) {
            list($_m, $_d) = str_split($birthday, 2);
            $userProfile->birthMonth = ltrim($_m, '0');
            $userProfile->birthDay = ltrim($_d, '0');
        }

        $nickname = @ $data->get('kakao_account')->profile->nickname;
        if (!$userProfile->displayName && $nickname) {
            $userProfile->displayName = $nickname;
        }

        $email = @ $data->get('kakao_account')->email;   // v2 version

        if( $email ){
            $userProfile->email = $email;
        }

        // $userProfile->sid         = get_social_convert_id( $userProfile->identifier, $this->providerId );
        // $userProfile->sid = $userProfile->identifier.'.'.$this->providerId;
        // $this->httpClient->request("https://kapi.kakao.com/v1/user/logout", "POST", [], $requestHeader);
        return $userProfile;
    }
}