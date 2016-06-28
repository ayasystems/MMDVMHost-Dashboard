<?php

function getMMDVMHostVersion() {
	// returns creation-time of MMDVMHost as version-number
	$filename = MMDVMHOSTPATH."/MMDVMHost";
	exec($filename." -v 2>&1", $output);
	if (!startsWith(substr($output[0],18,8),"20")) {
		return getMMDVMHostFileVersion();
	} else {
		return substr($output[0],18,8)." (compiled ".getMMDVMHostFileVersion().")";
	}
}

function getMMDVMHostFileVersion() {
	// returns creation-time of MMDVMHost as version-number
	$filename = MMDVMHOSTPATH."/MMDVMHost";
	if (file_exists($filename)) {
		return date("d M y", filectime($filename));
	}
}

function getMMDVMConfig() {
	// loads MMDVM.ini into array for further use
	$conf = array();
	if ($configs = fopen(MMDVMINIPATH."/".MMDVMINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getYSFGatewayConfig() {
	// loads MMDVM.ini into array for further use
	$conf = array();
	if ($configs = fopen(YSFGATEWAYINIPATH."/".YSFGATEWAYINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getCallsign($mmdvmconfigs) {
	// returns Callsign from MMDVM-config
	return getConfigItem("General", "Callsign", $mmdvmconfigs);
}

function getConfigItem($section, $key, $configs) {
	// retrieves the corresponding config-entry within a [section]
	$sectionpos = array_search("[" . $section . "]", $configs) + 1;
	$len = count($configs);
	while(startsWith($configs[$sectionpos],$key."=") === false && $sectionpos <= ($len) ) {
		if (startsWith($configs[$sectionpos],"[")) {
			return null;
		}
		$sectionpos++;
	}
	
	return substr($configs[$sectionpos], strlen($key) + 1);
}

function getEnabled ($mode, $mmdvmconfigs) {
	// returns enabled/disabled-State of mode
	return getConfigItem($mode, "Enable", $mmdvmconfigs);
}

function showMode($mode, $mmdvmconfigs) {
	// shows if mode is enabled or not.
?>
      <td><span class="label <?php 
	if (getEnabled($mode, $mmdvmconfigs) == 1) {
		if ($mode == "D-Star Network") {
			if (isProcessRunning(IRCDDBGATEWAY)) {
				echo "label-success";		
			} else {
				echo "label-danger\" title=\"ircddbgateway is down!";
			}
		} else {
			if ($mode == "D-Star" || $mode =="DMR" || $mode =="DMR Network" || $mode =="System Fusion" || $mode =="System Fusion Network") {
				if (isProcessRunning("MMDVMHost")) {
					echo "label-success";		
				} else {
					echo "label-danger\" title=\"MMDVMHost is down!";
				}
			}	
		}
	} else {
		echo "label-default";
    }
    ?>"><?php echo $mode ?></span></td>
<?php
}

function getMMDVMLog() {
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	if ($log = fopen(MMDVMLOGPATH."/".MMDVMLOGPREFIX."-".date("Y-m-d").".log", 'r')) {
		while ($logLine = fgets($log)) {
			if (!strpos($logLine, "Debug") && !strpos($logLine,"Received a NAK") && !startsWith($logLine,"I:")) {
				array_push($logLines, $logLine);
			}
		}
		fclose($log);
	}
	return $logLines;
}

function getYSFGatewayLog() {
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	if ($log = fopen(YSFGATEWAYLOGPATH."/".YSFGATEWAYLOGPREFIX."-".date("Y-m-d").".log", 'r')) {
		while ($logLine = fgets($log)) {
			if (startsWith($logLine,"D:")) {
				array_push($logLines, $logLine);
			}
		}
		fclose($log);
	}
	return $logLines;
}

// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
// M: 2016-04-29 00:15:00.013 D-Star, received network header from DG9VH   /ZEIT to CQCQCQ   via DCS002 S
// M: 2016-04-29 19:43:21.839 DMR Slot 2, received network voice header from DL1ESZ to TG 9
// M: 2016-04-30 14:57:43.072 DMR Slot 2, received RF voice header from DG9VH to 5000
function getHeardList($logLines) {
	//array_multisort($logLines,SORT_DESC);
	$heardList = array();
	$ts1duration = "";
	$ts1loss = "";
	$ts1ber = "";
	$ts2duration = "";
	$ts2loss = "";
	$ts2ber = "";
	$dstarduration = "";
	$dstarloss = "";
	$dstarber = "";
	$ysfduration = "";
    $ysfloss = "";
    $ysfber = "";
	foreach ($logLines as $logLine) {
		$duration = "";
		$loss = "";
		$ber = "";
		//removing invalid lines
		if(strpos($logLine,"BS_Dwn_Act")) {
			continue;
		} else if(strpos($logLine,"invalid access")) {
			continue;
		} else if(strpos($logLine,"received RF header for wrong repeater")) {
			continue;
		}
		
		if(strpos($logLine,"end of") || strpos($logLine,"watchdog has expired") || strpos($logLine,"ended RF data") || strpos($logLine,"ended network")) {
			$lineTokens = explode(", ",$logLine);
			if (array_key_exists(2,$lineTokens)) {
				$duration = strtok($lineTokens[2], " ");
			}
			if (array_key_exists(3,$lineTokens)) {
				$loss = $lineTokens[3];
			}
			
			// if RF-Packet, no LOSS would be reported, so BER is in LOSS position
			if (startsWith($loss,"BER")) {
				$ber = substr($loss, 5);
				$loss = "";
			} else {
				$loss = strtok($loss, " ");
				if (array_key_exists(4,$lineTokens)) {
					$ber = substr($lineTokens[4], 5);
				}
			}
			
			if (strpos($logLine,"ended RF data") || strpos($logLine,"ended network")) {
				switch (substr($logLine, 27, strpos($logLine,",") - 27)) {
					case "DMR Slot 1":
						$ts1duration = "SMS";
						break;
					case "DMR Slot 2":
						$ts2duration = "SMS";
						break;
				}
			} else {
				switch (substr($logLine, 27, strpos($logLine,",") - 27)) {
					case "D-Star":
						$dstarduration = $duration;
						$dstarloss = $loss;
						$dstarber = $ber;
						break;
					case "DMR Slot 1":
						$ts1duration = $duration;
						$ts1loss = $loss;
						$ts1ber = $ber;
						break;
					case "DMR Slot 2":
						$ts2duration = $duration;
						$ts2loss = $loss;
						$ts2ber = $ber;
						break;
					case "YSF":
						$ysfduration = $duration;
						$ysfloss = $loss;
						$ysfber = $ber;
						break;
				}
			}
		}
		
		$timestamp = substr($logLine, 3, 19);
		$mode = substr($logLine, 27, strpos($logLine,",") - 27);
		$callsign2 = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"to") - strpos($logLine,"from") - 6);
		$callsign = $callsign2;
		if (strpos($callsign2,"/") > 0) {
			$callsign = substr($callsign2, 0, strpos($callsign2,"/"));
		}
		$callsign = trim($callsign);
		
		$id ="";
		if ($mode == "D-Star") {
			$id = substr($callsign2, strpos($callsign2,"/") + 1);
		}
		
		$target = substr($logLine, strpos($logLine, "to") + 3); 
		$source = "RF";
		if (strpos($logLine,"network") > 0 ) {
			$source = "Net";
		}
		
		switch ($mode) {
			case "D-Star":
				$duration = $dstarduration;
				$loss = $dstarloss;
				$ber = $dstarber;
				break;
			case "DMR Slot 1":
				$duration = $ts1duration;
				$loss = $ts1loss;
				$ber = $ts1ber;
				break;
			case "DMR Slot 2":
				$duration = $ts2duration;
				$loss = $ts2loss;
				$ber = $ts2ber;
				break;
			case "YSF":
                $duration = $ysfduration;
                $loss = $ysfloss;
                $ber = $ysfber;
                break;
		}
		
		// Callsign or ID should be less than 11 chars long, otherwise it could be errorneous
		if ( strlen($callsign) < 11 ) {
			array_push($heardList, array($timestamp, $mode, $callsign, $id, $target, $source, $duration, $loss, $ber));
			$duration = "";
			$loss ="";
			$ber = "";
		}
	}
	return $heardList;
}

function getLastHeard($logLines) {
	//returns last heard list from log
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	$counter = 0;
	foreach ($heardList as $listElem) {
		if ( ($listElem[1] == "D-Star") || ($listElem[1] == "YSF") || (startsWith($listElem[1], "DMR")) ) {
			if(!(array_search($listElem[2]."#".$listElem[1].$listElem[3], $heardCalls) > -1)) {
				array_push($heardCalls, $listElem[2]."#".$listElem[1].$listElem[3]);
				array_push($lastHeard, $listElem);
				$counter++;
			}
			if ($counter == LHLINES) {
				return $lastHeard;
			}
		}
	}
	return $lastHeard;
}

function getActualMode($metaLastHeard, $mmdvmconfigs) {
	// returns mode of repeater actual working in
	$listElem = $metaLastHeard[0];
	$timestamp = new DateTime($listElem[0]);
	$mode = $listElem[1];
	if (startsWith($mode, "DMR")) {
		$mode = "DMR";
	}
	
	$now =  new DateTime();
	$hangtime = getConfigItem("General", "ModeHang", $mmdvmconfigs);
	$timestamp->add(new DateInterval('PT' . $hangtime . 'S'));

	if ($now->format('U') > $timestamp->format('U')) {
		return "idle";
	} else {
		return $mode;
	}
}

function getDSTARLinks() {
	// returns link-states of all D-Star-modules
	if (filesize(LINKLOGPATH."/Links.log") == 0) {
		return "not linked";
	}
	$out = "<table>";
	if ($linkLog = fopen(LINKLOGPATH."/Links.log",'r')) {
		while ($linkLine = fgets($linkLog)) {
			$linkDate = "&nbsp;";
			$protocol = "&nbsp;";
			$linkType = "&nbsp;";
			$linkSource = "&nbsp;";
			$linkDest = "&nbsp;";
			$linkDir = "&nbsp;";
// Reflector-Link, sample:
// 2011-09-22 02:15:06: DExtra link - Type: Repeater Rptr: DB0LJ	B Refl: XRF023 A Dir: Outgoing
// 2012-04-03 08:40:07: DPlus link - Type: Dongle Rptr: DB0ERK B Refl: REF006 D Dir: Outgoing
// 2012-04-03 08:40:07: DCS link - Type: Repeater Rptr: DB0ERK C Refl: DCS001 C Dir: Outgoing
			if(preg_match_all('/^(.{19}).*(D[A-Za-z]*).*Type: ([A-Za-z]*).*Rptr: (.{8}).*Refl: (.{8}).*Dir: (.{8})/',$linkLine,$linx) > 0){
				$linkDate = $linx[1][0];
				$protocol = $linx[2][0];
				$linkType = $linx[3][0];
				$linkSource = $linx[4][0];
				$linkDest = $linx[5][0];
				$linkDir = $linx[6][0];
			}
// CCS-Link, sample:
// 2013-03-30 23:21:53: CCS link - Rptr: PE1AGO C Remote: PE1KZU	Dir: Incoming
			if(preg_match_all('/^(.{19}).*(CC[A-Za-z]*).*Rptr: (.{8}).*Remote: (.{8}).*Dir: (.{8})/',$linkLine,$linx) > 0){
				$linkDate = $linx[1][0];
				$protocol = $linx[2][0];
				$linkType = $linx[2][0];
				$linkSource = $linx[3][0];
				$linkDest = $linx[4][0];
				$linkDir = $linx[5][0];
			}
// Dongle-Link, sample: 
// 2011-09-24 07:26:59: DPlus link - Type: Dongle User: DC1PIA	Dir: Incoming
// 2012-03-14 21:32:18: DPlus link - Type: Dongle User: DC1PIA Dir: Incoming
			if(preg_match_all('/^(.{19}).*(D[A-Za-z]*).*Type: ([A-Za-z]*).*User: (.{6,8}).*Dir: (.*)$/',$linkLine,$linx) > 0){
				$linkDate = $linx[1][0];
				$protocol = $linx[2][0];
				$linkType = $linx[3][0];
				$linkSource = "&nbsp;";
				$linkDest = $linx[4][0];
				$linkDir = $linx[5][0];
			}
			$out .= "<tr><td>" . $linkSource . "</td><td>&nbsp;" . $protocol . "-link</td><td>&nbsp;to&nbsp;</td><td>" . $linkDest . "</td><td>&nbsp;" . $linkDir . "</td></tr>";
		}
	}
	$out .= "</table>";
	
	fclose($linkLog);
	return $out;
}

function getActualLink($logLines, $mode) {
	// returns actual link state of specific mode
//M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
//M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
//M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	switch ($mode) {
    case "D-Star":
    	if (isProcessRunning(IRCDDBGATEWAY)) {
			return getDSTARLinks();
    	} else {
    		return "ircddbgateway not running!";
    	}
        break;
    case "DMR Slot 1":
        foreach ($logLines as $logLine) {
        	if(substr($logLine, 27, strpos($logLine,",") - 27) == "DMR Slot 1") {
				$to = ""; 
				if (strpos($logLine,"to")) {
					$to = trim(substr($logLine, strpos($logLine,"to") + 3));
				}
				if ($to !== "") {
					return $to;
				}
        	}
		}
		return "not linked";
        break;
    case "DMR Slot 2":
        foreach ($logLines as $logLine) {
        	if(substr($logLine, 27, strpos($logLine,",") - 27) == "DMR Slot 2") {
				$to = ""; 
				if (strpos($logLine,"to")) {
					$to = trim(substr($logLine, strpos($logLine,"to") + 3));
				}
				if ($to !== "") {
					return $to;
				}
        	}
		}
		return "not linked";
        break;
	}
	return "something went wrong!";
}

function getActualReflector($logLines, $mode) {
	// returns actual link state of specific mode
//M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
//M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
//M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	//array_multisort($logLines,SORT_DESC);
	
    foreach ($logLines as $logLine) {
		if(substr($logLine, 27, strpos($logLine,",") - 27) == "DMR Slot 2") {
			$from = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"to") - strpos($logLine,"from") - 6);
			
			if (strlen($from) == 4 && startsWith($from,"4")) {
				if ($from == "4000") {
					return "Reflector not linked";
				} else {
					return "Reflector ".$from;
				}
			} 
		}
	}
	return "Reflector not linked";
}

function getActiveYSFReflectors($logLines) {
// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
// D: 2016-06-11 19:09:31.371 Have reflector status reply from 89164/FUSIONBE2       /FusionBelgium /002
	$reflectors = Array();
	$reflectorlist = Array();
	foreach ($logLines as $logLine) {
		if (strpos($logLine, "Have reflector status reply from")) {
			$timestamp = substr($logLine, 3, 19);
			$timestamp2 = new DateTime($timestamp);
			$now =  new DateTime();
			$timestamp2->add(new DateInterval('PT2H'));
		
			if ($now->format('U') <= $timestamp2->format('U')) {
				$str = substr($logLine, 60);
				$id = strtok($str, "/");
				$name = strtok("/");
				$description = strtok("/");
				$concount = strtok("/");
				if(!(array_search($name, $reflectors) > -1)) {
					array_push($reflectors,$name);
					array_push($reflectorlist, array($name, $description, $id, $concount, $timestamp));
				}
			}
		}
	}
	array_multisort($reflectorlist);
	return $reflectorlist;
}


 function getSize($filesize, $precision = 2)
    {
        $units = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

        foreach ($units as $idUnit => $unit)
        {
            if ($filesize > 1024)
                $filesize /= 1024;
            else
                break;
        }
        
        return round($filesize, $precision).' '.$units[$idUnit].'B';
    }
//Some basic inits
$mmdvmconfigs = getMMDVMConfig();
$logLinesMMDVM = getMMDVMLog();
$reverseLogLinesMMDVM = $logLinesMMDVM;
array_multisort($reverseLogLinesMMDVM,SORT_DESC);
$lastHeard = getLastHeard($reverseLogLinesMMDVM);
$YSFGatewayconfigs = getYSFGatewayConfig();
$logLinesYSFGateway = getYSFGatewayLog();
$reverseLogLinesYSFGateway = $logLinesYSFGateway;
array_multisort($reverseLogLinesYSFGateway,SORT_DESC);
?>
