/**
 * The functions in this script are intended for initiating the 
 * dashboard. Only the user_id is required.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method is used to send the userId to the dashboard service
 * in order to pull that user's mailboxes.
 */
function initDashboard() {
	initDashboardAjax('start_dashboard', userId);
}