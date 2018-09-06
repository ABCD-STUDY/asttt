#!/usr/bin/env php
<?php
// arguments are in $argv
if (count($argv) < 2) {
    exit(-1); // don\'t run anything
}

$data = json_decode($argv[1], true);

$default = "0"; // 5am, "1" is 6am, ... 
$timelist = array(
   0  => "05:00",
   1  => "06:00",
   2  => "07:00",
   3  => "08:00",
   4  => "09:00",
   5  => "10:00",
   6  => "11:00",
   7  => "12:00",
   8  => "13:00",
   9  => "14:00",
   10 => "15:00",
   11 => "16:00",
   12 => "17:00",
   13 => "18:00",
   14 => "19:00",
   15 => "20:00",
   16 => "21:00"
);

if (count($data['event_param']) > 0) {
   if (isset($data['event_param'][0]['TimePoint'])) {
      $default = $data['event_param'][0]['TimePoint'];
   }
}
$now = new DateTime();
$hourofday = $now->format('H').":00";
$perform = false;
if ($hourofday == "".$timelist[$default]) {
    $perform = true;
}
echo("time of day: ".$hourofday. " " . $timelist[$default]);
$user=$data['user'];

# what is the key for this event?
$key=$user."_".date('Y-M-d-H').":00_".$default."_".$data['id'];
# if this key is new run the action, if we had this already don't run
$log=array();
if (file_exists("daily.log")) {
    $log=file_get_contents("daily.log");
    $log=explode("\n",$log);
}
$found = false;
foreach($log as $l) {
    if ($l == $key) {
        $found = true;
        break;
    }
}
if (!$found && $perform) {
    // signal that the action should be taken
    file_put_contents('daily.log', $key.PHP_EOL , FILE_APPEND | LOCK_EX);
    exit(1); // ok
} else {
    exit(-1); // not ok
}
?>
