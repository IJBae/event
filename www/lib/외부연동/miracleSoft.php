<?php
// 테크랩스 연동부
$result = json_encode(["result"=>false, "msg"=>"테크랩스 연동실패"]);
// $meta_url = "http://otsrcapi.themiraclesoft.com:90/api/customer"; // 개발
$meta_url = "http://otsrcapi.mm-event.co.kr/api/customer"; // 운영
if(empty($data['add1'])) $data['add1'] = '.'; //add1 빈값 허용안함
$interlock_data = [
	"access_token" => "rS5jpFSx4h5BrKleqxe5F527j2pQEKrh3FllxA9v57BNXQCFDIouzQUmJR3vUB8oaPlMNYbe6ENZXLwckjuDRw",
	"event_seq" => (int)$this->no,							//이벤트 번호
	"event_description" => $this->landing['description'],	//이벤트 구분
	"media" => (int)$this->landing['med_seq'],				//매체 번호
	"media_name" => $this->landing['media'],				//매체명
	"advertiser" => (int)$this->landing['adv_seq'],			//광고주 번호
	"advertiser_name" => $this->landing['name'],			//광고주명
	"user_name" => $data['name'],							//회원 이름
	"user_phone" => (string)$data['phone'],					//회원 전화번호
	"user_add1" => $data['add1'],							//입력항목 1
	"reg_date" => $data['reg_date'],						//등록일시
	// "status" => $status,									//데이터 유효성
	"ip_address" => $this->remote_addr, 					//아이피
];
if(isset($data['age']) && $data['age']) $interlock_data['user_age'] = intval($data['age']);
if(isset($data['branch']) && $data['branch']) $interlock_data['user_branch'] = $data['branch'];
if(isset($data['email']) && $data['email']) $interlock_data['user_email'] = $data['email'];
if(isset($data['add2']) && $data['add2']) $interlock_data['user_add2'] = $data['add2'];
if(isset($data['add3']) && $data['add3']) $interlock_data['user_add3'] = $data['add3'];
if(isset($data['add4']) && $data['add4']) $interlock_data['user_add4'] = $data['add4'];
if(isset($data['add5']) && $data['add5']) $interlock_data['user_add5'] = $data['add5'];
if(isset($data['add6']) && $data['add6']) $interlock_data['user_add6'] = $data['add6'];
if(isset($data['site']) && $data['site']) $interlock_data['site'] = $data['site'];
if(isset($data['gender']) && $data['gender']) {
	if($data['gender'] == 'M') $interlock_data['user_gender'] = '남';
	else if($data['gender'] == 'F') $interlock_data['user_gender'] = '여';
}

$ch = curl_init();
$postField = json_encode($interlock_data);
curl_setopt_array($ch, array(
	CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => $meta_url,
	CURLOPT_POSTFIELDS => count($interlock_data),
	CURLOPT_POSTFIELDS => $postField
));
$response =curl_exec($ch);
// var_dump($postField);
// var_dump($response);
curl_close($ch);
$interlock_result[] = [
	'url' => $meta_url,
	'send_data' => $postField,
	'response_data' => $response
];