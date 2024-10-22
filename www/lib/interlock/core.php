<?php
class Interlock {
	private $rwdb, $rodb, $db;
	public $no, $app_name, $remote_addr;
	private $landing;
	

	public function __construct() {
		$this->setConnectDB(); //DB연결
	}

	private function setConnectDB() { //DB연결
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

    private function query($sql, $error=false) { //쿼리 전송
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

	///////////////////////////////////////////////////////////////////////////

	private function getLandingBySeq($seq) { //랜딩 정보 가져오기
    	$sql = "SELECT info.*, adv.name, adv.interlock_url, adv.agreement_url, adv.agent, med.media, med.target, med.seq AS med_seq, adv.is_stop AS adv_stop, GROUP_CONCAT(ek.keyword) AS keywords, ec.id AS pixel_id, ec.name AS pixel_name, ec.token AS access_token
                FROM zenith.event_information AS info
    				LEFT JOIN zenith.event_advertiser AS adv ON info.advertiser = adv.seq
    				LEFT JOIN zenith.event_media AS med ON info.media = med.seq
                    LEFT JOIN zenith.event_keyword_idx AS ki ON info.seq = ki.ei_seq
                    LEFT JOIN zenith.event_keyword AS ek ON ki.ek_seq = ek.seq
                    LEFT JOIN zenith.event_conversion AS ec ON info.pixel_id = ec.id
    			WHERE info.seq = {$seq}";
    	$result = $this->query($sql);
    	if(!$result->db->num_rows) {
    		throw new Exception("존재하지 않는 랜딩입니다."); //랜딩번호가 존재하지 않을경우 Exception 처리
    	}
    	$this->landing = $result->db->fetch_assoc();
        return $this->landing;
    }

	private function getAppsubscribeByEid($eid) { //등록 사용자 정보 가져오기
    	if(!$eid) return NULL;
    	$sql = "SELECT * FROM app_subscribe WHERE eid = {$eid}";
    	$result = $this->query($sql);

    	return $result;
    }

	public function send($eid) {
		$result = $this->getAppsubscribeByEid($eid);
		if($result->db) {
    		$data = $result->db->fetch_assoc();
    		$data['phone'] = $this->aes_decrypt($data['phone']);
			$data['reg_date'] = '';
        } else {
        	echo "eid:{$eid} eid 없음<br>";
        }
        $this->no = $data['event_seq'];
        $this->remote_addr = $data['ip'];
        $this->app_name = 'evt_'.$data['event_seq'];
		if($data['event_seq']) {
			$this->getLandingBySeq($data['event_seq']);
			if($this->landing['interlock']) { //외부연동이 true 일 경우
	            $eid = $data['eid'];
				$status = $data['status'];
	            $interlock_file = "./data/{$this->landing['name']}/interlock.php";
	            if(file_exists($interlock_file)) {//외부연동 파일이 있으면 진행
	                include $interlock_file;
	                if($interlock_data)
		            	echo "url:{$url}#data:{$interlock_data} 전송완료<br>";
		            else
		            	echo "eid:{$eid}#advertiser:{$this->landing['name']}#url:{$url}전송실패<br>";
		        }
	            else
	            	echo "eid:{$eid}#advertiser:{$this->landing['name']} 외부연동파일 없음<br>";

	        } else {
	        	echo "eid:{$data['eid']}#advertiser:{$this->landing['name']} 외부연동 광고주 아님<br>";
	        }
	    } else {
	    	echo "eid:{$data['eid']}#event_seq:{$data['event_seq']} 랜딩 정보 없음<br>";
	    }
	}
}