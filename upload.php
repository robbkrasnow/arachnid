<?php
	// Require the Token class to check authentication
	require_once('../../../Token.class.php');
	
	// If the token is not set, you are not authenticated, leave!
	if(!isset($_COOKIE['token'])) {
		header("Location: login.php");
	}
	else {
		// If there is a token, grab it from the cookie and check it
		$token = new Token();
		$token->setTokenFromCookie($_COOKIE['token']);
		
		// If the token doesn't validate, user has tampered with it, send to logout
		if($token->checkToken() == false) {
			header("Location: logout.php");
		}
		else {
			// Start the session and grab session variables to use in HTML
			session_start();
			$username = $_SESSION['username'];
			$user_id = $_SESSION['user_id'];
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Arachnid | Upload</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/bootswatch-darkly.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/jasny-bootstrap.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/font-awesome-4.6.1/css/font-awesome.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/style.css" />
	
	<script type="text/javascript" src="js/vendor/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/vendor/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/vendor/jasny-bootstrap.min.js"></script>
	<script type="text/javascript" src="js/library.js"></script>
	<script type="text/javascript" src="js/ajaxFunctions.js"></script>
	<script type="text/javascript" src="js/uploadFunctions.js"></script>
	<script type="text/javascript">
		var userId = <?php echo $user_id; ?>;

		$(document).ready(function() {
			// Initialize upload function
			initUpload();
		});
	</script>
</head> 
<body>
	<!-- NAVBAR -->
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<a class="arachnid-title navbar-brand" href="#">Arachnid</a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li><a href="dashboard.php"><i class="icon-fix fa fa-tachometer"></i>Dashboard</a></li>
					<li><a href="#"><i class="icon-fix fa fa-envelope"></i>Mailbox</a></li>
					<li class="active"><a href="upload.php"><i class="icon-fix fa fa-upload"></i>Upload<span class="sr-only">(current)</span></a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li class="user-dropdown dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<i class="icon-fix fa fa-user"></i><?php echo $username; ?><span class="caret"></span>
						</a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="logout.php"><i class="icon-fix fa fa-sign-out"></i>Logout</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<!-- UPLOAD FILE -->
	<h3 class="alert alert-warning center">Please select an MBOX (*.mbox) file to upload (up to 4 GB)</h3>
	<div class="center upload-form">
		<h3 class="progress-status"></h3>
		<div class="progress progress-striped active">
			<div class="progress-bar progress-bar-info" role="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<form id="upload-form" method="post" enctype="multipart/form-data" class="upload-file-form">
			<div class="center success-box alert alert-success" role="alert"></div>
			<div class="center error-box alert alert-danger" role="alert"></div>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
				<div class="upload-input-field form-control" data-trigger="fileinput">
					<i class="icon-fix fa fa-file fileinput-exists"></i> 
					<span class="fileinput-preview"></span>
				</div>
				<span class="input-group-addon btn btn-default btn-file">
					<span class="fileinput-new">Select file</span>
					<span class="fileinput-exists">Change</span>
					<input type="hidden" name="MAX_FILE_SIZE" value="4294967296" />
					<input type="file" name="file" id="file" />
				</span>
				<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
			</div>
			<div class="upload-button-fix">
				<button type="submit" id="upload-mbox" class="btn btn-primary btn-lg" alt="Upload" name="submit">
					<i class="icon-fix fa fa-upload"></i>Upload
				</button>
			</div>
		</form>		
	</div>

	<!-- FOOTER -->
	<footer class="pad">
		<h5 class="text-muted">Copyright &copy; 2017 | 
			<a href="https://robbkrasnow.com" target="blank" class="text-muted">Robb Krasnow</a>
		</h5>
	</footer>	
</body>
</html>