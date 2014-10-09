punymce.plugins.ForceBlocks = function(ed) {
	var Event = punymce.Event, DOM, t = this;
	var isIE, isGecko, isOpera, isWebKit;
	var each, extend, st;

	isIE = punymce.isIE;
	isGecko = punymce.isGecko;
	isOpera = punymce.isOpera;
	isWebKit = punymce.isWebKit;
	each = punymce.each;
	extend = punymce.extend;

	// Default settings
	this.settings = st = extend({
		element : 'P'
	}, ed.settings.forceblocks);

	ed.onPreInit.add(setup, t);

	if (!isIE) {
		ed.onSetContent.add(function(ed, o) {
			if (o.format == 'html')
				o.content = o.content.replace(/<p>[\s\u00a0]+<\/p>/g, '<p><br /></p>');
		});
	}

	ed.onPostProcess.add(function(ed, o) {
		o.content = o.content.replace(/<p><\/p>/g, '<p>\u00a0</p>');
	});

	function setup() {
		DOM = ed.dom;

		// Force root blocks when typing and when getting output
		Event.add(ed.getDoc(), 'keyup', forceRoots);
		ed.onPreProcess.add(forceRoots);

		if (!isIE) {
			ed.onPreProcess.add(function(ed, o) {
				each(o.node.getElementsByTagName('br'), function(n) {
					var p = n.parentNode;

					if (p && p.nodeName == 'p' && (p.childNodes.length == 1 || p.lastChild == n)) {
						p.replaceChild(ed.getDoc().createTextNode('\u00a0'), n);
					}
				});
			});

			Event.add(ed.getDoc(), 'keypress', function(e) {
				if (e.keyCode == 13 && !e.shiftKey) {
					if (!insertPara(e))
						return Event.cancel(e);
				}
			});

			if (isGecko) {
				Event.add(ed.getDoc(), 'keydown', function(e) {
					if ((e.keyCode == 8 || e.keyCode == 46) && !e.shiftKey)
						backspaceDelete(e, e.keyCode == 8);
				});
			}
		}
	};

	function find(n, t, s) {
		var w = ed.getDoc().createTreeWalker(n, 4, null, false), n, c = -1;

		while (n = w.nextNode()) {
			c++;

			// Index by node
			if (t == 0 && n == s)
				return c;

			// Node by index
			if (t == 1 && c == s)
				return n;
		}

		return -1;
	};

	function forceRoots() {
		var b = ed.getBody(), d = ed.getDoc(), se = ed.selection, s = se.getSel(), r = se.getRng(), si = -2, ei, so, eo, tr, c = -0xFFFFFF;
		var ne = b.firstChild, nx, bl, bp, sp, le;

		// Wrap non blocks into blocks
		while (ne) {
			nx = ne.nextSibling;

			// Is text or non block element
			if (ne.nodeType == 3 || !isBlock(ne)) {
				if (!bl) {
					// Create new block but ignore whitespace
					if (!(ne.nodeType == 3 && /^\s+$/.test(ne.nodeValue))) {
						// Store selection
						if (si == -2 && r) {
							if (!isIE) {
								so = r.startOffset;
								eo = r.endOffset;
								si = find(b, 0, r.startContainer);
								ei = find(b, 0, r.endContainer);
							} else if (r.duplicate) {
								tr = b.createTextRange();
								tr.moveToElementText(b);
								tr.collapse(1);
								bp = tr.move('character', c) * -1;

								tr = r.duplicate();
								tr.collapse(1);
								sp = tr.move('character', c) * -1;

								tr = r.duplicate();
								tr.collapse(0);
								le = (tr.move('character', c) * -1) - sp;

								si = sp - bp;
								ei = le;
							}
						}

						bl = d.createElement(st.element);
						bl.appendChild(ne.cloneNode(1));
						b.replaceChild(bl, ne);
					}
				} else
					bl.appendChild(ne);
			} else
				bl = null; // Time to create new block

			ne = nx;
		}

		// Restore selection
		if (si != -2 && !punymce.isOldWebKit) {
			if (!isIE) {
				bl = d.getElementsByTagName(st.element)[0];
				r = d.createRange();

				// Select last location or generated block
				if (si != -1)
					r.setStart(find(b, 1, si), so);
				else
					r.setStart(bl, 0);

				// Select last location or generated block
				if (ei != -1)
					r.setEnd(find(b, 1, ei), eo);
				else
					r.setEnd(bl, 0);

				s.removeAllRanges();
				s.addRange(r);
			} else {
				try {
					r = s.createRange();
					r.moveToElementText(b);
					r.collapse(1);
					r.moveStart('character', si);
					r.moveEnd('character', ei);
					r.select();
				} catch (ex) {
					// Ignore
				}
			}
		}
	};

	function isBlock(n) {
		return n.nodeType == 1 && /^(H[1-6]|P|DIV|ADDRESS|PRE|FORM|TABLE|LI|OL|UL|TD|CODE|CAPTION|BLOCKQUOTE|CENTER|DL|DT|DD|DIR|FIELDSET|NOSCRIPT|NOFRAMES|MENU|ISINDEX|SAMP)$/.test(n.nodeName);
	};

	function getParentBlock(n) {
		return DOM.getParent(n, function(n) {
			return isBlock(n);
		});
	};

	function isEmpty(n) {
		n = n.innerHTML;
		n = n.replace(/<img|hr|table/g, 'd'); // Keep these
		n = n.replace(/<[^>]+>/g, ''); // Remove all tags

		return n.replace(/[ \t\r\n]+/g, '') == '';
	};

	function insertPara(e) {
		var d = ed.getDoc(), s = ed.selection.getSel(), r = ed.selection.getRng(), b = d.body;
		var rb, ra, dir, sn, so, en, eo, sb, eb, bn, bef, aft, sc, ec, n;

		// Check if Safari 2.x
		if (punymce.isOldWebKit)
			return true;

		// Setup before range
		rb = d.createRange();
		rb.setStart(s.anchorNode, s.anchorOffset);
		rb.collapse(true);

		// Setup after range
		ra = d.createRange();
		ra.setStart(s.focusNode, s.focusOffset);
		ra.collapse(true);

		// Setup start/end points
		dir = rb.compareBoundaryPoints(rb.START_TO_END, ra) < 0;
		sn = dir ? s.anchorNode : s.focusNode;
		so = dir ? s.anchorOffset : s.focusOffset;
		en = dir ? s.focusNode : s.anchorNode;
		eo = dir ? s.focusOffset : s.anchorOffset;

		// Never use body as start or end node
		sn = sn.nodeName == "BODY" ? sn.firstChild : sn;
		en = en.nodeName == "BODY" ? en.firstChild : en;

		// Get start and end blocks
		sb = getParentBlock(sn);
		eb = getParentBlock(en);
		bn = sb ? sb.nodeName : st.element; // Get block name to create

		// Return inside list use default browser behavior
		if (DOM.getParent(sb, function(n) { return /OL|UL/.test(n.nodeName); }))
			return true;

		// If caption or absolute layers then always generate new blocks within
		if (sb && (sb.nodeName == 'CAPTION' || /absolute|relative|static/gi.test(sb.style.position))) {
			bn = st.element;
			sb = null;
		}

		// If caption or absolute layers then always generate new blocks within
		if (eb && (eb.nodeName == 'CAPTION' || /absolute|relative|static/gi.test(eb.style.position))) {
			bn = st.element;
			eb = null;
		}

		// Use P instead
		if (/(TD|TABLE|TH|CAPTION)/.test(bn) || (sb && bn == "DIV" && /left|right/gi.test(sb.style.cssFloat))) {
			bn = st.element;
			sb = eb = null;
		}

		// Setup new before and after blocks
		bef = (sb && sb.nodeName == bn) ? sb.cloneNode(0) : d.createElement(bn);
		aft = (eb && eb.nodeName == bn) ? eb.cloneNode(0) : d.createElement(bn);

		// Remove id from after clone
		aft.id = '';

		// Is header and cursor is at the end, then force paragraph under
		if (/^(H[1-6])$/.test(bn) && sn.nodeValue && so == sn.nodeValue.length)
			aft = d.createElement(st.element);

		// Find start chop node
		n = sc = sn;
		do {
			if (n == b || n.nodeType == 9 || isBlock(n) || /(TD|TABLE|TH|CAPTION)/.test(n.nodeName))
				break;

			sc = n;
		} while ((n = n.previousSibling ? n.previousSibling : n.parentNode));

		// Find end chop node
		n = ec = en;
		do {
			if (n == b || n.nodeType == 9 || isBlock(n) || /(TD|TABLE|TH|CAPTION)/.test(n.nodeName))
				break;

			ec = n;
		} while ((n = n.nextSibling ? n.nextSibling : n.parentNode));

		// Place first chop part into before block element
		if (sc.nodeName == bn)
			rb.setStart(sc, 0);
		else
			rb.setStartBefore(sc);

		rb.setEnd(sn, so);
		bef.appendChild(rb.cloneContents());

		// Place secnd chop part within new block element
		ra.setEndAfter(ec);
		ra.setStart(en, eo);
		aft.appendChild(ra.cloneContents());

		// Create range around everything
		r = d.createRange();
		if (!sc.previousSibling && sc.parentNode.nodeName == bn) {
			r.setStartBefore(sc.parentNode);
		} else {
			if (rb.startContainer.nodeName == bn && rb.startOffset == 0)
				r.setStartBefore(rb.startContainer);
			else
				r.setStart(rb.startContainer, rb.startOffset);
		}

		if (!ec.nextSibling && ec.parentNode.nodeName == bn)
			r.setEndAfter(ec.parentNode);
		else
			r.setEnd(ra.endContainer, ra.endOffset);

		// Delete and replace it with new block elements
		r.deleteContents();

		// Never wrap blocks in blocks
		if (bef.firstChild && bef.firstChild.nodeName == bn)
			bef.innerHTML = bef.firstChild.innerHTML;

		if (aft.firstChild && aft.firstChild.nodeName == bn)
			aft.innerHTML = aft.firstChild.innerHTML;

		// Padd empty blocks
		if (isEmpty(bef))
			bef.innerHTML = isOpera ? ' <br />' : '<br />'; // Extra space for Opera

		if (isEmpty(aft))
			aft.innerHTML = isOpera ? ' <br />' : '<br />'; // Extra space for Opera

		// Opera needs this one backwards
		if (isOpera) {
			r.insertNode(bef);
			r.insertNode(aft);
		} else {
			r.insertNode(aft);
			r.insertNode(bef);
		}

		// Normalize
		aft.normalize();
		bef.normalize();

		// Move cursor and scroll into view
		r = d.createRange();
		r.selectNodeContents(aft);
		r.collapse(1);
		s.removeAllRanges();
		s.addRange(r);
		aft.scrollIntoView(0);

		return false;
	};

	function backspaceDelete(e, bs) {
		var b = ed.getBody(), n, se = ed.selection, r = se.getRng(), sc = r.startContainer, n, w, tn;

		// The caret sometimes gets stuck in Gecko if you delete empty paragraphs
		// This workaround removes the element by hand and moves the caret to the previous element
		if (sc && isBlock(sc) && !/^(TD|TH)$/.test(sc.nodeName) && bs) {
			if (sc.childNodes.length == 0 || (sc.childNodes.length == 1 && sc.firstChild.nodeName == 'BR')) {
				// Find previous block element
				n = sc;
				while ((n = n.previousSibling) && !isBlock(n)) ;

				if (n) {
					if (sc != b.firstChild) {
						// Find last text node
						w = ed.getDoc().createTreeWalker(n, NodeFilter.SHOW_TEXT, null, false);
						while (tn = w.nextNode())
							n = tn;

						// Place caret at the end of last text node
						r = ed.getDoc().createRange();
						r.setStart(n, n.nodeValue ? n.nodeValue.length : 0);
						r.setEnd(n, n.nodeValue ? n.nodeValue.length : 0);
						se.getSel().removeAllRanges();
						se.getSel().addRange(r);

						// Remove the target container
						sc.parentNode.removeChild(sc);
					}

					return Event.cancel(e);
				}
			}
		}

		// Gecko generates BR elements here and there, we don't like those so lets remove them
		function handler(e) {
			var pr;

			e = e.target;

			// A new BR was created in a block element, remove it
			if (e && e.parentNode && e.nodeName == 'BR' && (n = getParentBlock(e))) {
				pr = e.previousSibling;

				Event.remove(b, 'DOMNodeInserted', handler);

				// Is there whitespace at the end of the node before then we might need the pesky BR
				// to place the caret at a correct location see bug: #2013943
				if (pr && pr.nodeType == 3 && /\s+$/.test(pr.nodeValue))
					return;

				// Only remove BR elements that got inserted in the middle of the text
				if (e.previousSibling || e.nextSibling)
					e.parentNode.removeChild(e);
			}
		};

		// Listen for new nodes
		Event._add(b, 'DOMNodeInserted', handler);

		// Remove listener
		window.setTimeout(function() {
			Event._remove(b, 'DOMNodeInserted', handler);
		}, 1);
	};
};
