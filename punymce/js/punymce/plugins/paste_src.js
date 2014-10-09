punymce.plugins.Paste = function(ed) {
	ed.onPaste = new punymce.Dispatcher(ed);

	ed.onInit.add(function() {
		// Add paste event
		punymce.Event.add(ed.getBody(), 'paste', function(e) {
			ed.onPaste.dispatch(e);
		}, this);

		// Add paste event
		punymce.Event.add(ed.getDoc(), 'keydown', function(e) {
			// Fake onpaste event in Opera and Gecko (ctrl+v or shift+insert)
			if ((e.ctrlKey && e.keyCode == 86) || (e.shiftKey && e.keyCode == 45)) {
				setTimeout(function() {
					ed.onPaste.dispatch(e);
				}, 10);
			}
		}, this);
	});
};
