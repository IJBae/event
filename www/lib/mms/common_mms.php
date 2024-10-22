<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);
class MmsPacket
{
	//MMS Auth Variables
	var $SzCommand;
	var $SzDeptCode;
	var $SzClientVer;
	var $SzUserCode;
	var $SzReserved;
	
	//MMS Body Variables
	var $SzBodyType;
	var $SzSeqNo;
	var $SzCallPhone;
	var $SzReqPhone;
	var $SzTime;
	var $SzSubject;
	var $SzIsText;
	var $SzFileCnt;
	var $SzContinue = "Y";
	var $SzTextLen;
	var $SzMsg;

	//MMS File Variables
	var $SzFileLen1;
	var $SzFileType1;
	var $SzFile1 = array();	
	var $SzFileLen2;
	var $SzFileType2;
	var $SzFile2 = array();
	var $SzFileLen3;
	var $SzFileType3;
	var $SzFile3 = array();	

	//SMS 나머지
	var $ServiceID;
	var $TotalPrice;
	var $CallPrice;
	var $SeqNum;

	private $UserCode = "carelabs";
	private $DeptCode = "16-O6R-GF";
	private $DeptName = "케어렙스";


	//MMS Server Connection Method
	function getconnect()
	{
		$errno = 1;
		//$SERVER_IP = gethostbyname ("mms.surem.com");  //실제 서버 IP 얻기
		$SERVER_IP = "mms.surem.com";
		$PORT = 7744;  //포트 번호

		$TIME_OUT = 15;

		$fp = fsockopen( $SERVER_IP, $PORT, $errno, $errstr, $TIME_OUT);	//소켓 연결
		

		//연결 성공시 파일 포인터 반환
		return $fp;
	}

	//Socket Closed Method
	function disconnect($fp)
	{
		fclose($fp);
	}

	
	//Variables Add Space (Null)
	function addnull($index)
	{
		$str = "";

		for ($i=0;$i<$index;$i++)
			$str .= chr(0x00);

		return $str;
	}

	//Int to Byte Method
	function int2byte( $i ) 
	{
		settype($i,integer);

		$temp = sprintf("%c",($i&0xFF));
		$temp = $temp.sprintf("%c",($i>>8&0xFF));
		$temp = $temp.sprintf("%c",($i>>16&0xFF));
		$temp = $temp.sprintf("%c",($i>>24&0xFF));

		return $temp;
	}

	//Byte to String Method
	function byte2str($i)
	{
		$a = substr($i,0,1);
		$b = substr($i,1,1);
		$c = substr($i,2,1);
		$d = substr($i,3,1);

		$temp = ord($a) *1 + ord($b)*256 + ord($c) * 65536;

		return $temp;
	}

	//Mms Packet Send!
	function SendMms ($param_SeqNo, $param_CallPhone, $param_ReqPhone, $param_Time, $param_Subject, $param_Msg, $param_File1, $param_File2, $param_File3)
	{
		$Req = '';
		//MMS AUTH PACKET SETTING
		$this->SzCommand = "B";
		$this->SzDeptCode = $this->DeptCode . $this->addnull(12 - strlen($this->DeptCode));
		$this->SzClientVer = "P1.0.0" . $this->addnull(16 - strlen("P1.0.0"));
		$this->SzUserCode = $this->UserCode . $this->addnull(30 - strlen($this->UserCode));
		$this->SzReserved = "        "; 

		$fp=$this->getconnect();

		if($fp=="-1"){
			return "-1";
		}

		$AuthStr = $this->SzCommand.$this->SzDeptCode.$this->SzClientVer.$this->SzUserCode.$this->SzReserved;

		fwrite($fp, $AuthStr, 67);
		flush();

		socket_set_timeout($fp, 10);

		$AuthReq=fread($fp, 4);

		if(trim($AuthReq) == "1"){
			if ($SzTime == ""){
				$this->SzTime = "00000000000000";
			}
		
			//MMS BODY PACKET SETTING
			$this->SzBodyType = "Q";
			$this->SzSeqNo = $param_SeqNo . $this->addnull(32 - strlen($param_SeqNo));
			$this->SzCallPhone = $param_CallPhone . $this->addnull(13 - strlen($param_CallPhone));
			$this->SzReqPhone = $param_ReqPhone . $this->addnull(13 - strlen($param_ReqPhone));
			$this->SzTime = $param_Time . $this->addnull(16 - strlen($param_Time));
			$this->SzSubject = $param_Subject . $this->addnull(120 - strlen($param_Subject));
			$this->SzIsText = "1" . $this->addnull(4 - strlen("1"));
			$this->SzFileCnt = "0" . $this->addnull(4 - strlen("0"));
			$this->SzContinue = "N";
			$this->SzTextLen = strlen($param_Msg) . $this->addnull(4 - strlen(strlen($param_Msg)));
			$this->SzMsg = $param_Msg;		

			//MMS FILE PACKET SETTING
			if($param_File1!=null){								
				$file = basename($param_File1,"/");
				$filesize = filesize($param_File1);
				$arr = explode(".",$file);
				$filetype = $arr[1];				
				$file1 = fopen($param_File1,"rb");
				$content = fread($file1,$filesize);
				
				$this->SzFileLen1 = $filesize . $this->addnull(10 - strlen($filesize));
				$this->SzFileType1 = $filetype . $this->addnull(4 - strlen($filetype));
				$this->SzFile1 = $content;
				$this->SzFileCnt = "1" . $this->addnull(4 - strlen("1"));
			}
			
			if($param_File2!=null){				
				$file = basename($param_File2,"/");
				$filesize = filesize($param_File2);
				$arr = explode(".",$file);
				$filetype = $arr[1];				
				$file2 = fopen($param_File2,"rb");
				$content = fread($file2,$filesize);
						
				$this->SzFileLen2 = $filesize . $this->addnull(10 - strlen($filesize));
				$this->SzFileType2 = $filetype . $this->addnull(4 - strlen($filetype));
				$this->SzFile2 = $content;
				$this->SzFileCnt = "2" . $this->addnull(4 - strlen("2"));
			}

			if($param_File3!=null){				
				$file = basename($param_File3,"/");
				$filesize = filesize($param_File3);
				$arr = explode(".",$file);
				$filetype = $arr[1];				
				$file3 = fopen($param_File3,"rb");
				$content = fread($file3,$filesize);
						
				$this->SzFileLen3 = $filesize . $this->addnull(10 - strlen($filesize));
				$this->SzFileType3 = $filetype . $this->addnull(4 - strlen($filetype));
				$this->SzFile3 = $content;
				$this->SzFileCnt = "3" . $this->addnull(4 - strlen("3"));
			}			
			$Str = $this->SzBodyType.$this->SzSeqNo.$this->SzCallPhone.$this->SzReqPhone.$this->SzTime;
			$Str = $Str.$this->SzSubject.$this->SzIsText.$this->SzFileCnt.$this->SzContinue.$this->SzTextLen;
			$Str = $Str.$this->SzMsg;
			$Str = $Str.$this->SzFileLen1.$this->SzFileType1.$this->SzFile1;
			$Str = $Str.$this->SzFileLen2.$this->SzFileType2.$this->SzFile2;
			$Str = $Str.$this->SzFileLen3.$this->SzFileType3.$this->SzFile3;
			
		
			fwrite($fp, $Str, strlen($Str));
			flush();

			socket_set_timeout($fp, 10);

			$Req = fread($fp, 1024); 

			$Req = substr($Req, 32, 1);
		}

		$this->disconnect($fp);

		return $Req;
	}

	function SendLms ($param_SeqNo, $param_CallPhone, $param_ReqPhone, $param_Time, $param_Subject, $param_Msg)
	{
		$Req = '';
		//MMS AUTH PACKET SETTING
		$this->SzCommand = "B";
		$this->SzDeptCode = $this->DeptCode . $this->addnull(12 - strlen($this->DeptCode));
		$this->SzClientVer = "P1.0.0" . $this->addnull(16 - strlen("P1.0.0"));
		$this->SzUserCode = $this->UserCode . $this->addnull(30 - strlen($this->UserCode));
		$this->SzReserved = "        "; 

		$fp=$this->getconnect();

		if($fp=="-1"){
			return "-1";
		}

		$AuthStr = $this->SzCommand.$this->SzDeptCode.$this->SzClientVer.$this->SzUserCode.$this->SzReserved;

		fwrite($fp, $AuthStr, 67);
		flush();

		socket_set_timeout($fp, 10);

		$AuthReq=fread($fp, 4);

		if(trim($AuthReq) == "1"){
			if ( !isset($SzTime) || $SzTime == ""){
				$this->SzTime = "00000000000000";
			}
		
			//MMS BODY PACKET SETTING
			$this->SzBodyType = "Q";
			$this->SzSeqNo = $param_SeqNo . $this->addnull(32 - strlen($param_SeqNo));
			$this->SzCallPhone = $param_CallPhone . $this->addnull(13 - strlen($param_CallPhone));
			$this->SzReqPhone = $param_ReqPhone . $this->addnull(13 - strlen($param_ReqPhone));
			$this->SzTime = $param_Time . $this->addnull(16 - strlen($param_Time));
			$this->SzSubject = $param_Subject . $this->addnull(120 - strlen($param_Subject));
			$this->SzIsText = "1" . $this->addnull(4 - strlen("1"));
			$this->SzFileCnt = "0" . $this->addnull(4 - strlen("0"));
			$this->SzContinue = "N";
			$this->SzTextLen = strlen($param_Msg) . $this->addnull(4 - strlen(strlen($param_Msg)));
			$this->SzMsg = $param_Msg;		

			//MMS FILE PACKET SETTING			
			$Str = $this->SzBodyType.$this->SzSeqNo.$this->SzCallPhone.$this->SzReqPhone.$this->SzTime;
			$Str = $Str.$this->SzSubject.$this->SzIsText.$this->SzFileCnt.$this->SzContinue.$this->SzTextLen;
			$Str = $Str.$this->SzMsg;

		
			fwrite($fp, $Str, strlen($Str));
			flush();

			socket_set_timeout($fp, 10);

			$Req = fread($fp, 1024); 

			$Req = substr($Req, 32, 1);
		}

		$this->disconnect($fp);

		return $Req;
	}

	
	function SendMmsDongbo($pre_data)
	{
		$Req = '';
		if ( $pre_data[0]["SeqNo"] == "" ) {
			return "input data";
		}
	
		$flag = true;
		$this->SzContinue = "Y";
		$fp = $this->getconnect();
				
		if($fp=="-1"){
			return "-1";
		}
		
		$count = count($pre_data);
		array_unshift($pre_data,"");
		
		//MMS AUTH PACKET SETTING
		$this->SzCommand = "B";
		$this->SzDeptCode = $this->DeptCode . $this->addnull(12 - strlen($this->DeptCode));
		$this->SzClientVer = "P1.0.0" . $this->addnull(16 - strlen("P1.0.0"));
		$this->SzUserCode = $this->UserCode . $this->addnull(30 - strlen($this->UserCode));
		$this->SzReserved = "        "; //LENGTH = 8	
		
		$AuthStr = $this->SzCommand.$this->SzDeptCode.$this->SzClientVer.$this->SzUserCode.$this->SzReserved;
		$AuthAck = "";
		
		while($flag){
			$data = $pre_data[$count];
			
			if($count=="1"){
				$this->SzContinue = "N";
				$flag = false;
			}

			if(trim($AuthAck)!="1"){		
				fwrite($fp, $AuthStr, 67);
				flush();
				//socket_set_timeout($fp, 10);
				$AuthAck=fread($fp, 4);

				if(trim($AuthAck)=="1"){
					$Req = $this->SendPacket($data["SeqNo"], $data["CallPhone"], $data["ReqPhone"], $data["Time"], $data["Subject"], $data["Msg"], $data["filepath1"], $data["filepath2"], $data["filepath3"], $fp);
			
				}else{
					$Req = "-1";
				}

			}else if(trim($AuthAck)=="1"){
				$Req = $this->SendPacket($data["SeqNo"], $data["CallPhone"], $data["ReqPhone"], $data["Time"], $data["Subject"], $data["Msg"], $data["filepath1"], $data["filepath2"], $data["filepath3"], $fp);
			}
			$count--;
		}//end while
		
		$this->disconnect($fp);
		return $Req;				
			
	}

	function SendPacket($param_SeqNo, $param_CallPhone, $param_ReqPhone, $param_Time, $param_Subject, $param_Msg, $param_File1, $param_File2, $param_File3, $fp)
	{
		//MMS BODY PACKET SETTING
		$Req = '';
		$this->SzBodyType = "Q";
		$this->SzSeqNo = $param_SeqNo . $this->addnull(32 - strlen($param_SeqNo));
		$this->SzCallPhone = $param_CallPhone . $this->addnull(13 - strlen($param_CallPhone));	
		$this->SzReqPhone = $param_ReqPhone . $this->addnull(13 - strlen($param_ReqPhone));
		$this->SzTime = $param_Time . $this->addnull(16 - strlen($param_Time));
		$this->SzSubject = $param_Subject . $this->addnull(120 - strlen($param_Subject));
		$this->SzIsText = "1" . $this->addnull(4 - strlen("1"));
		$this->SzFileCnt = "0" . $this->addnull(4 - strlen("0"));
		$this->SzTextLen = strlen($param_Msg) . $this->addnull(4 - strlen(strlen($param_Msg)));
		$this->SzMsg = $param_Msg;

		$this->SzFile1 = null;
		$this->SzFile2 = null;
		$this->SzFile3 = null;

		//MMS FILE PACKET SETTING
		if($param_File1!=null){							
			$file = basename($param_File1,"/");
			$filesize = filesize($param_File1);
			$arr = explode(".",$file);
			$filetype = $arr[1];				
			$file1 = fopen($param_File1,"rb");
			$content = fread($file1,$filesize);
			
			$this->SzFileLen1 = $filesize . $this->addnull(10 - strlen($filesize));
			$this->SzFileType1 = $filetype . $this->addnull(4 - strlen($filetype));
			$this->SzFile1 = $content;
			$this->SzFileCnt = "1" . $this->addnull(4 - strlen("1"));
		}
		
		if($param_File2!=null){			
			$file = basename($param_File2,"/");
			$filesize = filesize($param_File2);
			$arr = explode(".",$file);
			$filetype = $arr[1];				
			$file2 = fopen($param_File2,"rb");
			$content = fread($file2,$filesize);
					
			$this->SzFileLen2 = $filesize . $this->addnull(10 - strlen($filesize));
			$this->SzFileType2 = $filetype . $this->addnull(4 - strlen($filetype));
			$this->SzFile2 = $content;
			$this->SzFileCnt = "2" . $this->addnull(4 - strlen("2"));
		}

		if($param_File3!=null){			
			$file = basename($param_File3,"/");
			$filesize = filesize($param_File3);
			$arr = explode(".",$file);
			$filetype = $arr[1];				
			$file3 = fopen($param_File3,"rb");
			$content = fread($file3,$filesize);
					
			$this->SzFileLen3 = $filesize . $this->addnull(10 - strlen($filesize));
			$this->SzFileType3 = $filetype . $this->addnull(4 - strlen($filetype));
			$this->SzFile3 = $content;
			$this->SzFileCnt = "3" . $this->addnull(4 - strlen("3"));
		}
		
		$Str = $this->SzBodyType.$this->SzSeqNo.$this->SzCallPhone.$this->SzReqPhone.$this->SzTime;
		$Str = $Str.$this->SzSubject.$this->SzIsText.$this->SzFileCnt.$this->SzContinue.$this->SzTextLen;
		$Str = $Str.$this->SzMsg;
		$Str = $Str.$this->SzFileLen1.$this->SzFileType1.$this->SzFile1;
		$Str = $Str.$this->SzFileLen2.$this->SzFileType2.$this->SzFile2;
		$Str = $Str.$this->SzFileLen3.$this->SzFileType3.$this->SzFile3;
		
		fwrite($fp, $Str, strlen($Str));
		flush();

		$Str = null;

		//socket_set_timeout($fp, 10);

		$Req = fread($fp, 1024); 
		$Req = substr($Req, 32, 1);		
		
		return $Req;


	}
	function reserveCancel($member,$date,$callphone1,$callphone2,$callphone3)
	{
		$cdate = mktime();

		$this->szCmd = "R";
		$this->SzDeptCode = $this->DeptCode . $this->addnull(12 - strlen($this->DeptCode));
		$this->SzDeptName = $this->DeptName . $this->addnull(16 - strlen($this->DeptName));
		$this->SzUserCode = $this->UserCode . $this->addnull(30 - strlen($this->UserCode));
		$this->SzContinue = $this->addnull(8);
		
		$fp=$this->getconnect("messenger");
		
		if($fp=="-1")
		{
			return "-1";
		}

		$AuthStr = $this->szCmd.$this->SzDeptCode.$this->SzDeptName.$this->SzUserCode.$this->SzContinue;

		fwrite($fp, $AuthStr, 67);
		flush();

		socket_set_timeout($fp, 10);

		$AuthReq=fread($fp, 4);
				
		$this->szTime = $date . $this->addnull(9 - strlen($date));
		$this->SzCallPhone = $callphone1.$callphone2.$callphone3 . $this->addnull(15 - strlen($callphone1.$callphone2.$callphone3));
		$this->SzClientVer = $member . $this->addnull(32 - strlen($member));
		
		$Str = $this->szTime.$this->SzCallPhone.$this->SzClientVer;

		fwrite($fp, $Str,56);
		flush();

		socket_set_timeout($fp, 10);

		$res=fread($fp,400);

		$this->disconnect($fp);

		return $res;
	}		

}
?>