/**
 * All methods are used to initialize the mailbox processs.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method is used to either display a warning message to the user
 * to select a particular mailbox, or display that the system is loading
 * the vizualization. It then calls to the mailbox service with the mailbox
 * id in order to grab the necessary data from the database.
 */
function initMailbox() {
	// -1 is used to check for clicking the mailbox nav link with no mailbox selected
	if(mailboxId === -1) {
		$('.mailbox-info')
			.append('<h1 class="alert alert-danger">' +
				'<i class="icon-fix fa fa-exclamation-triangle"></i>Please select one of your mailboxes.<br />' +
				'Redirecting...</h1>'
			);
		
		setTimeout(function() {
			window.location.href = 'dashboard.php';
		}, 2000);
	}
	else {
		$('.loading-info').text("Loading visualization ... please wait");
		initMailboxAjax('start_mailbox', userId + '|' + mailboxId);
	}
}