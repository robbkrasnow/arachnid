<?php
	// If the token is not set, you are not authenticated, leave!
	if(!isset($_COOKIE['token'])) {
		header("Location: login.php");
	}
	else {
		// Start the session and grab session variables to use in HTML
		session_start();
		$user_id = $_SESSION['user_id'];
		
		// Set token in cookie to blank and completely unset it
		unset($_COOKIE['token']);
		setcookie('token', '', time() - 3600, '/');		
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Connect4 | Logout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/bootswatch-darkly.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/font-awesome-4.6.1/css/font-awesome.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/style.css" />
	
	<script type="text/javascript" src="js/vendor/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/vendor/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/library.js"></script>
	<script type="text/javascript" src="js/ajaxFunctions.js"></script>
	<script type="text/javascript" src="js/logoutFunctions.js"></script>
	<script type="text/javascript">
		var userId = "<?php echo $user_id; ?>";
		
		$(document).ready(function() {
			// Initialize logout
			initLogout();
		});
	</script>
</head> 
<body class="logout-body">
	<!-- LOGOUT -->
	<div class="center hero-unit">
		<h1>Thanks for using Arachnid</h1>
		<h2>Come back soon!</h2>
		<p>Redirecting in <i id="timer"></i> seconds...</p>
		<p>
			<a class="btn btn-success btn-lg" href="login.php"><i class="icon-fix fa fa-home"></i>Home</a>
		</p>
	</div>

	<!-- FOOTER -->
	<footer class="pad">
		<h5 class="text-muted">Copyright &copy; 2017 | 
			<a href="https://robbkrasnow.com" target="blank" class="text-muted">Robb Krasnow</a>
		</h5>
	</footer>
</body>
</html>