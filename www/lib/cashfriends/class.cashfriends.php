<?php
Class CashFriends{
    public $url, $log_dir;
    /** cash friends log
     * (1) [IN_URL] [langding_no] [URL]
     * (2) [OUT_IRL] [langding_no] [URL]
     * 
     * D:\xampp\users\event\www\logs/cashFriends'.date('Ymd').'.log'
     * https://trx.cashfriends.io/postback/trevi_czS96v0i/cashfriends?callbackParams={callbackParams}&eventName={EVENT_NAME}
     */
    
    public function __construct($callbackParams, $eventName){
        $this->url = "https://trx.cashfriends.io/postback/trevi_czS96v0i/cashfriends?callbackParams=".$callbackParams."&eventName=".$eventName;
        $this->log_dir = "./logs/cashFriends_".date('Ymd').".log";
    }

    public function curl(){
        $ch = curl_init();                                //curl 초기화
        curl_setopt($ch, CURLOPT_URL, $this->url);        //URL 지정하기
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   //요청 결과를 문자열로 반환 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);      //connection timeout 2초 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //원격 서버의 인증서가 유효한지 검사 안함
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function log_write($log_dir, $str){
        $fp = fopen($log_dir,'a');
        if($log_dir=='' || $log_dir==null) throw new exception("Log_dir error");    // 1. log_dir check
        if($str=='' || $str==null) throw new exception("Log_data non-exist");       // 2. log_data check
        try{
            fwrite($fp, date('H:i:s')."\t".$str."\n");       // 3. write log
        }
        catch(Exception $e){
            $e = $e->getMessage().'(오류코드:'.$e->getCode().')';
        }
        
        fclose($fp);        // 4. close
    }
}
?>