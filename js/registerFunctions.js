/**
 * All methods are used as event listeners or to check for registration after
 * validation was successful.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This is the listening method used for when the "Sign Up" button is clicked.
 * It will listen for a click event, prevent the default click, and link the
 * "Sign Up" button to the form in the modal body. If it's clicked, the form
 * is then submitted and validated among the rules in the script at the end of
 * login.php.
 */
function initRegister() {
	// Set focus to the first input field in the register modal
	$("#register-modal").on('shown.bs.modal', function() {
		$("#new-username-field").focus();
	});

	// Submit the form to check for validation errors
	$("#sign-up").on('click', function(e) {
		e.preventDefault();
		$(".register-modal-form").submit();
	});
}


/**
 * This method is used when the "Sign Up!" button is clicked as well. If there
 * are no validation errors, then serialize the form and grab the new username
 * and password. Send it to the service layer method 'startRegister' using the
 * initRegisterAjax method call.
 */
function checkRegister() {
	var obj = $('.register-modal-form').serializeObject();
	initRegisterAjax('start_register', obj['new-username'] + '|' + obj['new-password']);
}