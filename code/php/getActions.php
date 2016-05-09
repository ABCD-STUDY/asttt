<?php

  // return a list of events

  $dir = "../actions/";
  $files = array_diff(scandir($dir), array('..','.'));
  $actions = array();
  foreach($files as $file) {
    if (is_dir($dir. DIRECTORY_SEPARATOR . $file)) {
       $actions[] = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'info.json'), true);
    }
  }

  echo(json_encode($actions, JSON_PRETTY_PRINT));

?>