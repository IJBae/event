<?php
/** AJAX 로 받은 이미지를 서버로 저장 *******
 * 저장 경로 : /home/event/www/static/images/랜딩번호/파일명
 * 
 * 
 *****************************************/

if($_FILES){
    // 경로 설정
    if(strpos($_POST['href_url'], '?')){
        $href_url = explode('?', $_POST['href_url']);
    }else{
        $href_url = $_POST['href_url'];
    }
    $url =  explode('/',$href_url);
    $evt = end($url);
    $uploads_dir = '../../static/images/'.$evt;
    $oldumask = umask(0);
    if(!is_dir($uploads_dir)){
        mkdir($uploads_dir, 0777, true);
    }
    umask($oldumask);


    // 변수 정리
    $allowed_ext = array('jpg','jpeg','png','gif');
    $error = $_FILES['file']['error'];
    $name = $_FILES['file']['name'];
    $time = time();
    $filepath = $uploads_dir.DIRECTORY_SEPARATOR.$time;
    $ext = @array_pop(explode('.', $name));

    if($_FILES['file']['name']){
        // 오류 확인
        if( $error != UPLOAD_ERR_OK ) {
            switch( $error ) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    alert("파일이 너무 큽니다. ($error)");
                    break;
                case UPLOAD_ERR_NO_FILE:
                    alert("파일이 첨부되지 않았습니다. ($error)");
                    break;
                default:
                    alert("파일이 제대로 업로드되지 않았습니다. ($error)");
            }
            exit;
        }
         
        // 확장자 확인
        if( !in_array($ext, $allowed_ext) ) {
            alert("허용되지 않는 확장자입니다.");
            exit;
        }
        // 파일 이동
        if(!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
            alert('업로드에 실패하였습니다.');
        } else {
            echo $time;
        }
    }
}else{
    echo "NONE";
}


?>