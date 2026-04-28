/**
 * Gallery SVN ID:
 * $Id: progressbar.js 17864 2008-08-17 15:24:23Z JensT $
*/

function updateProgressBar(htmlId, status, percentDone) {
	var progressDescription	= document.getElementById('progressDescription_' + htmlId);
	var progressBarDone	= document.getElementById('progressBarDone_' + htmlId);

	if (status != '-1') {
		progressDescription.innerHTML = status;
	}

	if(percentDone >= 0) {
		progressBarDone.style.width = percentDone + "%";
	}
}

function addProgressBarText(htmlId, text) {
	var progressAddText = document.getElementById('progressAddText_' + htmlId);

	progressAddText.innerHTML = progressAddText.innerHTML + text;
}
