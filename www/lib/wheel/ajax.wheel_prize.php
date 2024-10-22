<?php
$type = $_POST['type'];
$segments = $_POST['segments'];

$grade_1 = ['1등', '무료']; //1등 텍스트 인식문구 (array)

$result = [];
if(!$type || !$segments) exit(json_encode(['result'=>false]));

$numbers = [];
foreach($segments as $idx => $row) {
	if(isset($row['text'])) $numbers[] = $idx;
}
switch($type) {
	case '1' : //1등만 당첨
		$numbers = [];
		foreach($segments as $idx => $row) {
			if(isset($row['text']) && in_array($row['text'], $grade_1)) $numbers[] = $idx;
		}
		break;
	case '2' : //가위바위보 전용
		$numbers = [];
		foreach($segments as $idx => $row) {
			if(isset($row['text']) && in_array($row['text'], ['바위','가위'])) $numbers[] = $idx;
		}
		function weighted_random($weights) {
			$r = rand(1, array_sum($weights));
			for($i=0; $i<count($weights); $i++) {
				$r -= $weights[$i];
				if($r < 1) return $i;
			}
			return false;
		}
		$weights = [60,40];
		$index = weighted_random($weights);
		$numbers = [$numbers[$index]];
	default :
		break;
}
$selected = array_rand($numbers);
$data = $numbers[$selected];

echo json_encode(['result'=>true, 'data'=>$data]);
