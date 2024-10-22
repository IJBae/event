<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------
//$_REQUEST['hauth_done'] = 'Live';
require_once( "Hybrid/Auth.php" );
$config = include 'config.php';
$is_success = false;		// 로그인 + userprofile 가져오기 성공 여부
$s = '';	// 오류문구
try{
	$hybridauth = new Hybrid_Auth( $config );
	$provider = @ trim( strip_tags( $provider ) );
	$adapter = $hybridauth->authenticate( $provider );
	//$adapter = $hybridauth->getAdapter( $provider );
	$user_data = $adapter->getUserProfile();
	if ($user_data) $is_success = true;
	$adapter->logout();
}
catch( Exception $e ){
	switch( $e->getCode() ){
		case 0 : $s = "Unspecified error."; break;  
		case 1 : $s = "Hybriauth configuration error."; break;  
		case 2 : $s = "Provider not properly configured."; break;  
		case 3 : $s = "Unknown or disabled provider."; break;  
		case 4 : $s = "Missing provider application credentials."; break;  
		case 5 : $s = "Authentication failed. "   
		. "The user has canceled the authentication or the provider refused the connection.";   
		case 6 : $s = "User profile request failed. Most likely the user is not connected "  
		. "to the provider and he should to authenticate again.";   
		if (isset($adapter)) $adapter->logout();   
		break;  
		case 7 : $s = "User not connected to the provider.";   
		if (isset($adapter)) $adapter->logout();
		break;
	}
	$s .= "Original error message : " . $e->getMessage();
	//$s .= $e->getTraceAsString();
}
