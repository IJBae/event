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

class Naver extends OAuth2
{
    protected $scope = '';
    protected $apiBaseUrl = 'https://apis.naver.com/nidlogin/';
    protected $authorizeUrl = 'https://nid.naver.com/oauth2.0/authorize';
    protected $accessTokenUrl = 'https://nid.naver.com/oauth2.0/token';
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
        $response = $this->apiRequest("https://openapi.naver.com/v1/nid/me");
        // $response = $this->httpClient->request("https://openapi.naver.com/v1/nid/me");
        $response = new Data\Collection($response);
        $data = array();
        if ( $response->get('resultcode') == '00' ) {
            foreach ($response->get('response') as $k => $v) {
                if(!is_array($v)) $data[(string)$k] = (string) $v;
            }
        } else {
            throw new UnexpectedApiResponseException("User profile request failed! {$this->providerId} returned an invalid response.", 6);
        }
        # store the user profile.
        $userProfile = new User\Profile();
        //$this->user->profile->identifier    = (array_key_exists('enc_id',$data))?$data['enc_id']:"";
        $userProfile->identifier    = (array_key_exists('id',$data))?@$data['id']:"";
        $userProfile->age           = (array_key_exists('age',$data))?@$data['age']:"";
        $userProfile->displayName = (array_key_exists('name', $data)) ? @$data['name'] : "";
        $userProfile->phone = (array_key_exists('mobile', $data)) ? @$data['mobile'] : "";
        $userProfile->birthYear = (array_key_exists('birthyear', $data)) ? @$data['birthyear'] : "";
        $userProfile->age = date('Y') - @$data['birthyear'] + 1;
        if(array_key_exists('birthday', $data)) 
            list($userProfile->birthDay, $userProfile->birthMonth) = @explode("-",$data['birthday']);
        $userProfile->email         = (array_key_exists('email',$data))?@$data['email']:"";
        $userProfile->gender        = (array_key_exists('gender',$data))?@$data['gender']:"";
        $userProfile->photoURL      = (array_key_exists('profile_image',$data))?@$data['profile_image']:"";

        // $userProfile->sid         = get_social_convert_id( $userProfile->identifier, $this->providerId );
        // $userProfile->sid = $userProfile->identifier.'.'.$this->providerId;
        return $userProfile;
    }
}