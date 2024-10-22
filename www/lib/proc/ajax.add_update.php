<?php 
//add 업데이트
include '../../dbinfo.php';
include '../../core.php';
$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("SET SESSION character_set_client=utf8mb4;");
$db->query("SET SESSION character_set_connection=utf8mb4;");

$eid = $_POST['eid'];
if(empty($eid)){echo false;}

$env = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/../.env');
$aes_key = $env['aes.key'];
$val = hex2bin($eid);
$key = substr(hex2bin(openssl_digest($aes_key, 'sha512')), 0, 16);
$eid = @openssl_decrypt($val, "AES-128-ECB", $key, true);

$sql = "UPDATE app_subscribe SET ";
$updates = array();
for($i=1; $i<=8; $i++) {
    if(!empty($_POST["add$i"])) {
        $updates[] = "add$i = '{$_POST["add$i"]}'";
    }
}
$sql .= implode(", ", $updates);
$sql .= " WHERE eid = {$eid}";

$result = $db->query($sql);

if($result) { //저장 완료 시
    echo true;
} else {
    echo false;
}

exit;
?>
