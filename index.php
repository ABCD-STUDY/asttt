<?php
  session_start();

  include($_SERVER["DOCUMENT_ROOT"]."/code/php/AC.php");
  $user_name = check_logged(); /// function checks if visitor is logged in
  $admin = false;
  $email = getEmailFromUserName($user_name);

  if ($user_name == "") {
    // user is not logged in
    return;
  } else {
    $admin = true;
    echo('<script type="text/javascript"> user_name = "'.$user_name.'"; </script>'."\n");
    echo('<script type="text/javascript"> admin = '.($admin?"true":"false").'; </script>'."\n");
    echo('<script type="text/javascript"> email = "'.$email.'"; </script>'."\n");
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
        <li><a href="/index.php" title="Back to report page">Report</a></li>
        <li type="button" data-toggle="modal" data-target="#list-apprises"><a>Overview</a></li>
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
	        <h1>ABCD apprise - sign up for emails</h1>
	        <div class='date2'>September 2018</div>
	      </div>
	      <p style="margin-bottom: 20px;">
To create a new task, click the "+" in the upper-right corner of the available actions and triggers. Select the action you would like to receive a report for and the frequency for which you like to receive this report. You can configure the settings to select the time of day, day of the week, and time of the month. The report will be forwarded to your email address (<span class="email"></span>). To stop the emails, remove the task from the "Active task list" by clicking "x" in the upper-right corner.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
	      <p>Active task list (<span class="num-my-tasks">0</span>)</p>
          <div id="myevents"></div>
	    </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-md-12">
	      <p>To create a new task select from this list of available actions and triggers.</p>
          <div id="events"></div>
	    </div>
      </div>
      <div class="row">&nbsp;</div>
    </div>
  </section>

  <section>
    <div class="container" style="margin-bottom: 20px;">
      <hr>
      <i>A service provided by the Data Analysis and Informatics Group of ABCD.</i>
    </div>
  </section>

<div class="modal fade" tabindex="-1" role="dialog" id="list-apprises">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title">What other people are using</h4>
      </div>
      <div class="modal-body">
        <table class="table table-striped table-sm">
          <thead>
            <tr><th>Site</th><th>User</th><th>Trigger</th><th>Action</th></tr>
          </thead>
          <tbody id="apprise-table"></tbody>
        </table>
      </div>
      <div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


  
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
