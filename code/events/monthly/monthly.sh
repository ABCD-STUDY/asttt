#!/usr/bin/env php
<?php
// arguments are in $argv
if (count($argv) < 2) {
    exit(-1); // don\'t run anything
}

$data = json_decode($argv[1], true);

$default = "0"; // first of the month, "1" is mid of the month, "2" is end of the month

if (count($data['event_param']) > 0) {
   if (isset($data['event_param'][0]['TimePoint'])) {
      $default = $data['event_param'][0]['TimePoint'];
   }
}
$day = "1";
if ($default == "0") {
    $day = "1";
} elseif ($default == "1") {
    $day = "15";
} else {
    $dat = new DateTime('last day of this month');
    $day = $dat->format('d');
}
$now = new DateTime();
$daytoday = $now->format('j');
$perform = false;
if ($daytoday == $day) {
    $perform = true;
}
#echo("days: ".$daytoday. " " . $day);
$user=$data['user'];

echo("dayofmonth: ".$daytoday. " " . $default);
# what is the key for this event?
$key=$user."_".date('Y-M')."_".$default."_".$data['id'];
# if this key is new run the action, if we had this already don't run
$log=array();
if (file_exists("monthly.log")) {
    $log=file_get_contents("monthly.log");
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
    file_put_contents('monthly.log', $key.PHP_EOL , FILE_APPEND | LOCK_EX);
    exit(1); // ok
} else {
    exit(-1); // not ok
}
?>
