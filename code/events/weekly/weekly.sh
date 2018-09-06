#!/usr/bin/env php
<?php
// arguments are in $argv
if (count($argv) < 2) {
    exit(-1); // don\'t run anything
}

$data = json_decode($argv[1], true);

$default = "0"; // Sunday, "1" is Monday, ... 
$daylist = array( "0" => "Sunday", "1" => "Monday", "2" => "Tuesday", "3" => "Wednesday", "4" => "Thursday", "5" => "Friday", "6" => "Saturday" );

if (count($data['event_param']) > 0) {
   if (isset($data['event_param'][0]['TimePoint'])) {
      $default = $data['event_param'][0]['TimePoint'];
   }
}
$now = new DateTime();
$dayofweek = $now->format('l');
$perform = false;
if ($dayofweek == $daylist[$default]) {
    $perform = true;
}
echo("dayofweek: ".$dayofweek. " " . $default);
$user=$data['user'];

# what is the key for this event?
$key=$user."_".date('Y-M-d')."_".$default."_".$data['id'];
# if this key is new run the action, if we had this already don't run
$log=array();
if (file_exists("weekly.log")) {
    $log=file_get_contents("weekly.log");
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
    file_put_contents('weekly.log', $key.PHP_EOL , FILE_APPEND | LOCK_EX);
    exit(1); // ok
} else {
    exit(-1); // not ok
}
?>
