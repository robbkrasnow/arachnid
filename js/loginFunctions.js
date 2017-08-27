/**
 * All methods are used to either initialize and validate logins.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method sets up the event listeners for the click and enter of the "Sign In"
 * button. If the button is clicked or enter is pressed, call the method checkLogin()
 * to see if this is a valid login.
 */
function initLogin() {
	$("#username-field").focus();
	$("#sign-in").click(function(e) {
		e.preventDefault();
		checkLogin();
	});
}

/**
 * This method is used when the "Sign In" button is clicked. If an event listener
 * from initLogin() is triggered, serialize the login form and grab both the username
 * and password. Send them to the service layer method, startLogin() and reset the form.
 */
function checkLogin() {
	var obj = $('#login-form').serializeObject();
	initLoginAjax('start_login', obj['username'] + '|' + obj['password']);
	$("#login-form")[0].reset();
}