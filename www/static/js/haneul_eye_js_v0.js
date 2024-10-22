$(() => {
	let eventPage = $('#tab_04');
	if (eventPage.css('display') == 'block') {
		let target = $('.formContents.submit');
		target.on('click', () => {
			try {
	            Android.makeAndroidEvent();
	        } catch (e) {
	            console.log("no Android class exist : ", e.message);
	        }
		});
	}
});