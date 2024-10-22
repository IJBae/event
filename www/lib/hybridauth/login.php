<?php
// 회원가입 & 로그인 하기
session_start();

include 'login_process.inc.php';

if ($is_success === true) {
	if ($user_data) {
		/*
		echo '<pre>';
		print_r($user_data);
		echo '</pre>';
		*/
		$adapter->logout();

		$userid = $config['providers'][$provider]['initial'].'_'.hash('adler32', md5($user_data->identifier));

		// 로직 출처 - /home3/member/login_facebook.php
		include($_SERVER["DOCUMENT_ROOT"]."/home3/inc/configuser.php"); //사용자환경
		require ($_SERVER["DOCUMENT_ROOT"]  ."/home3/inc/lib_admin/db_class.php");
		$dbcon = new MysqlClass;

		// 이미 sns회원가입 했는지 여부 체크
		$need_join = false;
		if ($userid) {
			$query = "SELECT * FROM member where id='".$userid."'";
			$row = $dbcon->fetch($query);

			if($row[exit_chk]=="on") alert_popup('탈퇴회원 입니다.');
			elseif($row[inter_chk]=="on") alert_popup('차단회원 입니다.');
			elseif(!$row[id]) $need_join = true;
		} else {
			$need_join = true;
		}
		$_hp = $_tel = '';
		// $user_data->phone 핸드폰번호 여부 체크
		$tmp = preg_replace("/[^0-9]/", "", $user_data->phone);
		if(preg_match("/^01[0-9]{8,9}$/", $tmp))
			$_hp = preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $tmp);
		else
			$_tel = $user_data->phone;

		$_birth = ($user_data->birthYear)? trim($user_data->birthYear.'-'.str_pad($user_data->birthMonth, 2, '0', STR_PAD_LEFT).'-'.str_pad($user_data->birthDay, 2, '0', STR_PAD_LEFT), '-'):'';

		if ($need_join) {
			$query2 = "INSERT INTO member (id,pass, email, nick, name, lev, hp, tel, birth, indate) VALUES ( '".$userid."','".$passwd."',  '".$user_data->email."', '".$user_data->displayName."', '".$user_data->displayName."','0','".$_hp."','".$_tel."','".$_birth."','".date("Y-m-d H:i:s")."')";
			$dbcon->excute($query2);
			
			$query = "SELECT * FROM member where id='".$userid."'";
			$row = $dbcon->fetch($query);
		}

		// 생년월일 값이 있는 경우, 나이 계산(만 나이)
		if (!$_birth && $row['birth']) $_birth = trim($row['birth'], '-');
		if ($_birth) {
			$birthday =				date('Ymd' , strtotime($_birth));
			$user_data->age =	floor((date('Ymd') - $birthday) / 10000);
		}

		// $user_data 에는 값이 없는데 $row는 값이 있는 경우, $row의 값으로 셋팅
		if ($user_data->displayName == '' && $row['name']) $user_data->displayName = $row['name'];
		if ($user_data->phone == '' && $row['hp']) {
			$user_data->phone = $_hp = $row['hp'];
			$_hp = preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", preg_replace("/[^0-9]/", "", $_hp));
		}
		if ($user_data->email == '' && $row['email']) $user_data->email = $row['email'];

		$_SESSION['member']['host']		= $_SERVER[HTTP_HOST];
		$_SESSION['member']['id']		= $row['id'];
		$_SESSION['member']['name']		= $row['name'];

		if($row['nick'] != "") {
			$_SESSION['member']['nick']		= $row['nick'];
		}else {
			$_SESSION['member']['nick']		= $row['name'];
		}

		$_SESSION['member']['email']	= $row['email'];
		$_SESSION['member']['lev']		= $row['lev'];


		$query = "update member set last_date = '".date("Y-m-d H:i:s")."' where id = '".$userid."'";
		$dbcon->excute($query);
		$form = '<!doctype html><html lang="en"><head><meta charset="UTF-8"></head><body><script>%1$s; self.close();</script></body></html>';
		if (isset($_GET['callback']) && $_GET['callback']) {
			// 연락처 정보
			$user_data->phone2 = $_hp;
			printf($form, 'var userData = '.json_encode($user_data).'; opener.'.$_GET['callback'].'(userData)');	//opener.focus(); 
		} else {
			printf($form, 'opener.reload()');
		}
	} else {
		alert_popup('사용자 프로필을 찾을 수 없습니다.');
	}
} elseif (empty($s) === false) {
	alert_popup($s);
}