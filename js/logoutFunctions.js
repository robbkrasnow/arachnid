/**
 * All methods are used to check whether or not anyone has logged out. Send
 * ajax calls to the server to log that user out.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method initializes the logout directly from the logout.php page.
 * Send the user's id to the server to log them out.
 */
function initLogout() {
	initLogoutAjax('start_logout', userId);
}


/**
 * This method acts as an event listener to anytime the "Logout" button is
 * clicked from the user dropdown menu. If the user's gameId is greater than
 * or equal to 0, send the message to the user to quit the game to let the
 * other user know they have quit, hence redirecting them to the lobby to
 * play another user.
 */
function logoutClicked() {
	$("body").delegate("#logoutButton", "click", function(e) {
		e.preventDefault();
		window.location = "logout.php";
	});
}