<?php
include __DIR__ . "/zenith_db.php";

class UpdateMemo {
    private $db;

    public function __construct() {
        $validate = $this->validator($_POST);
        if($validate['result'] == false){
            exit(json_encode($validate, JSON_UNESCAPED_UNICODE));
        }
        $this->db = new ZenithDB();
        $this->insertMemo($_POST);
    }

    private function insertMemo($data) {
        $memoData = [
            'seq' => $data['seq'],
            'memo' => $data['memo']
        ];
        $result = $this->db->insertToMemo($memoData);
        
        echo json_encode(['result' => $result]);
        exit;
    }

    private function validator($postData)
    {
        if (!is_array($postData)){
            return ['result' => false, 'msg' => 'POST 데이터를 전송해주십시오.'];
        }

        if (!isset($postData['seq']) || !$postData['seq']){
            return ['result' => false, 'msg' => 'seq는 필수값입니다.'];
        }
        if (!isset($postData['memo']) || !$postData['memo']){
            return ['result' => false, 'msg' => 'memo는 필수값입니다.'];
        }

        return ['result' => true];
    }
}