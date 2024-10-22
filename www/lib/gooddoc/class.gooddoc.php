<?php
Class Gooddoc{
    public $url;
    public function __construct(){
        $this->url = "https://api-event-v2.goodoc.co.kr/call_request";
    }

    // DATA FORM 형성
    public function form_data_set($data, $event_id, $funnel, $supply_third_party_agree){
        $privacy_agree = $data['agree']=='Y'?1:0;
        $privacy_process_agree = $data['agree']=='Y'?1:0;
        $supply_third_party_agree = $supply_third_party_agree==1?$supply_third_party_agree:0;
        $array_data = array(
            "phone"=>$data['phone'],        // 유저 전번
            "name"=>$data['name'],          // 유저 이름
            "event_id"=>$event_id,          // 굿닥파트너스에서 받은 id
            "funnel"=>$funnel,
            "privacy_agree"=>$privacy_agree,                        // 동의 "1" / 미동의 "0"
            "privacy_process_agree"=>$privacy_process_agree,        // 동의 "1" / 미동의 "0"
            "supply_third_party_agree"=>$supply_third_party_agree,  // 동의 "1" / 미동의 "0"
            // 하드코딩
            "call_time"=>$data['add1']."/".$data['add2']."/".$data['add3']."/".$data['add4'],
            // "is_import"=>"1",
            "channel"=>"1",                 // 굿닥 파트너스 flag값
            "device"=>"3",
            "content"=>"",                  // 문의란이 있다면 문의란
            "sex"=>$data['gender'],            // 성별: 1(여자), 2(남자), 3(알수 없음)
            "age"=>$data['age']             // 옵셔널
        );

        return $array_data;
    }

    // 전송
    public function curlDBData_gooddoc($data){
        $postData = json_encode($data);
        $headers = array( "Content-Type: application/json;" , "Content-Length: ".strlen($postData)."" , );

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url,
            // CURLOPT_POSTFIELDS => count($data),
            CURLOPT_POSTFIELDS => $postData
        ));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    // 응답값 저장
    public function result_save($db, $returnvalue, $eid){
        $sql = "UPDATE app_subscribe SET memo3 = '$returnvalue' WHERE eid = '{$eid}'";
        if(!$result = $db->query($sql)){
            // echo $db->error;
        }
        return $sql;
    }


    #########################################################################################################

    private $rwdb, $rodb, $db;

	public function setConnectDB() { //DB연결
		include __DIR__."/../../dbinfo.php";
		$this->rwdb = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
		$this->rwdb->query("set session character_set_client=utf8mb4;");
		$this->rwdb->query("set session character_set_connection=utf8mb4;");
		$this->db = $this->rwdb;
		if(defined('MYSQL_RO_HOST')) { //ReadOnly Host가 있을 경우 연결
			$this->rodb = mysqli_connect(MYSQL_RO_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
			$this->rodb->query("set session character_set_client=utf8mb4;");
			$this->rodb->query("set session character_set_connection=utf8mb4;");
		}
	}

    public function query($sql, $error=false) { //쿼리 전송
        if(!$sql) return false;
        $result = null;
        $data = new stdClass();
        if($this->rodb && preg_match('#^select.*#i', trim($sql))) //Select 는 ReadOnly DB로 연결
            $this->db = $this->rodb;
        else
            $this->db = $this->rwdb;

        $this->db->query("BEGIN"); //트랜젝션 시작
        if($error) {//error 가 true 일 경우 에러메시지 출력
            $result = $this->db->query($sql) or die($this->db->error);
        } else {
            $result = $this->db->query($sql);
        }
        if($result) {
        	$data->db = $result;
        	$data->insert_id = $this->db->insert_id;
        	$this->db->query("COMMIT"); //트랜젝션 커밋
        } else 
        	$this->db->query("ROLLBACK"); //트랜젝션 롤백
        return $data;
    }

    //암호화 함수
    private function aes_encrypt($data, $key='++!CHAINSAW!++'){
		$key = substr(hex2bin(openssl_digest($key, 'sha512')),0,16);
		$enc = openssl_encrypt($data, "AES-128-ECB", $key, true);
		return strtoupper(bin2hex($enc));
	}

    //복호화 함수
	private function aes_decrypt($data, $key='++!CHAINSAW!++'){
		$data = hex2bin($data);
		$key = substr(hex2bin(openssl_digest($key, 'sha512')),0,16);
		$dec = openssl_decrypt($data, "AES-128-ECB", $key, true);
		return $dec;
	}

	private function getAppsubscribeByEid($eid) { //등록 사용자 정보 가져오기
    	if(!$eid) return NULL;
    	$sql = "SELECT * FROM app_subscribe WHERE eid = {$eid}";
    	$result = $this->query($sql);

    	return $result;
    }

    public function send($eid, $event_id, $funnel, $agBox_mkt=0) {
		$result = $this->getAppsubscribeByEid($eid);
		if($result->db) {
    		$data = $result->db->fetch_assoc();
            if (is_array($data)) {
                array_walk_recursive($data,function(&$v){if(is_string($v))$v=$this->rwdb->real_escape_string($v);});
            }
    		$data['phone'] = $this->aes_decrypt($data['phone']);

            $gd_data = $this->form_data_set($data, $event_id, $funnel, $agBox_mkt);
            $gd_rst = $this->curlDBData_gooddoc($gd_data);
            $rst = json_decode($gd_rst, true);
            if($rst['status']){
                echo "eid:{$eid} 굿닥 연동 완료<br>";
            }else{
                echo "eid:{$eid} 굿닥 연동 실패 >> ".$rst['message']."<br>";
            }
            $this->result_save($this->rwdb, $gd_rst, $data['eid']);
        } else {
        	echo "eid:{$eid} eid 없음<br>";
        }
	}
}
?>