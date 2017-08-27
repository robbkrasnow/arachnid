<?php
	// If the token is set in the cookie, redirect to dashboard where authenication will occur
	if(isset($_COOKIE['token'])) {
		header("Location: dashboard.php");
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Arachnid | Login</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/bootswatch-darkly.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/vendor/font-awesome-4.6.1/css/font-awesome.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="css/style.css" />
	
	<script type="text/javascript" src="js/vendor/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/vendor/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/vendor/validate.min.js"></script>
	<script type="text/javascript" src="js/library.js"></script>
	<script type="text/javascript" src="js/ajaxFunctions.js"></script>
	<script type="text/javascript" src="js/loginFunctions.js"></script>
	<script type="text/javascript" src="js/registerFunctions.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			// Initialize login and registration functions
			initLogin();
			initRegister();
		});
	</script>
</head> 

<body class="well no-border">
	<!-- LOGIN -->
	<div class="container">
		<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post" id="login-form" class="login-form-div center" name="login-form">
			<img class="fix-width" src="img/arachnid.png"><h1 class="font-color-black">Arachnid</h1>
			<br />
			<div class="center invalid-login-box alert alert-danger" role="alert"></div>
			<div class="center success-login-box alert alert-success" role="alert"></div>
			<div class="form-group">
				<div class="input-group fix-width">
					<span class="input-group-addon username-addon-icon-fix"><i class="login-form-input-addon-fix fa fa-user"></i></span>				
					<input autocomplete="off" id="username-field" class="form-control" type="text" placeholder="Username" name="username" />
				</div>
			</div>
			<div class="form-group">
				<div class="input-group fix-width">
					<span class="input-group-addon"><i class="fa fa-key"></i></span>
					<input autocomplete="off" id="password-field" class="form-control" type="password" placeholder="Password" name="password" />
				</div>
			</div>
			<button id="register" class="login-button-fix btn btn-primary btn-lg" type="button" name="register" data-target="#register-modal" data-toggle="modal">
				<i class="icon-fix fa fa-pencil"></i>Register
			</button>
			<button id="sign-in" class="login-button-fix btn btn-success btn-lg" name="submit">
				<i class="icon-fix fa fa-sign-in"></i>Sign In
			</button>
		</form>
	</div>

	<!-- REGISTRATION -->
	<div id="register-modal" class="modal fade" aria-hidden="true" aria-labelledby="register-label" role="dialog" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						<span aria-hidden="true"><i class="fa fa-times"></i></span>
						<span class="sr-only">Close</span>
					</button>
					<h3 class="modal-title">Register</h3>
				</div>
				<div class="modal-body">
					<form action="login.php" method="post" class="register-modal-form" name="register-modal-form">
						<div class="center success-box alert alert-success" role="alert"></div>
						<div class="center error-box alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i></div>
						<div class="form-group">
							<label class="register-modal-form-label" for="new-username">Username:</label><br />
							<div class="input-group fix-width">								
								<span class="input-group-addon"><i class="login-form-input-addon-fix fa fa-user"></i></span>				
								<input autocomplete="off" id="new-username-field" class="form-control" type="text" placeholder="4 or more alpha-numeric characters" name="new-username" />
							</div>
						</div>
						<div class="form-group">
							<label class="register-modal-form-label" for="new-password">Password:</label><br />
							<div class="input-group fix-width">								
								<span class="input-group-addon"><i class="fa fa-key"></i></span>
								<input autocomplete="off" class="form-control" type="password" placeholder="8 or more characters" name="new-password" />
							</div>
						</div>
						<div class="form-group">
							<label class="register-modal-form-label" for="new-password-confirm">Confirm Password:</label><br />
							<div class="input-group fix-width">								
								<span class="input-group-addon"><i class="fa fa-key"></i></span>
								<input autocomplete="off" class="form-control" type="password" placeholder="Enter password again" name="new-password-confirm" />
							</div>
						</div>
						<div class="center modal-footer">
							<button type="button" class="login-button-fix btn btn-danger btn-lg" data-dismiss="modal"><i class="icon-fix fa fa-times"></i>Cancel</button>
							<button type="submit" id="sign-up" class="login-button-fix btn btn-success btn-lg"><i class="icon-fix fa fa-check"></i>Sign Up!</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- REGISTRATION VALIDATION -->
	<script type="text/javascript">
		// Create new form validation for the registration form to present user feedback
		new FormValidator('register-modal-form', [{
			name: 'new-username',
			display: '<strong>username</strong>',
			rules: 'required|alpha_numeric|min_length[4]|max_length[20]'
		}, {
			name: 'new-password',
			display: '<strong>password</strong>',
			rules: 'required|min_length[8]'
		}, {
			name: 'new-password-confirm',
			display: '<strong>confirm password</strong>',
			rules: 'required|matches[new-password]'
		}], function(errors, evt) {
				// Grab areas for alert boxes for visuals to user
				var successString,
					errorString,
					duplicateString;

				successString = $('.success-box');
				errorString	= $('.error-box');
				duplicateString = $('.duplicate-box');
				
				errorString.css({ display: 'none' });
				duplicateString.css({ display: 'none' });
				
				// Check if there are any errors in validation. If so, print them
				if(errors.length > 0) {
					errorString.empty();
					errorLength = errors.length;
					
					for(var i = 0; i < errorLength; i++) {
						errorString.append(errors[i].message + '<br />');
					}
					
					errorString.fadeIn(200);
				}
				else {
					// If no errors in registration validation, proceed to registration
					errorString.css({ display: 'none' });
					checkRegister();
				}
				
				// Used for IE if the "Sign Up" button is clicked or enter is pressed
				if(evt && evt.preventDefault) {
					evt.preventDefault();
				}
				else if(event) {
					event.returnValue = false;
				}
			});	 
	</script>
	
	<!-- FOOTER -->
	<footer class="pad">
		<h5 class="text-muted">Copyright &copy; 2017 | 
			<a href="https://robbkrasnow.com" target="blank" class="text-muted">Robb Krasnow</a>
		</h5>
	</footer>	
</body>
</html>