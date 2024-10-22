<?php
include '/home/event/www/dbinfo.php';

$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("SET SESSION character_set_client=utf8mb;");
$db->query("SET SESSION character_set_connection=utf8mb;");

$count = shell_exec("netstat -nap | grep :80 | grep ESTABLISHED | wc -l");
$cpu = shell_exec('top -b -n1 | grep -Po "[0-9.]+ id" | awk "{print 100-\$1}"');
$date = date('Y-m-d');
$time = date('H:i');
$sql = "INSERT INTO event_ccu(date, time, user, cpu) VALUES('{$date}', '{$time}', {$count}, '{$cpu}') ON DUPLICATE KEY UPDATE user = IF(user < {$count}, {$count}, user), cpu = IF(cpu < {$cpu}, {$cpu}, cpu)";
$db->query($sql);