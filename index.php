<?php
  session_start();

  include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
  $user_name = check_logged(); /// function checks if visitor is logged in
  $admin = false;

  if ($user_name == "") {
    // user is not logged in
    return;
  } else {
    $admin = true;
    echo('<script type="text/javascript"> user_name = "'.$user_name.'"; </script>'."\n");
    echo('<script type="text/javascript"> admin = '.($admin?"true":"false").'; </script>'."\n");
  }
  
  $permissions = list_permissions_for_user( $user_name );

  // find the first permission that corresponds to a site
  // Assumption here is that a user can only add assessment for the first site he has permissions for!
  $site = "";
  foreach ($permissions as $per) {
      $a = explode("Site", $per); // permissions should be structured as "Site<site name>"
      if (count($a) > 1) {
          $site = $a[1];
	      break;
      }
  }
  if ($site == "") {
     echo (json_encode ( array( "message" => "Error: no site assigned to this user" ) ) );
     return;
  }

  echo('<script type="text/javascript"> site = "'.$site.'"; </script>'."\n");
?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>ABCD apprise</title>

  <!-- Bootstrap Core CSS -->
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

  <!-- Custom CSS -->
  <!-- required for the date and time pickers -->
  <link href="css/bootstrap-datetimepicker.css" rel="stylesheet" type="text/css">

  <!-- <link rel='stylesheet' href='//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.min.css' /> -->
  <!-- media="print" is required to display the fullcalendar header buttons -->
  <!-- <link rel='stylesheet' media='print' href='//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.print.css' /> -->

  <link rel="stylesheet" href="css/style.css">

</head>

<body>

  <nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">ABCD apprise</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="active"><a href="/index.php" title="Back to report page">Report</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="#" class="connection-status" id="connection-status">Connection Status</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span id="session-active">User</span> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="#" id="user_name"></a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#" onclick="closeSession();">Close Session</a></li>
            <li><a href="#" onclick="logout();">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

  <!-- start session button -->
  <section id="admin-top" class="bg-light-gray">
    <div class="container">
      <div class="row" style="margin-bottom: 20px;"></div>
      <div class="row start-page">
        <div class="col-md-12">
          <div class="date">Adolescent Brain Cognitive Development</div>
	      <div style='position: relative;'>
	        <h1>ABCD apprise</h1>
	        <div class='date2'>September 2018</div>
	      </div>
	      <p style="margin-bottom: 20px;">Specify a trigger and link it with an action. The action creates a report once the trigger activates. That report will be forwarded to your email address. If you want to receive the scheduling list with overdue and due now participants at the first day of each week select the "Scheduling List" action and the trigger "Weekly Event". Configure the trigger to select the day of the week. Emails are generated at mid-night. Remove the task to stop the emails.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
	      <p>Active tasks (<span class="num-my-tasks">0</span>)</p>
          <div id="myevents"></div>
	    </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-md-12">
	      <p>Create a new task (list of available actions and triggers)</p>
          <div id="events"></div>
	    </div>
      </div>
      <div class="row">&nbsp;</div>
    </div>
  </section>

  <section>
    <div class="container">
      <hr>
      <i>A service provided by the Data Analysis and Informatics Group of ABCD.</i>
    </div>
  </section>
  
<div class="modal fade" tabindex="-1" role="dialog" id="edit-props">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title">Edit Event Properties</h4>
      </div>
      <div class="modal-body">
         <H3 id="param-title"></H3>
         <div id="param-options"></div>
      </div>
      <div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	<button type="button" id="save-params" class="btn btn-primary" data-dismiss="modal">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="define-link">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title">Link Event to Action</h4>
      </div>
      <div class="modal-body">
         <div class="form-group">
	    <label for="#list-of-events">Select the trigger</label>
	    <select class="form-control" id="list-of-events"></select>
	 </div>
         <div class="form-group">
	    <label for="#list-of-actions">Select the action</label>
	    <select class="form-control" id="list-of-actions"></select>   
	 </div>
      </div>
      <div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	<button type="button" id="save-link" class="btn btn-primary" data-dismiss="modal">Save</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



  <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
  <script src='js/moment.min.js'></script>

  <!-- Bootstrap Core JavaScript -->
  <script src="js/bootstrap.min.js"></script>

  <script src="js/bootstrap-datetimepicker.js"></script>
  <script src="js/bootstrap-datepicker.js"></script>

  <script type="text/javascript" src="js/all.js"></script>

</body>

</html>
