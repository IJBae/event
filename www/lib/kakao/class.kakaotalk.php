<?php
class kakaotalk{
    private $service_url = 'https://api.surem.com/alimtalk/v1/json';
    private $unicode = "carelabs";
    private $deptcode = "16-O6R-GF";

    public function get_yellowid_key($uid){
        if($uid=="리얼딥"){
            $yellowid_key = "5ea6afa03bc9af6e29b6333ea1266b7b1c9b84fa";
        }else if($uid=="원진성형외과의원"){
            $yellowid_key = "bf7835d7d13421a8b9d25d961c0c57440bd4faa5";
        }else if($uid=="TU치과"){
            $yellowid_key = "551923bdb239e309c0734c2ffcf080734d09fea1";
        }else if($uid=="클래시성형외과의원"){
            $yellowid_key = "e19c8c1a7d10df53de0b469e55826928956450c1";
        }else if($uid=="세가지소원의원"){
            $yellowid_key = "47270aec10d8661ad7049927f93dbd0872e4364e";
        }else if($uid=="GS안과의원"){
            $yellowid_key = "dd6afdead02ea5cac6c91878d230352155e362a6";
        }else if($uid=="원치과의원"){
            $yellowid_key = "9d17cd71e032773ff49676c21384737099604ceb";
        }else if($uid=="티오피성형외과의원"){
            $yellowid_key = "9c8ccc2d1459fd57042307dc5605dd197d8e3a9b";
        }

        return $yellowid_key;
    }

    public function form_kakao_msg($uid, $eid, $name, $phone, $etc=""){
        $kakao_txt = $this->get_registered_msg($uid, $name, $etc, $phone);
        $from_digit = $this->get_from_digit($uid);
        $template_code = $this->get_template_code($uid);
        $kakaophone= '8210'.mb_substr($phone, '3', 8);
        // $button_array = array(                   // ※ 버튼은 따로 정해진 바가 없으므로 예시를 작성하였다. !반드시! 수정 후 사용!!
        //     "button_type" => "WL", 
        //     "button_name" => "홈페이지 이동", 
        //     "button_url" => "http://www.naver.co.kr"
        // );

        $messages = array(
            "message_id" => $eid,
            "to" => $kakaophone, 											// 국가번호 포함한 전화번호 ex) 821012345678
            "text" => $kakao_txt,											//템플릿 내용과 동일해야함
            "from" => $from_digit,											// 문자 재전송시 발신번호 ex) 15884640
            "template_code" => $template_code, 									// 템플린코드 - 이미생성된것중에 선택
            //"reserved_time" => "209912310000", // 예약 발송시 예약시간, 미 입력시 즉시발송
            "re_send" => "Y", // 알림톡 전송 실패시 문자 - Y : 재전송, N : 재전송 안함, R : 대체 메시지로 전송 미입력시 N
            // "re_text" => "대체 메시지입니다.",
            //"buttons" => $button_array,
        );

        return $messages;
    }

    public function sendKakao($uid, $messages){
        $yellowid_key = $this->get_yellowid_key($uid);
        $messagesArr= array($messages);

        // if($this->is_local()) return false;

        $curl_post_data = array(
            "usercode" => $this->unicode, //슈어엠 제공 아이디, 수정하지 마세요
            "deptcode" => $this->deptcode, // 슈어엠 제공 회사코드, 수정하지 마세요
            "yellowid_key" => $yellowid_key, 
            "messages" => $messagesArr
        );
    
        $json_data = json_encode($curl_post_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json', 
                                'Content-Length: '.strlen($json_data)));
        curl_setopt($ch, CURLOPT_URL, $this->service_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);   
    
        // Send the request
        $response = curl_exec($ch);
    
        // Check for errors
        if($response === FALSE){
            die(curl_error($ch));
        }
    
        // Decode the response
        $responseData = json_decode($response, TRUE);
    
        // Print the date from the response
        //echo $responseData['code'];
        //echo $responseData['message'];

        return $responseData;
    }

    public function is_local(){
        $local = false;
        if(preg_match('/^local\./', $_SERVER['HTTP_HOST'])) $local = true;
        return $local;
    }


    /********* DATA SET *********************/

    public function get_template_code($uid){
        if($uid=="리얼딥"){
            $template_code = "realdeep_01";
        }else if($uid=="원진성형외과의원"){
            $template_code = "wonjin_01";
        }else if($uid=="TU치과"){
            $template_code = "tu_01";
        }else if($uid=="클래시성형외과의원"){
            $template_code = "classy_01";
        }else if($uid=="세가지소원의원"){
            $template_code = "twscmobal_001";
        }else if($uid=="GS안과의원"){
            $template_code = "gs_01";
        }else if($uid=="원치과의원"){
            $template_code = "one_001";
        }else if($uid=="티오피성형외과의원"){
            $template_code = "top_001";
        }
        
        return $template_code;
    }

    public function get_from_digit($uid){
        if($uid=="리얼딥"){
            $from_digit = "01029253789";
        }else if($uid=="원진성형외과의원"){
            $from_digit = "0234773300";
        }else if($uid=="TU치과"){
            $from_digit = "025988896";
        }else if($uid=="클래시성형외과의원"){
            $from_digit = "025452115";
        }else if($uid=="세가지소원의원"){
            $from_digit = "0221355877";
        }else if($uid=="GS안과의원"){
            $from_digit = "01064779009";
        }else if($uid=="원치과의원"){
            $from_digit = "01030911511";
        }else if($uid=="티오피성형외과의원"){
            $from_digit = "01044988700";
        }
        
        return $from_digit;
    }

    public function get_registered_msg($uid, $name="", $etc="", $phone=""){
        if($uid=="리얼딥"){
            $msg = "[리얼딥]
            안녕하세요. ".$name."님^^
            리얼딥 상담 신청 해주셔서 감사합니다.
            
            고객님께서 알려주신 예약날짜 에
            전문 상담원이 전화를 드려
            이벤트 설명을 드릴 예정이오니,
            
            꼭 전화 받아주시면 감사하겠습니다^^
            
            * 대표전화 : 010-7111-0971
            * 전화상담시간 : 평일 오전 12시 ~ 오후 6시";
        }else if($uid=="원진성형외과의원"){
            $msg = "[원진성형외과의원 이벤트 신청]\n";
            $msg.= "안녕하세요. 고객님!\n";
            $msg.= "고객님의 이벤트 신청이 완료되었습니다.\n";
            $msg.= "전문 상담원이 전화를 드려 설명을 드릴 예정이오니 꼭 전화를 받아주시길 바랍니다.\n";
            $msg.= "감사합니다.";
        }
        else if($uid=="TU치과"){
            $msg = "◆티유치과의원 이벤트 신청안내\n\n";
            $msg.= "웃음을 드리는 TU치과입니다:)\n";
            $msg.= "고객님의 이벤트 신청이 완료되었습니다.\n\n";
            $msg.= "■ 성명 : $name\n";
            $msg.= "■ 신청일시 : ".date('Y-m-d')."\n";
            $msg.= "■ 이벤트명 : ".$etc."\n\n";
            $msg.= "전문 상담원이 전화를 드려\n";
            $msg.= "간단한 예약안내 드릴 예정이오니\n";
            $msg.= "전화를 꼭 받아 주세요^^\n\n";
            $msg.= "☎ 예약 및 진료문의 : 02-598-8896\n\n";
            $msg.= "▶ 오시는 길 : https://www.tudentalse.com/intro/map.html\n\n";
            $msg.= "감사합니다.";
        }else if($uid=="클래시성형외과의원"){
            $msg = "◆ 클래시성형외과의원 이벤트 신청안내\n\n";
            $msg.= "안녕하세요. 클래시성형외과입니다.\n";
            $msg.= "고객님의 이벤트 신청이 완료되었습니다.\n\n";
            $msg.= "■ 성명 : $name\n";
            $msg.= "■ 신청일시 : ".date('Y-m-d')."\n\n";
            $msg.= "전문 상담원이 전화를 드려\n";
            $msg.= "간단한 예약안내 드릴 예정이오니\n";
            $msg.= "전화를 꼭 받아 주세요^^\n\n";
            $msg.= "감사합니다.";
        }else if($uid=="세가지소원의원"){
            $msg = "◆ 세가지소원의원 신촌점 이벤트 신청안내\n\n";
            $msg.= "자신감을 드리는 세가지소원의원 신촌 모발센터입니다.\n";
            $msg.= "고객님의 모발이식 이벤트 신청이 완료되었습니다.\n\n";
            $msg.= "■ 성명 : $name\n";
            $msg.= "■ 신청일시 : ".date('Y-m-d')."\n";
            $msg.= "■ 이벤트명 : ".$etc."\n\n";
            $msg.= "전문 상담원이 전화를 드려\n";
            $msg.= "간단한 예약안내 드릴 예정이오니\n";
            $msg.= "전화를 꼭 받아 주세요^^\n\n";
            $msg.= "예약 및 진료문의 : 02-2135-5877\n";
            $msg.= "오시는 길 : http://www.twscmobal.com/page/page4\n\n";
            $msg.= "감사합니다.";
        }else if($uid=="GS안과의원"){
            $msg = "◆ GS안과의원 이벤트 신청 안내\n\n";
            $msg.= "실력이 시력을 만드는 GS안과의원 입니다.\n";
            $msg.= "고객님의 이벤트 신청이 완료되었습니다.\n\n";
            $msg.= "■ 성명 : $name\n";
            $msg.= "■ 신청일시 : ".date('Y-m-d')."\n";
            $msg.= "■ 이벤트명 : ".$etc."\n\n";
            $msg.= "전문 상담원이 연락을 드려\n";
            $msg.= "간단한 안내 드릴 예정이오니\n";
            $msg.= "꼭 받아 주세요^^\n\n";
            $msg.= "감사합니다.";
        }else if($uid=="원치과의원") {
            $msg  = "안녕하세요 {$name}님!\n";
            $msg .= "임플란트 혜택 신청이 완료되었습니다.\n\n";
            $msg .= "■ 신청자명 : {$name}\n";
            $msg .= "■ 전화번호 : {$phone}\n";
            $msg .= "■ 신청일시 : ".date('Y-m-d')."\n";
            $msg .= "■ 이벤트명 : {$etc}\n\n";
            $msg .= "혜택 안내를 위해 빠른 시일 내로 연락을 드릴 예정입니다. 기간이 한정된 이벤트이기 때문에 전화를 꼭 받아주세요~\n\n";
            $msg .= "안심하고 내원하세요! 내 가족을 치료하는 마음으로 정직한 진료를 약속드립니다. ^^\n\n";
            $msg .= "- 원치과의원 병원장 유현진 드림 -";
        }else if($uid=="티오피성형외과의원") {
            $msg  = "안녕하세요 {$name}님!\n";
            $msg .= "이벤트 신청이 완료되었습니다.\n\n";
            $msg .= "■ 신청자명 : {$name}\n";
            $msg .= "■ 신청일시 : ".date('Y-m-d H:i:s')."\n";
            $msg .= "■ 이벤트명 : {$etc}\n\n";
            $msg .= "이벤트 안내를 위해 빠른 시일 내로 연락을 드릴 예정입니다. 기간이 한정된 이벤트이기 때문에 전화를 꼭 받아주세요~\n\n";
            $msg .= "안심하고 내원하세요! 아름다운 결과를 드리기 위해 최선을 다하겠습니다. ^^";
        }

        return $msg;
    }

    /********* DATA SET END*********************/

}

?>