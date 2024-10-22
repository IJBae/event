<?php
if($_SERVER['REMOTE_ADDR'] != '127.0.0.1') exit;
require "../../dbinfo.php";
$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);

if(isset($_POST['seqs'])) {
    $seqs = explode(",", $_POST['seqs']);
    foreach($seqs as $seq) {
        if(!$seq) continue;
        $sql = "SELECT el.event_seq, el.name, DEC_DATA(el.phone) AS phone, el.gender, el.age, el.branch, el.status, el.add1, el.add2, el.add3, el.add4, el.ip, el.reg_date, ei.*, el.seq 
                FROM `zenith`.event_leads AS el
                    LEFT JOIN `zenith`.event_information AS ei ON el.event_seq = ei.seq
                    LEFT JOIN `zenith`.event_advertiser AS ea ON ei.advertiser = ea.seq
                    LEFT JOIN `zenith`.event_media AS em ON ei.media = em.seq
                WHERE el.seq = {$seq}";
        $result = $db->query($sql);
        if(!$result->num_rows) continue;
        $data = $result->fetch_assoc();

        switch($data['branch']) {
            case '서울' :
            case '경기' :
                $branch = 'gangnam'; break;
            case '부평' :
            case 'bupyeong' :
            case '인천' :
            case 'incheon' :
                $branch = 'bupyeong'; break;	
            default : $branch = $data['branch']; break;
        }
        $selectname = $data['add1']."/".$data['add2']."/".$data['add3']."/".$data['add4'];
        $interlock_data = array(
            "phone" => $data['phone'],
            "name" => $data['name'],
            "gender" => $data['gender'],		//성별
            "poss_time" => "",//상담가능시간
            "etc5" => $branch,	//지점
            "branch" => $branch,	//지점
            "age" => $data['age'],			//나이
            "status" => $data['status'],	//데이터 유효성
            "landing_key" => $data['partner_id'],	// 플란치과 연동코드는 파트너id 에 입력 예정
            "event_num" => 'evt_'.$data['event_seq'], //이벤트 번호
            "event_seq" => $data['event_seq'], //이벤트 번호
            "add1" => $selectname, 			//설문
            "reg_ip" => $data['ip'], //아이피
            "ip" => $data['ip'], //아이피
            "reg_date" => $data['reg_date'], 	//등록일
            "mkt_chk" => 'N', 	//마케팅 동의	//케어랩스 랜딩에는 마케팅 동의 항목이 없으므로 N 으로 고정
            "group_id" => 'evt_'.$data['event_seq'],
            "outsource" => 'viberc',
        );
        $meta_url = "https://event.metamarketing.co.kr/lib/proc/viberc.php";
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $meta_url,
            CURLOPT_POSTFIELDS => count($interlock_data),
            CURLOPT_POSTFIELDS => $interlock_data
        ));
        $result =curl_exec($ch);
        curl_close($ch);
        $is_success = 0;
        $response = @json_decode($result, true);
        if($response['result'] == 'success') {
            $is_success = 1;
            echo "{$data['seq']}-{$data['name']} ";
            $update = $db->query("UPDATE zenith.event_leads SET interlock_success = 1 WHERE seq = {$data['seq']}");
            if($update) {
                echo "성공";
            } else {
                echo "실패";
                echo $db->error;
                print_r($response);
            }
            echo '<br>';
        }
        $data['send_data'] = @json_encode($interlock_data, JSON_UNESCAPED_UNICODE);
        array_walk($data, function(&$string) use($db) { $string = $db->real_escape_string($string); }); //DB저장을 위해 escape 처리
        //외부연동 내역
        $sql = "INSERT INTO `zenith`.`event_leads_interlock`(`leads_seq`,`event_seq`,`url`,`partner_id`,`partner_name`,`paper_code`,`paper_name`,`send_data`,`response_data`,`is_success`)
                VALUES('{$data['seq']}','{$data['event_seq']}','{$meta_url}','{$data['partner_id']}','{$data['partner_name']}','{$data['paper_code']}','{$data['paper_name']}','{$data['send_data']}','{$result}',{$is_success});";
        $db->query($sql) or die($db->error);
    }
}

//전송 실패 DB 목록
$data = [];
$sdate = date('Y-m-d');
$edate = date('Y-m-d');
if(isset($_GET['sdate'])) $sdate = $_GET['sdate'];
if(isset($_GET['edate'])) $edate = $_GET['edate'];
$sql = "SELECT ea.name AS advertiser, em.media, el.seq, el.name, DEC_DATA(el.phone) AS phone, eli.* FROM `zenith`.event_leads_interlock AS eli
            LEFT JOIN `zenith`.event_leads AS el ON el.seq = eli.leads_seq
            LEFT JOIN `zenith`.event_information AS ei ON eli.event_seq = ei.seq
            LEFT JOIN `zenith`.event_advertiser AS ea ON ei.advertiser = ea.seq
            LEFT JOIN `zenith`.event_media AS em ON ei.media = em.seq
        WHERE ei.interlock = 1 AND el.interlock_success = 0 AND eli.is_success = 0 AND DATE(eli.reg_date) >= '{$sdate}' AND DATE(eli.reg_date) <= '{$edate}' AND eli.url LIKE 'https://event.metamarketing.co.kr%'
        GROUP BY eli.leads_seq
        ORDER BY eli.reg_date DESC
        ";
$result = $db->query($sql) or die($db->error);
// echo '<pre>'.print_r($result,1).'</pre>';
if(!$result->num_rows) exit('No Data');
while($row = $result->fetch_assoc()) $data[] = array_map('strip_tags', $row);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>메타마케팅 외부연동 재전송</title>
    <script src="//static.hotblood.co.kr/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://carezenith.co.kr/static/node_modules/moment/moment.js"></script>
    <script src="https://carezenith.co.kr/static/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="https://carezenith.co.kr/static/node_modules/daterangepicker/daterangepicker.js"></script>
    <link href="https://carezenith.co.kr/static/node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://carezenith.co.kr/static/node_modules/daterangepicker/daterangepicker.css" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" rel="stylesheet"/>
    <link href="https://cdn.datatables.net/v/ju/dt-1.13.4/fh-3.3.2/datatables.min.css" rel="stylesheet"/>
    <script src="https://cdn.datatables.net/v/ju/dt-1.13.4/fh-3.3.2/datatables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.jqueryui.min.js"></script>
<script>
$(function() {
    $('[name="all"]').bind('click', function() {
        $('[name*="seqs"]').prop('checked', this.checked);
    });
    $('tbody tr td:not(:first-child)').bind('click', function(e) {
        $('td input', this.parentNode).prop('checked', !$('td input', this.parentNode).prop('checked'));
    });
    var today = moment().format('YYYY-MM-DD');
    $('#sdate, #edate').val(today);
    $('#sdate, #edate').daterangepicker({
        locale: {
                "format": 'YYYY-MM-DD',     // 일시 노출 포맷
                "applyLabel": "확인",                    // 확인 버튼 텍스트
                "cancelLabel": "취소",                   // 취소 버튼 텍스트
                "daysOfWeek": ["일", "월", "화", "수", "목", "금", "토"],
                "monthNames": ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"]
        },
        alwaysShowCalendars: true,                        // 시간 노출 여부
        showDropdowns: true,                     // 년월 수동 설정 여부
        autoApply: true,                         // 확인/취소 버튼 사용여부
        maxDate: new Date(),
        autoUpdateInput: false,
        ranges: {
            '오늘': [moment(), moment()],
            '어제': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '지난 일주일': [moment().subtract(6, 'days'), moment()],
            '지난 한달': [moment().subtract(29, 'days'), moment()],
            '이번달': [moment().startOf('month'), moment().endOf('month')],
        }
    }, function(start, end, label) {
        // Lets update the fields manually this event fires on selection of range
        startDate = start.format('YYYY-MM-DD'); // selected start
        endDate = end.format('YYYY-MM-DD'); // selected end

        checkinInput = $('#sdate');
        checkoutInput = $('#edate');

        // Updating Fields with selected dates
        checkinInput.val(startDate);
        checkoutInput.val(endDate);

        // Setting the Selection of dates on calender on CHECKOUT FIELD (To get this it must be binded by Ids not Calss)
        var checkOutPicker = checkoutInput.data('daterangepicker');
        checkOutPicker.setStartDate(startDate);
        checkOutPicker.setEndDate(endDate);

        // Setting the Selection of dates on calender on CHECKIN FIELD (To get this it must be binded by Ids not Calss)
        var checkInPicker = checkinInput.data('daterangepicker');
        checkInPicker.setStartDate(checkinInput.val(startDate));
        checkInPicker.setEndDate(endDate);
    });
})
</script>
</head>
<body>
<h1>테크랩스 외부연동 재전송 프로그램</h1>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="get" name="search-form" class="search d-flex justify-content-center">
    <div class="term d-flex align-items-center">
        <input type="text" name="sdate" id="sdate">
        <span> ~ </span>
        <input type="text" name="edate" id="edate">
    </div>
    <div class="input">
        <button class="btn-primary" id="search_btn" type="submit">조회</button>
    </div>
</form>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
<input type="hidden" name="seqs">
<button type="submit">재전송</button>
<table>
    <thead>
    <tr>
        <th style="width:50px;">이벤트</th>
        <th style="width:150px;">광고주</th>
        <th style="width:80px;">매체</th>
        <th style="width:80px;">이름</th>
        <th style="width:100px;">연락처</th>
        <th>리턴값</th>
        <th style="width:90px;">일시</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($data as $row) {?>
    <tr id="<?php echo $row['seq'];?>">
        <td><?php echo $row['event_seq'];?></td>
        <td><?php echo $row['advertiser'];?></td>
        <td><?php echo $row['media'];?></td>
        <td><?php echo $row['name'];?></td>
        <td><?php echo $row['phone'];?></td>
        <td><?php echo $row['response_data'];?></td>
        <td><?php echo $row['reg_date'];?></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
</form>
<script>
var dataTable = $('table').DataTable({
    autoWidth: true,
    paging: false,
    search: true,
    order: [[6,'desc']],
});
$('table').on('click', 'tr', function () {
    $(this).toggleClass('selected');
});
 
$('form').bind('submit', function() {
    var seqs = dataTable.rows('.selected').ids();
    $('input[name="seqs"]').val(seqs.join(','));
    if(!$('input[name="seqs"]').val()) {
        return false;
    }
});
</script>
</body>
</html>