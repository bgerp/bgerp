punymce.plugins.EditSource = function(ed) {
	var DOM = punymce.DOM, extend = punymce.extend, each = punymce.each, isWebKit = punymce.isWebKit;
	var sourceView = 0;

	if (!ed.settings.editsource || ed.settings.editsource.skip_css)
		DOM.loadCSS(punymce.baseURL + '/plugins/editsource/css/editor.css');

	// Add commands
	extend(ed.commands, {
		mceEditSource : function(u, v, e) {
			var ta, ifr = ed.getIfr(), id = ed.settings.id, w = ed.width, h = ed.height, f, but = DOM.get(id + '_editsource');

			// Enable source view
			if (!sourceView) {
				DOM.addClass(but, 'active');

				// Disable all buttons
				each(DOM.select('li', id + '_c'), function(n) {
					if (n != but)
						DOM.addClass(n, 'disabled');
				});

				// Hide iframe and view textarea
				ta = DOM.add(ifr.parentNode, 'textarea', {id : id + '_editsourcearea', 'class' : 'editsource', style : 'width:' + w + 'px;height:' + h + 'px;'});
				ta.value = ed.getContent({save : true});
				ta.focus();

				// A spacer element was need since IE 6/7 produces bugs if the container is sized
				// Iframe needs to be hidden IE and FF so that the designMode caret doesn't get shown
				// And on Safari 2.x hiding an iframe will break the iframe
				if (!isWebKit) {
					DOM.add(ifr.parentNode, 'div', {id : id + '_edspacer', 'class' : 'spacer', style : 'width:' + w + 'px;height:' + h + 'px;'});
					ifr.style.display = 'none';
				}

				sourceView = 1;
				return false;
			}

			// Disable source view
			sourceView = 0;
			DOM.removeClass(but, 'active');

			// Show iframe and remove spacer
			if (!isWebKit) {
				ifr.style.display = 'block';
				ta = DOM.get(id + '_edspacer');
				ta.parentNode.removeChild(ta);
			}

			// Remove textarea and set contents
			ta = DOM.get(id + '_editsourcearea');
			ed.setContent(ta.value, {load : true});
			ta.parentNode.removeChild(ta);

			// Enable all buttons
			each(DOM.select('li', id + '_c'), function(n) {
				DOM.removeClass(n, 'disabled');
			});

			return false;
		}
	});

	// Add tools
	extend(ed.tools, {
		editsource : {cmd : 'mceEditSource', title : punymce.I18n.editsource}
	});
};

// English i18n strings
punymce.extend(punymce.I18n, {
	editsource : 'Edit HTML source'
});
