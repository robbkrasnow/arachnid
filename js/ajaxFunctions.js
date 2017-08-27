/**
 * Everything related to ajax calls resides in this script. All ajax calls
 * must go through the ajaxCall method.
 *
 * @author Robb Krasnow
 * @version 2.0
 */


/*********************
 *****  HELPERS  *****
 *********************/


/**
 * Ajax utility used to make all ajax calls to server.
 *
 * @param getPost	The type of method (GET or POST) used to make the ajax call
 * @param d			The data being sent to the server {name:value, name2:val2}
 * @param callback	The callback method used if the call was successful
 */
function ajaxCall(getPost, d, callback) {
	$.ajax({
		type: getPost,
		url: 'mid.php',
		data: d,
		dataType: 'json',
		async: true,
		cache: false,
		success: callback,
		error: err
	});
}


/**
 * Used as a utility to show any error messages on the console if the server
 * doesn't return the proper data in JSON format from the AJAX calls.
 *
 * @param jqXHR			The jQuery XMLHttpRequest
 * @param textStatus	The status of the error from the AJAX call
 * @param err			The error coming back from the server
 */
function err(jqXHR, textStatus, e) {
	console.log('jqXHR:\n', jqXHR);
	console.log('textStatus:\n', textStatus);
	console.log('responseText:\n', jqXHR.responseText);
	console.log('err:\n', e);
}


/**********************************
 *****  LOGIN AJAX FUNCTIONS  *****
 **********************************/


/**
 * Used to send login data to server checking login credentials.
 * 
 * Callback method: callbackLogin
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (username|password)
 */
function initLoginAjax(whatMethod, val) {
	ajaxCall("POST", { method:whatMethod, a:"login", data:val }, callbackLogin);
}


/**
 * Callback method from initLoginAjax telling app what to do if it receives
 * a proper JSON object back from the server. If false login, display error,
 * else display success message and log the user in.
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackLogin(jsonObj) {
	var success,
		invalidLoginBox,
		successLoginBox;

	// Set valid variables
	success = jsonObj[0].success;
	invalidLoginBox = $('.invalid-login-box');
	successLoginBox = $('.success-login-box');
	
	// Check if the request was a success or failure.
	// If failure, set focus to the username input field again and show error
	// If success, hide any previous errors and welcome back user
	if(success === "false") {
		$("#username-field").focus();
		invalidLoginBox.text(jsonObj[1].error).show();
	}
	else if(success === "true") {
		invalidLoginBox.hide();
		successLoginBox.text("Welcome back " + jsonObj[1].username + "!").show();

		// Set quick redirect to the user's dashboard
		setTimeout(function() {
			window.location.href = 'dashboard.php';
		}, 1000);
	}
}


/***********************************
 *****  LOGOUT AJAX FUNCTIONS  *****
 ***********************************/


/**
 * Used to send data to service layer for logging user out.
 * 
 * Callback method: callbackLogout
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (userId)
 */
function initLogoutAjax(whatMethod, val) {
	ajaxCall("POST", { method:whatMethod, a:"logout", data:val }, callbackLogout);
}


/**
 * Callback method from initLogoutAjax telling app what to do if it receives
 * a proper JSON object back from the server. If the logout was successful,
 * set a timer to be displayed for redirection to login page.
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackLogout(jsonObj) {
	var success = jsonObj[0].success;
	
	if(success == "true") {
		// Set a timer for redirect
		timer(
			5000, // 5 seconds
			function(timeleft) { // Called every step to update the visible countdown
				$("#timer").text(timeleft);
			},
			function() { // Redirect to login after 5 seconds
				window.location = 'login.php';
			}
		);
	}
}


/*************************************
 *****  REGISTER AJAX FUNCTIONS  *****
 *************************************/


/**
 * Used to send data to service layer for checking to register a new user.
 * 
 * Callback method: callbackRegister
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (newUsername|newPassword)
 */
function initRegisterAjax(whatMethod, val) {
	ajaxCall("POST", { method:whatMethod, a:"register", data:val }, callbackRegister);
}


/**
 * Callback method from initRegisterAjax telling app what to do if it receives
 * a proper JSON object back from the server. If the registration was unsuccessful,
 * meaning, there was a duplicate on the server, report back to user they must
 * change their registration credentials. If it was successful, grab the input from
 * the form, and register them in the system.
 *
 * @param jsonObj The JSON object coming back from the server
 */
function callbackRegister(jsonObj) {
	// Grab all alert divs and the success (true/false) from the object returned
	var success,
		successString,
		errorString,
		duplicateString;

	success = jsonObj[0].success;
	successString = $('.success-box');
	errorString	= $('.error-box');
	
	// If the registration failed, grab the error message
	if(success === "false") {
		var error = jsonObj[1].error;

		// If the error message starts with "Duplicate entry", that user already exists
		// in the DB. Warn the user and have them create a new one
		if(/^Duplicate entry/.test(error)) {
			errorString.alert().html('<i class="icon-fix fa fa-exclamation-triangle"></i>Username already exists. Please choose a different one.').show();
			$(".register-modal-form")[0].reset();
			$("#new-username-field").focus();
		}
	}
	else {
		// Otherwise, registration was successful. Show the user they are being logged in
		// and actually log them in.
		successString.alert().text("Success! Logging in...").show();
		
		var obj = $('.register-modal-form').serializeObject();
		initLoginAjax('start_login', obj['new-username'] + '|' + obj['new-password']);
	}
}


/**************************************
 *****  DASHBOARD AJAX FUNCTIONS  *****
 **************************************/


/**
 * Used to send data to service layer for pulling a user's mailboxes
 * from the database.
 * 
 * Callback method: callbackDashboard
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (userId)
 */
function initDashboardAjax(whatMethod, val) {
	ajaxCall("POST", { method: whatMethod, a:"dashboard", data:val }, callbackDashboard);
}


/**
 * Callback method to display a nice table with all the user's mailboxes.
 * Each row in the table displays the mailbox's ID, Filename, Message Count,
 * File size, file type, date added, and last accessed.
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackDashboard(jsonObj) {
	var mailboxes,
		dashboardDiv,
		userMailboxes;

	mailboxes = [];
	dashboardDiv = $('.dashboard');
	userMailboxes = '';

	// Check if the return from the server has mailboxes in its JSON
	if(jsonObj[1].mailboxes) {
		mailboxes = jsonObj[1].mailboxes;
	}

	if(mailboxes.length === 1) {
		userMailboxes += '<h1>You have 1 mailbox</h1>';
	}
	else {
		userMailboxes += '<h1>You have ' + mailboxes.length + ' mailboxes</h1>';
	}

	// Start table creation
	userMailboxes +=
		'<h5>Select a mailbox to view its visualization</h5>' +
		'<table class="dashboard-table table table-striped table-hover">' +
			'<thead>' +
				'<tr class="info">' +
					'<th>ID</th>' +
					'<th>Filename</th>' +
					'<th>Message Count</th>' +
					'<th>Size (bytes)</th>' +
					'<th>Type</th>' +
					'<th>Date Added (CDT)</th>' +
					'<th>Last Accessed (CDT)</th>' +
				'</tr>' +
			'</thead>' +
			'<tbody>';

	// If there was a failure, display the error
	if(jsonObj[0].success === 'false') {
		userMailboxes +=
			'<tr id="dashboard-row">' +
				'<td colspan="7">' + jsonObj[1].error + '</td>' +
			'</tr>';
	}
	else if(mailboxes.length > 0) {
		// If there is more than one mailbox, for each one, add a new row to the table
		// with each mailbox's details
		for(var m = 0; m < mailboxes.length; m++) {
			userMailboxes +=
				'<tr class="left">' +
					'<td>' + mailboxes[m].mailbox_id + '</td>' +
					'<td>' +
						'<a href="mailbox.php?mailbox_id=' + mailboxes[m].mailbox_id + '">' + mailboxes[m].file_name + '</a>' +
					'</td>' +
					'<td>' + mailboxes[m].num_messages + '</td>' +
					'<td>' + mailboxes[m].file_size + '</td>' +
					'<td>' + mailboxes[m].file_type + '</td>' +
					'<td>' + mailboxes[m].date_added + '</td>' +
					'<td>' + mailboxes[m].last_accessed + '</td>' +
				'</tr>';
		}

		userMailboxes +=
				'</tbody>' +
			'</table>';
	}

	// Display the table to the user
	dashboardDiv.html(userMailboxes);
}


/***********************************
 *****  UPLOAD AJAX FUNCTIONS  *****
 ***********************************/


/**
 * Used to send the uploaded file's metadata to the server. This includes:
 *	- User ID
 *	- File hash
 *	- File name
 *	- MIME type
 *	- File size in bytes
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (userId|fileHash|fileName|fileType|fileSize)
 */
function initUploadAjax(whatMethod, val) {
	ajaxCall("POST", { method: whatMethod, a:"upload", data:val }, callbackUpload);
}


/**
 * Callback function to determine what to do if there was a successful or failed
 * upload. If it was a failure, show error from server. If it was successful,
 * grab the success message and the mailbox ID, and redirect the user to their
 * recently uploaded mailbox's visualization.
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackUpload(jsonObj) {
	var success,
		successString,
		errorString,
		progress,
		progressStatus;

	success = jsonObj[0].success;
	successString = $('.success-box');
	errorString	= $('.error-box');
	progress = $('.progress');
	progressStatus = $('.progress-status');

	// Hide progress bar stuff in case it shows up
	progress.hide();
	progressStatus.hide();
	
	// If there was a failure with the upload, report the error
	// Otherwise, report successfull upload and redirect to user's visualization
	if(success === "false") {
		var error = jsonObj[1].error;

		errorString.empty()
			.append('<i class="icon-fix fa fa-exclamation-triangle"></i>' + error)
			.fadeIn(200);
	}
	else if(success === "true") {
		var message = jsonObj[1].message;
		var mailboxId = jsonObj[2].mailbox_id;

		errorString.css({ display: 'none' });
		successString.append('<i class="icon-fix fa fa-check"></i>' + message)
			.fadeIn(200);

		// Set delay to redirect to visualization
		setTimeout(function() {
			window.location.href = 'mailbox.php?mailbox_id=' + mailboxId;
		}, 1500);
	}
}


/*****************************************
 *****  PARSE STATUS AJAX FUNCTIONS  *****
 *****************************************/


/**
 * Used to send a constant beacon asking the server if the parsing
 * completed or not. Indended for use to create progress bar for parsing.
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (userId|fileHash)
 */
function initGetParseStatusAjax(whatMethod, val) {
	ajaxCall('POST', { method:whatMethod, a:'upload', data:val }, callbackGetParseStatus);
}


/**
 * Callback function to determine what to do if the uploaded file
 * was successfully parsed or not
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackGetParseStatus(jsonObj) {
	console.log(jsonObj[1].num_messages);
}


/************************************
 *****  MAILBOX AJAX FUNCTIONS  *****
 ************************************/


/**
 * Used to request the mailbox for the user based on their selection
 * either from the dashboard or when a new file is uploaded.
 *
 * @param whatMethod	The method to look for in the service layer
 * @param val			The data to be sent to the server (userId|mailboxId)
 */
function initMailboxAjax(whatMethod, val) {
	ajaxCall("POST", { method: whatMethod, a:"mailbox", data:val }, callbackMailbox);
}


/**
 * Callback method for displaying the mailbox's visualization. First, the mailbox's
 * metadata is displayed for, then D3 takes over to draw the visualization.
 *
 * @param jsonObj	The JSON object coming back from the server
 */
function callbackMailbox(jsonObj) {
	var success,
		mailboxInfo,
		loadingInfo;

	success = jsonObj[0].success;
	mailboxInfo = $('.mailbox-info');
	loadingInfo = $('.loading-info');

	// If there was a failure in grabbing the mailbox, display error to user
	// and redirect user to the dashboard.
	// Otherwise, display mailbox's metadata and draw viz
	if(success === 'false') {
		var error = jsonObj[1].error;
		
		loadingInfo.hide();
		mailboxInfo.append('<h1 class="alert alert-danger">' +
			'<i class="icon-fix fa fa-exclamation-triangle"></i>' + error + '<br />' +
			'Redirecting...</h1>')
			.fadeIn(200);

		// Redirect user to dashboard if they try to access a mailbox not belonging to them
		setTimeout(function() {
			window.location.href = 'dashboard.php';
		}, 2000);
	}
	else if(success === 'true') {
		var json,
			fileProperties,
			mailboxId,
			fileName,
			fileType,
			fileSize,
			numMessages,
			dateAdded,
			lastAccessed;

		// Grab all data from JSON return
		json = jsonObj[2].json;
		fileProperties = jsonObj[1].file_properties;
		mailboxId = fileProperties.mailbox_id;
		fileName = fileProperties.file_name;
		fileType = fileProperties.file_type;
		fileSize = fileProperties.file_size;
		numMessages = fileProperties.num_messages;
		dateAdded = fileProperties.date_added;
		lastAccessed = fileProperties.last_accessed;

		loadingInfo.hide();

		// Display mailbox's metadata to the user
		var currentMailbox =
			'<h1 class="alert alert-success">' + fileName + '</h1>' +
			'<table class="table table-striped selected-mailbox">' +
				'<thead>' +
					'<tr class="info">' +
						'<th>ID</th>' +
						'<th>Message Count</th>' +
						'<th>Size (bytes)</th>' +
						'<th>Type</th>' +
						'<th>Date Added (CDT)</th>' +
						'<th>Last Accessed (CDT)</th>' +
					'</tr>' +
				'</thead>' +
				'<tbody>' +
					'<tr class="left">' +
						'<td>' + mailboxId + '</td>' +
						'<td>' + numMessages + '</td>' +
						'<td>' + fileSize + '</td>' +
						'<td>' + fileType + '</td>' +
						'<td>' + dateAdded + '</td>' +
						'<td>' + lastAccessed + '</td>' +
					'</tr>' +
				'</tbody>' +
			'</table>';

		mailboxInfo.html(currentMailbox)
			.fadeIn(200);
	

		/**************************
		 ***** NETWORK OBJECT *****
		 **************************/


		var Network;


		/**
		 * The Network function is the overall function that draws the visualization.
		 * It includes all the visualizations properties and interactivity methods.
		 */
		Network = function() {
			var viz,				// Needs to be global for setting cursor styles
				width,				// Width of the SVG
				height,				// Height of the SVG
				allData,			// All the data if not sorted
				curLinksData,		// Current links
				curNodesData,		// Current nodes
				curMessagesData,	// Current messages
				linkedByIndex,		// Mapping of all nodes to links
				nodesG,				// SVG group element for all the nodes
				linksG,				// SVG group element for all the links
				node,				// Variable used for each specific node when looping
				link,				// Variable used for each specific link when looping
				message,			// Variable used for each specific message when looping
				messages,			// Messages from JSON
				layout,				// What type of layout (network graph/force layout)
				zoom,				// Zoom object
				zoomMin,			// Minimum amount of zoom
				zoomMax;			// Maximum amount of zoom

			viz = null;
			width = 960;
			height = 600;
			allData = [];
			curLinksData = [];
			curNodesData = [];
			curMessagesData = [];
			linkedByIndex = {};
			nodesG = null;
			linksG = null;
			nodesAndLinksG = null;
			node = null;
			link = null;
			message = null;
			messages = null;
			layout = "force";
			zoom = null;
			zoomMin = 0.1;
			zoomMax = 10;

			var force,			// Create force layout
				tooltip,		// Create tooltip
				nodeColors;		// Create set of node colors

			force = d3.layout.force();				// Set force layout
			tooltip = Tooltip("vis-tooltip", 300);	// Set up tooltips with 300px width

			// Setup color scheme based on group. Each group has its own colors based on email count
			// Group 0 - #be90d4 - violet (light wisteria)
			// Group 1 - #663399 - indigo (rebeccapurple)
			// Group 2 - #2980b9 - blue (belize hole)
			// Group 3 - #27ae60 - green (nephritis)
			// Group 4 - #f1c40f - yellow (sun flower)
			// Group 5 - #e67e22 - orange (carrot)
			// Group 6 - #c0392b - red (pomegranate)
			nodeColors = d3.scale.ordinal()
				.range(["#be90d4" , "#663399", "#2980b9", "#27ae60", "#f1c40f", "#e67e22", "#c0392b"])
				.domain([0, 1, 2, 3, 4, 5, 6]);

			var charge,		// Used to create a charge of the force layout
				network,	// Used to create the network itself
				update;		// Used for updating the network if any selections have been made


			/**
			 * Used for setting the viz's charge towards the center node.
			 *
			 * @param	node	The node to set the charge on
			 * @return			The newly calculated charge for the viz
			 */
			charge = function(node) {
				return -Math.pow(node.radius, 2.0) / 2;
			};


			/**
			 * This is the main method that makes the whole visualization come together.
			 * It calls upon other methods to help draw necessary features and add interactivity.
			 *
			 * @param	selection	The part of the HTML where the viz is placed
			 * @param	data		The data to be visualized
			 * @return				The entire visualization
			 */
			network = function(selection, data) {
				// Set variables to be used for a slim gray border around the svg to match
				// the column next to it
				var svgBorderStroke,
					svgBorderColor,
					svgBorderPath;

				svgBorderStroke = 1;
				svgBorderColor = "#464545";
				svgBorderPath = null;

				// Set up the zoom behavior with min/mix scale, and call on zoom function
				zoom = d3.behavior.zoom()
					.scaleExtent([zoomMin, zoomMax])
					.on("zoom", zoomed);

				// Get all data after all links have been mapped and modified
				allData = setupData(data);

				// Set up the vizualization svg by adding an svg element and setting its width and height
				viz = d3.select(selection)
					.append("svg")
					.attr("width", width)
					.attr("height", height)
					.attr("border", svgBorderStroke)
					.style("background-color", "#ffffff");

				// Add very thin border around SVG container
				svgBorderPath = viz.append("rect")
					.attr("x", 0)
					.attr("y", 0)
					.attr("width", width)
					.attr("height", height)
					.style("fill", "none")
					.style("stroke", svgBorderColor)
					.style("stroke-width", svgBorderStroke);

				// Declare and set up variables for the legend
				var legend,
					legendG,
					textInLegend,
					circleInLegend;

				legend = [];
				legendG = null;
				textInLegend = null;
				circleInLegend = null;

				// Set the legend to match the group numbers for the color scheme
				legend = [
					{group: 0, target: "1 - 100"},
					{group: 1, target: "101 - 500"},
					{group: 2, target: "501 - 1,000"},
					{group: 3, target: "1,001 - 5,000"},
					{group: 4, target: "5,001 - 10,000"},
					{group: 5, target: "10,001 - 50,000"},
					{group: 6, target: "50,001+"}
				];

				// Create a group for the legend in order to translate the entire legend as a whole
				legendG = viz.append("g")
					.attr("id", "legend")
					.attr("transform", "translate(" + 20 + "," + 30 + ")");

				// Add "Legend" to notify of the legend
				textInLegend = legendG.selectAll("textInLegend")
					.data([0], function(d) {
						return d;
					})
					.enter()
					.append("text")
					.text("Legend")
					.style("font-weight", "bold")
					.style("font-size", "18px");

				// Add the legend data to the legend group to create the circles
				circleInLegend = legendG.selectAll("circleInLegend")
					.data(legend);

				// Add all circles to the legend with associated colors for email count
				circleInLegend.enter()
					.append("circle")
					.attr("cx", 10)
					.attr("cy", function(d) {
						// 23 = spacing between circles, 20 = space between "Legend" and top circle
						return 23 * d.group + 20;
					})
					.attr("r", 9)
					.style("fill", function(d) {
						return nodeColors(d.group);
					});

				// Add all text values for email count to their circles
				circleInLegend.enter()
					.append("text")
					.attr("x", 25)
					.attr("y", function(d) {
						// 23 = spacing between circles, 26 = space between "Legend" and top circle
						return 23 * d.group + 26;
					})
					.text(function(d) {
						return d.target;
					})
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Add to the legend group a set of instructions
				instructions = legendG.selectAll("instructions")
					.data([0], function(d) {
						return d;
					});

				// Add the "Instructions" label to the section for instructions
				instructions.enter()
					.append("text")
					.attr("y", 200)
					.text("Instructions")
					.style("font-weight", "bold")
					.style("font-size", "18px");

				// Add "Click to drag" to instructions list
				instructions.enter()
					.append("text")
					.attr("y", 220)
					.text("Click to drag")
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Add "Double click to view emails" to instructions list
				instructions.enter()
					.append("text")
					.attr("y", 240)
					.text("Double click to view emails")
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Add "Scroll to zoom" to instructions list
				instructions.enter()
					.append("text")
					.attr("y", 260)
					.text("Scroll to zoom")
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Add "Mouse over circle to see details" to instructions list
				instructions.enter()
					.append("text")
					.attr("y", 280)
					.text("Mouse over circle to see details")
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Add "Mouse over link to see communication" to instructions list
				instructions.enter()
					.append("text")
					.attr("y", 300)
					.text("Mouse over link to see communication")
					.style("font-size", "12px")
					.style("font-family", "sans-serif");

				// Create group to house the group of links and the group of nodes
				nodesAndLinksG = viz.append("g");

				// Add a group of links to the nodes and links group that all have an id of links
				linksG = nodesAndLinksG.append("g")
					.attr("id", "links");
				
				// Add a group of nodes to the nodes and links group that all have an id of nodes
				nodesG = nodesAndLinksG.append("g")
					.attr("id", "nodes");

				// Set the size of the force layout svg with the width and height
				force.size([width, height]);

				// Set the default layout to force
				setLayout("force");

				// Call the zoom behavior, disable double-click to zoom and change cursor to arrow-crosshairs
				viz.call(zoom)
					.on("dblclick.zoom", null)
					.style("cursor", "move");

				// Run the update function in case any options have been selected by the user
				return update();
			};


			/**
			 * Used update the visualization with the current set of filtered nodes and
			 * links. This method also starts the visualization. The filtereing has not
			 * yet been implemented, so some code in this method is unnecessary for now.
			 *
			 * @return	The starting of the visualization
			 */
			update = function() {
				// Get all current nodes
				curNodesData = filterNodes(allData.nodes);
				curMessagesData = filterMessages(allData.messages);
				curLinksData = filterLinks(allData.links, curNodesData, curMessagesData);

				force.nodes(curNodesData);
				updateNodes();

				if(layout === "force") {
					force.links(curLinksData);
					updateLinks();
				}

				// Start the viz!
				return force.start();
			};


			/**
			 * Used to actually set up the visualization data. This includes:
			 *	- Setting circle radius
			 *	- Setting node placement
			 *	- Setting source and target mapping for links
			 *
			 * @param	data	The set of data to be visualized
			 * @return	data	The new set of data
			 */
			setupData = function(data) {
				var circleRadius,
					countExtent,
					nodesMap;

				// Get the min and max values of all email counts from the nodes
				countExtent = d3.extent(data.nodes, function(d) {
					return d.email_count;
				});

				// Set the node's radius to the sqrt of the email count to generate node size
				// based on amount of emails sent/received. Example: d3.scale.sqrt(7)
				circleRadius = d3.scale.sqrt()
					.range([5, 50])
					.domain(countExtent);

				// For every node, create a random location for the node to end up within the SVG
				data.nodes.forEach(function(n) {
					var randomNumber;

					// Sets the x and y coordiantes randomly for each node
					n.x = randomNumber = Math.floor(Math.random() * width);
					n.y = randomNumber = Math.floor(Math.random() * height);

					// Send email count to create the node's radius size
					return n.radius = circleRadius(n.email_count);
				});

				// Send data to map the nodes and get back mapping
				nodesMap = mapNodes(data.nodes, data.messages);

				// Turn the sources and targets into the mapping based on the id
				// mapping from the nodesMap
				data.links.forEach(function(l) {
					l.source = nodesMap.get(l.source);
					l.target = nodesMap.get(l.target);

					return linkedByIndex[l.source.id + "," + l.target.id] = 1;
				});

				return data;
			};


			/**
			 * Creates a mapping of each node's ID to the node itself. This method
			 * also maps messages of either "Sent" or "Received" to a specific node.
			 *
			 * @param	nodes		The set of nodes to be mapped
			 * @param	messages	The set of messages to be mapped to nodes
			 * @return	nodesMap	The new set of mapped nodes to messages
			 */
			mapNodes = function(nodes, messages) {
				var nodesMap,
					messageObject,
					sentMessageList,
					receivedMessageList;

				// Create the mapping for the nodes
				nodesMap = d3.map();

				// For each node, create an object where the node's id is mapped
				// to the node itself
				nodes.forEach(function(n) {
					// Create the message object and an array for each type of message, sent or received
					// These need to be inside the forEach loop because they need to be reset every iteration
					messageObject = {};
					sentMessageList = [];
					receivedMessageList = [];

					messages.forEach(function(m) {
						if(n.id === m.sender) {
							sentMessageList.push(m);
						}

						m.receivers.forEach(function(r) {
							if(n.id === r) {
								receivedMessageList.push(m);
							}
						});
					});

					// Turn sent and received message lists into objects
					sentMessageObject = Object.setPrototypeOf(sentMessageList, Object.prototype);
					receivedMessageObject = Object.setPrototypeOf(receivedMessageList, Object.prototype);

					// Create the message object and its sub objects of sent and received
					n.messages = messageObject;
					n.messages.sent = sentMessageObject;
					n.messages.received = receivedMessageObject;

					// Add all message objects to each node's message list
					nodesMap.get(messageObject);
					nodesMap.get(sentMessageObject);
					nodesMap.get(receivedMessageObject);

					// In the end, map each node by its respective id
					return nodesMap.set(n.id, n);
				});

				// Return the mapping of the nodes
				return nodesMap;
			};


			/**
			 * Used to create an indexed listing of all nodes and how they are connected
			 * to their neighbors. This method works with nodes from both sides to simplify
			 * various other parts of drawing the visualization.
			 *
			 * @param	a				Node on one side
			 * @param	b				Node on the other side
			 * @return	linkedByIndex	An indexed listing of all the nodes and their immediate neighbors
			 */
			neighboring = function(a, b) {
				return linkedByIndex[a.id + "," + b.id] || linkedByIndex[b.id + "," + a.id];
			};


			/**
			 * FUTURE FEATURE
			 *
			 * Used as a placeholder method for future feature of filtering nodes
			 * based on a search criteria.
			 *
			 * @param	allNodes		The set of nodes to be filtered
			 * @return	filteredNodes	The new set of filtered nodes
			 */
			filterNodes = function(allNodes) {
				var	filteredNodes;

				filteredNodes = allNodes;

				return filteredNodes;
			};


			/**
			 * FUTURE FEATURE
			 *
			 * Used as a placeholder method for future feature of filtering links
			 * based on a search criteria.
			 *
			 * @param	allLinks		The set of links to be filtered
			 * @param	curNodes		The set of current nodes to be filtered
			 * @param	curMessages		The set of current messages to be filtered
			 * @return	allLinks		The new set of filtered links
			 */
			filterLinks = function(allLinks, curNodes, curMessages) {
				curNodes = mapNodes(curNodes, curMessages);
				
				return allLinks.filter(function(l) {
					return curNodes.get(l.source.id) && curNodes.get(l.target.id);
				});
			};


			/**
			 * FUTURE FEATURE
			 *
			 * Used as a placeholder method for future feature of filtering messages
			 * based on a search criteria.
			 *
			 * @param	allMessages			The set of messages to be filtered
			 * @return	filteredMessages	The new set of filtered messages
			 */
			filterMessages = function(allMessages) {
				var filteredMessages;

				filteredMessages = allMessages;

				return filteredMessages;
			};


			/**
			 * Used to update how the nodes look and where they are placed in the viz.
			 *
			 * @return	node	The node to be drawn
			 */
			updateNodes = function() {
				node = nodesG.selectAll("circle.node")
					.data(curNodesData, function(d) {
						return d.id;
					});
			
				// Set all nodes attributes
				node.enter()
					.append("circle")
					.attr("class", "node")
					.attr("cx", function(d) {
						return d.x;
					})
					.attr("cy", function(d) {
						return d.y;
					})
					.attr("r", function(d) {
						return d.radius;
					})
					.style("fill", function(d) {
						return nodeColors(d.group);
					})
					.style("stroke", function(d) {
						return strokeForNode(d);
					})
					.style("stroke-width", 1.0);

				// Handle mouse events
				node.on("mouseover", showNodeDetails)
					.on("mouseout", hideNodeDetails)
					.on("dblclick", nodeDoubleClicked);
					// .call(force.drag)	// Can be turned on to drag nodes around
				
				return node.exit().remove();
			};


			/**
			 * Used to update how the links look and where they are placed in the viz.
			 *
			 * @return	link	The node to be drawn
			 */
			updateLinks = function() {
				link = linksG.selectAll("line.link")
					.data(curLinksData, function(d) {
						return d.source.id + "_" + d.target.id;
					});

				// Set all links attributes
				link.enter()
					.append("line")
					.attr("class", "link")
					.attr("stroke", "#ccc")
					.attr("stroke-opacity", 0.8)
					.attr("x1", function(d) {
						return d.source.x;
					})
					.attr("y1", function(d) {
						return d.source.y;
					})
					.attr("x2", function(d) {
						return d.target.x;
					})
					.attr("y2", function(d) {
						return d.target.y;
					});

				link.on("mouseover", showLinkDetails)
					.on("mouseout", hideLinkDetails)
					.on("dblclick", linkDoubleClicked);

				return link.exit().remove();
			};


			/**
			 * FUTURE FEATURE
			 *
			 * Used to set the layout if changing between visualizations. In it's current
			 * state, only the force layout is allowed. Future work will include adding
			 * a radial tree visualization. If the selection is force layout, call on the
			 * "forceTick" function to set the proper tick on the force.
			 *
			 * @param	newLayout	The selected layout
			 * @return	force		The force layout
			 */
			setLayout = function(newLayout) {
				layout = newLayout;
				
				if(layout === "force") {
					return force.on("tick", forceTick)
						.charge(-300)			// Change charge of force
						.linkDistance(100);		// Change link distance
				}
			};


			/**
			 * Sets the tick on the force layout.
			 */
			forceTick = function() {
				node.attr("cx", function(d) {
					return d.x;
				})
				.attr("cy", function(d) {
					return d.y;
				});

				return link.attr("x1", function(d) {
					return d.source.x;
				})
				.attr("y1", function(d) {
					return d.source.y;
				})
				.attr("x2", function(d) {
					return d.target.x;
				})
				.attr("y2", function(d) {
					return d.target.y;
				});
			};


			/**
			 * Method to set the border of the node to a darker shade of the color
			 * of the node itself to make it stand out.
			 *
			 * @param	d	The node object
			 * @return		The newly generated color for the specific node
			 */
			strokeForNode = function(d) {
				return d3.rgb(nodeColors(d.group)).darker().toString();
			};
			

			/***********************************
			 *****  MOUSE EVENT FUNCTIONS  *****
			 ***********************************/


			var showNodeDetails,
				hideNodeDetails,
				showLinkDetails,
				hideLinkDetails,
				nodeDoubleClicked,
				zoomed;


			/**
			 * This method is used to show a tooltip with the node's email address
			 * and email count. It will change the highlighting of the nodes and 
			 * their neighbors.
			 *
			 * @param	d	The node to add the details to
			 * @return		The current node that's selected
			 */
			showNodeDetails = function(d) {
				// Change the curser to the pointer finger on mouseover for tooltip
				viz.style("cursor", "pointer");

				var content;

				// Set up tooltip content
				content = '<p class="tooltip-main tooltip-email-addr center">' + d.id + '</p>';
				content += '<hr class="tooltip-hr">';
				content += '<p class="tooltip-main tooltip-email-count center"><span class="tooltip-span">Email Count: </span>' + d.email_count + '</p>';

				// Show the tooltip while listening to the mouseover event
				tooltip.showTooltip(content, d3.event);

				if(link) {
					// If so, set the stroke color darker to highlight
					link.attr("stroke", function(l) {
						return (l.source === d || l.target === d) ? "#555" : "#ddd";
					})
					.attr("stroke-opacity", function(l) {
						return (l.source === d || l.target === d) ? 1.0 : 0.5;
					});
				}

				// Change node's stroke styling
				node.style("stroke", function(n) {
					return (n.searched || neighboring(n, d)) ? "#555" : strokeForNode(n);
				})
				.style("stroke-width", function(n) {
					return (n.searched || neighboring(n, d)) ? 2.0 : 1.0;
				});

				// Return the node with an increase in size animation
				return d3.select(this)
					.transition()
					.duration(750)
					.attr("r", function(n) {
						return n.radius + 10;
					})
					.style("stroke", "#000")
					.style("stroke-width", 2.0);
			};


			/**
			 * This method is used to hide a tooltip. It will change the 
			 * highlighting of the nodes and their neighbors.
			 *
			 * @param	d	The node to hide the details for
			 * @return		The link's attributes of the connecting node
			 */
			hideNodeDetails = function(d) {
				// Change mouse back to move icon for panning/zooming
				viz.style("cursor", "move");

				// Hide the tooltip when the mouse is no longer over the node
				tooltip.hideTooltip();
				
				// Flip what happens from showNodeDetails: put node back to original size
				node.transition()
					.duration(750)
					.attr("r", function(n) {
						return n.radius;
					})
					.style("stroke", function(n) {
						return (!n.searched) ? strokeForNode(n) : "#555";
					})
					.style("stroke-width", function(n) {
						return (!n.searched) ? 1.0 : 2.0;
					});

				if(link) {
					return link.attr("stroke", "#ddd")
						.attr("stroke-width", 1.0)
						.attr("stroke-opacity", 1.0);
				}
			};


			/**
			 * This method is used to show a link's details including source and
			 * target email addresses and total email count between them. It will
			 * change the highlighting of the links and their neighboring nodes.
			 *
			 * @param	d	The link to add the details to
			 * @return		The current link that's selected
			 */
			showLinkDetails = function(d) {
				// Change the curser to the pointer finger on mouseover for tooltip
				viz.style("cursor", "pointer");

				var content;

				// Set up tooltip content
				content = '<p class="tooltip-main tooltip-email-addr center"><span class="tooltip-span">From: </span>' + d.source.id + '</p>' +
					'<p class="tooltip-main tooltip-email-addr center"><span class="tooltip-span">To: </span>' + d.target.id + '</p>' +
					'<hr class="tooltip-hr">' +
					'<p class="tooltip-main tooltip-email-count center"><span class="tooltip-span">Email Count: </span>' + d.value + '</p>';

				// Show the tooltip while listening to the mouseover event
				tooltip.showTooltip(content, d3.event);

				if(node) {
					// Set every node's stroke and stroke-width
					node.style("stroke", function(n) {
						return (n === d.source || n === d.target) ? "#555" : strokeForNode(n);
					})
					.style("stroke-width", function(n) {
						return (n === d.source || n === d.target) ? 2.0 : 1.0;
					});
				}

				// Set all non-highlighted links to a lighter faded color with a lower opacity
				link.attr("stroke", "#ddd")
					.attr("stroke-width", 1.0)
					.attr("stroke-opacity", 0.5);

				// Set the highlighted link's color darker, thicker width, and 100% opacity
				return d3.select(this)
					.attr("stroke", "#000")
					.attr("stroke-width", 2.0)
					.attr("stroke-opacity", 1.0);
			};


			/**
			 * This method is used to hide a link's details. It will
			 * change the highlighting of the links and their neighboring nodes.
			 *
			 * @param	d	The link to hide the details to
			 * @return		The node's attributes of the connecting link
			 */
			hideLinkDetails = function(d) {
				// Change mouse back to move icon for panning/zooming
				viz.style("cursor", "move");

				// Hide the tooltip when the mouse is no longer over the node
				tooltip.hideTooltip();

				// For all links, including the highlighted one, reset back to normal
				link.attr("stroke", "#ccc")
					.attr("stroke-width", 1.0)
					.attr("stroke-opacity", 1.0);

				// For all nodes, reset back to normal
				if(node) {
					return node.style("stroke", function(n) {
						return strokeForNode(n);
					})
					.style("stroke-width", 1.0)
					.style("stroke-opacity", 1.0);
				}
			};


			// Used for checking whether a node is already highlighted
			var toggle = 0;


			/**
			 * This method is used to display all the sent and received email messages
			 * from the double-clicked node.
			 *
			 * @param	d	The node to show messages for
			 * @return		The highlighted node
			 */
			nodeDoubleClicked = function(d) {
				var messageDiv = $('.messages');
				var email = '';
				
				// Sent Email
				var sent = d.messages.sent;

				email =
					'<ul class="accordion-menu animate">' +
						'<li class="has-children sent-email">' +
							'<input type="checkbox" name="sent" id="sent">' +
							'<label for="sent"><i class="icon-fix fa fa-share"></i>SENT (' + sent.length + ')</label>' +
							'<ul>';

				// Check the length of the sent emails.
				// If 0, display "NONE"
				// If more than 0, display them
				if(sent.length === 0) {
					email += '<li><p class="center empty-inbox">NONE</p></li>' +
						'</ul>' +
					'</li>';
				}
				else if(sent.length > 0) {
					for(var m = 0; m < sent.length; m++) {
						var receivers = sent[m].receivers;

						email +=
							'<li>' +
								'<table class="table table-hover">' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">From: </span></td>' +
										'<td class="success">' + sent[m].sender + '</td>' +
									'</tr>' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">To: </span></td>';

						if(receivers.length === 0) {
							email += '<td class="success">N/A</td>';
						}
						else if(receivers.length === 1) {
							email += '<td class="success">' + sent[m].receivers + '</td>';
						}
						else if(receivers.length > 1) {
							email += '<td class="success">';

							for(var r = 1; r < receivers.length; r++) {
								email += sent[m].receivers[r] + '<br />';
							}

							email += '</td>' +
								'</tr>';
						}

						email += '<tr>' +
									'<td class="email-header active"><span class="email-span">Date: </span></td>' +
									'<td class="success">' + sent[m].date_sent + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Subject: </span></td>' +
									'<td class="success">' + sent[m].subject + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Content: </span></td>' +
									'<td class="success">' + sent[m].content + '</td>' +
								'</tr>' +
							'</table>' +
						'</li>';
					}

					email += '</ul>' +
						'</li>';
				}

				// Received Email
				var received = d.messages.received;

				email +=
						'<li class="has-children received-email">' +
							'<input type="checkbox" name="received" id="received">' +
							'<label for="received"><i class="icon-fix fa fa-reply"></i>RECEIVED (' + received.length + ')</label>' +
							'<ul>';

				// Check the length of the received emails.
				// If 0, display "NONE"
				// If more than 0, display them
				if(received.length === 0) {
					email += '<li><p class="center empty-inbox">NONE</p></li>' +
						'</ul>' +
					'</li>';
				}
				else if(received.length > 0) {
					for(var m = 0; m < received.length; m++) {
						var receivers = received[m].receivers;

						email +=
							'<li>' +
								'<table class="table table-hover">' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">From: </span></td>' +
										'<td class="info">' + received[m].sender + '</td>' +
									'</tr>' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">To: </span></td>';

						if(receivers.length === 0) {
							email += '<td class="info">N/A</td>';
						}
						else if(receivers.length === 1) {
							email += '<td class="info">' + received[m].receivers + '</td>';
						}
						else if(receivers.length > 1) {
							email += '<td class="info">';

							for(var r = 1; r < receivers.length; r++) {
								email += received[m].receivers[r] + '<br />';
							}

							email += '</td>' +
								'</tr>';
						}

						email += '<tr>' +
									'<td class="email-header active"><span class="email-span">Date: </span></td>' +
									'<td class="info">' + received[m].date_sent + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Subject: </span></td>' +
									'<td class="info">' + received[m].subject + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Content: </span></td>' +
									'<td class="info">' + received[m].content + '</td>' +
								'</tr>' +
							'</table>' +
						'</li>';
					}

					email += '</ul>' +
						'</li>' +
					'</ul>';
				}

				// Append all email messages to the side panel next to the viz
				messageDiv.html(email);

				// Display messages
				messagesMenu();

				// Check whether node and neighbors are already highlighted
				// Toggle = 0 for non-highlighted nodes and neighbors
				// Toggle = 1 for highlighted nodes and neighbors
				if(toggle === 0) {
					toggle = 1;

					// Set double-clicked node and immediate neighbors to be highlighted
					node.style("opacity", function(n) {
						return neighboring(n, d) ? 1 : 0.1;
					});

					link.style("opacity", function(l) {
						return (l.source.index === d.index || l.target.index === d.index) ? 1.0 : 0.1;
					});
				}
				else {
					// Reset toggle for next double-click event
					toggle = 0;

					// Reset every node's and link's opacity back to normal
					node.style("opacity", 1);
					link.style("opacity", 1);
				}

				// Return the highlighted node
				return d3.select(this)
					.style("opacity", 1)
					.style("stroke", "#000")
					.style("stroke-width", 2.0);
			};


			/**
			 * This method is used to display all the sent and received email messages
			 * from the double-clicked node.
			 *
			 * BUG: Currently only the sent messages from the source and 
			 *		received messages from the target are displayed.
			 * BUG: After double-clicking a link, link stays highlighted bold
			 *
			 * @param	d	The link to show messages for
			 * @return		The highlighted link
			 */
			linkDoubleClicked = function(d) {
				var messageDiv = $('.messages');
				var email = '';

				// Sent Email
				var sent = d.source.messages.sent;

				email =
					'<ul class="accordion-menu animate">' +
						'<li class="has-children sent-email">' +
							'<input type="checkbox" name="sent" id="sent">' +
							'<label for="sent"><i class="icon-fix fa fa-share"></i>SENT (' + sent.length + ')</label>' +
							'<ul>';

				if(sent.length === 0) {
					email += '<li><p class="center empty-inbox">NONE</p></li>' +
						'</ul>' +
					'</li>';
				}
				else if(sent.length > 0) {
					for(var m = 0; m < sent.length; m++) {
						var receivers = sent[m].receivers;

						email +=
							'<li>' +
								'<table class="table table-hover">' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">From: </span></td>' +
										'<td class="success">' + sent[m].sender + '</td>' +
									'</tr>' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">To: </span></td>';

						if(receivers.length === 0) {
							email += '<td class="success">N/A</td>';
						}
						else if(receivers.length === 1) {
							email += '<td class="success">' + sent[m].receivers + '</td>';
						}
						else if(receivers.length > 1) {
							email += '<td class="success">';

							for(var r = 1; r < receivers.length; r++) {
								email += sent[m].receivers[r] + '<br />';
							}

							email += '</td>' +
								'</tr>';
						}

						email += '<tr>' +
									'<td class="email-header active"><span class="email-span">Date: </span></td>' +
									'<td class="success">' + sent[m].date_sent + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Subject: </span></td>' +
									'<td class="success">' + sent[m].subject + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Content: </span></td>' +
									'<td class="success">' + sent[m].content + '</td>' +
								'</tr>' +
							'</table>' +
						'</li>';
					}

					email += '</ul>' +
						'</li>';
				}

				// Received Email
				var received = d.target.messages.received;
				
				email +=
						'<li class="has-children received-email">' +
							'<input type="checkbox" name="received" id="received">' +
							'<label for="received"><i class="icon-fix fa fa-reply"></i>RECEIVED (' + received.length + ')</label>' +
							'<ul>';

				if(received.length === 0) {
					email += '<li><p class="center empty-inbox">NONE</p></li>' +
						'</ul>' +
					'</li>';
				}
				else if(received.length > 0) {
					for(var m = 0; m < received.length; m++) {
						var receivers = received[m].receivers;

						email +=
							'<li>' +
								'<table class="table table-hover">' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">From: </span></td>' +
										'<td class="info">' + received[m].sender + '</td>' +
									'</tr>' +
									'<tr>' +
										'<td class="email-header active"><span class="email-span">To: </span></td>';

						if(receivers.length === 0) {
							email += '<td class="info">N/A</td>';
						}
						else if(receivers.length === 1) {
							email += '<td class="info">' + received[m].receivers + '</td>';
						}
						else if(receivers.length > 1) {
							email += '<td class="info">';

							for(var r = 1; r < receivers.length; r++) {
								email += received[m].receivers[r] + '<br />';
							}

							email += '</td>' +
								'</tr>';
						}

						email += '<tr>' +
									'<td class="email-header active"><span class="email-span">Date: </span></td>' +
									'<td class="info">' + received[m].date_sent + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Subject: </span></td>' +
									'<td class="info">' + received[m].subject + '</td>' +
								'</tr>' +
								'<tr>' +
									'<td class="email-header active"><span class="email-span">Content: </span></td>' +
									'<td class="info">' + received[m].content + '</td>' +
								'</tr>' +
							'</table>' +
						'</li>';
					}

					email += '</ul>' +
						'</li>' +
					'</ul>';
				}

				// Append all email messages to the side panel next to the viz
				messageDiv.html(email);

				// Display messages
				messagesMenu();

				// Check whether node and neighbors are already highlighted
				// Toggle = 0 for non-highlighted nodes and neighbors
				// Toggle = 1 for highlighted nodes and neighbors
				if(toggle === 0) {
					toggle = 1;

					// Set double-clicked node and immediate neighbors to be highlighted
					node.style("opacity", function(n) {
						return neighboring(n, d) ? 1 : 0.1;
					});

					link.style("opacity", function(l) {
						return (l.source.index === d.index || l.target.index === d.index) ? 1.0 : 0.1;
					});
				}
				else {
					// Reset toggle for next double-click event
					toggle = 0;

					// Reset every node's and link's opacity back to normal
					node.style("opacity", 1);
					link.style("opacity", 1);
				}

				// Return the highlighted link
				return d3.select(this)
					.style("opacity", 1)
					.style("stroke", "#000")
					.style("stroke-width", 2.0);
			};


			/**
			 * This method is used to set the proper scaling and translation of the
			 * visualization when it's zoomed in and out.
			 */
			zoomed = function() {
				var translate,
					scale;

				translate = d3.event.translate;
				scale = d3.event.scale;

				nodesAndLinksG.attr("transform", "translate(" + translate + ")scale(" + scale + ")");
			};
				
			// Return the entire network object
			return network;
		};


		// Create network viz with data from callback
		$(function() {
			var myNetwork;

			myNetwork = Network();

			// Return a new network visualization, in the "viz" div, and send in the json from 
			// the callback for that particular mailbox
			return myNetwork(".viz", json);

			// FOR STATIC JSON FILES THAT ARE TOO BIG
			// return d3.json(encodeURI("demo_json/enron_Vincent Kaminski.json"), function(json) {
			// 	return myNetwork(".viz", json);
			// });
		});
	}
}