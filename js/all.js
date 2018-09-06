//----------------------------------------
// User accounts
//----------------------------------------
// logout the current user
function logout() {
    jQuery.get('/code/php/logout.php', function(data) {
	if (data == "success") {
            // user is logged out, reload this page
	} else {
            alert('something went terribly wrong during logout: ' + data);
	}
	window.location.href = "/applications/User/login.php";
    });
}

function checkConnectionStatus() {
    jQuery.getJSON('/code/php/heartbeat.php', function() {
	//jQuery('#connection-status').addClass('connection-status-ok');
	jQuery('#connection-status').css('color', "#228B22");
	jQuery('#connection-status').attr('title', 'Connection established last at ' + Date());
    }).error(function() {
	// jQuery('#connection-status').removeClass('connection-status-ok');
	jQuery('#connection-status').css('color', "#CD5C5C");
	jQuery('#connection-status').attr('title', 'Connection failed at ' + Date());
    });
}

// fill in events cards (and get all actions at the same time)
function readEvents() {
    jQuery('#events').children().remove();
    jQuery('#myevents').children().remove();
    
    // lets get the data and hope for the best
    jQuery.when( jQuery.getJSON('code/php/getLinks.php', function(data) {
	    links = data;
    }) ).then( function() {
	    // links should exist now
	    jQuery.getJSON('code/php/getEvents.php', function(data) {
	        events  = data[0];
	        actions = data[1];
	        str  = "";
	        str2 = "";
            numMyTasks = 0;
	        for (var i = 0; i < events.length; i++) {
		        parms = JSON.stringify(events[i].parameter); 
			    
		        for (var j = 0; j < links.length; j++) {
		            var n = links[j].user;
		            var e = links[j].event;
		            if (n !== user_name || e !== events[i].name){
			            continue;
		            }
		            var a = links[j].action;
		            var id = links[j].id;
                    var sites = links[j].sites;
		            for (var k = 0; k < actions.length; k++) {
			            if (actions[k].name == a) {
			                a = actions[k];
			                break;
			            }
		            }
	                var parms2 = JSON.stringify(a.parameter);
                    numMyTasks++;
  		            str2 = str2 + "<div class=\"card mixed\" eventid=\""+id+"\">"
                        + "<div class='title'><button type=\"button\" class=\"close delete-active-task\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>" + id + "</div>"
			            + "<button class=\"close\" data-toggle=\"modal\" data-target=\"#edit-props\" type=\"event\" eventid=\""+id+"\" event=\""+ events[i].name +"\" parms='" + parms + "'><i class=\"glyphicon glyphicon-cog\" aria-hidden=\"true\"></i></button>"
			            + "<div class=\"event-name\"> Trigger:<br><b>" + events[i].name + "</b></div>"

			            + "<button class=\"close\" data-toggle=\"modal\" data-target=\"#edit-props\" type=\"action\" eventid=\""+id+"\" event=\""+ events[i].name +"\" parms='" + parms2 + "'><i class=\"glyphicon glyphicon-cog\" aria-hidden=\"true\"></i></button>"			
			            + "<div class=\"action-name\"> Action:<br><b>" + a.name + "</b></div>"
                        + "<div class=\"site-list\"><i>Sites: " + sites.join(", ") + "</i></div>"
			            + "</div>";
		        }
  		        str = str + "<div class=\"card2 event\" parms=\"" + parms + "\">"
		            + "<div class=\"pull-right\"><button class=\"close\" type=\"event\" name=\""+ events[i].name+"\" data-toggle=\"modal\" data-target=\"#define-link\"><i class=\"glyphicons glyphicon-plus\" style=\"font-size: 32pt;\"></i></button></div>"
                    + "<div class='trigger'>Trigger:<br><b>" + events[i].name + "</b></div>"
                    + "<div class='action-description'>" + events[i].description + "</div>"
		            + "</div>";
	        }
	        jQuery('#events').append(str);
	        jQuery('#myevents').append(str2);
            jQuery('.num-my-tasks').text(numMyTasks);
	        
	        str  = "";
	        for (var i = 0; i < actions.length; i++) {
		        parms = JSON.stringify(actions[i].parameter); // Object.keys(actions[i].parameter).map(function(value, index) { return value + ":" + actions[i].parameter[value]; });
		        str = str + "<div class=\"card2 action\" parms='" + parms + "'>"
		        //+ "<button class=\"btn pull-right\" data-toggle=\"modal\" data-target=\"#edit-props\" event=\""+ data[i].name +"\"><span class=\"glyphicon glyphicon-cog\" aria-hidden=\"true\"> </span></button>"
		            + "<div class=\"pull-right\"><button class=\"close\" type=\"action\" name=\""+actions[i].name+"\" data-toggle=\"modal\" data-target=\"#define-link\"><i class=\"glyphicons glyphicon-plus\" style=\"font-size: 32pt;\"></i></button></div>"
                    + "<div class='action'>Action:<br><b>" + actions[i].name + "</b>"
                    + "<div class='action-description'>" + actions[i].description + "</div>"
                    + "</div>"
		            + "</div>";
	        }
	        jQuery('#events').append(str);
	    });
    });
}


			       
var events = [];
var actions = [];
var links = [];

jQuery(document).ready(function() {


    jQuery('#myevents').on('click', '.delete-active-task', function() {
        // delete this event and re-draw the page again
        jQuery.getJSON('code/php/getLinks.php?action=delete&id=' + jQuery(this).parent().parent().attr('eventid'), function(data) {
            //alert("got back: " + JSON.stringify(data));
            readEvents();
        });
    });
    
    jQuery('#user_name').text(user_name);
    readEvents();
    
    jQuery('#save-params').click(function() {
	    // what are the values that we need to save for this event?
	    var evName = jQuery('#param-options').attr('event');
	    var type = jQuery('#param-options').attr('type');
	    var id = jQuery('#param-options').attr('eventid'); // tells us what link this is
	    var arStr = [];
	    var inputs = jQuery('#param-options').find('input');
	    for (var i = 0; i < inputs.length; i++) {
	        var item = inputs[i];
            arStr.push( jQuery(item).attr('placeholder') + ":" + jQuery(item).val());
	    }
        var inputs2 = jQuery('#param-options').find('select option:selected');
	    for (var i = 0; i < inputs2.length; i++) {
	        var item = inputs2[i];
            arStr.push( jQuery(item).attr('placeholder') + ":" + jQuery(item).val());
	    }
	    arStr = arStr.join(",");
        
	    jQuery.getJSON("code/php/getLinks.php?action=save&id=" + id + "&type=" + type + "&value=" + arStr, function(data) {
	        console.log("saved values");
	    });
    });
    
    jQuery('#define-link').on('show.bs.modal', function(event) {
        var button        = jQuery(event.relatedTarget);
	    // fill in all events and all actions
        jQuery('#list-of-events').children().remove();
	    for (var i = 0; i < events.length; i++) {
	        jQuery('#list-of-events').append('<option value="'+events[i].name+'">' + events[i].name + '</option>');
	    }
        jQuery('#list-of-actions').children().remove();
	    for (var i = 0; i < actions.length; i++) {
	        jQuery('#list-of-actions').append('<option value="'+actions[i].name+'">' + actions[i].name + '</option>');
	    }
	    var type = jQuery(button).attr('type');
	    if (type == "event") {
	        jQuery('#list-of-events').val(jQuery(button).attr('name')); 
	    } else {
	        jQuery('#list-of-actions').val(jQuery(button).attr('name')); 
	    }
    });
    
    jQuery('#save-link').click(function() {
	    var event = jQuery('#list-of-events').val();
	    var action = jQuery('#list-of-actions').val();
        
	    // now save the two
	    jQuery.getJSON('code/php/getLinks.php?action=new&event='+event+'&eventaction='+action, function() {
            readEvents();
	    });
    });
    
    jQuery('#edit-props').on('show.bs.modal', function(event) {
        var button        = jQuery(event.relatedTarget);
	    var parameters    = JSON.parse(button.attr('parms'));
	    var eventOrAction = button.attr('type');
	    var evName        = button.attr('event');
	    var id            = button.attr('eventid'); // in the users list what event are we talking about
	    jQuery('#param-title').html(evName + " <span>[" + button.attr('type') + "]</span>");
	    
	    // we should get a list of parameters from the event first, afterwards we fill in the defaults
	    var eve = {};
	    if (eventOrAction == 'event') {
	        for (var i = 0; i < events.length; i++) {
		        if (events[i].name == evName) {
		            var keys = Object.keys(events[i].parameter);
		            for ( var j = 0; j < keys.length; j++)
			            eve[keys[j]] = "";
		            break;
		        }
	        }
	    } else {
	        for (var i = 0; i < actions.length; i++) {
		        if (actions[i].name == evName) {
		            var keys = Object.keys(actions[i].parameter);
		            for ( var j = 0; j < keys.length; j++)
			            eve[keys[j]] = "";
		            break;
		        }
	        }
	    }
	    
	    // get the button params and fill fields with values (or defaults)
        var parArray = Object.keys(parameters); // .split(",");
	    jQuery('#param-options').children().remove();
	    for (var i = 0; i < parArray.length; i++) {
	        var a = [];
            a[0] = parArray[i];
            a[1] = parameters[parArray[i]];
	        
	        // do we have a value for this option?
	        var event = true;
	        if (eventOrAction == "action") {
		        event = false;
	        }
	        // try to find this event/action to check for default values for the current user
	        var parForThisEv = [ eve ];
	        for (var j = 0; j < links.length; j++) {
		        if (links[j].id == id) {
		            if (event) {
  			            for ( var k = 0; k < links[j].event_param.length; k++) { // always one element (object in array of length 1)
			                var keys = Object.keys(links[j].event_param[0]);
			                for (var l = 0; l < keys.length; l++) {
  				                parForThisEv[0][keys[l]] = links[j].event_param[0][keys[l]];
			                }
			            }
			            break;
		            } else {
  			            for ( var k = 0; k < links[j].action_param.length; k++) { // always one element (object in array of length 1)
			                var keys = Object.keys(links[j].action_param[0]);
			                for (var l = 0; l < keys.length; l++) {
  				                parForThisEv[0][keys[l]] = links[j].action_param[0][keys[l]];
			                }
			            }
			            break;
		            }
		        }
	        }
	        var valForPar = "";
	        for( var j = 0; j < parForThisEv.length; j++) {
		        if (Object.keys(parForThisEv[j]).indexOf(a[0]) > -1) {
                    valForPar = parForThisEv[j][a[0]];
		        }
	        }
	        
	        jQuery('#param-options').attr('event', button.attr('event'));
	        jQuery('#param-options').attr('type', button.attr('type'));
	        jQuery('#param-options').attr('eventid', button.attr('eventid'));
            if (a[1].constructor === Array) {
                var select = jQuery('<select class="form-control"></select>');
                for (var j = 0; j < a[1].length; j++) {
                    sel = "";
                    if (valForPar == ""+j)
                        sel = "selected";
                    jQuery(select).append("<option value='" + j + "' placeholder='" + a[0] + "'" + sel + ">" + a[1][j] + "</option>");
                }
                var div = jQuery("<div class=\"form-group\"></div>");
                jQuery(div).append(select);
	            jQuery('#param-options').append(div);
                // now set the value for the option
                
                
                // "<div class=\"form-group\"><select class=\"form-control\" type=\""+ a[1] +"\" placeholder=\"" + a[0] + "\" value=\""+valForPar+"\"></select></div>");
            } else {
	            jQuery('#param-options').append("<div class=\"form-group\"><input class=\"form-control\" type=\""+ a[1] +"\" placeholder=\"" + a[0] + "\" value=\""+valForPar+"\"></div>");
            }
	    }
    });
});
