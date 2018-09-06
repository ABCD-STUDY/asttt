#!/usr/bin/env php
<?php
// arguments are in $argv
if (count($argv) < 2) {
    exit(-1); // don\'t run anything
}

// get the arguments for this action
$data = json_decode($argv[1], true);
// arguments are in the array $data['action_params']
$sites = $data['sites'];
$user  = $data['user'];
$type  = "2";
if (isset($data['action_params']['Type'])) {
  $type = $data['action_params']['Type'];
}
// now use the arguments in $data, if there are any
// do what you have to do here
$data = json_decode(file_get_contents("/var/www/html/applications/progress-report/cache/schedule_status_days.json"), true);
$output_overdue = array();
$output_due_now = array();

foreach($sites as $s){
    $d = $data[$s];
    foreach($d["data"] as $p => $events){
	foreach($events as $e => $estatus){
	    if($estatus[0] == "0,right" and ($type == "0" or $type == "2") ){
		$output_overdue[] = array( $p, $estatus[1], $s, $e);
	    }
	    if($estatus[0] == "0,in" and ($type == "1" or $type == "2") ){
		$output_due_now[] = array( $p, $estatus[1], $s, $e);
	    }
	}
    }
}


function cmp($a, $b){
    return ($a[1] < $b[1]) ? -1 : 1;
}

usort($output_overdue, "cmp");
usort($output_due_now, "cmp");


$temp = array();
foreach($output_overdue as $row){
    $p = $row[0];
    $s = $row[2];
    $stat = $row[1];
    $e = $row[3];
    $temp[] ="<tr><td>".$p."</td><td>".$e."</td><td>".$stat."</td><td>".$s."</td><td>Overdue</td><td> "."<a href = 'https://abcd-rc.ucsd.edu/redcap/redcap_v8.7.0/DataEntry/index.php?pid=12&id=".$p."&event_id=40&page=scheduled'> Link to REDCap</a></td></tr>";
}
$output_overdue = $temp;

$temp = array();
foreach($output_due_now as $row){
    $p = $row[0];
    $s = $row[2];
    $stat = $row[1];
    $e = $row[3];
    $temp[] = "<tr><td>".$p."</td><td>".$e."</td><td>".$stat."</td><td>".$s."</td><td>Due Now</td><td> "."<a href = 'https://abcd-rc.ucsd.edu/redcap/redcap_v8.7.0/DataEntry/index.php?pid=12&id=".$p."&event_id=40&page=scheduled'> Link to REDCap</a></td></tr>";
}
$output_due_now = $temp;



echo("<p>This is an automated message. You can change/add your message subscriptions at <a href=\"https://abcd-report.ucsd.edu/applications/asttt/index.php\">abcd-report</a>.</p>");

// put stuff here
if($type == "0" or $type == "2" and count($output_overdue) > 0){
  echo("<table cellpadding=\"2\" cellspacing=\"3\">");
  echo("<thead><tr> <th>pGuid</th> <th>Visit</th>  <th>Days unitl Never Done</th> <th>Site</th> <th>status</th> <th>Link</th> </tr></thead><tbody>");
  foreach($output_overdue as $row){
    echo($row);
  }
  echo("</tbody></table>");
}

if($type == "1" or $type == "2" and count($output_due_now) > 0){
  echo("<table cellpadding=\"2\" cellspacing=\"3\" >");
  echo("<thead><tr> <th>pGuid</th> <th>Visit</th> <th>Days unitl Overdue</th> <th>Site</th> <th>status</th> <th>Link</th> </tr></thead><tbody>");
  foreach($output_due_now as $row){
    echo($row);
  }
  echo("</tbody></table>");
}
echo("<hr><i>A service provided by the Data Analysis and Informatics Group of ABCD.</i>");
?>
