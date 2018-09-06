<?php

// return a list of links between events and actions
session_start();

include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
$user_name = check_logged(); /// function checks if visitor is logged in  


$permissions = list_permissions_for_user( $user_name );
$sites = array();
foreach ($permissions as $per) {
    $a = explode("Site", $per); // permissions should be structured as "Site<site name>"
    if (count($a) > 1) {
        array_push($sites,$a[1]);
    }
}

$action = "";
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if ( $action == "new") {
    $event = "";
    if (isset($_GET['event'])) {
        $event = $_GET['event'];
    } else {
        echo("{ \"message\": \"Error: no event found\" }");
    }
    $eventaction = "";
    if (isset($_GET['eventaction'])) {
        $eventaction = $_GET['eventaction'];
    } else {
        echo("{ \"message\": \"Error: no action found\" }");
    }
    $data = json_decode(file_get_contents('../links.json'), true);
    
    $data[] = array( 'id' => uniqid(), "user" => $user_name, "event" => $event, "action" => $eventaction, "event_param" => array(), "action_param" => array(), "sites" => $sites );
    
    // save the data again
    if (!is_writable('../links.json')) {
        echo ("{ \"message\": \"Error: file is not writable\" }");
        return;
    } else {
        file_put_contents('../links.json', json_encode($data, JSON_PRETTY_PRINT));
    }
    echo ("{ \"message\": \"Ok, change has been saved... \" }");            
} elseif ( $action == "save" ) {
    $id = "";
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        echo("{ \"message\": \"Error: no id found\" }");
    }
    $type = "";
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    } else {
        echo("{ \"message\": \"Error: no type found\" }");
    }
    $value = "";
    if (isset($_GET['value'])) {
        $value = $_GET['value'];
        $val = explode(",", $value);
        $value = [];
        foreach($val as $v) {
            $vv = explode(":", $v);
	        $value[$vv[0]] = $vv[1];
        }
    } else {
        echo("{ \"message\": \"Error: nothing to save found\" }");
    }
    $data = json_decode(file_get_contents('../links.json'), true);
    
    foreach($data as &$d) {
        if ($d['id'] == $id) {
            if ($type == 'event') {
	            $d['event_param'] = array($value);
	        }
	        if ($type == 'action') {
	            $d['action_param'] = array($value);
	        }	  
        }
    }
    
    // save the data again
    if (!is_writable('../links.json')) {
        echo ("{ \"message\": \"Error: file is not writable\" }");
    } else {
        file_put_contents('../links.json', json_encode($data, JSON_PRETTY_PRINT));
    }
} elseif ( $action == "delete" ) {
    $id = "";
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        echo("{ \"message\": \"Error: no id found\" }");
        return;
    }
    
    $data = json_decode(file_get_contents('../links.json'), true);
    $found = false;
    foreach($data as $key => $dat) {
        if ($dat['id'] == $id) {
            $found = true;
            unset($data[$key]);
        }
    }
    // remove all the keys introduced by the delete above
    $data = array_values($data);
    
    if (!is_writable('../links.json')) {
        echo ("{ \"message\": \"Error: file is not writable\" }");
    } else {
        file_put_contents('../links.json', json_encode($data, JSON_PRETTY_PRINT));
        echo ("{ \"message\": \"Ok, change has been saved... \" }");        
    }
} else {
    echo(json_encode( json_decode(file_get_contents('../links.json'), true), JSON_PRETTY_PRINT));
}
?>
