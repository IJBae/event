<?php
include __DIR__.'/../../zenith/zenith_db.php';
include __DIR__ .'/../../zenith/zenith_ip.php';
include __DIR__ .'/../../zenith/zenith_encryption.php';
include __DIR__ .'/../../zenith/zenith_cookie.php';
include __DIR__ .'/../../zenith/zenith_check_proc.php';

class EventLead 
{
    private $rwdb, $rodb, $db;
    private $ip;
    private $our_ip = ['59.9.155.0/24', '127.0.0.1']; //사무실IP, 

    public function __construct()
    {
        $this->db = new ZenithDB();
        $this->ip = new ZenithIP();
    }

    public function sendLeads()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);

        $validate = $this->validator($_POST);
        if($validate['result'] == false){
            exit(json_encode($validate, JSON_UNESCAPED_UNICODE));
        }
        
        $this->writeLog($_POST['remote_addr']);
        $advertiser = $this->checkCompanyAdvertiser($_POST);
        if($advertiser['result'] == false || !isset($advertiser['seq'])){
            exit(json_encode($advertiser, JSON_UNESCAPED_UNICODE));
        }
        
        $media = $this->checkMedia($_POST);
        if($media['result'] == false || !isset($media['seq'])){
            exit(json_encode($media, JSON_UNESCAPED_UNICODE));
        }

        $event = $this->checkEvent($_POST, $advertiser['seq'], $media['seq']);
        if($event['result'] == false){
            exit(json_encode($event, JSON_UNESCAPED_UNICODE));
        }

        $result = $this->db->getLandingBySeq($event['data']['seq']);
        $landing = $result->db->fetch_assoc();

        $remote_addr = $_POST['remote_addr'];
        $is_our = $this->ip->chk_ip($remote_addr, $this->our_ip); //내부 사용자인지 체크
        $landing['is_our'] = $is_our;

        $check = new CheckProc($event['data']['seq'], $_POST, $landing, $remote_addr, $is_our);
        $result = $check->check_proc();

        echo json_encode($result);
        exit;
    }

    private function checkCompanyAdvertiser($postData)
    {
        if(empty($postData)){return ['result' => false, 'msg' => "필수값이 누락되어 데이터를 저장할 수 없습니다."];}

        $company = $this->db->getCompanyByName($postData['advertiser_name']);
        if(!$company->db->num_rows){
            $company = $this->db->createCompany($postData);
            $companySeq = (integer)$company->insert_id;
            if(!$companySeq){return ['result' => false, 'msg' => "광고주(Company) 저장에 실패하였습니다."];}
        }else{
            $com = $company->db->fetch_assoc();
            if(!empty($com)){
                $companySeq = (integer)$com['id'];
            }else{
                return ['result' => false, 'msg' => "광고주(Company) 불러오기에 실패하였습니다."];
            }
        }

        $advertiser = $this->db->getAdvertiserByCompanySeq($companySeq);
        if(!$advertiser->db->num_rows){
            $postData['company_seq'] = $companySeq;
            $advertiserSeq = $this->db->createAdvertiser($postData);
            if(!$advertiserSeq){
                return ['result' => false, 'msg' => "광고주[{$companySeq}](Advertiser) 저장에 실패하였습니다."];
            }else{
                return ['result' => true, 'seq' => $advertiserSeq];
            }
        }else{
            $adv = $advertiser->db->fetch_assoc();
            if(!empty($adv)){
                return ['result' => true, 'seq' => (integer)$adv['seq']];
            }else{
                return ['result' => false, 'msg' => "광고주(Advertiser) 불러오기에 실패하였습니다."];
            }
        }
    }

    private function checkMedia($postData)
    {
        if(empty($postData)){return ['result' => false, 'msg' => "필수값이 누락되어 데이터를 저장할 수 없습니다."];}

        $media = $this->db->getMedia($postData['media_name']);
        if(!$media->db->num_rows){
            if(!isset($postData['media_target']) || empty($postData['media_target'])) $postData['media_target'] = $postData['media_name'];
            $mediaSeq = $this->db->createMedia($postData);
            if(!$mediaSeq){
                return ['result' => false, 'msg' => "매체 저장에 문제가 발생하였습니다"];
            }else{
                return ['result' => true, 'seq' => $mediaSeq];
            }
        }else{
            $med = $media->db->fetch_assoc();
            if(!empty($med)){
                return ['result' => true, 'seq' => (integer)$med['seq']];
            }else{
                return ['result' => false, 'msg' => "매체 불러오기에 실패하였습니다."];
            }
        }
    }

    private function checkEvent($postData, $advertiserSeq, $mediaSeq)
    {
        if(empty($postData) || (empty($advertiserSeq) || empty($mediaSeq))){return ['result' => false, 'msg' => "필수값이 누락되어 데이터를 저장할 수 없습니다."];}

        $event = $this->db->getEventByPartnerSeq($postData['event_seq'], $advertiserSeq, $mediaSeq);
        if(!$event->db->num_rows){
            $createEvent = $this->db->createEvent($postData, $advertiserSeq, $mediaSeq);
            if(empty($createEvent)){
                return ['result' => false, 'msg' => "이벤트 저장에 문제가 발생하였습니다"];
            }else{
                $eventData = $createEvent->fetch_assoc();
            }
        }else{
            $eventData = $event->db->fetch_assoc();
            $this->db->updateEvent($eventData, $postData);
        }
        
        return ['result' => true, 'data' => $eventData];
    }

    private function validator($postData)
    {
        if (!is_array($postData)){
            return ['result' => false, 'msg' => 'JSON 형태로 전송해주십시오. ex) {"name":"홍길동","phone":"01012345678"...}'];
        }

        if (!isset($postData['remote_addr']) || !$postData['remote_addr']){
            return ['result' => false, 'msg' => '사용자IP는 필수값입니다.'];
        }

        if (!isset($postData['advertiser_name']) || !$postData['advertiser_name']){
            return ['result' => false, 'msg' => '광고주명은 필수값입니다.'];
        }

        if (!isset($postData['media_name']) || !$postData['media_name']){
            return ['result' => false, 'msg' => '매체명은 필수값입니다.'];
        }

        /* if (!isset($postData['partner_name']) || !$postData['partner_name']){
            return ['result' => false, 'msg' => '전송 업체명은 필수값입니다.'];
        } */

		if (!isset($postData['name']) || !$postData['name']){
            return ['result' => false, 'msg' => '이름은 필수값입니다.'];
        }

        if (!isset($postData['phone']) || !$postData['phone']){
            return ['result' => false, 'msg' => '전화번호는 필수값입니다.'];
        }

        return ['result' => true];
    }

    private function writeLog($remote_addr)
    {
        $path = explode('?', $_SERVER['REQUEST_URI']);
        $php_self = array_shift($path);
        $DBdata = [
            'evt_seq' => 1, 'scheme' => $_SERVER['REQUEST_SCHEME'], 'host' =>  $_SERVER['HTTP_HOST'], 'php_self' => $php_self, 'query_string' => $_SERVER['QUERY_STRING'], 'data' => json_encode(file_get_contents('php://input'), JSON_UNESCAPED_UNICODE), 'post_data' =>urldecode(http_build_query($_POST)), 'remote_addr' => $remote_addr, 'server_addr' => $_SERVER['SERVER_ADDR'], 'content_type' => $_SERVER['CONTENT_TYPE'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'], 'datetime' => date('Y-m-d H:i:s')
        ];
        $field = array();
        foreach ($DBdata as $k => $v) $field[] = "`$k` = '{$v}'";
        $fields = implode(', ', $field);
        $this->db->writeLog($fields);
    }
}