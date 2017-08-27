/**
 * All methods are used to initialize the file upload processs.
 *
 * @author Robb Krasnow
 * @version 1.0
 */


/**
 * This method initializes the file upload process. It will first check
 * if the browser is capable of using the File API and display appropriate
 * errors before the file is even uploaded to the server. It will check for
 * file size limits as well as file extenstions. Once the file passes all
 * validation, AJAX sends the file to the server.
 */
function initUpload() {
	var successString,
		errorString,
		progress,
		file;

	successString = $('.success-box');
	errorString	= $('.error-box');
	progress = $('.progress');
	file = $('#file');

	progress.hide();
	successString.css({ display: 'none' });
	errorString.css({ display: 'none' });

	$('button[type="submit"]').prop('disabled', true);

	// Check if browser is capable of File API
	if(window.File && window.FileReader && window.FileList && window.Blob) {
		file.bind('change', function() {
			var fileSize = this.files[0].size;
			var fileExt = file.val().split('.').pop().toLowerCase();

			// Check if file size is larger than 128 MB (134217728 bytes)
			// 4096 MB (4294967296 bytes)
			if(fileSize > 4294967296) {
				$('button[type="submit"]').prop('disabled', true);
				errorString.empty();
				errorString.append('<i class="icon-fix fa fa-exclamation-triangle"></i>Exceeded file size limit.');
				errorString.fadeIn(200);

				return false;
			}
			
			// Check if file is a .mbox file
			if(fileExt !== 'mbox') {
				$('button[type="submit"]').prop('disabled', true);
				errorString.empty();
				errorString.append('<i class="icon-fix fa fa-exclamation-triangle"></i>Invalid file format. Please add <span class="italics">.mbox</span> extention if it\'s missing.');
				errorString.fadeIn(200);

				return false;
			}

			errorString.css({ display: 'none' });
			$('button[type="submit"]').prop('disabled', false);
		});
	}
	else {
		errorString.empty();
		errorString.append('<i class="icon-fix fa fa-exclamation-triangle"></i>Please upgrade your browser');
		errorString.fadeIn(200);
	}

	// When the Upload button is clicked, make an AJAX request to
	// send the file to the server.
	// @see https://www.developphp.com/video/JavaScript/File-Upload-Progress-Bar-Meter-Tutorial-Ajax-PHP
	$('#upload-form').submit(function(e) {
		e.preventDefault();

		var file,
			formData;

		file = document.getElementById('file').files[0];
		formData = new FormData();
		formData.append('file', file);

		// Make AJAX call to the uploader script
		$.ajax({
			type: 'POST',
			url: 'uploader.php',
			data: formData,
			dataType: 'json',
			async: true,
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var xhr = $.ajaxSettings.xhr();

				// Follow upload progress
				xhr.upload.onprogress = function(e) {
					var progress,
						progressStatus,
						progressBar,
						percent;

					progress = $('.progress');
					progressStatus = $('.progress-status');
					progressBar = $('.progress-bar');
					percent = Math.floor(e.loaded / e.total * 100);

					progress.show();
					progressStatus.text(percent + '% uploaded ... please wait');
					progressBar.css('width', percent + '%')
						.attr('aria-valuenow', percent);
				};

				return xhr;
			},
			success: function(result) {
				var successString,
					errorString,
					progressStatus,
					progress;

				successString = $('.success-box');
				errorString	= $('.error-box');
				progressStatus = $('.progress-status');
				progress = $('.progress');

				progressStatus.text('Upload Complete');

				if(result.error) {
					progressStatus.hide();
					progress.hide();
					errorString.empty()
						.append('<i class="icon-fix fa fa-exclamation-triangle"></i>' + result.error.msg)
						.fadeIn(200);
				}
				else if(result.success) {
					setTimeout(function() {
						progressStatus.text('Parsing file ... please wait');
					}, 1000);

					// Start the upload/parsing process
					initUploadAjax('start_upload',
						userId + '|' +
						result.success.file_hash + '|' +
						result.success.file_name + '|' +
						result.success.file_type + '|' +
						result.success.file_size
					);

					// Anonymous function to continually ask for the parsing status from the server
					// for the particular file that was just uploaded
					(function getParse() {
						initGetParseStatusAjax('start_get_parse_status',
							userId + '|' +
							result.success.file_hash
						);
						
						// Set interval loop for every 1 second
						setTimeout(getParse, 1000);
					})();
				}
			},
			error: function(err) {
				var errorString,
					progressStatus,
					progress;

				errorString = $('.error-box');
				progressStatus = $('.progress-status');
				progress = $('.progress');

				progressStatus.hide();
				progress.hide();
				errorString.empty()
					.append('<i class="icon-fix fa fa-exclamation-triangle"></i>Error uploading file. Please try again.')
					.fadeIn(200);
			}
		});
	});
}