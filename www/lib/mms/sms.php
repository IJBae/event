<?php
include(__DIR__."/common_mms.php");

// $phone  = str_replace("-","",$phone);
$packettest = new MmsPacket;

$SeqNo = "1325";			//고객사측 일련번호
$CallPhone = $phone;	//수신번호 ex)01012345678
$ReqPhone = $ReqPhone;	//회신번호 ex)01078454545
$Time = "";		//안 넣었을 경우 즉시 발송 예약시 ex) 20991225231100
$SzTime = "";
$Subject = iconv('utf-8', 'euc-kr', $Subject);	//메시지 제목
$mms_img = $mms_img ?? '';
$filepath1="".$mms_img."";	//파일경로1
//$filepath2="";	//파일경로2
//$filepath3="";	//파일경로3

$Msg = $Msg;		//메시지 내용
$Msg = iconv('utf-8', 'euc-kr', $Msg);

/*단보 전송용*/
$result=$packettest->SendMms($SeqNo, $CallPhone, $ReqPhone, $Time, $Subject, $Msg,$filepath1,$filepath2 ?? '',$filepath3 ?? '');
?>