<?php
include __DIR__."/../../dbinfo.php";
$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("set session character_set_client=utf8mb4;");
$db->query("set session character_set_connection=utf8mb4;");

$type = @$_POST['type'];
$phone = @$_POST['phone'];
if(!$phone) exit(json_encode(['result'=>false, 'msg'=>'휴대폰번호를 입력해주세요.']));
if($type == 'confirm') {
	$auth_no = @$_POST['auth_no'];
	$sql = "SELECT * FROM event_sms_auth WHERE is_auth = 0 AND phone = '{$phone}' AND auth = '{$auth_no}' AND reg_datetime >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY reg_datetime DESC LIMIT 1";
	$result = $db->query($sql);
	if($result->num_rows) {
		$sql = "UPDATE event_sms_auth SET is_auth = 1, auth_datetime = NOW() WHERE is_auth = 0 AND phone = '{$phone}' AND auth = '{$auth_no}'";
		if($db->query($sql)) {
			exit(json_encode(['result'=>true, 'msg'=>'인증코드가 확인되었습니다.']));
		}
	} else {
		exit(json_encode(['result'=>false, 'msg'=>'인증코드가 잘못되었습니다.']));
	}
} else {
	/* SMS 구간 */
	$Subject  ="SMS인증"; //메시지제목
	$ReqPhone = $phone; //발신번호
	$phone = preg_replace('/[^0-9]/', '', $phone);
	$auth_no = rand(100000, 999999);
	$Msg = "문자 인증번호는 [{$auth_no}]입니다.";

	$sql = "SELECT * FROM event_sms_auth WHERE is_auth = 0 AND phone = '{$phone}' AND reg_datetime >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY reg_datetime DESC";
	$result = $db->query($sql);
	if($result->num_rows) {
		$data = $result->fetch_assoc();
		if($result->num_rows && strtotime($data['reg_datetime']) >= strtotime('-1 minute')) {
			exit(json_encode(['result'=>false, 'msg'=>'코드 재전송은 1분 후 가능합니다.']));
		} else if($result->num_rows > 5) {
			exit(json_encode(['result'=>false, 'msg'=>'불량이용자로 등록되어 24시간동안 SMS인증을 사용할 수 없습니다.']));
		}
	}
	include(__DIR__."/common_mms.php");
	$packettest = new MmsPacket;
	$SeqNo = "1325";			//고객사측 일련번호
	$CallPhone = $phone;	//수신번호 ex)01012345678
	$ReqPhone = $ReqPhone;	//회신번호 ex)01078454545
	$Time = "";		//안 넣었을 경우 즉시 발송 예약시 ex) 20991225231100
	$SzTime = "";
	$Subject = iconv('utf-8', 'euc-kr', $Subject);	//메시지 제목

	$Msg = $Msg;		//메시지 내용
	$Msg = iconv('utf-8', 'euc-kr', $Msg);

	/*단보 전송용*/
	$response = $packettest->SendLms($SeqNo, $CallPhone, $ReqPhone, $Time, $Subject, $Msg);
	if($response == "O") {
		$now = date('Y-m-d H:i:s');
		$sql = "INSERT INTO event_sms_auth(phone, auth, reg_datetime) VALUES('{$CallPhone}', '{$auth_no}', '{$now}');";
		if($db->query($sql)) {
			exit(json_encode(['result'=>true, 'msg'=>'인증코드가 전송되었습니다.', 'datetime'=>$now]));
		}
	} else {
		exit(json_encode(['result'=>false, 'msg'=>'인증코드 전송에 실패하였습니다.']));
	}
}