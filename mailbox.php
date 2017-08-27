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
			
			if(isset($_GET['mailbox_id'])) {
				$mailbox_id_get = intval($_GET['mailbox_id']);
			}
			else {
				$mailbox_id_get = -1;	// Set to -1 indicating no mailbox id
			}
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Arachnid | Mailbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/bootswatch-darkly.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/font-awesome-4.6.1/css/font-awesome.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/style.css" />
	
	<script type="text/javascript" src="js/vendor/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/vendor/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/vendor/d3.min.js"></script>
	<script type="text/javascript" src="js/vendor/Tooltip.js"></script>
	<script type="text/javascript" src="js/library.js"></script>
	<script type="text/javascript" src="js/ajaxFunctions.js"></script>
	<script type="text/javascript" src="js/mailboxFunctions.js"></script>
	<script type="text/javascript">
		var userId = <?php echo $user_id; ?>;
		var mailboxId = <?php echo $mailbox_id_get; ?>;

		$(document).ready(function() {
			// Initialize mailbox functions
			initMailbox();
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
					<li class="active"><a href="#"><i class="icon-fix fa fa-envelope"></i>Mailbox<span class="sr-only">(current)</span></a></li>
					<li><a href="upload.php"><i class="icon-fix fa fa-upload"></i>Upload</a></li>
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

	<!-- VISUALIZATION AND EMAILS -->
	<h3 class="center loading-info"></h3>
	<div class="emails center">
		<div class="mailbox-info"></div>
		<div class="viz"></div>
		<div class="messages"></div>
	</div>

	<!-- FOOTER -->
	<footer class="pad">
		<h5 class="text-muted">Copyright &copy; 2017 | 
			<a href="https://robbkrasnow.com" target="blank" class="text-muted">Robb Krasnow</a>
		</h5>
	</footer>	
</body>
</html>