<?php
$valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'bmp', 'pdf', 'doc', 'ppt'); // valid extensions
$real_path = __DIR__ . '/../..';
$upload_path = '/uploads/'; // upload directory
$data = ['result'=>false];
if ($_FILES['image']) {
    $img = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];
    $original_filesize = $_FILES['image']['size'];
    // get uploaded file's extension
    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    // can upload same image using rand function
    $final_image = (microtime(true)*10000).'_'.date('YmdHis').'_'.$this->landing['seq'];
    // check's valid format
    if (in_array($ext, $valid_extensions)) {
        $filename = strtolower($final_image).'.'.$ext;
        $real_path .= $upload_path.$filename;
        $upload_path .= $filename;
        if (resize_image($tmp)) {
            if (move_uploaded_file( $tmp, $real_path)) {
                $resize_filesize = @filesize($real_path);
                $data = ['result' => true, 'filename' => $filename, 'real_path' => $real_path, 'upload_path' => 'https:'.EVENT_URL.$upload_path, 'original_filesize' => $original_filesize, 'resize_filesize' => $resize_filesize];
            }
        }
    }
}

function resize_image($img, $ratio = 'true')
{
    $exif = @exif_read_data($img);
    $size = _getimagesize($img, 1000); 
    if ($size[2] == 1)    //-- GIF 
        $src = imagecreatefromgif($img);
    elseif ($size[2] == 2) //-- JPG 
        $src = imagecreatefromjpeg($img);
    else    //-- $size[2] == 3, PNG 
        $src = imagecreatefrompng($img);
    
    $dst = imagecreatetruecolor($size['w'], $size['h']);
    $dstX = 0;
    $dstY = 0;
    $dstW = $size['w'];
    $dstH = $size['h'];

    if ($ratio != 'false' && $size['w'] / $size['h'] <= $size[0] / $size[1]) {
        $srcX = ceil(($size[0] - $size[1] * ($size['w'] / $size['h'])) / 2);
        $srcY = 0;
        $srcW = $size[1] * ($size['w'] / $size['h']);
        $srcH = $size[1];
    } elseif ($ratio != 'false') {
        $srcX = 0;
        $srcY = ceil(($size[1] - $size[0] * ($size['h'] / $size['w'])) / 2);
        $srcW = $size[0];
        $srcH = $size[0] * ($size['h'] / $size['w']);
    } else {
        $srcX = 0;
        $srcY = 0;
        $srcW = $size[0];
        $srcH = $size[1];
    }
    imagecopyresampled($dst, $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    if(!empty($exif['Orientation'])) {
        switch($exif['Orientation']) {
            case 8:
                $dst = imagerotate($dst,90,0);
                break;
            case 3:
                $dst = imagerotate($dst,180,0);
                break;
            case 6:
                $dst = imagerotate($dst,-90,0);
                break;
        }
    }
    if(imagejpeg($dst, $img, 100)) {
        imagedestroy($src);
        imagedestroy($dst);
        return TRUE;
    }
}
function _getimagesize($img, $m, $ratio = 'false')
{ 
    $v = @getImageSize($img); 
    if($v === FALSE || $v[2] < 1 || $v[2] > 3) 
        return FALSE; 
    $m = intval($m); 
    if($m > $v[0] && $m > $v[1]) 
        return array_merge($v, array("w"=>$v[0], "h"=>$v[1])); 
    if($ratio != 'false') { 
        $xy = explode(':',$ratio); 
        return array_merge($v, array("w"=>$m, "h"=>ceil($m*intval(trim($xy[1]))/intval(trim($xy[0])))));
    } else if ($v[0] > $v[1]) { 
        $t = $v[0]/$m; 
        $s = floor($v[1]/$t);
        $m = ($m > 0) ? $m : 1; 
        $s = ($s > 0) ? $s : 1; 
        return array_merge($v, array("w"=>$m, "h"=>$s)); 
    } else { 
        $t = $v[1]/intval($m); 
        $s = floor($v[0]/$t); 
        $m = ($m > 0) ? $m : 1; 
        $s = ($s > 0) ? $s : 1; 
        return array_merge($v, array("w"=>$s, "h"=>$m)); 
    }
} 
