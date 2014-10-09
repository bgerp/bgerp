punymce.plugins.Emoticons = function(ed) {
	var Event = punymce.Event, each, extend, isIE, isGecko, st, emoReg, DOM, h;

	each = punymce.each;
	extend = punymce.extend;
	isIE = punymce.isIE;
	isGecko = punymce.isGecko;
	DOM = punymce.DOM;

	// Default settings
	this.settings = st = extend({
		emoticons : {
			happy : [':)', '=)'],
			unhappy : [':|', '=|'],
			sad : [':(','=('],
			grin : [':D', '=D'],
			surprised : [':o',':O','=o', '=O'],
			wink : [';)'],
			halfhappy : [':/', '=/'],
			tounge : [':P', ':p', '=P', '=p'],
			lol : [],
			mad : [],
			rolleyes : [],
			cool : []
		},
		row_length : 4,
		trans_img : punymce.baseURL + 'plugins/emoticons/img/trans.gif',
		skip_css : 0,
		auto_convert : 1
	}, ed.settings.emoticons);

	if (!st.skip_css)
		DOM.loadCSS(punymce.baseURL + '/plugins/emoticons/css/editor.css');

	// Build regexp from emoticons
	emoReg = '';
	each(st.emoticons, function(v) {
		each(v, function(v) {
			if (emoReg.length != 0)
				emoReg += '|';

			emoReg += v.replace(/([^a-zA-Z0-9])/g, '\\$1');
		});
	});

	emoReg = new RegExp(emoReg, 'g');

	// Add commands
	extend(ed.commands, {
		mceEmoticons : function(u, v, e) {
			var n, t = this, id = ed.settings.id, p = DOM.getPos(e.target), co, cb;

			if (ed.hideMenu)
				return ed.hideMenu();

			function hide(e) {
				ed.hideMenu = null;
				Event.remove(document, 'click', hide);
				Event.remove(ed.getDoc(), 'click', hide);
				DOM.get(id + '_memoticons').style.display = 'none';
				return 1;
			};

			n = DOM.get(id + '_memoticons');
			if (!n) {
				n = DOM.get(id + '_t');
				n = DOM.add(document.body, 'div', {id : id + '_memoticons', 'class' : 'punymce_emoticons punymce'});
				n = DOM.add(n, 'table', {'class' : 'punymce'});
				n = DOM.add(n, 'tbody');
				co = st.row_length;
				each(st.emoticons, function(c, k) {
					if (co == st.row_length) {
						r = DOM.add(n, 'tr');
						co = 0;
					}

					co++;

					Event.add(DOM.add(DOM.add(r, 'td'), 'a', {href : '#', 'class' : 'emoticon ' + k}), 'mousedown', function(e) {
						hide.call(t);

						ed.selection.setNode(ed.dom.create('img', { title : c[0] || k, src : st.trans_img, 'class' : 'emoticon ' + k }));

						return Event.cancel(e);
					});
				});
			}

			Event.add(document, 'click', hide, t);
			Event.add(ed.getDoc(), 'click', hide, t);
			ed.hideMenu = hide;

			s = DOM.get(id + '_memoticons').style;
			s.left = p.x + 'px';
			s.top = (p.y + e.target.clientHeight + 2) + 'px';
			s.display = 'block';
		}
	});

	function find(e) {
		var c;

		each(st.emoticons, function(v, k) {
			each(v, function(v) {
				if (v == e) {
					c = k;
					return false;
				}
			});

			return !c;
		});

		return c;
	};

	ed.onPreProcess.add(function(se, o) {
		var nl = o.node.getElementsByTagName('img'), a = [];

		each(nl, function(n) {
			a.push(n);
		});

		each(a, function(n) {
			var c = ed.dom.getAttr(n, 'class');

			if (c && c.indexOf('emoticon') != -1) {
				n.parentNode.replaceChild(ed.getDoc().createTextNode(n.getAttribute('title')), n);
			}
		});
	});

	ed.onSetContent.add(function(ed, o) {
		var ar = [];

		// Store away all tags
		h = o.content.replace(/<\/?[^>]+>/g, function(a) {
			return a.replace(emoReg, function(a) {
				var c = find(a);

				if (c) {
					ar.push(a);
					return '¤' + ar.length + '¤';
				}

				return a;
			});
		});

		// Replace emoticons in remaining text nodes
		h = h.replace(emoReg, function(a) {
			return '<img src="' + st.trans_img + '" title="' + a + '" class="emoticon ' + find(a) + '" />';
		});

		// Restore attribs
		h = h.replace(/¤([^¤]+)¤/g, function(a, b) {
			return ar[parseInt(b) - 1];
		});

		o.content = h;
	});

	ed.onInit.add(function() {
		var DOM = ed.dom;

		if (!st.skip_css)
			DOM.loadCSS(punymce.baseURL + '/plugins/emoticons/css/content.css');

		// Disable selection on emoticons in IE
		Event.add(ed.getDoc(), 'controlselect', function(e) {
			if (DOM.getAttr(e.target, 'class').indexOf('emoticon') != -1)
				return Event.cancel(e);
		});

		if (isGecko) {
			// Disable selection of emoticons in Gecko
			Event.add(ed.getDoc(), 'mousedown', function(e) {
				if (DOM.getAttr(e.target, 'class').indexOf('emoticon') != -1) {
					ed.getDoc().execCommand('enableObjectResizing', false, false);
					return Event.cancel(e);
				} else
					ed.getDoc().execCommand('enableObjectResizing', false, true);
			});

			// Fix for bug: https://bugzilla.mozilla.org/show_bug.cgi?id=392569
			Event.add(ed.getDoc(), 'keydown', function(e) {
				var s = ed.selection, se = s.getSel(), d = ed.getDoc(), r = s.getRng(), sc, so, n, l;

				sc = r.startContainer;
				so = r.startOffset;

				// Element instead of text just pass it though
				if (sc.nodeType == 1)
					return;

				if (sc) {
					// Right key
					if (e.keyCode == 39 && so == sc.nodeValue.length) {
						n = sc.nextSibling;
						if (n && n.nodeName == 'IMG') {
							n = n.nextSibling;

							r = d.createRange();
							r.setStart(n, 0);
							r.setEnd(n, 0);

							se.removeAllRanges();
							se.addRange(r);

							return Event.cancel(e);
						}
					}

					// Left key
					if (e.keyCode == 37 && so == 0) {
						n = sc.previousSibling;
						if (n && n.nodeName == 'IMG') {
							n = n.previousSibling;
							l = n.nodeValue.length;

							r = d.createRange();
							r.setStart(n, l);
							r.setEnd(n, l);

							se.removeAllRanges();
							se.addRange(r);

							return Event.cancel(e);
						}
					}
				}
			});
		}

		Event.add(ed.getDoc(), 'keypress', function(e) {
			var em, cls, lc, d = ed.getDoc(), se = ed.selection, s = se.getSel(), r = se.getRng(), sn, so, en, eo;

			// Check if Safari 2.x
			if (punymce.isOldWebKit || !st.auto_convert)
				return true;

			if (!isIE) {
				sn = r.startContainer;
				so = r.startOffset;
				en = r.endContainer;
				eo = r.endOffset;

				if (so > 0 && sn.nodeType == 3) {
					r = d.createRange();
					r.setStart(sn, so - 1);
					r.setEnd(en, eo);
					lc = sn.nodeValue[so - 1];
				}
			} else {
				r = r.duplicate();
				r.moveStart('character', -1);
				lc = r.text;
			}

			em = lc + String.fromCharCode(e.charCode || e.keyCode);
			emoReg.lastIndex = 0;

			if (!lc || !emoReg.test(em))
				return;

			if (cls = find(em)) {
				if (!isIE) {
					s.removeAllRanges();
					s.addRange(r);
				} else {
					r = ed.selection.getRng();
					r.moveStart('character', -1);
					r.select();
				}

				ed.selection.setNode(DOM.create('img', { id : 'emoticon', title : em, src : st.trans_img, 'class' : 'emoticon ' + cls }));

				em = DOM.get('emoticon');
				DOM.setAttr(em, 'id', '');
				ed.selection.select(em);
				ed.selection.collapse(0);

				return Event.cancel(e);
			}
		});
	});

	extend(ed.tools, {
		emoticons : {cmd : 'mceEmoticons', title : punymce.I18n.emoticons}
	});
};

// English i18n strings
punymce.extend(punymce.I18n, {
	emoticons : 'Insert emoticon'
});
