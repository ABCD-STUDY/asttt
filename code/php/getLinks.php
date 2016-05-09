<?php

  // return a list of links between events and actions
  session_start();

  include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
  $user_name = check_logged(); /// function checks if visitor is logged in  

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

    $data[] = array( 'id' => uniqid(), "user" => $user_name, "event" => $event, "action" => $eventaction, "event_param" => array(), "action_param" => array() );

    // save the data again
    if (!is_writable('../links.json')) {
      echo (" \"message\": \"Error: file is not writable\" }");
    } else {
      file_put_contents('../links.json', json_encode($data, JSON_PRETTY_PRINT));
    }   
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
      echo (" \"message\": \"Error: file is not writable\" }");
    } else {
      file_put_contents('../links.json', json_encode($data, JSON_PRETTY_PRINT));
    }
  } else {
    echo(json_encode( json_decode(file_get_contents('../links.json'), true), JSON_PRETTY_PRINT));
  }
?>