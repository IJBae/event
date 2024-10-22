<?php
/*
* @brief hotevent 를 대체하기 위해 새롭게 개발된 신규 랜딩
* @author Jaybe
* @see 
* 	event_information - 랜딩 테이블
* 	event_advertiser - 광고주 테이블
* 	event_media - 매체 테이블
* 	랜딩테이블 기준으로 광고주, 매체 테이블이 인덱싱 구조로 구성되어있음
* 	URL Structure : https://event.hotblood.co.kr/랜딩번호/메소드
*/
class HotbloodEvent
{ //Event 클래스
    private $rwdb, $rodb, $db;
    private $paths, $real_paths, $landing;
    private $remote_addr, $visitor;
    private $our_ip = ['59.9.155.0/24', '127.0.0.1']; //사무실IP, 로컬호스트
    private $is_our = false;
    private $comments = [];
    private $replys = [];
    private $totalCount = 0;
    private $todayCount = 0;
    public $no, $hash_no, $app_name, $method = 'view', $params;
    private $aes_key;
    private $userProfile = [];

    public function __construct($paths = null)
    {
        $this->real_paths = $paths;
        if(is_null($paths)) {
            $this->setConnectDB();
            return;
        }
        session_set_cookie_params(0, "/", '.' . $_SERVER['HTTP_HOST'], false, true);
        ini_set("session.cookie_domain", '.' . $_SERVER['HTTP_HOST']);
        ini_set("session.cookie_secure", 1);
        ini_set("session.cookie_httponly", 1);
        @session_start();
        header('P3P: CP="NOI CURa ADMa DEVa TAIa OUR DELa BUS IND PHY ONL UNI COM NAV INT DEM PRE"');
        header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');
        header('P3P: CP="ALL ADM DEV PSAi COM OUR OTRo STP IND ONL"');
        header('P3P: CP="CAO PSA OUR"');
        @set_exception_handler(array($this, 'exception_handler'));
        if (@__DEV__) { //개발모드 일 경우 error_reporting 작동
            error_reporting(E_ALL & ~E_NOTICE);
            ini_set('display_errors', 'On');
            define("EVENT_URL", "");
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
            define("EVENT_URL", "//event.hotblood.co.kr");
        }
        /*
        if (preg_match('/^(local\.)?event\.hotblood\.co\.kr/', $_SERVER['HTTP_HOST'])) {
            define("ROOT_PATH", ".");
        } else {
            define("ROOT_PATH", "..");
        }
        */
        define("ROOT_PATH", "..");
        $this->getAesKey();
        $this->hash_no = $paths[1];
        if(preg_match('/[A-Z]{2}[0-9]+/', strtoupper($paths[1]))) {
            if($this->chkHash($paths[1])) {
                $this->hash_no = $paths[1];
                $paths[1] = substr($paths[1], 2);
            }
        } else {
            $this->hash_no = $this->makeHash($paths[1]);
        }
        $this->paths = $paths;
        $this->no = $paths[1]; //랜딩번호
        $this->app_name = 'evt_' . $this->no;
        if(isset($paths[2]))
            $this->method = @$paths[2]; //method 저장
        if(!preg_match('/^([A-Z]{2})?[0-9]+$/', strtoupper($paths[1])))
            $this->method = $paths[1];

        if(!$this->no) //랜딩번호가 없으면 exception 처리
            throw new Exception("잘못된 접근입니다.");
        $this->setConnectDB(); //DB연결
        $this->getRemoteAddr(); //REMOTE_ADDR 세팅
        if (!isset($_SESSION['browser'])) //Browser 정보 세션으로 저장
            $_SESSION['browser'] = get_browser();

        if (preg_match('/[0-9]+/', $this->no)) {
            $this->getLandingBySeq(); //랜딩 정보 호출
            $_SESSION['no'] = $this->no;
            if (isset($_GET['site'])) $_SESSION['site'] = $_GET['site'];
            if (isset($_GET['code'])) $_SESSION['code'] = $_GET['code'];
        }
        if(!$this->landing['no_hash'] && @__DEV__ === false && $this->real_paths[1] == $this->no && $this->method == 'view') //일반 주소도 같이 사용에 체크가 되어있는지 확인
            throw new Exception("잘못된 접근입니다.");

        // $this->getKakao();

        //메가더포르테 도메인 전용
        if (preg_match('/^megatheforte\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '메가더포르테') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }

        //미담치과의원 도메인 전용
        //개발요청번호:22481 / 20220824 김하정 요청
        //        if (preg_match('/^careevt\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '미담치과의원') {
        //            throw new Exception("존재하지 않는 이벤트입니다.");
        //        }

        //스탠다드치과의원 도메인 전용
        if (preg_match('/^heybt\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '스탠다드치과의원') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }

        //서울권치과의원 도메인 전용
        if (preg_match('/^cutbut\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '서울권치과의원') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }

        //그라클레스 도메인 전용
        if (preg_match('/^gragracules\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '그라클레스') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }
        if (preg_match('/^smrstoremall\.shop/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '그라클레스') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }
        if (preg_match('/^smrstoremall\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '그라클레스') {
            throw new Exception("존재하지 않는 이벤트입니다.");
        }

        //닥터크리미의원 도메인 전용 - 찬영님 요청 > 220705/하정님 요청으로 해제
        // if (preg_match('/^vviibbee\.co\.kr/', $_SERVER['HTTP_HOST']) && $this->landing['name'] != '닥터크리미의원') {
        //     throw new Exception("존재하지 않는 이벤트입니다.");
        // }


        $this->is_our = $this->chk_ip($this->our_ip); //내부 사용자인지 체크
        $this->landing['is_our'] = $this->is_our;

        $this->visitor = $this->get_cookie(md5('chainsaw'));
        if (!$this->visitor) {
            $this->visitor = $this->aes_encrypt($this->remote_addr . '/' . microtime(true) . '/' . $this->no); //사용자 고유코드 생성
            $this->set_cookie(md5('chainsaw'), $this->visitor, 2592000); //30일 Cookie Set
        }

        $this->ipBlocker();
    }

    protected function chk_ip($list=[]) {
        $is_ip_chk = false;
        foreach($list as &$ip) {
            $ip = trim($ip);
            if(strpos($ip, "/") !== false) {
                if($this->_ip_match($ip, $this->remote_addr) == true) {
                    $is_ip_chk = true;
                    break;
                }
            }
        }
        if(in_array($this->remote_addr, $list) === false && $is_ip_chk === false) {
            return false;
        }
        return true;
    }

    protected function _ip_match($network, $remote_addr) {
        $ip_arr = explode("/", $network);
        $network_long = ip2long($ip_arr[0]);
        $mask_long = pow(2,32)-pow(2,(32-$ip_arr[1]));
        $ip_long = ip2long($remote_addr);
        if(($ip_long & $mask_long) == $network_long) {
            return true;
        }
        return false;
    }

    private function ipBlocker() {
        /*
        $mobile_ip = [
            '203.226.0.0/16'     // SKT 3G
            ,'211.234.0.0/16'    // SKT 3G
            ,'223.32.0.0/11'     // SKT 4G, 5G
            //,'2001:2d8::/32'     // SKT 4G, 5G IPv6
            ,'39.7.0.0/24'       // KT 3G, 4G, 5G
            ,'110.70.0.0/16'     // KT 3G, 4G, 5G
            ,'175.223.0.0/16'    // KT 3G, 4G
            ,'211.246.0.0/16'    // KT 3G
            ,'118.235.0.0/16'    // KT 4G, 5G
            ,'211.246.0.0/16'    // KT 4G
            //,'2001:e60::/32'     // KT 4G, 5G IPv6
            ,'61.43.0.0/16'      // LG 3G
            ,'211.234.0.0/16'    // LG 3G
            ,'106.102.0.0/16'    // LG 4G
            ,'117.111.0.0/16'    // LG 4G
            ,'211.36.0.0/16'     // LG 4G
            ,'106.101.0.0/16'    // LG 5G
            //,'2001:4430::/32'    // LG 5G IPv6
        ];
        */
        //IP Blocker
        if ($this->landing['lead'] == 3) return; //외부연동일 때 외부에서 대량으로 POST를 보내야할 수도 있어서 IP Blocker를 실행하지 않음
        $sql = "SELECT * FROM zenith.ip_blocker WHERE ip = '{$this->remote_addr}' ORDER BY seq DESC LIMIT 1";
        $result = $this->query($sql);
        $blocked = $result->db->num_rows;
        if ($blocked) $block = $result->db->fetch_assoc();
        if ($blocked && strtotime($block['term']) >= time()) {
            throw new Exception("부정접속으로 확인되어 1시간동안 접속이 차단됩니다.");
        } else if ($blocked && $block['forever'] == 1) {
            throw new Exception("지속적인 부정접속으로 확인되어 사이트 접속이 차단됩니다.");
        }

        //session 으로 분당 접속 카운트
        if ($_SESSION['visit']['datetime'] && strtotime($_SESSION['visit']['datetime'] . ' +1 minute') >= time()) {
            $_SESSION['visit']['count']++;
        } else {
            $_SESSION['visit']['datetime'] = date('Y-m-d H:i:s');
            $_SESSION['visit']['count'] = 1;
        }
        //카운트 분당 30회 이상일 경우 블럭처리
        if ($_SESSION['visit']['count'] >= 30 && !$this->is_our) {
            $sql = "INSERT INTO zenith.ip_blocker(ip, term, memo, reg_date) VALUES('{$this->remote_addr}', DATE_ADD(NOW(), INTERVAL 1 HOUR), '30회 이상 접속 차단', NOW()) ON DUPLICATE KEY UPDATE ip = VALUES(ip), term = VALUES(term), reg_date = VALUES(reg_date)";
            $this->query($sql);
            unset($_SESSION['visit']);
        }
        //IP Blocker End
    }

    public function __call($method, $params)
    { //잘못된 메소드 호출은 예외처리
        throw new Exception("잘못된 호출입니다.");
    }

    private function setConnectDB()
    { //DB연결
        if(!isset($this->db)) require_once __DIR__ . "/dbinfo.php";
        $this->rwdb = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
        $this->rwdb->query("set session character_set_client=utf8mb4;");
        $this->rwdb->query("set session character_set_connection=utf8mb4;");
        $this->db = $this->rwdb;
        if (defined('MYSQL_RO_HOST')) { //ReadOnly Host가 있을 경우 연결
            $this->rodb = mysqli_connect(MYSQL_RO_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
            $this->rodb->query("set session character_set_client=utf8mb4;");
            $this->rodb->query("set session character_set_connection=utf8mb4;");
        }
    }

    public function copy()
    {
        $old_seq = $this->paths[2];
        $new_seq = $this->paths[3];
        $res = $this->db->query("SELECT MAX(seq) AS seq FROM zenith.event_advertiser LIMIT 1");
        $row = $res->fetch_assoc();
        if ($row['seq'] != $new_seq) {
            exit('The event does not exist in database.');
        }
        if ($old_seq && $new_seq) {
            $res = $this->db->query("SELECT name FROM zenith.event_advertiser WHERE seq = {$old_seq}");
            $row = $res->fetch_assoc();
            copy(__DIR__ . "/data/{$row['name']}/v_{$old_seq}.php", __DIR__ . "/data/{$row['name']}/v_{$new_seq}.php");
        }
    }


    public function view()
    { //랜딩 페이지 호출
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');

        $appendHtml = [];
        if ($this->landing['is_stop'] || $this->landing['adv_stop']) {
            if ($this->landing['is_stop']) $txt = '차단 중인 이벤트입니다.';
            if ($this->landing['adv_stop']) $txt = '사용 중지된 광고주입니다.';
            if ($this->is_our)
                $appendHtml['header'] = '<div class="block_msg animate__animated animate__flash animate__slow animate__repeat-2 animate__delay-1s" onclick="$(this).addClass(\'hide\');">' . $txt . '</div>';
            else
                throw new Exception("사용할 수 없는 이벤트입니다."); //사용중지 상태일 경우 exception 처리
        }

        $file = "./data/{$this->landing['name']}/v_{$this->no}.php";
        if (!file_exists($file)) throw new Exception("이벤트 파일이 존재하지 않습니다."); //랜딩파일이 없을 경우 exception 처리
        if (isset($_SESSION['media'])) $this->landing['media'] = $_SESSION['media'];
        unset($_SESSION['params']);
        if(count($_GET) > 0)
            $_SESSION['params'] = $_GET; //랜딩 접근시 parameter가 있을 시 추후 활용을 위해 세션에 저장
        if(!isset($_GET['site'])) $_GET['site'] = '';
        // app_subscribe 기반 댓글
        $sql = "SELECT * FROM APP_SUBSCRIBE WHERE group_id = '{$this->app_name}' AND deleted = 0 order by eid desc LIMIT 0,20";
        $result = $this->query($sql);
        if ($result->db->num_rows) {
            while ($row = $result->db->fetch_assoc()) {
                $row['name'] = mb_substr($row['name'], 0, 1, "UTF-8") . "**";
                $row['reg_date'] = date('m-d H:i', strtotime($row['reg_date']));
                if ($row['enc_status'] == 1) $row['phone'] = $this->aes_decrypt($row['phone']);
                $row['phone'] = '010-****-**' . substr($row['phone'], -2); //이름 마스크 처리 
                $this->comments[] = $row;
            }
        }
        // event_reply 기반 댓글
        $sql = "SELECT * FROM EVENT_REPLY WHERE event_seq = '{$this->no}' order by seq desc LIMIT 0,20";
        $result = $this->query($sql);
        if ($result->db->num_rows) {
            while ($row = $result->db->fetch_assoc()) {
                $row['reg_date'] = date('m-d H:i', strtotime($row['er_datetime']));
                $this->replys[] = $row;
            }
        }
        // app_subscribe 기반 db 갯수
        $sql = "SELECT count(eid) as count FROM APP_SUBSCRIBE WHERE group_id = '{$this->app_name}' AND deleted = 0 ";
        $result = $this->query($sql);
        if ($result->db->num_rows) {
            $row = $result->db->fetch_assoc();
            $this->totalCount = $row['count'];
        }
        $sql = "SELECT count(eid) as count FROM APP_SUBSCRIBE WHERE group_id = '{$this->app_name}' AND deleted = 0 AND date(reg_date) = date(NOW())";
        $result = $this->query($sql);
        if ($result->db->num_rows) {
            $row = $result->db->fetch_assoc();
            $this->todayCount = $row['count'];
        }

        $this->makePage($file, NULL, $appendHtml);
        $_SESSION['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        if ($this->landing['pixel_id']) {
            $this->FBPixel();
        }
    }

    public function act()
    {
        $file = "./data/{$this->landing['name']}/v_{$this->no}_act.php";
        if (!file_exists($file)) die(json_encode(['result' => false, 'msg' => '파일이 존재하지 않습니다.']));

        include $file;
    }

    private function FBPixel($event = 'ViewContent', $param = null)
    {
        if (!$this->landing['pixel_id'] || !$this->landing['access_token']) return false;
        $url = "https://graph.facebook.com/v10.0/{$this->landing['pixel_id']}/events?access_token={$this->landing['access_token']}";
        $data = [
            'event_name' => $event, 'event_id' => time() . $this->visitor, 'event_time' => time(), 'event_source_url' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}" . urlencode($_SERVER['REQUEST_URI']), 'action_source' => "website", 'user_data' => [
                'client_ip_address' => $this->remote_addr, 'client_user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]
        ];
        if ($event == 'CompleteRegistration') {
            $data['user_data']['fn'] = hash("sha256", $param['name']);
            $data['user_data']['ph'] = hash("sha256", $param['phone']);
            $data['custom_data']['currency'] = "KRW";
        }

        $data = "data=[" . json_encode($data) . "]";
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ));
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $result = json_decode($result, true);
        // echo json_encode($data);
        // echo '<pre>'.print_r($headers,1).'</pre>';
        // echo '<pre>'.print_r($data,1).'</pre>';
        // echo '<pre>'.print_r($info,1).'</pre>';
        // echo '<pre>'.print_r($result,1).'</pre>';
        curl_close($ch);
        if ($result['events_received']) {
            echo "<!-- {$this->landing['pixel_name']}({$this->landing['pixel_id']}) / {$event} -->";
            return true;
        } else
            echo "<!-- ".json_encode($result)." -->";
            return false;
    }

    public function check_proc()
    {
        $return = $this->validator();
        if ($return['result'] === true) {
            foreach ($_POST as $k => $v) if (is_array($v)) $_POST[$k] = implode(',', $v);
            //Injection 방지 및 따옴표 오류 방지를 위해 escape 처리
            $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
            $data['group_id'] = $this->app_name;
            $data['event_seq']= $this->no;
            $data['name']     = $this->db->real_escape_string(trim($_POST['name'] ?? ''));
            $data['age']      = $this->db->real_escape_string(trim($age));
            $data['branch']   = $this->db->real_escape_string(trim($_POST['branch'] ?? ''));
            $data['email']    = $this->db->real_escape_string(trim($_POST['email'] ?? ''));
            $data['gender']   = $this->db->real_escape_string(trim($_POST['gender'] ?? ''));
            $data['phone']    = $this->db->real_escape_string(preg_replace("/[^0-9]/", "", $_POST["phone"] ?? ''));
            $data['add1']     = $this->db->real_escape_string(trim($_POST['add1'] ?? ''));
            $data['add2']     = $this->db->real_escape_string(trim($_POST['add2'] ?? ''));
            $data['add3']     = $this->db->real_escape_string(trim($_POST['add3'] ?? ''));
            $data['add4']     = $this->db->real_escape_string(trim($_POST['add4'] ?? ''));
            $data['add5']     = $this->db->real_escape_string(trim($_POST['add5'] ?? ''));
            $data['add6']     = $this->db->real_escape_string(trim($_POST['add6'] ?? ''));
            $data['add7']     = $this->db->real_escape_string(trim($_POST['add7'] ?? ''));
            $data['add8']     = $this->db->real_escape_string(trim($_POST['add8'] ?? ''));
            $data['add9']     = $this->db->real_escape_string(trim($_POST['add9'] ?? ''));
            $data['addr']     = $this->db->real_escape_string(trim($_POST['addr'] ?? ''));
            $data['site']     = $this->db->real_escape_string(trim($_POST['site'] ?? ''));
            $data['memo']     = $this->db->real_escape_string(trim($_POST['memo'] ?? ''));
            $data['memo3']    = $this->db->real_escape_string(trim($_POST['memo3'] ?? ''));
            $data['reg_date'] = date('Y-m-d H:i:s');
            $agBox            = $this->db->real_escape_string(trim($_POST['agBox'] ?? ''));
            if($_POST['local']) $data['add9'] = $_POST['local'];
            if ($this->landing['custom']) {
                $json = json_decode(str_replace('\\', '', $this->landing['custom']), true);
                $idx = 0;
                while ($idx < sizeof($json)) {
                    // if(!$data[$json[$idx]['key']]) $data[$json[$idx]['key']] = $json[$idx]['val'];
                    if ($json[$idx]['val'] && $json[$idx]['val'] != "") $data[$json[$idx]['key']] = $json[$idx]['val'];
                    $idx++;
                }
            }

            $data['agree'] = 'N';
            if ($agBox) $data['agree'] = 'Y'; //agBox 값이 있으면 동의 체크된 값이므로 value에 상관없이 Y로 보정

            $sql = "SELECT * FROM zenith.event_blacklist WHERE `data` = '{$data['phone']}' OR `data` = '{$this->remote_addr}'";
            $result = $this->query($sql);
            if ($result->db->num_rows) { //연락처 또는 아이피가 블랙리스트에 있을 경우 저장하지않고 thanks로 바로 넘김
                $sql = "INSERT INTO app_subscribe_blacklist(`group_id`, `event_seq`, `branch`, `name`, `age`, `gender`, `phone`, `email`, `agree`, `add1`, `add2`, `add3`, `add4`, `add5`, `add6`, `add7`, `add8`, `add9`, `memo`, `memo3`, `addr`, `site`, `ip`, `enc_status`)
                        VALUES('$this->app_name', '$this->no', '{$data['branch']}', '{$data['name']}', '{$data['age']}', '{$data['gender']}', enc_data('{$data['phone']}'), '{$data['email']}', '{$data['agree']}', '{$data['add1']}', '{$data['add2']}', '{$data['add3']}', '{$data['add4']}', '{$data['add5']}', '{$data['add6']}', '{$data['add7']}', '{$data['add8']}', '{$data['add9']}', '{$data['memo']}', '{$data['memo3']}', '{$data['addr']}', '{$data['site']}', '$this->remote_addr', 1)";
                $result = $this->query($sql, true);
                $return = ['result' => false, 'data' => 'thanks'];
                return $return;
            }
            if ($data['name'] && ($data['phone'] || $data['email'])) { //이름과 전화번호는 필수
                $data['reg_date'] = date('Y-m-d H:i:s');
                $sql = "INSERT INTO app_subscribe(`group_id`, `event_seq`, `branch`, `name`, `age`, `gender`, `phone`, `email`, `agree`, `add1`, `add2`, `add3`, `add4`, `add5`, `add6`, `add7`, `add8`, `add9`, `memo`, `memo3`, `addr`, `site`, `ip`, `enc_status`, `reg_date`)
                        VALUES('$this->app_name', '$this->no', '{$data['branch']}', '{$data['name']}', '{$data['age']}', '{$data['gender']}', enc_data('{$data['phone']}'), '{$data['email']}', '{$data['agree']}', '{$data['add1']}', '{$data['add2']}', '{$data['add3']}', '{$data['add4']}', '{$data['add5']}', '{$data['add6']}', '{$data['add7']}', '{$data['add8']}', '{$data['add9']}', '{$data['memo']}', '{$data['memo3']}', '{$data['addr']}', '{$data['site']}', '$this->remote_addr', 1, '{$data['reg_date']}')";
                $result = $this->query($sql, true);
                if ($result->insert_id) { //저장 완료 시
                    $data['eid'] = $result->insert_id;
                    $enc_param = $this->aes_encrypt($data['eid']);
                    $check = $this->checkAppsubscribe($data); //인정기준 처리
                    $status = $check['status'];
                    $zenith_result = $this->insertDataToZenith($data); //Zenith 처리
                    if($zenith_result)
                        $data['seq'] = $zenith_result->insert_id;
                    $done_cookie = $this->aes_encrypt("{$this->remote_addr}_".time());
                    $this->set_cookie("Thanks_{$this->no}", $done_cookie, 86400*$this->landing['duplicate_term']); //저장완료 시 고유 쿠키 생성
                    $interlock_result = [];
                    if ($this->landing['interlock']) { //외부연동이 true 일 경우
                        $eid = $data['eid'];
                        $interlock_file = "./data/{$this->landing['name']}/interlock.php";
                        if (file_exists($interlock_file)) //외부연동 파일이 있으면 진행
                            include $interlock_file;
                    }
                    $proc_file = "./data/{$this->landing['name']}/v_{$this->no}_proc.php";
                    if (file_exists($proc_file))
                        include $proc_file;
                    
                    if(count($interlock_result)){ //proc 에서도 interlock 이 일어나는 경우가 있어서 직전에 정리
                        $data = array_merge($data, ['interlock_result'=>$interlock_result]);
                    }
                    $this->zenithAfterInsert($data);
                    
                    $return = ['result' => true, 'data' => $enc_param];
                } else {
                    $return = ['result' => false, 'msg' => "데이터 저장에 문제가 발생하였습니다.<br><br>문제가 계속 될 경우 jaybe@carelabs.co.kr 로 문의주세요."];
                }
            } else {
                if (preg_match('/instagram/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $return = ['result' => false, 'msg' => "개인정보 암호화 성공!\n\n다시 한 번 신청 버튼을 눌러주세요."];
                } else {
                    $return = ['result' => false, 'msg' => "필수값이 누락되어 데이터를 저장할 수 없습니다."];
                }
            }
        }
        return $return;
    }
    //! Zenith
    private function insertDataToZenith($data) { //Zenith DB저장 처리
        if(!isset($data['file_url'])){
            $data['file_url'] = '';
        }
        
        $sql = "INSERT INTO `zenith`.`event_leads`(`event_seq`,`site`,`name`,`phone`,`reg_date`,`gender`,`age`,`branch`,`addr`,`email`,`agree`,`add1`,`add2`,`add3`,`add4`,`add5`,`file_url`,`is_encryption`,`ip`, `reg_timestamp`)
            VALUES('{$data['event_seq']}','{$data['site']}','{$data['name']}',enc_data('{$data['phone']}'),'{$data['reg_date']}','{$data['gender']}','{$data['age']}','{$data['branch']}','{$data['addr']}','{$data['email']}','{$data['agree']}','{$data['add1']}','{$data['add2']}','{$data['add3']}','{$data['add4']}','{$data['add5']}','{$data['file_url']}',1,'{$this->remote_addr}', UNIX_TIMESTAMP(NOW()));";
        $result = $this->query($sql);
        
        return $result;
    }

    private function zenithAfterInsert($data) {
        $this->checkEventLeads($data); //인정기준 처리
        $this->eventLeadsInterlock($data);
    }

    private function eventLeadsInterlock($data) { //Zenith 외부연동 처리
        if(!isset($data['interlock_result'])) return;
        $interlock_failed = @count($data['interlock_result']); //랜딩별 총 외부연동 갯수를 실패횟수로 정의
        
        foreach($data['interlock_result'] as $row) {
            //수신데이터 정리
            if (self::is_json($row['response_data'])) { //수신 데이터가 json 형태라면..        
                $response = @json_decode($row['response_data'], true); //json을 배열로 변형
                $row['response_data'] = json_encode($response, JSON_UNESCAPED_UNICODE); //DB에 저장하기 위해 배열로 변형한 데이터를 unicode 처리하지 않고 json으로 변환
            } else {
                $response = $row['response_data']; //수신데이터가 배열이면 그대로 지정
            }
            if(isset($response['state'])) $response['result'] = $response['state']; //수신 데이터가 result가 아닌 state가 있어서 result로 변경
            if(isset($response['status'])) $response['result'] = $response['status']; //수신 데이터가 result가 아닌 status가 있어서 result로 변경
            if(isset($response['result_code']) && isset($response['result_msg'])) $response = $response['result_msg']; //야호맨 result_code = '0', result_msg = 'success'
			if(isset($response['code'])) $response['result'] = $response['code']; //수신 데이터가 result가 아닌 code가 있어서 result로 변경
			if(isset($response['rtnCode'])) $response['result'] = $response['rtnCode']; //수신 데이터가 result가 아닌 rtnCode가 있어서 result로 변경
			if(isset($response['msg']) && count($response) == 1) $response['result'] = $response['msg']; //수신 데이터가 result가 아닌 rtnCode가 있어서 result로 변경
            $is_success = 0;
            /*
            ! result 값에 따라 $is_success 를 처리할 수 있도록 모든 성공값을 처리
            ? 정의된 배열 이외 성공값이 있을 경우 수식을 수정하거나 배열에 추가하여야 함
            */
            if((isset($response['result']) //reponse가 result 배열이 존재 할 경우
                && (@in_array(@strtolower($response['result']), ["ok","200","y","success","01","true","0000"]) //배열 내 result 값이 정의된 배열 안에 존재하거나
                || $response['result'] === true)) //배열 내 result 가 boolean 값 true로 넘어왔거나
                || @in_array(@strtolower((string) $response), ["ok","200","y","1","success"])) //response가 배열이 아닌 text값이 정의된 배열에 존재할 경우
            {
                $is_success = 1; //성공
            }
            //전송데이터 정리
            if(!is_array($row['send_data']) && self::is_json($row['send_data'])) $row['send_data'] = json_decode($row['send_data'], true);
            //전송한 데이터의 문자가 변형 된 것을 UTF-8 형태로 치환
            $row['send_data'] = array_map(function($v){
                if(is_array($v)) return;
                if(@urlencode(@urldecode($v)) === $v) $v = @urldecode($v);
                return @iconv(@mb_detect_encoding($v, ['ASCII','UTF-8','EUC-KR']), 'UTF-8//TRANSLIT', @urldecode($v));
            },$row['send_data']);
            if(isset($row['send_data']['bo_table']) && $row['send_data']['bo_table'] == 'dent_reserv') $is_success = 1; //원데이 리턴값 없어서 강제 성공처리
            //DB에 저장하기 위해 전송한 데이터를 unicode 처리하지 않고 json으로 변환
            $row['send_data'] = @json_encode($row['send_data'], JSON_UNESCAPED_UNICODE);
            array_walk($row, function(&$string) { $string = $this->db->real_escape_string($string); }); //DB저장을 위해 escape 처리
            //외부연동 내역
            
            $row['partner_id'] = $row['partner_id'] ?? '';
            $row['partner_name'] = $row['partner_name'] ?? '';
            $row['paper_code'] = $row['paper_code'] ?? '';
            $row['paper_name'] = $row['paper_name'] ?? '';
            $sql = "INSERT INTO `zenith`.`event_leads_interlock`(`leads_seq`,`event_seq`,`url`,`partner_id`,`partner_name`,`paper_code`,`paper_name`,`send_data`,`response_data`,`is_success`)
                    VALUES('{$data['seq']}','{$data['event_seq']}','{$row['url']}','{$row['partner_id']}','{$row['partner_name']}','{$row['paper_code']}','{$row['paper_name']}','{$row['send_data']}','{$row['response_data']}',{$is_success});";
            $this->query($sql);
            if($is_success === 1) $interlock_failed--; //성공할 때 마다 실패횟수 차감
            else { //외부연동 실패 할 때 마다
                $memoData = [
                    'seq' => $data['seq']
                    ,'event_seq' => $data['event_seq']
                    ,'memo' => "외부연동을 실패하였습니다."
                ];
                if(isset($response['msg'])) $msg = $response['msg'];
                if(isset($response['message'])) $msg = $response['message'];
                if(isset($response['code_message'])) $msg = $response['code_message'];
                if(isset($response['result_msg'])) $msg = $response['result_msg'];
                if(isset($response['rtnMessage'])) $msg = $response['rtnMessage'];
                if(!empty($msg)) $memoData['memo'] .= "[메세지:{$msg}]";
                $this->insertToMemo($memoData);
                /*
                $mailTo = "min.heo@carelabs.co.kr,jaybe@carelabs.co.kr";
                $mailFrom = 'noreply@chainsaw.co.kr';
                $header = "Content-Type: text/html; charset=utf-8\r\n";
                $header .= "MIME-Version: 1.0\r\n";
                $header .= "Return-Path: <" . $mailFrom . ">\r\n";
                $header .= "From: CHAINSAW알림 <" . $mailFrom . ">\r\n";
                $header .= "Reply-To: <" . $mailFrom . ">\r\n";
                $subject = "[EVENT] {$data['event_seq']} 랜딩 DB전송 실패알림";
                $content = print_r($memoData, 1);
                $content .= print_r($data, 1);
                $result = mail($mailTo, $subject, $content, $header);
                */
            }
        }

        if($interlock_failed === 0) { //외부연동 모두 성공 했을 때
            //외부연동 결과 처리
            $sql = "UPDATE `zenith`.`event_leads` SET interlock_success = 1 WHERE seq = {$data['seq']}";
            $this->query($sql);
        }
    }

    private function updateStatusToEventLeads($data, $user) { //Zenith 상태변경 처리
        $sql = "UPDATE `zenith`.`event_leads` SET `status` = {$data['status']} WHERE `seq` = {$user['seq']}";
        $result = $this->query($sql);
        if($result) {
            if($data['status_memo']) {
                $msg = "시스템이 불량DB({$data['status_memo']})로 처리하였습니다.";
                if($data['status'] == 13)
                    $msg = "시스템이 불량DB({$data['status_memo']})가능성이 있음을 감지하였습니다.";
                $memoData = [
                    'seq' => $user['seq'],
                    'event_seq' => $user['event_seq'],
                    'memo' => $msg
                ];
                $this->insertToMemo($memoData);
            }
        }
    }

    private function insertToMemo($data) {
        if(!isset($data['seq']) || !$data['seq']) return false;
        array_walk($data, function(&$string) { $string = $this->db->real_escape_string($string); });
        $sql = "INSERT INTO `zenith`.`event_leads_memo`(`leads_seq`,`event_seq`,`memo`) 
        VALUES('{$data['seq']}','{$data['event_seq']}','{$data['memo']}')";
        $this->query($sql);
    }

    private function checkEventLeads($user) { //Zenith 불량DB 처리
        $status = 1;
        $msg = '';
        $thanks_cookie = $this->get_cookie("Thanks_{$user['event_seq']}");
        if($this->landing['check_cookie'] && $thanks_cookie) { //제니스에서는 상태값 변경 메모가 저장되기 때문에 쿠키 중복을 확인할 수 있어서, return을 하지 않고 다음 체크로 넘김
            $ck = $this->aes_decrypt($thanks_cookie);
            $ck = explode('_', $ck);
            $status = 13;
            $msg = "쿠키 중복-{$ck[0]}";
            $data = ['status' => $status, 'status_memo' => $msg];
            $this->updateStatusToEventLeads($data, $user);
        }
        if ($user['gender'] && $this->landing['check_gender']) { //성별 체크
            if ($this->landing['check_gender'] == 'm') {
                if (!preg_match('/(남|m)/i', $user['gender'])) $status = 3;
                $msg = '남자체크';
            } else if ($this->landing['check_gender'] == 'f') {
                if (!preg_match('/(여|f)/i', $user['gender'])) $status = 3;
                $msg = '여자체크';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateStatusToEventLeads($data, $user);
                return;
            }
        }
        if ($user['age'] && $this->landing['check_age_min'] && $user['age'] < $this->landing['check_age_min']) { //최소나이 체크
            $status = 4;
            $data = ['status' => $status, 'status_memo' => '나이조건 이하'];
            $this->updateStatusToEventLeads($data, $user);
            return;
        }
        if ($user['age'] && $this->landing['check_age_max'] && $user['age'] > $this->landing['check_age_max']) { //최대나이 체크
            $status = 4;
            $data = ['status' => $status, 'status_memo' => '나이조건 이상'];
            $this->updateStatusToEventLeads($data, $user);
            return;
        }
        if ($user['phone'] && $this->landing['check_phone']) { //전화번호 불량,중복 체크
            $number = substr($user['phone'], 3, 8);
            if (!preg_match('/^[\d]{11}$/', $user['phone'])) {
                $status = 6;
                $msg = '전화번호 길이 불량';
            } else if (in_array($number, ['00000000', '11111111', '22222222', '33333333', '44444444', '55555555', '66666666', '77777777', '88888888', '99999999'])) {
                $status = 6;
                $msg = '8자리 같은번호';
            }
            $sql = "SELECT seq FROM `zenith`.`event_leads` WHERE `event_seq` = '{$user['event_seq']}' AND `phone` = enc_data('{$user['phone']}') AND `seq` <> '{$user['seq']}' AND `is_deleted` = 0 AND `status` <> 0";
            if ($this->landing['duplicate_term']) {
                $sql .= " AND `reg_date` >= DATE_SUB(NOW(), INTERVAL {$this->landing['duplicate_term']} DAY)";
            }
            $result = $this->query($sql);
            if ($result->db->num_rows) {
                $status = 2;
                $msg = '전화번호 중복';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateStatusToEventLeads($data, $user);
                return;
            }
        }
        if ($user['name']) { //이름 불량,중복 체크
            if ($this->landing['check_name']) {
                $sql = "SELECT seq FROM `zenith`.`event_leads WHERE `event_seq` = '{$user['event_seq']}' AND `name` = '{$user['name']}' AND `seq` <> '{$user['seq']}' AND `is_deleted` = 0 AND status <> 0
                ";
                if ($this->landing['duplicate_term']) {
                    $sql .= " AND `reg_date` >= DATE_SUB(NOW(), INTERVAL {$this->landing['duplicate_term']} DAY)";
                }
                $result = $this->query($sql);
                if ($result->db->num_rows) {
                    $status = 2;
                    $msg = '이름 중복';
                }
            }
            if (preg_match('/(테스트|테스트|test)/i', $user['name'])) { //테스트 체크
                $status = 7;
                $msg = '테스트';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateStatusToEventLeads($data, $user);
                return;
            }
        }
        $data = ['status' => $status, 'status_memo' => $msg];
        $this->updateStatusToEventLeads($data, $user);
        return;
    }

    //Zenith 외부연동
    public function zenithInterlock() {
        //Zenith DB Connection
        $mysqli = new mysqli(MYSQL_RW_HOST, ZENITH_DB_ID, ZENITH_DB_PW, ZENITH_DB_NAME);
        /*
        $sql = "SELECT ea.name AS advertiser_name, el.*, `zenith`.dec_data(el.phone) AS phone
        FROM `zenith`.`event_leads` AS el
            LEFT JOIN `zenith`.`event_information` AS ei ON el.event_seq = ei.seq
            LEFT JOIN `zenith`.`event_advertiser` AS ea ON ei.advertiser = ea.seq
        WHERE el.interlock_success = 0
            AND el.status = 1
            AND ei.interlock = 1
            AND ea.interlock_url <> ''
            AND el.reg_date >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
        */
        $sql = "SELECT ea.name AS advertiser_name, el.*, DEC_DATA(el.phone) as phone FROM event_leads AS el
                    LEFT JOIN event_information AS ei ON el.event_seq = ei.seq
                    LEFT JOIN event_advertiser AS ea ON ei.advertiser = ea.seq
                WHERE ei.interlock = 1 
                AND el.interlock_success = 0 
                AND ea.interlock_url <> '' 
                AND el.reg_timestamp >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 MINUTE)) 
                AND el.reg_timestamp <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 SECOND));";
        $leads_result = $mysqli->query($sql);
        if(!$leads_result->num_rows) return;
        while($row_data = $leads_result->fetch_assoc()) {
            $data = [];
            $interlock_result = [];
            $this->getLandingBySeq($row_data['event_seq']);
            $this->app_name = "evt_{$this->no}";
            if(empty($row_data['ip'])) $row_data['ip'] = '127.0.0.1';
            $this->remote_addr = $row_data['ip'];
            $data = $row_data;
            $interlock_status = $status = $data['status'];
            // $data = array_map('addslashes', $row);
            $interlock_file = __DIR__."/data/{$data['advertiser_name']}/interlock.php";
            // echo '<br>'.$row_data['advertiser_name'].'/';
            // var_dump($interlock_file);
            // echo '<pre>'.print_r($data,1).'</pre>';
            // continue;
            if(file_exists($interlock_file)) {//외부연동 파일이 있으면 진행
                include $interlock_file;
                if(count($interlock_result)) {
                    $data = array_merge($data, ['interlock_result'=>$interlock_result]);
                }
                $this->eventLeadsInterlock($data);
                // echo '<pre>'.print_r($interlock_result,1).'</pre>';
            } else {
                echo "<b>외부연동파일 없음</b>";
            }
        }
    }

    //! Zenith End

    private function validator()
    {
        if (isset($_POST['age']) && $this->landing['check_age_min']) {
            if ($_POST['age'] < $this->landing['check_age_min']) {
                return ['result' => false, 'msg' => "이 이벤트는 {$this->landing['check_age_min']}세 이상만 신청가능합니다."];
            } else if ($_POST['age'] > $this->landing['check_age_max']) {
                return ['result' => false, 'msg' => "이 이벤트는 {$this->landing['check_age_max']}세 이하로 신청가능합니다."];
            }
        }

        /* var align */
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $enc_phone = $this->aes_encrypt($_POST['phone']);
        $advertiser_no = $this->landing['advertiser'];
        $media_no = $this->landing['med_seq'];
        $ip = $this->remote_addr;
        /* 태국 랜딩 사용으로 사용중지
        if(!preg_match_all('/^[가-힣\ ]+$/', $name) && $this->landing['lead'] == 0) { //한글이름만 받음
            return ['result' => false, 'msg' => "한글만 사용가능합니다."];
        }
        */

        /** 사전 중복 체크 **/
        if ($this->landing['duplicate_precheck'] == '0') {
            return ['result' => true];
        }

        // 분기
        if ($this->landing['duplicate_precheck'] == '1') {
            // 해당 랜딩 - 이름&연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND name = '$name' AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id = '$this->app_name'";
        } else if ($this->landing['duplicate_precheck'] == '2') {
            // 해당 광고주 - 이름&연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND name = '$name' AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' )";
        } else if ($this->landing['duplicate_precheck'] == '3') {
            // 해당 광고주&매체 - 이름&연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND name = '$name' AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' AND media = '$media_no' )";
        } else if ($this->landing['duplicate_precheck'] == '7') {
            // 해당 랜딩 - 연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id = '$this->app_name'";
        } else if ($this->landing['duplicate_precheck'] == '8') {
            // 해당 광고주 - 연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' )";
        } else if ($this->landing['duplicate_precheck'] == '9') {
            // 해당 광고주&매체 - 연락처 중복
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' AND media = '$media_no' )";
        }else {
            $sql = "SELECT eid FROM app_subscribe where deleted=0 AND name = '$name' AND (phone = '$phone' OR phone = '$enc_phone') ";
            $sql .= "AND group_id = '$this->app_name'";
        }

        if($this->is_our == false){
            if ($this->landing['duplicate_precheck'] == '4') {
                // 해당 랜딩 - IP 중복
                $sql = "SELECT eid FROM app_subscribe where deleted=0 AND ip = '$ip'";
                $sql .= "AND group_id = '$this->app_name'";
            } else if ($this->landing['duplicate_precheck'] == '5') {
                // 해당 광고주 - IP 중복
                $sql = "SELECT eid FROM app_subscribe where deleted=0 AND ip = '$ip'";
                $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' )";
            } else if ($this->landing['duplicate_precheck'] == '6') {
                // 해당 광고주&매체 - IP 중복
                $sql = "SELECT eid FROM app_subscribe where deleted=0 AND ip = '$ip' AND (phone = '$phone' OR phone = '$enc_phone') ";
                $sql .= "AND group_id IN (SELECT CONCAT('evt_',seq) FROM zenith.event_information WHERE advertiser = '$advertiser_no' AND media = '$media_no' )";
            }
        }

        $result = $this->query($sql);
        if ($result->db->num_rows) {
            return ['result' => false, 'msg' => "이미 참가하셨습니다!"];
        }

        return ['result' => true];
    }

    public function send()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        $this->writeLog();
        $_POST = json_decode(file_get_contents('php://input'), true);
        if(isset($_POST['no']) && preg_match('/[0-9]+/', $_POST['no'])) //no 변수값이 넘어오면 랜딩번호를 교체
            $this->getLandingBySeq($_POST['no']);
        if (!$this->landing['seq']) exit(json_encode(['result' => false, 'msg' => '존재하지 않는 이벤트입니다.']));
        if ($this->landing['lead'] != 3) exit(json_encode(['result' => false, 'msg' => '외부에서 전송 할 수 없는 이벤트입니다.']));
        if ($this->landing['is_stop']) exit(json_encode(['result' => false, 'msg' => '사용할 수 없는 이벤트입니다.']));
        if ($this->landing['adv_stop']) exit(json_encode(['result' => false, 'msg' => '사용할 수 없는 이벤트입니다.']));
        if (!is_array($_POST)) exit(json_encode(['result' => false, 'msg' => 'JSON 형태로 전송해주십시오. ex) {"name":"홍길동","phone":"01012345678"...}']));
        if (!isset($_POST['remote_addr']) || !$_POST['remote_addr']) exit(json_encode(['result' => false, 'msg' => '사용자IP는 필수값입니다.']));
        $this->remote_addr = $_POST['remote_addr'];
        $result = $this->check_proc();
        // $result = array_merge($result, $_SERVER);
        echo json_encode($result);
        exit;
    }

    public function proc()
    { //데이터 저장
        $is_ajax = $_POST['ajax']; //ajax 형태 인지
        $result = $this->check_proc();
        if ($is_ajax) {
            echo json_encode($result);
            exit;
        } else {
            if ($result['result'] === true) {
                session_write_close();
                header("Location: " . ROOT_PATH . "/{$this->hash_no}/thanks/{$result['data']}");
                exit; //저장이 정상적으로 처리 됐을 경우 result 페이지로 이동
            } else {
                if ($result['data'] == 'thanks') {
                    session_write_close();
                    header("Location: " . ROOT_PATH . "/{$this->hash_no}/thanks");
                    exit;
                } else if ($result['msg']) {
                    $this->alert($result['msg']);
                }
            }
        }
    }

    private function checkAppsubscribe($user)
    {
        $status = 1;
        $msg = '';
        if ($user['gender'] && $this->landing['check_gender']) { //성별 체크
            if ($this->landing['check_gender'] == 'm') {
                if (!preg_match('/(남|m)/i', $user['gender'])) {
                    $status = 3;
                }
                $msg = '남자체크';
            } else if ($this->landing['check_gender'] == 'f') {
                if (!preg_match('/(여|f)/i', $user['gender'])) {
                    $status = 3;
                }
                $msg = '여자체크';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateAppsubscribe($data, $user['eid']);
                return $data;
            }
        }
        if ($user['age'] && $this->landing['check_age_min'] && $user['age'] < $this->landing['check_age_min']) { //최소나이 체크
            $status = 4;
            $data = ['status' => $status, 'status_memo' => '나이조건 이하'];
            $this->updateAppsubscribe($data, $user['eid']);
            return $data;
        }
        if ($user['age'] && $this->landing['check_age_max'] && $user['age'] > $this->landing['check_age_max']) { //최대나이 체크
            $status = 4;
            $data = ['status' => $status, 'status_memo' => '나이조건 이상'];
            $this->updateAppsubscribe($data, $user['eid']);
            return $data;
        }
        if ($user['phone'] && $this->landing['check_phone']) { //전화번호 불량,중복 체크
            $number = substr($user['phone'], 3, 8);
            if (!preg_match('/^[\d]{11}$/', $user['phone'])) {
                $status = 6;
                $msg = '전화번호 길이 불량';
            } else if (in_array($number, ['00000000', '11111111', '22222222', '33333333', '44444444', '55555555', '66666666', '77777777', '88888888', '99999999'])) {
                $status = 6;
                $msg = '8자리 같은번호';
            }
            $sql = "SELECT eid FROM app_subscribe WHERE group_id = '{$user['group_id']}' AND phone = enc_data('{$user['phone']}') AND eid <> '{$user['eid']}' AND deleted = 0 AND status <> 0";
            if ($this->landing['duplicate_term']) {
                $sql .= " AND reg_date >= DATE_SUB(NOW(), INTERVAL {$this->landing['duplicate_term']} DAY)";
            }
            $result = $this->query($sql);
            if ($result->db->num_rows) {
                $status = 2;
                $msg = '전화번호 중복';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateAppsubscribe($data, $user['eid']);
                return $data;
            }
        }
        if ($user['name']) { //이름 불량,중복 체크
            if ($this->landing['check_name']) {
                $sql = "SELECT eid FROM app_subscribe WHERE group_id = '{$user['group_id']}' AND name = '{$user['name']}' AND eid <> '{$user['eid']}' AND deleted = 0 AND status <> 0
                ";
                if ($this->landing['duplicate_term']) {
                    $sql .= " AND reg_date >= DATE_SUB(NOW(), INTERVAL {$this->landing['duplicate_term']} DAY)";
                }
                $result = $this->query($sql);
                if ($result->db->num_rows) {
                    $status = 2;
                    $msg = '이름 중복';
                }
            }
            if (preg_match('/(테스트|테스트|test)/i', $user['name'])) { //테스트 체크
                $status = 7;
                $msg = '테스트';
            }
            if ($status != 1) {
                $data = ['status' => $status, 'status_memo' => $msg];
                $this->updateAppsubscribe($data, $user['eid']);
                return $data;
            }
        }
        $thanks_cookie = $this->get_cookie("Thanks_{$this->no}");
        if($this->landing['check_cookie'] && $thanks_cookie) {
            $ck = $this->aes_decrypt($thanks_cookie);
            $ck = explode('_', $ck);
            $status = 13;
            $msg = "쿠키 중복-{$ck[0]}";
            $data = ['status' => $status, 'status_memo' => $msg];
            $this->updateAppsubscribe($data, $user['eid']);
            return $data;
        }
        $data = ['status' => $status, 'status_memo' => $msg];
        $this->updateAppsubscribe($data, $user['eid']);
        return $data;
    }

    public function ajaxProc()
    { //ajax 처리용
        $mode = $_POST['mode'];
        if ($mode == "getComment") { //코멘트 가져오기
            $limit = $_POST['limit'] ? $_POST['limit'] : 10;
            $query = "";
            $data = ['result' => false, 'more' => false, 'data' => null];
            if ($_POST['lastmsg']) {
                $lastmsg = $_POST['lastmsg'];
                $query = " AND eid < '$lastmsg'";
            }
            $sql = "SELECT eid,name,phone,age,add1,enc_status,reg_date FROM app_subscribe WHERE group_id = '{$this->app_name}' AND deleted = 0";
            $cnt_sql = $this->query($sql);
            $total = $cnt_sql->db->num_rows;
            $sql .= "{$query} ORDER BY eid DESC LIMIT $limit"; //eid보다 작은 값 20개 출력 
            $result = $this->query($sql);
            if ($result->db->num_rows) {
                $data['result'] = true;
                while ($row = $result->db->fetch_assoc()) {
                    if ($row['enc_status'] == 1) $row['phone'] = $this->aes_decrypt($row['phone']);

                    $row['eid'] = $row['eid'];
                    $row['name'] = mb_substr($row['name'], '0', 1) . "**"; //이름 마스크 처리 
                    $row['phone'] = '010-****-**' . substr($row['phone'], -2); //전화번호 마스크 처리 
                    $row['age'] = substr($row['age'], 0, 1) . '0대'; //전화번호 마스크 처리 
                    $row['reg_date'] = date('m-d H:i', strtotime($row['reg_date']));
                    $row['msg'] = '신청했습니다~!';
                    $data['data'][] = $row;
                }
                if ($result->db->num_rows == $limit)
                    $data['more'] = true;
            }
            echo json_encode($data);
            exit;
        }
    }
    public function thanks()
    { //완료페이지
        unset($_SESSION['userProfile']);
        $eid = $this->aes_decrypt($this->paths[3]);
        $result = $this->getAppsubscribeByEid($eid);
        $user = [];
        if ($result->db->num_rows) {
            $user = $result->db->fetch_assoc();
            $user['dec_phone'] = $this->aes_decrypt($user['phone']);
            $user['international_phone'] = str_replace('010', '+8210', $user['dec_phone']);
        }

        $file = "./data/{$this->landing['name']}/v_{$this->no}_thanks.php";
        if (!file_exists($file)) $file = "./inc/thanks.php";

        $this->makePage($file, $user);
        if ($this->landing['pixel_id']) {
            $this->FBPixel('CompleteRegistration', $user);
        }
    }

    private function updateAppsubscribe($data, $eid)
    {
        $sql = 'UPDATE app_subscribe SET ';
        $cnt = 0;
        foreach ($data as $key => $val) {
            if ($cnt) $sql .= ", ";
            $sql .= "{$key} = '" . $this->db->escape_string($val) . "'";
            $cnt++;
        }
        $sql .= " WHERE eid = {$eid}";

        return $this->query($sql);
    }

    public function result()
    {
        $path = '../' . strtoupper($this->hash_no);
        if ($_SESSION['params']) { //세션에 저장 된 parameter URL에 세팅
            $params = http_build_query($_SESSION['params']);
            $path .= '?' . $params;
        }
        if (!isset($_SESSION["view_{$this->no}"])) { //view 페이지를 방문했던 세션 확인, 내부 사용자면 패스
            session_write_close();
            header("Location: {$path}");
            exit; //view 페이지를 방문한 적이 없다면 돌려보냄
        }
        $sql = "SELECT * FROM event_result_landing_category WHERE allow_landing = '{$this->no}' ORDER BY no ASC LIMIT 1"; //허용된 랜딩
        $result = $this->query($sql);
        $row = $result->db->fetch_assoc();
        $category = $row['no'];

        $visit = false;
        if (!$this->get_cookie($this->no . '_result_view')) { //같은 쿠키가 존재 할 경우 기록하지 않음
            $visit = true;
            $this->set_cookie($this->no . '_result_view', time(), 600); //10분짜리 쿠키 생성
        }
        if (!$result->db->num_rows) {
            session_write_close();
            header("Location: {$path}");
            exit; //result 목록이 없다면 돌려보냄
        } else { //이벤트모음을 노출 할 수 있는 랜딩인지
            $sql = "SELECT * FROM event_result_landing WHERE category = {$category}";
            $result = $this->query($sql);
            $maxWidth = 0;
            $data = [];
            for ($i = 0; $row = $result->db->fetch_assoc(); $i++) {
                $imgWidth = 0;
                $imgHeight = 0;
                if ($row['image']) {
                    $image = imagecreatefromstring(file_get_contents($row['image'], false, stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]])));
                    $imgWidth = imagesx($image);
                    $imgHeight = imagesy($image);
                }
                $adv = 0;
                $adv_result = $this->query("SELECT b.name, c.media FROM zenith.event_information AS a, zenith.event_advertiser AS b, zenith.event_media AS c WHERE a.advertiser = b.seq AND b.is_stop = 0 AND a.media = c.seq AND a.seq = '{$this->no}'"); //광고주 등록 확인
                $adv_row = $adv_result->db->fetch_assoc();
                $adv = $adv_result->num_rows;

                $db_result = $this->query("SELECT COUNT(*) AS cnt FROM app_subscribe WHERE group_id = 'evt_{$this->no}' AND DATE(reg_date) = DATE(NOW()) AND status = 1 AND deleted = 0");
                $db = $db_result->db->fetch_assoc();

                if ($row['image'] && ($adv || empty($row['link']) || $row['db_count'] == '0' || $db['cnt'] < $row['db_count'])) { //이미지가 있고 ('광고주 등록' 또는 '타이틀이미지' 또는 'DB수 설정이 0' 또는 'DB수가 미달' 이라면 노출
                    $data['data'][$i] = $row;
                    $data['data'][$i]['db_cnt'] = $db['cnt'];
                    $data['data'][$i]['image_info']['width'] = $imgWidth;
                    $data['data'][$i]['image_info']['height'] = $imgHeight;
                    if ($visit) { //10분 안에 연속 접속이 아니라면
                        //방문 기록
                        $setData = array(
                            'ld_seq'           => $row['no'], 'ld_category'      => $row['category'], 'ld_link'          => $row['link'], 'ld_advertiser'    => $adv_row['name'], 'ld_media'         => $adv_row['media'], 'app_no'           => $row['app_no'], 'ld_id'            => $this->no, 'lv_act'           => 'view', 'lv_ip'            => $_SERVER['REMOTE_ADDR'], 'lv_user'          => $this->visitor, 'lv_date'          => date('Y-m-d'), 'lv_time'          => date('H:i:s'), 'lv_referer'       => $_SERVER['HTTP_REFERER'], 'lv_agent'         => $_SERVER['HTTP_USER_AGENT'], 'lv_os'            => $_SESSION['browser']->platform, 'lv_browser'       => $_SESSION['browser']->parent, 'lv_device'        => $_SESSION['browser']->device_name
                        );

                        $field = array();
                        foreach ($setData as $k => $v) $field[] = "`$k` = '{$v}'";
                        $fields = implode(', ', $field);
                        $sql = " INSERT INTO event_result_landing_visit SET {$fields} ";
                        if (!$this->is_our) $this->query($sql);
                    }
                }
                $data['maxWidth'] = ($data['maxWidth'] < $imgWidth) ? $imgWidth : $data['maxWidth'];
            }
            unset($_SESSION['media']);
            if (isset($data['data'])) {
                $_SESSION['media'] = $this->landing['media']; //result 랜딩목록에서 랜딩 이동 시 매체값 변경을 위해 세션에 저장
                $file = './inc/inc.result_landing.php';
                $this->makePage($file, $data);
            }
        }
    }

    private function getLandingBySeq($no = null)
    { //랜딩 정보 가져오기
        if(!is_null($no))
            $this->no = $no;
        $sql = "SELECT info.*, adv.name, adv.seq AS adv_seq, adv.homepage_url, adv.interlock_url, adv.agreement_url, adv.agent, med.media, med.target, med.seq AS med_seq, adv.is_stop AS adv_stop, GROUP_CONCAT(ek.keyword) AS keywords, ec.id AS pixel_id, ec.name AS pixel_name, ec.token AS access_token
                FROM zenith.event_information AS info
    				LEFT JOIN zenith.event_advertiser AS adv ON info.advertiser = adv.seq
    				LEFT JOIN zenith.event_media AS med ON info.media = med.seq
                    LEFT JOIN zenith.event_keyword_idx AS ki ON info.seq = ki.ei_seq
                    LEFT JOIN zenith.event_keyword AS ek ON ki.ek_seq = ek.seq
                    LEFT JOIN zenith.event_conversion AS ec ON info.pixel_id = ec.id
    			WHERE info.seq = {$this->no}";
        $result = $this->query($sql);
        if (!$result->db->num_rows) {
            throw new Exception("존재하지 않는 랜딩입니다."); //랜딩번호가 존재하지 않을경우 Exception 처리
        }
        $this->landing = $result->db->fetch_assoc();
        return $this->landing;
    }

    private function getAppsubscribeByEid($eid)
    { //등록 사용자 정보 가져오기
        if (!$eid) return NULL;
        $sql = "SELECT * FROM app_subscribe WHERE eid = {$eid}";
        $result = $this->query($sql);

        return $result;
    }

    private function setVisit($action = '')
    { //방문자 정보 저장
        $setData = array('ld_num'=> $this->app_name, 'evt_seq'=> $this->no, 'hv_act'=> $action, 'hv_ip'=> $this->remote_addr, 'hv_user'=> $this->visitor, 'hv_date'=> date('Y-m-d'), 'hv_time'=> date('H:i:s'), 'hv_qs'=> $_SERVER['QUERY_STRING']??'', 'hv_data'=> json_encode($_REQUEST), 'hv_referer'=> $_SERVER['HTTP_REFERER']??'', 'hv_agent'=> $_SERVER['HTTP_USER_AGENT']??'', 'hv_os'=> $_SESSION['browser']->platform??'', 'hv_browser'=> $_SESSION['browser']->parent??'', 'hv_device'=> $_SESSION['browser']->device_name??'');
        $field = array();
        foreach ($setData as $k => $v) $field[] = "`$k` = '{$v}'";
        $fields = implode(', ', $field);
        $sql = " INSERT INTO hotevent_visit SET {$fields}, regtime = NOW() ";
        $today = date('Y-m-d');
        $imp_sql = "INSERT INTO zenith.event_impressions_history(seq, date, code, site, impressions, last_datetime) VALUES('{$this->no}', '{$today}', '".($_SESSION['code']??'')."', '".($_SESSION['site']??'')."', 1, NOW()) ON DUPLICATE KEY UPDATE impressions = impressions + 1, last_datetime = NOW()";
        if (!$this->is_our && !$this->get_cookie('CSActTime_' . $this->no . '_' . $action)) {
            $this->query($sql);
            if ($action == 'view' && !preg_match('/bot/i', $_SERVER['HTTP_USER_AGENT']))
                $this->query($imp_sql);
        }

        $this->set_cookie('CSActTime_' . $this->no . '_' . $action, time(), 600); //10분짜리 쿠키 생성
        $_SESSION["{$action}_{$this->no}"] = true; //세션 생성
    }

    public function go()
    {
        $category_no = $this->paths[2];
        $landing_no = $this->paths[3];

        $sql = "SELECT A.*, C.name AS adv, D.media FROM event_result_landing A, zenith.event_information B, zenith.event_advertiser C, zenith.event_media D WHERE A.app_no = B.seq AND C.is_stop = 0 AND B.advertiser = C.seq AND B.media = D.seq AND A.category = {$category_no} AND A.no = {$landing_no}";
        $result = $this->query($sql, true);
        $landing = $result->db->fetch_assoc();
        $click_cookie = $this->get_cookie("{$landing['category']}_{$landing['no']}_{$app_no}_click"); //1분 재방문 쿠키
        if ($_SESSION['no'] && !$click_cookie) { //이벤트 모음을 통해 클릭을 했고, 1분안에 재방문을 하지 않았다면 방문기록
            $setData = array(
                'ld_seq'           => $landing['no'], 'ld_category'      => $landing['category'], 'ld_link'          => $landing['link'], 'app_no'           => $landing['app_no'], 'ld_advertiser'    => $landing['adv'], 'ld_media'         => $landing['media'], 'ld_id'            => $_SESSION['no'], 'lv_act'           => 'click', 'lv_ip'            => $this->remote_addr, 'lv_user'          => $this->visitor, 'lv_date'          => date('Y-m-d'), 'lv_time'          => date('H:i:s'), 'lv_referer'       => $_SERVER['HTTP_REFERER'], 'lv_agent'         => $_SERVER['HTTP_USER_AGENT'], 'lv_os'            => $_SESSION['browser']->platform, 'lv_browser'       => $_SESSION['browser']->parent, 'lv_device'        => $_SESSION['browser']->device_name
            );

            $field = array();
            foreach ($setData as $k => $v) $field[] = "`$k` = '{$v}'";
            $fields = implode(', ', $field);
            $sql = " INSERT INTO event_result_landing_visit SET {$fields} ";
            if (!$this->is_our) $this->query($sql);
            $this->set_cookie("{$landing['category']}_{$landing['no']}_{$app_no}_click", time(), 60); //1분짜리 쿠키 생성
        }
        if ($landing['link'] && preg_match('/^http.+/', $landing['link'])) { //클릭된 이미지가 링크가 존재해야하고, http로 시작한다면 이동
            session_write_close();
            header("Location: {$landing['link']}");
            exit;
        }
        if ($_SERVER['HTTP_REFERER']) {
            session_write_close();
            header('Location: ' . $_SERVER['HTTP_REFERER']); //그렇지 않으면 원래 페이지로 되돌림
        }
    }

    private function getRemoteAddr()
    { //IP정보 가져오기
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        $this->remote_addr = $ipaddress;
        return;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function getKakao() {
        if(preg_match('/KAKAOTALK/i', $_SERVER['HTTP_USER_AGENT'])) {
        // if($this->no == 8367) {
            if(empty($_SESSION['userProfile'])) {
                $api_key = '500a99d34478a54c7c4fbfc04ff90512';
                $redirect_url = urlencode("https://event.hotblood.co.kr/oauth/kakao");
                if(@__DEV__) $redirect_url = urlencode("https://local.event.hotblood.co.kr/oauth/kakao");
                $url = "https://kauth.kakao.com/oauth/authorize?client_id={$api_key}&redirect_uri={$redirect_url}&through_account=true&response_type=code";//&prompt=none";
                $_SESSION['kakaosync'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                header("Location: {$url}");
                exit;
            } else {
                $profile = $_SESSION['userProfile'];
                $profile['name'] = ($profile['firstName'] && $profile['lastName']) ? $profile['lastName'].$profile['firstName'] : $profile['displayName'];
                $this->userProfile = $profile;
            }
        } else {
            $this->userProfile = [];
        }
        // echo nl2br(print_r($this->userProfile,1)); exit;
    }

    private function html($page)
    { //HTML 출력
        $this->setVisit($this->method);
        if (is_array($page)) { //page 변수가 배열일 경우
            echo $page['header'];
            echo $page['content'];
            echo $page['footer'];
        } else { //page변수가 페이지 전체일 경우
            echo $page;
        }
    }

    private function makePage($file, $data = "", $appendHtml = [])
    { //Page 생성
        @$GLOBALS['data'] = $data;
        $header = $this->setHeader($appendHtml['header']??'');
        $content = $this->setContent($file, $data);
        $header = preg_replace_callback('/\{\{([^\}]+)\}\}/m', function ($matches) {
            return @$GLOBALS["data"][$matches[1]];
        }, $header);
        $content = preg_replace_callback('/\{\{([^\}]+)\}\}/m', function ($matches) {
            return @$GLOBALS["data"][$matches[1]];
        }, $content);
        $page = $this->addStyle($header, $content);
        $page['footer'] = $this->getFooter(); //Footer를 buffer로 저장
        $this->html($page); //html 출력
    }

    private function getHeader()
    { //Header 호출
        ob_start();
        include "./inc/head.php";
        $header = ob_get_contents();
        ob_end_clean();
        return $header;
    }

    private function getFooter()
    { //Footer 호출
        ob_start();
        include "./inc/tail.php";
        $footer = ob_get_contents();
        ob_end_clean();

        return $footer;
    }

    private function setContent($file, $data = [])
    {
        if (!file_exists($file)) return NULL;
        ob_start();
        include $file;
        $content = ob_get_contents(); //Contents를 buffer로 저장
        ob_end_clean();

        return $content;
    }

    private function addStyle($header, $content)
    { //Header에 스타일시트 추가
        $content = $this->splitStyle($content);
        $page['header'] = preg_replace('#(</head>[^<]*<body[^>]*>)#', "{$content['style']}\n$1", $header); //Header에 스타일시트 추가
        $page['content'] = preg_replace('#(<style[^>]*>[^<]*<\/style>)#m', "", $content['content']); //스타일시트를 제외한 컨텐츠 재정의

        return $page;
    }

    private function setHeader($add_header = "")
    { //Header 제작
        $title = $this->landing['title'];
        if (!$title) $title = $this->landing['name']; //타이틀 없을 경우 광고주명으로 대체
        $header = $this->getHeader(); //Header를 buffer로 저장
        $script = "";
        switch ($this->method) {
            case 'view':
                $script = stripslashes($this->landing['view_script']);
                break;
            case 'thanks':
                $script = stripslashes($this->landing['done_script']);
                break;
            case 'result':
                $title = "이벤트";
                break;
        }
        $header = preg_replace('#(<title>)([^<]*)(<\/title>)#', "$1 {$title}$3", $header); //저장된 buffer에 title 추가
        $header = preg_replace('#(</head>[^<]*<body[^>]*>)#', "{$script}\n$1", $header); //저장된 buffer에 header 스크립트 추가
        if ($add_header)
            $header = preg_replace('#(</head>[^<]*<body[^>]*>)#', "$1\n{$add_header}\n", $header); //저장된 buffer에 header 스크립트 추가   

        return $header;
    }

    //스타일 분리
    private function splitStyle($content)
    {
        preg_match_all('#(<style>(.*?)</style>)?(.*)#is', $content, $matches, PREG_SET_ORDER); //content 파일내 스타일시트 분리
        $result['style'] = $matches[0][1];
        $result['content'] = $matches[0][3];

        return $result;
    }

    private function query($sql, $error = false)
    { //쿼리 전송
        if (!$sql) return false;
        $result = null;
        $data = new stdClass();
        if ($this->rodb && preg_match('#^select.*#i', trim($sql))) //Select 는 ReadOnly DB로 연결
            $this->db = $this->rodb;
        else
            $this->db = $this->rwdb;

        $this->db->query("BEGIN"); //트랜젝션 시작
        if ($error) { //error 가 true 일 경우 에러메시지 출력
            $result = $this->db->query($sql) or die($this->db->error);
        } else {
            $result = $this->db->query($sql);
        }
        if ($result) {
            $data->db = $result;
            $data->insert_id = $this->db->insert_id;
            $this->db->query("COMMIT"); //트랜젝션 커밋
        } else
            $this->db->query("ROLLBACK"); //트랜젝션 롤백
        return $data;
    }

    //event 파일 목록 가져오기
    public function getFiles()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        $files = [];
        $dirs = glob('data/*', GLOB_ONLYDIR);
        if (count($dirs) > 0) {
            foreach ($dirs as $d) {
                if ($file_list = array_map('basename', glob($d . '/v_*.php')))
                    $files = array_merge($files, $file_list);
            }
        }
        echo json_encode($files);
    }

    //파일체크
    public function filecheck()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        $seq = $this->paths[2];
        $this->no = $seq;
        $this->getLandingBySeq();
        $file = "./data/{$this->landing['name']}/v_{$this->no}.php";
        echo file_exists($file);
    }

    // 신청자수 계산 함수
    public function applicant_num($input, $min = null, $max = null, $digit = 3)
    {
        $tmp = 0;
        if ($min < 0 || !$min) $min = 0;
        if ($max < 0 || !$max) $max = pow(10, $digit) - 1;

        $rst = $min + ($input % ($max - $min));

        return $rst;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //예외처리
    public static function exception_handler($e)
    {
        $error = ['title' => "PAGE NOT FOUND", 'subtitle' => "죄송합니다. 요청하신 페이지를 찾을 수 없습니다.", 'description' => "<!--{$e->getMessage()}-->", 'error' => $e->getFile()." ".$e->getLine()];
        include "error/404/index.html";
    }

    //경고처리
    public function alert($msg)
    {
        include "./inc/head.php";
        include "./inc/alert.php";
        include "./inc/tail.php";
        exit;
    }

    private function writeLog()
    {
        // echo '<pre>'.print_r($_SERVER,1).'</pre>';
        $DBdata = [
            'evt_seq' => $this->no, 'scheme' => $_SERVER['REQUEST_SCHEME'], 'host' =>  $_SERVER['HTTP_HOST'], 'php_self' => array_shift(explode('?', $_SERVER['REQUEST_URI'])), 'query_string' => $_SERVER['QUERY_STRING'], 'data' => file_get_contents('php://input'), 'post_data' => http_build_query($_POST), 'remote_addr' => $this->remote_addr, 'server_addr' => $_SERVER['SERVER_ADDR'], 'content_type' => $_SERVER['CONTENT_TYPE'], 'user_agent' => $_SERVER['HTTP_USER_AGENT'], 'datetime' => date('Y-m-d H:i:s')
        ];
        $field = array();
        foreach ($DBdata as $k => $v) $field[] = "`$k` = '{$v}'";
        $fields = implode(', ', $field);
        $sql = " INSERT INTO zenith.event_logs SET {$fields} ";
        $this->query($sql, true);
    }

    public function serverinfo()
    {
        if ($this->our_ip || $_GET['auth']) echo '<pre>' . print_r($_SERVER, 1) . '</pre>';
    }

    //쿠키변수 설정
    private function set_cookie($cookie_name, $value, $expire)
    {
        setcookie(md5($cookie_name), base64_encode($value), time() + $expire, '/', '.event.hotblood.co.kr');
    }

    private function chkHash($uid) {
        $is_chk = false;
        $ab = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $uid = strtoupper($uid);
        $r_hash = substr($uid, 0, 2);
        $uid = substr($uid, 2);
        if(!preg_match('/[A-Z]{2}/', $r_hash)) return false;
        if(!preg_match('/[0-9]+/', $uid)) return false;
        $s_id = str_split($uid);
        $make_hash = [0,0];
        for($i=0; $i<count($s_id); $i++) $make_hash[0] += $s_id[$i]*($i+$s_id[count($s_id)-1]);
	    for($i=0; $i<count($s_id); $i++) $make_hash[1] += $s_id[$i]*($i+$s_id[0]);
        $make_hash = array_map(function($v) use($ab) { $chksum = ($v % 26); return $ab[$chksum]; }, $make_hash);
        $hash = implode("", $make_hash);
        if($hash.$uid == $r_hash.$uid) $is_chk = true;
        return $is_chk;
    }

    private function makeHash($uid) {
        $ab = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $s_id = str_split($uid);
        $make_hash = [0,0];
        for($i=0; $i<count($s_id); $i++) $make_hash[0] += $s_id[$i]*($i+$s_id[count($s_id)-1]);
        for($i=0; $i<count($s_id); $i++) $make_hash[1] += $s_id[$i]*($i+$s_id[0]);
        $make_hash = array_map(function($v) use($ab){$chksum = ($v % 26); return $ab[$chksum];}, $make_hash);
        $hash = implode("", $make_hash);
        $result = $hash.$uid;
        return $result;
    }


    // 쿠키변수값 얻음
    private function get_cookie($cookie_name)
    {
        $cookie = md5($cookie_name);
        if (array_key_exists($cookie, $_COOKIE))
            return base64_decode($_COOKIE[$cookie]);
        else
            return "";
    }

    private function getAesKey() {
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/../.env');
        $this->aes_key = $env['aes.key'];
    }

    //암호화 함수
    private function aes_encrypt($data)
    {
        $key = substr(hex2bin(openssl_digest($this->aes_key, 'sha512')), 0, 16);
        $enc = @openssl_encrypt($data, "AES-128-ECB", $key, true);
        return strtoupper(bin2hex($enc));
    }

    //복호화 함수
    private function aes_decrypt($data)
    {
        $data = hex2bin($data);
        $key = substr(hex2bin(openssl_digest($this->aes_key, 'sha512')), 0, 16);
        $dec = @openssl_decrypt($data, "AES-128-ECB", $key, true);
        return $dec;
    }

    private function is_json($string = null)
    {
        $ret = true;
        if(null === @json_decode($string)){
            $ret = false;
        }
        return $ret;
        /*
        if ($string !== false && $string !== null && $string !== '') {
            @json_decode($string);
            if (json_last_error() === JSON_ERROR_NONE) {
                return true;
            }
        }
        return false;
        */
    }

    //배열 그리딩
    public function grid($data, $link = null)
    {
        if (empty($data)) {
            echo '<p>null data</p>';
            return;
        }
        $table = '';
        foreach ($data as $row) {
            if (is_array($row)) {
                $table .= '<tr>';
                foreach ($row as $key => $var) {
                    if ($link) {
                        foreach ($link as $k => $v) {
                            if ($k == $key) {
                                $var = str_replace('{' . $k . '}', $var, $v);
                            }
                        }
                    }
                    $table .= '<td>' . (is_object($var) ? $var->load() : $var) . '</td>';
                }
                $table .= '</tr>';
            }
        }
        if (isset($row) && is_array($row)) {
            $thead = '<thead><tr>';
            foreach ($row as $key => $tmp) {
                $thead .= '<th>' . $key . '</th>';
            }
            $thead .= '</tr></thead>';
        } else {
            $thead = '<thead><tr>';
            $table = '<tr>';
            foreach ($data as $k => $v) {
                $thead .= '<th>' . $k . '</th>';
                $table .= '<td>' . $v . '</td>';
            }
            $thead .= '</tr></thead>';
            $table .= '</tr>';
        }
        echo '<table class="_dev_util_grid" border="1">' . $thead . $table . '</table>';
    }
}
