#!/usr/bin/env php
<?php

if (count($argv) != 4) {
    echo("Usage: <user name> <originator> - (and send email text on stdin), got only ".count($argv)." arguments \n");
    exit(-1);
}

// get the email for this user
$pw = json_decode(file_get_contents("/var/www/html/code/php/passwords.json"),true);
$email = "";
foreach($pw['users'] as $user) {
    # echo("check if \"".$user['name']."\" is equal to \"".$argv[1]."\" \n");
    if ($user['name'] == $argv[1]) {
        $email = $user['email'];
        break;
    }
}
if ($email == "") {
    echo("Error: no email found for user: ".$argv[1]."\n");
    exit(-1);
}
$stdin = fopen('php://stdin', 'r');
$text = "";
while(!feof($stdin)) {
    $text = $text.fread($stdin, 1e7); // read at most 10mb
}
fclose($stdin);

$from = "abcd-report@ucsd.edu";
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

// Create email headers
$headers .= 'From: '.$from."\r\n".
        'Reply-To: '.$from."\r\n" .
        'X-Mailer: PHP/' . phpversion();

if (mail($email, "Ask and receive DAIC service \"".$argv[2]."\"", $text, $headers)) {
    echo("Mail has been sent to ".$email."\n");
} else {
    echo("Error: mail could not be sent ".$email."\n");
}

?>
