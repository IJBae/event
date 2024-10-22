<?php
preg_match_all('/^(([A-Z]{2})?[0-9]+)(\?)?(.+)?$/i', urldecode($_SERVER['QUERY_STRING']), $matches);
// print_r($matches); exit;
if($matches[1][0]) {
    $url = "https://event.hotblood.co.kr/{$matches[1][0]}";
    if(isset($matches[4][0]))
        $url .= "?{$matches[4][0]}";
    header("Location: {$url}");
    exit;
}
//echo nl2br(print_r($matches,1));