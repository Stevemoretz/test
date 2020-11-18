<?php

define('WORKING_DIRECTORY', getcwd());
$options = getopt("o:");
$cityCodes = $options["o"];
$cityCodes = explode(',',$cityCodes);

$GLOBALS['city_codes'] = $cityCodes ? : [
//  '092',
//  '093',
    '094',

    '096',
    '097',
    '098',
];
//php multi.php -u=387075 -c=500 -o=092,093,094,096,097,098,105,106
$options = getopt("c:");
$cityChunkInput = $options["c"];
$options = getopt("s:");
$checkCitiesAtTheSameTimeInput = $options["s"];
$options = getopt("u:");
$userInput = $options["u"];
if(!$userInput) return;
$cityChunk = $cityChunkInput ?: 10;
$checkCitiesAtTheSameTime = $checkCitiesAtTheSameTimeInput ? : 1;



function error_log2($content,$filename=null){
    $date = date('m/d/Y h:i:s a', time());
    file_put_contents(($filename ?: 'progress') . '.txt', '['.$date.'] '.$content . "\n",FILE_APPEND);
}

$index = 0;
foreach ($GLOBALS['city_codes'] as $cityCodePrefix) {
    $cityChunkSize = 1000000 / $cityChunk;
    $arr = [];
    for($i = 0; $i < $cityChunk; $i++){
        $from = $i;
        if($i === 0){
            $from = sprintf('%03d',(string)($cityCodePrefix)).'000000';
        }else{
            $from = (string)$cityCodePrefix .  sprintf('%06d',$i * $cityChunkSize);
        }
        if($i + 1 === $cityChunk){
            $to = sprintf('%03d',(string)($cityCodePrefix + 1)) . '000000';
        }else{
            $to = sprintf('%09d',(string)($from + $cityChunkSize));
        }
        $arr[] = ['from'=>$from,'to'=>$to];
    }
    $GLOBALS['city_ranges'][$index++][$cityCodePrefix] = $arr;
}
$index = 0;
$indexTo = $index + $checkCitiesAtTheSameTime;
$lastCityCode = '---';
while($index < count($GLOBALS['city_ranges'])){
    if($indexTo > count($GLOBALS['city_ranges'])){
        $indexTo = count($GLOBALS['city_ranges']);
    }
    $items = [];
    for($i = $index; $i<$indexTo;$i++){
        $cityCode = array_keys($GLOBALS['city_ranges'][$i])[0];
        foreach ($GLOBALS['city_ranges'][$i][$cityCode] as $item){
            $item['code'] = $cityCode;
            $items[] = $item;
        }
    }

    $j=0;
    $pipe = [];
    foreach ($items as $item){
        $from = $item['from'];
        $to = $item['to'];
        // open ten processes
        $pipe[$j]['process'] = popen("php ./test.php -f=$from -t=$to -u=".$userInput, 'w');
        $pipe[$j]['from'] = $from;
        $pipe[$j++]['to'] = $to;
    }

    for ($k=0; $k<$j; $k++) {
        if((strpos($pipe[$k]['from'],$lastCityCode)) === false){
            $lastCityCode = substr($pipe[$k]['from'],0,3);
            error_log2('last status on : ' . $lastCityCode);
        }
        pclose($pipe[$k]['process']);
        echo 'finished : ' . $pipe[$k]['from'] . ' - ' . $pipe[$k]['to'].PHP_EOL;
    }

    $index += $checkCitiesAtTheSameTime;
    $indexTo += $checkCitiesAtTheSameTime;
//    if($index >= $checkCitiesAtTheSameTime + 1) break;

}
exit(0);