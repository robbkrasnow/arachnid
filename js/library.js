/**
 * All methods in this library serve as helper methods to various other scripts
 * needed throughout the app. A personally written plugin for form validation
 * is included, but it is not used after finding Validate.js which has many
 * more options for validation.
 * 
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method is a helper to display all the message in an
 * accordion style when a node or link is double-clicked from
 * the visualization.
 */
function messagesMenu() {
    $(document).ready(function(){
        var accordionMenu = $('.accordion-menu');

        if(accordionMenu.length > 0 ) {
            accordionMenu.each(function(){
                var accordion = $(this);
                //detect change in the input[type="checkbox"] value
                accordion.on('change', 'input[type="checkbox"]', function() {
                    var checkbox = $(this);
                    (checkbox.prop('checked') ) ? checkbox.siblings('ul').attr('style', 'display:none;').slideDown(300) : checkbox.siblings('ul').attr('style', 'display:block;').slideUp(300);
                });
            });
        }
    });
}


/**
 * Timer and countdown method used for calculating the current time to be used for viewing
 * when called.
 * 
 * @param time The amount of time you want the timer to run for (basically a delay)
 * @param update The updated time
 * @param complete The method you want to run when the timer is completed
 * @see http://stackoverflow.com/questions/1191865/code-for-a-simple-javascript-countdown-timer
 */
function timer(time, update, complete) {
    var start = new Date().getTime();
    var interval = setInterval(function() {
        var now = time - (new Date().getTime() - start);
        
        if(now <= 0) {
            clearInterval(interval);
            complete();
        }
        else update(Math.floor(now/1000));
    }, 100); // The smaller this number, the more accurate the timer will be
}


/**
 * jQuery Plugin used to listen directly for "enter/return" keypress events
 *
 * @see http://stackoverflow.com/questions/302122/jquery-event-keypress-which-key-was-pressed
 */
(function ($) {
    $.prototype.enterPressed = function (fn) {
        $(this).keyup(function(e) {
            if ((e.keyCode || e.which) == 13) {
                fn();
            }
        });
    };
}(jQuery || {}));


/**
 * This method was found and is used to grab input from a form and serialize it.
 * It will basically turn the data into an object to be used for passing to the
 * server.
 *
 * @see http://stackoverflow.com/questions/1184624/convert-form-data-to-js-object-with-jquery
 */
$.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};