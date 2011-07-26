punymce.plugins.Safari2x = function(ed) {
	var each = punymce.each, Event = punymce.Event, html, setContent, collapse;

	// Is Safari 2.x
	if (!punymce.isOldWebKit)
		return;

	// Fake range on Safari 2.x
	ed.selection.getRng = function() {
		var t = this, s = t.getSel(), d = ed.getDoc(), r, rb, ra, di;

		// Fake range on Safari 2.x
		if (s.anchorNode) {
			r = d.createRange();

			try {
				// Setup before range
				rb = d.createRange();
				rb.setStart(s.anchorNode, s.anchorOffset);
				rb.collapse(1);

				// Setup after range
				ra = d.createRange();
				ra.setStart(s.focusNode, s.focusOffset);
				ra.collapse(1);

				// Setup start/end points by comparing locations
				di = rb.compareBoundaryPoints(rb.START_TO_END, ra) < 0;
				r.setStart(di ? s.anchorNode : s.focusNode, di ? s.anchorOffset : s.focusOffset);
				r.setEnd(di ? s.focusNode : s.anchorNode, di ? s.focusOffset : s.anchorOffset);
			} catch (ex) {
				// Sometimes fails, at least we tried to do it by the book. I hope Safari 2.x will go away soooon
			}
		}

		return r;
	};

	// Fix setContent so it works
	setContent = ed.selection.setContent;
	ed.selection.setContent = function(h, s) {
		var r = this.getRng();

		try {
			setContent.call(this, h, s);
		} catch (ex) {
			// Workaround for Safari 2.x
			b = ed.dom.create('body');
			b.innerHTML = h;

			each(b.childNodes, function(n) {
				r.insertNode(n.cloneNode(true));
			});
		}
	};

	collapse = ed.selection.collapse;
	ed.selection.collapse = function(b) {
		try {
			collapse.call(this, b);
		} catch (ex) {
			// Safari 2.x might fail to collapse
		}
	};

	// Resize is not supported too buggy
	ed.onInit.add(function() {
		punymce.DOM.get(ed.settings.id + '_r').style.display = 'none';
	});

	ed.onPreInit.add(function() {
		Event.add(ed.getDoc(), 'click', function(e) {
			if (e.target.nodeName == "A") {
				ed.selection.select(e.target);
				return Event.cancel(e);
			}
		});

		Event.add(ed.getDoc(), 'keydown', function(e) {
			var s = ed.selection, n, o, r, c;

			if (e.charCode > 32 || e.keyCode == 13) {
				n = ed.dom.getParent(s.getNode(), function(n) {return n.nodeName == 'LI';});

				if (n) {
					o = s.getRng().startOffset;

					// Create new LI on enter
					if (e.keyCode == 13) {
						// Empty list item
						if (!n.hasChildNodes()) {
							// At end of list then use default behavior
							if (!n.nextSibling || n.nextSibling.nodeName != 'LI') {
								r = ed.dom.getParent(s.getNode(), function(n) {return /(UL|OL)/.test(n.nodeName);});
								n.parentNode.removeChild(n);
								s.select(r.nextSibling);
								return;
							}

							// Cancel if in middle of list
							return Event.cancel(e);
						}

						// Insert temp character
						c = ed.getDoc().createTextNode('\u00a0');
						n.appendChild(c);
						//s.getSel().setBaseAndExtent(c, 0, c, 0);
						window.setTimeout(function() {
							var n = s.getNode();

							if (n.firstChild && n.firstChild.nodeValue.charAt(0) == '\u00a0') {
								n.removeChild(n.firstChild);
								s.select(n);
							}
						}, 1);
					} else {
						// Get char and check if it's alpha numeric
						c = String.fromCharCode(e.charCode);
						if (!/^\w$/.test(c))
							return;

						s.setContent(c);
						r = s.getRng();
						s = s.getSel();
						n = s.anchorNode;

						if (n.nodeName == 'LI') {
							s.setBaseAndExtent(n, 1, n, 1);
						} else {
							n = n.nextSibling;
							s.setBaseAndExtent(n, 1, n, 1);
						}

						return Event.cancel(e);
					}
				}
			}
		});
	});

	function wrap(n, a) {
		var d = ed.getDoc(), r, s;

		a = a || {};

		d.execCommand('FontName', false, '_tmp');

		each(ed.dom.select('span'), function(e) {
			if (e.style.fontFamily == '_tmp') {
				r = rename(e, n, a);

				if (!s)
					s = r;
			}
		});

		// Select
		r = r.firstChild;
		ed.selection.getSel().setBaseAndExtent(s, 0, r, r.nodeValue.length);
	};

	function insertList(n) {
		var s = ed.selection, li;

		s.setContent('<' + n + '><li id="_tmp"></li></' + n + '>');
		li = ed.dom.get('_tmp');
		li.id = '';

		s.select(li);
		s.collapse(1);
	};

	function getParentBlock(n) {
		return ed.dom.getParent(n, function(n) {
			return /^(H[1-6]|P|DIV|ADDRESS|PRE|FORM|TABLE|LI|OL|UL|TD|CODE|CAPTION|BLOCKQUOTE|CENTER|DL|DT|DD|DIR|FIELDSET|NOSCRIPT|NOFRAMES|MENU|ISINDEX|SAMP)$/.test(n.nodeName);
		});
	};

	function rename(e, n, a) {
		var d = ed.getDoc(), r;

		a = a || {};
		r = d.createElement(n);

		// Copy attributes
		each(e.attributes, function(n) {
			if (n.specified && n.nodeValue)
				r.setAttribute(n.nodeName, n.nodeValue);
		});

		// Add attributes
		each(a, function(v, k) {
			r.setAttribute(k, v);
		});

		// Add children
		each(e.childNodes, function(n) {
			r.appendChild(n.cloneNode(true));
		});

		// Replace old node
		e.parentNode.replaceChild(r, e);

		return r;
	};

	// Fake commands
	punymce.extend(ed.commands, {
		IncreaseFontSize : function() {
			var d = ed.getDoc(), v = parseInt(d.queryCommandValue('FontSize'));

			d.execCommand('FontSize', false, (v + 1) + 'px');
		},

		DecreaseFontSize : function() {
			var d = ed.getDoc(), v = parseInt(d.queryCommandValue('FontSize'));

			if (v > 0)
				d.execCommand('FontSize', false, (v - 1) + 'px');
		},

		Strikethrough : function() {
			wrap('strike');
		},

		CreateLink : function(u, v) {
			wrap('a', {href : v, mce_href : v});
		},

		Unlink : function() {
			var s = ed.selection;

			s.setContent(s.getContent().replace(/(<a[^>]+>|<\/a>)/, ''));
		},

		RemoveFormat : function() {
			var s = ed.selection;

			s.setContent(s.getContent().replace(/(<(span|b|i|strong|em|strike) [^>]+>|<(span|b|i|strong|em|strike)>|<\/(span|b|i|strong|em|strike)>|)/g, ''));
		},

		FormatBlock : function(u, v) {
			var s = ed.selection, n;

			n = getParentBlock(ed.selection.getNode());

			if (n)
				r = rename(n, v.replace(/<|>/g, ''));

			s.select(r);
			s.collapse(1);
		},

		InsertUnorderedList : function() {
			insertList('ul');
		},

		InsertOrderedList : function() {
			insertList('ol');
		},

		Indent : function() {
			var n = getParentBlock(ed.selection.getNode()), v;

			if (n) {
				v = parseInt(n.style.paddingLeft) || 0;
				n.style.paddingLeft = (v + 10) + 'px';
			}
		},

		Outdent : function() {
			var n = getParentBlock(ed.selection.getNode()), v;

			if (n) {
				v = parseInt(n.style.paddingLeft) || 0;

				if (v >= 10)
					n.style.paddingLeft = (v - 10) + 'px';
			}
		}
	});
};
