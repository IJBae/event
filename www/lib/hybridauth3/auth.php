<?php
session_start();
include __DIR__."/src/autoload.php";

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

// include __DIR__."/../../dbinfo.php";
$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("set session character_set_client=utf8mb4;");
$db->query("set session character_set_connection=utf8mb4;");
// echo nl2br(print_r($_SESSION,1));
$config = [
    'callback' => "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REDIRECT_URL']}",

    'providers' => [
        'Kakao' => [
            'enabled' => true,
            'name' => '카카오톡',
            'keys' => ['id' => '500a99d34478a54c7c4fbfc04ff90512', 'secret' => ' '],
            // 'keys' => ['id' => '2b01f5f8aed3146fb045034489a410ba', 'secret' => ' '],	//예쁨주의쁨의원
            // 'keys' => ['id' => '9934f98df726d20ad728eec94d40e323', 'secret' => ' '],	//하늘안과
        ],
        'Facebook' => [
        	'enabled' => true,
        	'name' => '페이스북',
        	'keys' => ['id' => '1225097934573584', 'secret' => '08f4b7d15f725c2856fef1a8a696607d']
        ],
        'Naver' => [
        	'enabled' => true,
        	'name' => '네이버',
        	// 'keys' => ['id' => 'PiV62dqtvaoffn0GkK4Y', 'secret' => 'gye6ngjg0_'], //케어랩스
        	'keys' => ['id' => 'EoQUcrKT07bjcBt2rDZ0', 'secret' => '9cIABEwMeG'], //에스앤유안과
        ]
    ]
];

$hybridauth = new Hybridauth($config);
$adapter = @$hybridauth->authenticate($provider);
$userProfile = @$adapter->getUserProfile();
$_SESSION['userProfile'] = (array)$userProfile;
if(isset($_SESSION['kakaosync'])) {
	$landing = $_SESSION['kakaosync'];
	$url = "https://{$landing}";
	header("Location: {$url}");
	exit;
}
// echo nl2br(print_r($adapter->storage->get('REQUEST_URI'),1)); exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SNS 로그인</title>
	<script src="//static.hotblood.co.kr/libs/jquery/1.12.4/jquery.min.js"></script>
	<style>
	.action{text-align: center;}
	.btn_close{border: 0 none; text-decoration: underline; background-color: transparent; font-size: 120%; cursor: pointer;}
	.lds-facebook {
		display: block;
		position: relative;
		width: 80px;
		height: 80px;
		margin: 0 auto;
	}
	.lds-facebook div {
		display: inline-block;
		position: absolute;
		left: 8px;
		width: 16px;
		background: #fed;
		animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
	}
	.lds-facebook div:nth-child(1) {
		left: 8px;
		animation-delay: -0.24s;
	}
	.lds-facebook div:nth-child(2) {
		left: 32px;
		animation-delay: -0.12s;
	}
	.lds-facebook div:nth-child(3) {
		left: 56px;
		animation-delay: 0;
	}
	@keyframes lds-facebook {
		0% {
			top: 8px;
			height: 64px;
		}
		50%, 100% {
			top: 24px;
			height: 32px;
		}
	</style>
</head>
<body>
	<div class="action">
		<div class="lds-facebook"><div></div><div></div><div></div></div>
		<p><?php echo $config['providers'][ucfirst($provider)]['name'];?>에서 정보를 받아오고 있습니다.</p>
		<p class="done">완료되었습니다. 이 탭을 닫으신 후 신청페이지로 돌아가주세요.</p>
	</div>
<?
$adapter->disconnect();
// echo '<pre>'.print_r($adapter,1).'</pre>'; exit;
if(!$userProfile->displayName) exit;
if(!empty($userProfile->gender) && preg_match('/^(여|f)/i', $userProfile->gender)) {
	$gender = "1";
} else if(!empty($userProfile->gender) && preg_match('/^(남|m)/i', $userProfile->gender)) {
	$gender = "0";
}

$evt_no = $_SESSION['no'];
$code = @$_SESSION['code'];
$site = @$_SESSION['site'];
$today = date('Y-m-d');
$imp_sql = "INSERT INTO event_sns_clicks_history(seq, date, code, site, provider, clicks, last_datetime) VALUES('{$evt_no}', '{$today}', '{$code}', '{$site}', '{$provider}', 1, NOW()) ON DUPLICATE KEY UPDATE clicks = clicks + 1, last_datetime = NOW()";
if(!preg_match('/bot/i', $_SERVER['HTTP_USER_AGENT']))
    $db->query($imp_sql, true);
?>
	<script defer>
	$(document).ready(function() {
	setTimeout(function() {
		var o = window.opener;
		var $doc = $(o.document);
		$('[name="name"]', $doc).val('<?php echo @$userProfile->displayName;?>');
		$('[name="name"]', $doc).after('<input name="memo3" type="hidden">').next('[name="memo3"]').val('<?php echo @$provider;?>');
		$('[name="age"]', $doc).val('<?php echo @$userProfile->age;?>');
		$('[name="email"]', $doc).val('<?php echo @$userProfile->email;?>');
		$('[name="phone"]', $doc).val('<?php echo @$userProfile->phone;?>');
		<?php if(isset($gender)) {?>
		$('[name="gender"]', $doc).eq(<?php echo @$gender;?>).prop('checked', true);
		<?php } ?>
		$('.submit input', $doc).trigger('click');
		setTimeout(function() {
			window.self.close();
		}, 500)
	},500)
		$('.btn_close').click(function(e) {
			e.preventDefault();
			self.close;
		})
	})
	</script>
</body>
</html>