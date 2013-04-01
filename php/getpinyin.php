<?php

/**将汉字转化为拼音（扒自dedecms）**/

//获取拼音以gbk编码为准
function getpy($str,$ishead=0,$isclose=1) {
	if(is_utf8($str)) {
		return SpGetPinyin(utf82gb($str),$ishead,$isclose);
	}
	else {
		return SpGetPinyin($str,$ishead,$isclose);
	}
}

function SpGetPinyin($str,$ishead=0,$isclose=1) {
	global $pinyins;
	$restr = '';
	$str = trim($str);
	$slen = strlen($str);
	if($slen<2) {
		return $str;
	}
	if(count($pinyins)==0) {
		$fp = fopen('pinyin.dat','r');
		while(!feof($fp)) {
			$line = trim(fgets($fp));
			$pinyins[$line[0].$line[1]] = substr($line,3,strlen($line)-3);
		}
		fclose($fp);
	}
	for($i=0;$i<$slen;$i++) {
		if(ord($str[$i])>0x80) {
			$c = $str[$i].$str[$i+1];
			$i++;
			if(isset($pinyins[$c])) {
				if($ishead==0) {
					$restr .= $pinyins[$c];
				} else {
					$restr .= $pinyins[$c][0];
				}
			} else {
				$restr .= "_";
			}
		} else if( eregi("[a-z0-9]",$str[$i]) ) {
			$restr .= $str[$i];
		} else {
			$restr .= "_";
		}
	}
	if($isclose==0) {
		unset($pinyins);
	}
	return $restr;
}


//UTF-8 转GB编码
function utf82gb($utfstr) {
	if(function_exists('iconv')) {
		return iconv('utf-8','gbk//ignore',$utfstr);
	}
	global $UC2GBTABLE;
	$okstr = "";
	if(trim($utfstr)=="") {
		return $utfstr;
	}
	if(empty($UC2GBTABLE)) {
		$filename = "/getpinyin/gb2312-utf8.dat";
		$fp = fopen($filename,"r");
		while($l = fgets($fp,15)) {
			$UC2GBTABLE[hexdec(substr($l, 7, 6))] = hexdec(substr($l, 0, 6));
		}
		fclose($fp);
	}
	$okstr = "";
	$ulen = strlen($utfstr);
	for($i=0;$i<$ulen;$i++) {
		$c = $utfstr[$i];
		$cb = decbin(ord($utfstr[$i]));
		if(strlen($cb)==8) {
			$csize = strpos(decbin(ord($cb)),"0");
			for($j=0;$j < $csize;$j++) {
				$i++; $c .= $utfstr[$i];
			}
			$c = utf82u($c);
			if(isset($UC2GBTABLE[$c])) {
				$c = dechex($UC2GBTABLE[$c]+0x8080);
				$okstr .= chr(hexdec($c[0].$c[1])).chr(hexdec($c[2].$c[3]));
			} else {
				$okstr .= "&#".$c.";";
			}
		} else {
			$okstr .= $c;
		}
	}
	$okstr = trim($okstr);
	return $okstr;
}

//GB转UTF-8编码
function gb2utf8($gbstr) {
	if(function_exists('iconv')) {
		return iconv('gbk','utf-8//ignore',$gbstr);
	}
	global $CODETABLE;
	if(trim($gbstr)=="") {
		return $gbstr;
	}
	if(empty($CODETABLE)) {
		$filename = "/getpinyin/gb2312-utf8.dat";
		$fp = fopen($filename,"r");
		while ($l = fgets($fp,15)) {
			$CODETABLE[hexdec(substr($l, 0, 6))] = substr($l, 7, 6);
		}
		fclose($fp);
	}
	$ret = "";
	$utf8 = "";
	while ($gbstr != '') {
		if (ord(substr($gbstr, 0, 1)) > 0x80) {
			$thisW = substr($gbstr, 0, 2);
			$gbstr = substr($gbstr, 2, strlen($gbstr));
			$utf8 = "";
			@$utf8 = u2utf8(hexdec($CODETABLE[hexdec(bin2hex($thisW)) - 0x8080]));
			if($utf8!="") {
				for ($i = 0;$i < strlen($utf8);$i += 3)
				$ret .= chr(substr($utf8, $i, 3));
			}
		} else {
			$ret .= substr($gbstr, 0, 1);
			$gbstr = substr($gbstr, 1, strlen($gbstr));
		}
	}
	return $ret;
}
//utf8转Unicode
function utf82u($c) {
	switch(strlen($c)) {
		case 1:
			return ord($c);
		case 2:
			$n = (ord($c[0]) & 0x3f) << 6;
			$n += ord($c[1]) & 0x3f;
			return $n;
		case 3:
			$n = (ord($c[0]) & 0x1f) << 12;
			$n += (ord($c[1]) & 0x3f) << 6;
			$n += ord($c[2]) & 0x3f;
			return $n;
		case 4:
			$n = (ord($c[0]) & 0x0f) << 18;
			$n += (ord($c[1]) & 0x3f) << 12;
			$n += (ord($c[2]) & 0x3f) << 6;
			$n += ord($c[3]) & 0x3f;
			return $n;
	}
}

// Returns true if $string is valid UTF-8 and false otherwise.
function is_utf8($word) {
    if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$word) == true) {
        return true;
    } else {
        return false;
    }
} // function is_utf8



$text = trim($_GET['text']);
echo getpy($text);
