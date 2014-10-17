var punymce = {};

(function() {
	var DOMUtils, Engine, Control, Editor, Selection, Dispatcher, Event, Serializer, I18n; // Shorten class names
	var pageDOM, isIE, isGecko, isOpera, isWebKit, isOldWebKit, ua; // Global objects

	// Browser checks
	ua = navigator.userAgent;
	punymce.isOpera = isOpera = window['opera'] && opera.buildNumber;
	punymce.isWebKit = isWebKit = /WebKit/.test(ua);
	punymce.isOldWebKit = isOldWebKit = isWebKit && !window.getSelection().getRangeAt;
	punymce.isIE = isIE = !isWebKit && !isOpera && (/MSIE/gi).test(ua) && (/Explorer/gi).test(navigator.appName);
	punymce.isGecko = isGecko = !isWebKit && /Gecko/.test(ua);

	// Plugins namespace
	punymce.plugins = {};

	// Global functions

	function is(o, t) {
		o = typeof(o);

		if (!t)
			return o != 'undefined';

		return o == t;
	};

	function each(o, cb, s) {
		var n;

		if (!o)
			return 0;

		s = !s ? o : s;

		if (is(o.length)) {
			// Indexed arrays, needed for Safari
			for (n=0; n<o.length; n++) {
				if (cb.call(s, o[n], n, o) === false)
					return 0;
			}
		} else {
			// Hashtables
			for (n in o) {
				if (o.hasOwnProperty(n)) {
					if (cb.call(s, o[n], n, o) === false)
						return 0;
				}
			}
		}

		return 1;
	};

	function extend(o, e) {
		each(e, function(v, n) {
			o[n] = v;
		});

		return o;
	};

	// Store them in API as well for plugin use
	extend(punymce, {
		is : is,
		each : each,
		extend : extend
	});

	// English language pack
	punymce.I18n = I18n = {
		bold : 'Bold (Ctrl+B)',
		italic : 'Italic (Ctrl+I)',
		underline : 'Underline (Ctrl+U)',
		strike : 'Striketrough',
		ul : 'Insert unordered list',
		ol : 'Insert ordered list',
		indent : 'Indent',
		outdent : 'Outdent',
		left : 'Align left',
		center : 'Align center',
		right : 'Align right',
		style : 'Font style',
		removeformat : 'Remove format',
		increasefontsize : 'Increase text size',
		decreasefontsize : 'Decrease text size'
	};

	// Static DOM class
	punymce.DOMUtils = DOMUtils = function(d) {
		this.files = [];

		// Fix IE6SP2 flicker
		try {d.execCommand('BackgroundImageCache', 0, 1);} catch (e) {}

		// Public methods
		extend(this, {
			get : function(e) {
				return e && (!e.nodeType && !e.location ? d.getElementById(e) : e);
			},

			add : function(p, n, a, h) {
				var t = this, e;

				e = d.createElement(n);

				each(a, function(v, n) {
					t.setAttr(e, n, v);
				});

				if (h) {
					if (h.nodeType)
						e.appendChild(h);
					else
						e.innerHTML = h;
				}

				return p ? p.appendChild(e) : e;
			},

			create : function(n, a, h) {
				return this.add(0, n, a, h);
			},

			setAttr : function(e, n, v) {
				e = this.get(e);

				if (!e)
					return 0;

				if (n == "style") {
					e.setAttribute('mce_style', v);
					e.style.cssText = v;
				}

				if (n == "class")
					e.className = v;

				if (v != null && v != "")
					e.setAttribute(n, '' + v);
				else
					e.removeAttribute(n);

				return 1;
			},

			getAttr : function(e, n, dv) {
				var v;

				e = this.get(e);

				if (!e)
					return false;

				if (!is(dv))
					dv = "";

				// Try the mce variant for these
				if (/^(src|href|style)$/.test(n)) {
					v = this.getAttr(e, "mce_" + n);

					if (v)
						return v;
				}

				v = e.getAttribute(n, 2);

				if (n == "class" && !v)
					v = e.className;

				if (n == "style" && !v)
					v = e.style.cssText;
				else if (!v) {
					v = e.attributes[n];
					v = v && is(v.nodeValue) ? v.nodeValue : v;
				}

				// Remove Apple and WebKit stuff
				if (isWebKit && n == "class" && v)
					v = v.replace(/(apple|webkit)\-[a-z\-]+/gi, '');

				return (v && v != "") ? '' + v : dv;
			},

			select : function(k, n, f) {
				var o = [];

				n = this.get(n);
				n = !n ? d : n;

				each(k.split(','), function(v) {
					each(n.getElementsByTagName(v), function(v) {
						if (!f || f(v))
							o.push(v);
					});
				});

				return o;
			},

			getPos : function(n, cn) {
				var l = 0, t = 0, p, r, d;

				n = this.get(n);

				// IE specific method (less quirks in IE6)
				if (n && n.getBoundingClientRect) {
					r = n.getBoundingClientRect();
					d = document;
					n = d.compatMode == 'CSS1Compat' ? d.documentElement : d.body;

					return { x : r.left + (n.scrollLeft || 0), y : r.top + (n.scrollTop || 0) };
				}

				while (n && n != cn) {
					l += n.offsetLeft || 0;
					t += n.offsetTop || 0;
					n = n.offsetParent;
				}

				return {x : l, y : t};
			},

			loadCSS : function(u) {
				var t = this;

				if (u) {
					each(u.split(','), function(u) {
						var l = -1;
	
						each(t.files, function(c, i) {
							if (c == u) {
								l = i;
								return false;
							}
						});
	
						if (l != -1)
							return;
	
						t.files.push(u);
	
						if (!d.createStyleSheet)
							t.add(t.select('head')[0], 'link', {rel : 'stylesheet', href : u});
						else
							d.createStyleSheet(u);
					});
				}
			},

			addClass : function(e, c, b) {
				var o;

				e = this.get(e);

				if (!e)
					return 0;

				o = this.removeClass(e, c);

				return e.className = b ? c + (o != '' ? (' ' + o) : '') : (o != '' ? (o + ' ') : '') + c;
			},

			removeClass : function(e, c) {
				e = this.get(e);

				if (!e)
					return 0;

				c = e.className.replace(new RegExp("(^|\\s+)" + c + "(\\s+|$)", "g"), ' ');

				return e.className = c != ' ' ? c : '';
			},

			hasClass : function(n, c) {
				n = this.get(n);

				return new RegExp('\\b' + c + '\\b', 'g').test(n.className);
			},

			getParent : function(n, f, r) {
				while (n) {
					if (n == r)
						return null;

					if (f(n))
						return n;

					n = n.parentNode;
				}

				return null;
			},

			keep : function(h) {
				// Convert strong and em to b and i in FF since it can't handle them
				if (isGecko) {
					h = h.replace(/<(\/?)strong>|<strong( [^>]+)>/gi, '<$1b$2>');
					h = h.replace(/<(\/?)em>|<em( [^>]+)>/gi, '<$1i$2>');
					h = h.replace(/<(\/?)del|<del( [^>]+)>/gi, '<$1strike$2>');
				}

				// Store away src and href in mce_src and mce_href since browsers mess them up
				h = h.replace(/ (src|href|style)=\"([^\"]+)\"/gi, ' $1="$2" mce_$1="$2"');

				return h;
			}
		});
	};

	// Global DOM instance
	punymce.DOM = pageDOM = new DOMUtils(document);

	// Static Event class
	punymce.Event = Event = {
		events : [],
		inits : [],
		unloads : [],

		add : function(o, n, f, s) {
			var cb, t = this, el = t.events;

			o = pageDOM.get(o);

			// Setup event callback
			cb = function(e) {
				e = e || window.event;

				// Patch in target in IE it's W3C valid
				if (e && !e.target && isIE)
					e.target = e.srcElement;

				if (!s)
					return f(e);

				return f.call(s, e);
			};

			if (n == 'unload') {
				t.unloads.push(cb);
				return cb;
			}

			if (n == 'init') {
				if (t._init)
					cb();
				else
					t.inits.push(cb);

				return cb;
			}

			// Store away listener reference
			el.push({
				obj : o,
				name : n,
				func : f,
				cfunc : cb,
				scope : s
			});

			t._add(o, n, cb);

			// Cleanup memory leaks in IE
			if (isIE && el.length == 1)
				Event._add(window, 'unload', t._unload, Event);

			return cb;
		},

		remove : function(o, n, f) {
			var t = this, a = t.events;

			o = pageDOM.get(o);

			each(a, function(e, i) {
				if (e.obj == o && e.name == n && (!f || e.func == f)) {
					a.splice(i, 1);
					t._remove(o, n, e.cfunc);
					return false;
				}
			});
		},

		cancel : function(e) {
			this.stop(e);
			return this.prevent(e);
		},

		stop : function(e) {
			if (e.stopPropagation)
				e.stopPropagation();
			else
				e.cancelBubble = true;

			return false;
		},

		prevent : function(e) {
			if (e.preventDefault)
				e.preventDefault();
			else
				e.returnValue = false;

			return false;
		},

		_unload : function() {
			var t = Event;

			each(t.events, function(e) {
				t._remove(e.obj, e.name, e.cfunc);
			});

			t._remove(window, 'unload', t._unload);
			t.events = [];

			each(t.unloads, function(e) {
				e();
			});
		},

		_add : function(o, n, f) {
			if (o.attachEvent)
				o.attachEvent('on' + n, f);
			else if (o.addEventListener)
				o.addEventListener(n, f, false);
			else
				o['on' + n] = f;
		},

		_remove : function(o, n, f) {
			if (o.detachEvent)
				o.detachEvent('on' + n, f);
			else if (o.removeEventListener)
				o.removeEventListener(n, f, false);
			else
				o['on' + n] = null;
		},

		_pageInit : function() {
			var e = Event;

			e._remove(window, 'DOMContentLoaded', e._pageInit);
			e._init = true;

			each(e.inits, function(c) {
				c();
			});

			e.inits = [];
		},

		_wait : function() {
			var t;

			if (isIE && document.location.protocol != 'https:') {
				// Fake DOMContentLoaded on IE when not running under HTTPs
				document.write('<script id=__ie_onload defer src=javascript:void(0)><\/script>');
				pageDOM.get("__ie_onload").onreadystatechange = function() {
					if (this.readyState == "complete") {
						Event._pageInit();
						pageDOM.get("__ie_onload").onreadystatechange = null; // Prevent leak
					}
				};
			} else {
				Event._add(window, 'DOMContentLoaded', Event._pageInit, Event);

				if (isIE || isWebKit) {
					t = setInterval(function() {
						if (/loaded|complete/.test(document.readyState)) {
							clearInterval(t);
							Event._pageInit();
						}
					}, 10);
				}
			}
		}
	};

	punymce.Dispatcher = Dispatcher = function(ds) {
		var cbl = [];

		if (!ds)
			ds = this;

		// Public methods
		extend(this, {
			add : function(cb, s) {
				cbl.push({cb : cb, scope : !s ? ds : s});

				return cb;
			},

			remove : function(cb) {
				each(cbl, function(c, i) {
					if (cb == c.cb)
						cbl.splice(i, 1);

					return false;
				});

				return cb;
			},

			dispatch : function() {
				var s, a = arguments;

				each(cbl, function(c) {
					return s = c.cb.apply(c.scope, a);
				});

				return s;
			}
		});
	};

	punymce.Editor = Editor = function(e) {
		var s, DOM, t = this;

		// Setup baseURL
		if (!punymce.baseURL) {
			each(pageDOM.select('script'), function(n) {
				if (/puny_mce/g.test('' + n.src))
					punymce.baseURL = n.src.replace(/^(.+)\/puny_mce.+$/, '$1/');
			});
		}

		// Default settings
		this.settings = s = extend({
			content_css : punymce.baseURL + '/css/content.css',
			editor_css : punymce.baseURL + '/css/editor.css',
			width : 0,
			height : 0,
			min_width : 260,
			min_height : 50,
			max_width : 800,
			max_height : 600,
			entities : 'raw',
			spellcheck : 0,
			resize : true,
			plugins : '',

			styles : [
				{ title : 'H1', cls : 'h1', cmd : 'FormatBlock', val : '<h1>' },
				{ title : 'H2', cls : 'h2', cmd : 'FormatBlock', val : '<h2>' },
				{ title : 'H3', cls : 'h3', cmd : 'FormatBlock', val : '<h3>' },
				{ title : 'Pre', cls : 'pre', cmd : 'FormatBlock', val : '<pre>' },
				{ title : 'Times', cls : 'times', cmd : 'FontName', val : 'Times'},
				{ title : 'Arial', cls : 'arial', cmd : 'FontName', val : 'Arial'},
				{ title : 'Courier', cls : 'courier', cmd : 'FontName', val : 'Courier'}
			],

			toolbar : 'bold,italic,underline,strike,increasefontsize,decreasefontsize,ul,ol,indent,outdent,left,center,right,style,removeformat'
		}, e);

		// Default tools
		t.tools = {
			bold : {cmd : 'Bold', title : I18n.bold},
			italic : {cmd : 'Italic', title : I18n.italic},
			underline : {cmd : 'Underline', title : I18n.underline},
			strike : {cmd : 'Strikethrough', title : I18n.strike},
			ul : {cmd : 'InsertUnorderedList', title : I18n.ul},
			ol : {cmd : 'InsertOrderedList', title : I18n.ol},
			indent : {cmd : 'Indent', title : I18n.indent},
			outdent : {cmd : 'Outdent', title : I18n.outdent},
			left : {cmd : 'JustifyLeft', title : I18n.left},
			center : {cmd : 'JustifyCenter', title : I18n.center},
			right : {cmd : 'JustifyRight', title : I18n.right},
			style : {cmd : 'mceStyle', title : I18n.style},
			removeformat : {cmd : 'RemoveFormat', title : I18n.removeformat},
			increasefontsize : {cmd : 'IncreaseFontSize', title : I18n.increasefontsize},
			decreasefontsize : {cmd : 'DecreaseFontSize', title : I18n.decreasefontsize}
		};

		pageDOM.loadCSS(s.editor_css);

		// Default commands
		this.commands = {
			mceStyle : function(u, v, e) {
				var n, t = this, s = t.settings, id = s.id, p = pageDOM.getPos(e.target), cb;

				if (t.hideMenu)
					return t.hideMenu();

				function hide(e) {
					t.hideMenu = null;
					Event.remove(document, 'click', hide);
					Event.remove(t.getDoc(), 'click', hide);
					pageDOM.get(id + '_mstyle').style.display = 'none';
				};

				n = pageDOM.get(id + '_mstyle');
				if (!n) {
					n = pageDOM.get(id + '_t');
					n = pageDOM.add(document.body, 'div', {id : id + '_mstyle', 'class' : 'punymce_style punymce'});
					each(s.styles, function(r) {
						Event.add(pageDOM.add(n, 'a', {href : '#', 'class' : r.cls}, r.title), 'mousedown', function(e) {
							hide.call(t);

							t.execCommand(r.cmd, r.ui, r.val);

							// Opera looses focus (could not be placed in execCommand)
							if (isOpera)
								t.getIfr().focus();

							return Event.cancel(e);
						});
					});
				}

				t.hideMenu = hide;
				Event.add(document, 'click', hide, t);
				Event.add(t.getDoc(), 'click', hide, t);

				s = n.style;
				s.left = p.x + 'px';
				s.top = (p.y + e.target.clientHeight + 2) + 'px';
				s.display = 'block';
			},

			FormatBlock : function(u, v, e) {
				// Gecko can't handle <> correctly on some elements
				if (isGecko && /<(div|blockquote|code|dt|dd|dl|samp)>/gi.test(v))
					v = v.replace(/[^a-z]/gi, '');

				t.getDoc().execCommand("FormatBlock", 0, v);
			},

			CreateLink : function(u, v) {
				var dom = t.dom, k = 'javascript:mox();';

				t.getDoc().execCommand("CreateLink", 0, k);
				each(dom.select('A'), function(n) {
					if (dom.getAttr(n, 'href') == k) {
						dom.setAttr(n, 'href', v);
						dom.setAttr(n, 'mce_href', v);
					}
				});
			},

			mceFontSizeDelta : function(u, v) {
				var d = t.getDoc(), cv, sp, fo, s = t.selection;

				cv = parseInt(d.queryCommandValue('FontSize') || 3);

				// WebKit returns pixel value, convert it to a size value
				if (isWebKit) {
					each([10, 13, 16, 18, 24, 32, 48], function(c, i) {
						if (cv == c) {
							cv = i + 1;
							return false;
						}
					});
				}

				if (cv + v <= 1)
					return;

				d.execCommand('FontSize', false, cv + v);

				if (isWebKit) {
					sp = s.getNode();

					if (sp.nodeName == 'SPAN') {
						fo = sp.parentNode;

						s.select(t.getBody());

						each(sp.childNodes, function(c) {
							sp.removeChild(c);
							fo.appendChild(c.cloneNode(1));
						});

						fo.removeChild(sp);
						cv = ['x-small', 'small', 'medium', 'large', 'x-large', 'xx-large', '-webkit-xxx-large'].indexOf(sp.style.fontSize);
						if (cv > 0 && cv < 7)
							fo.setAttribute('size', (cv + 1));

						s.select(fo);
					}
				}
			},

			IncreaseFontSize : function() {
				t.execCommand('mceFontSizeDelta', 0, 1);
			},

			DecreaseFontSize : function() {
				t.execCommand('mceFontSizeDelta', 0, -1);
			},

			Indent : function(u, v, e) {
				t.getDoc().execCommand("Indent", 0, 0);

				if (isIE) {
					// IE adds strange ltr and margin right when making a blockquote
					t.dom.getParent(t.selection.getNode(), function(n) {
						if (n.nodeName == 'BLOCKQUOTE')
							n.dir = n.style.marginRight = '';
					});
				}
			},

			mceSetClass : function(u, v, e) {
				var s = t.selection;

				// Wrap it
				if (is(v, 'string')) {
					v = {
						element : 'span',
						'class' : v
					};
				}

				if (s.isCollapsed())
					t.dom.setAttr(s.getNode(), 'class', v['class']);
				else
					s.setContent('<' + v.element + ' class="' + v['class'] + '">' + s.getContent() + '</' + v.element + '>');
			},

			RemoveFormat : function(u, v, e) {
				var s = t.selection;

				t.getDoc().execCommand('RemoveFormat', u, v);

				// Extra format removal for IE 6
				if (isIE) {
					v = s.getContent();

					v = v.replace(/ (class|style)=\"[^\"]+\"/g, '');
					v = v.replace(/<\/?(font|strong|em|b|i|u|strike)>/g, '');
					v = v.replace(/<(font|strong|em|b|i|u|strike) [^>]+>/g, '');

					s.setContent(v);
				}
			}
		};

		// Private methods

		function startResize(e) {
			var c, p, w, h;

			// Measure container
			c = pageDOM.get(s.id + '_c');
			w = c.clientWidth - 2;
			h = c.clientHeight - 2;

			// Setup placeholder
			p = pageDOM.get(s.id + '_p');
			p.style.width = w + 'px';
			p.style.height = h + 'px';

			// Replace with placeholder
			c.style.display = 'none';
			p.style.display = 'block';

			// Create internal resize obj
			t.resize = {
				x : e.screenX,
				y : e.screenY,
				w : w,
				h : h
			};

			// Start listening
			Event.add(document, 'mousemove', resizeMove, this);
			Event.add(document, 'mouseup', endResize, this);

			t.onResizeStart.dispatch(t, e, w, h);
		};

		function resizeMove(e) {
			var r = t.resize, p, w, h;

			// Calc delta values
			r.dx = e.screenX - r.x;
			r.dy = e.screenY - r.y;

			// Boundery fix box
			w = Math.max(s.min_width, r.w + r.dx);
			h = Math.max(s.min_height, r.h + r.dy);
			w = Math.min(s.max_width, w);
			h = Math.min(s.max_height, h);

			// Resize placeholder
			p = pageDOM.get(s.id + '_p');
			p.style.width = w + 'px';
			p.style.height = h + 'px';

			return Event.cancel(e);
		};

		function endResize(e) {
			var r = t.resize;

			// Stop listening
			Event.remove(document, 'mousemove', resizeMove, this);
			Event.remove(document, 'mouseup', endResize, this);

			// Replace with editor
			pageDOM.get(s.id + '_c').style.display = '';
			pageDOM.get(s.id + '_p').style.display = 'none';

			// Resize it to new size
			t.resizeBy(r.dx, r.dy);

			t.onResizeEnd.dispatch(t, e, r.dx, r.dy);
		};

		function setup() {
			var e = pageDOM.get(s.id), d = t.getDoc();

			// Design mode needs to be added here Ctrl+A will fail otherwise
			if (isGecko)
				t.getDoc().designMode = 'On';

			// Setup body
			d.open();
			d.write('<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body id="punymce"></body></html>');
			d.close();

			t.dom = DOM = new DOMUtils(t.getDoc());
			DOM.loadCSS(s.content_css);

			t.onPreInit.dispatch(t);

			if (!s.spellcheck)
				t.getBody().spellcheck = 0;

			// Add node change handlers
			Event.add(t.getDoc(), 'mouseup', t.nodeChanged, t);
			Event.add(t.getDoc(), 'keyup', t.nodeChanged, t );

			// Add focus event
			Event.add(isGecko ? t.getDoc() : t.getWin(), 'focus', function(e) {
				var ed;

				if ((ed = punymce.focusEditor) != null)
					ed.onBlur.dispatch(ed, t);

				t.onFocus.dispatch(t, ed);
				punymce.focusEditor = t;
			}, this);

			// IE fired load event twice if designMode is set
			if (!isIE)
				t.getDoc().designMode = 'On';
			else
				t.getBody().contentEditable = true;

			t.setContent(is(e.value) ? e.value : e.innerHTML, {load : true});

			pageDOM.get(s.id + '_c').style.display = t.orgDisplay;
			pageDOM.get(s.id).style.display = 'none';

			t.onInit.dispatch(t);
		};

		// Public fields
		extend(this, {
			serializer : new Serializer(t),
			selection : new Selection(t),
			plugins : []
		});

		// Add events
		each(['onPreInit', 'onInit', 'onFocus', 'onBlur', 'onResizeStart', 'onResizeEnd', 'onPreProcess', 'onPostProcess', 'onSetContent', 'onGetContent', 'onNodeChange'], function(e) {
			t[e] = new Dispatcher(t);
		});

		// Public methods
		extend(this, {
			init : function() {
				var e = pageDOM.get(s.id), pe = e.parentNode, w, h, ht, ul, n, r, f, sta = ['bold', 'italic', 'underline', 'left', 'center', 'right'];

				// Create plugins
				each(s.plugins.split(','), function(p) {
					if (p)
						t.plugins.push(new punymce.plugins[p](t));
				});

				// Handle common states
				t.onNodeChange.add(function() {
					each(sta, function(n) {
						var f;

						f = t.getDoc().queryCommandState(t.tools[n].cmd) ? pageDOM.addClass : pageDOM.removeClass;
						f.call(pageDOM, s.id + '_' + n, 'active');
					});
				});

				// Setup numeric entities
				if (s.entities == 'numeric') {
					t.onGetContent.add(function(ed, o) {
						if (o.format == 'html') {
							o.content = o.content.replace(/[\u007E-\uFFFF]/g, function(a) {
								return '&#' + a.charCodeAt(0) + ';';
							});
						}
					});
				}

				w = !s.width ? e.offsetWidth : s.width;
				h = !s.height ? e.offsetHeight : s.height;
				t.orgDisplay = e.style.display;
				e.style.display = 'none';

				// Add submit handlers
				if (e.form) {
					f = e.form;

					// Piggy back
					f._submit = f.submit;
					f.submit = function() {
						var e = pageDOM.get(s.id), f = e.form;
						t.save();
						f.submit = f._submit;
						f.submit();
					};

					// Prevent IE from memory leaking
					Event.add(0, 'unload', function() {
						var f = pageDOM.get(s.id).form;
						f._submit = f.submit = null;
					});

					// Submit event
					Event.add(f, 'submit', t.save, t);
				}

				// Setup UI table, could not use DOM since IE couldn't add the table this method is smaller in size anyway
				ht = '<div id="' + s.id + '_w" class="punymce"><table id="' + s.id + '_c" class="punymce"><tr class="mceToolbar">';
				ht += '<td id="' + s.id + '_t"></td></tr><tr class="mceBody"><td></div><div id="' + s.id + '_b" class="mceBody">';

				if (s.resize)
					ht += '<div id="' + s.id + '_r" class="mceResize"></div>';
	
				ht += '</td></tr></table>';
				ht += '<div id="' + s.id + '_p" class="mcePlaceholder"></div></div>';

				if (!e.insertAdjacentHTML) {
					r = e.ownerDocument.createRange();
					r.setStartBefore(e);
					pe.insertBefore(r.createContextualFragment(ht), e);
				} else
					e.insertAdjacentHTML("beforeBegin", ht);

				// Add tools to toolbar
				n = pageDOM.get(s.id + '_t');
				ul = pageDOM.add(n, 'ul', {id : s.id + '_tb', 'class' : 'punymce'});

				each(s.toolbar.split(','), function(v) {
					var to = t.tools[v];

					n = pageDOM.add(pageDOM.add(ul, 'li', {id : s.id + '_' + v, 'class' : v}), 'a', {'href' : 'javascript:void(0);', 'class' : v, title : to.title, onmousedown : 'return false;'});

					Event.add(n, 'click', function(e) {
						if (!pageDOM.hasClass(e.target.parentNode, 'disabled'))
							t.execCommand(to.cmd, 0, 0, e);

						return Event.cancel(e);
					});
				});

				// Add iframe to body
				n = pageDOM.get(s.id + '_b');

				// Create iframe
				n = pageDOM.add(n, 'iframe', {
					id : s.id + "_f",
					src : 'javascript:""', // Workaround for HTTPs warning in IE6/7
					frameBorder : '0',
					'class' : 'punymce',
					style : 'width:' + w + 'px;height:' + h + 'px'
				});

				t.resizeTo(w, h);

				// WebKit needs to be loaded this way to force it in to quirksmode to get <b> instead of <span>
				if (isWebKit) {
					Event.add(n, 'load', setup, t);
					n.src = punymce.baseURL + 'blank.htm';
				} else
					setup();

				// Add resize event
				if (s.resize) {
					Event.add(s.id + '_r', 'mousedown', function(e) {
						return startResize(e, s.id);
					}, this);
				}

				ul = f = e = n = null; // Prevent IE memory leak
			},

			getSize : function() {
				var e = pageDOM.get(s.id + '_f');

				return {
					w : e.clientWidth,
					h : e.clientHeight
				};
			},

			resizeTo : function(w, h) {
				var st = pageDOM.get(s.id + '_f').style;

				// Fix size
				w = Math.max(s.min_width, w);
				h = Math.max(s.min_height, h);
				w = Math.min(s.max_width, w);
				h = Math.min(s.max_height, h);

				// Store away size
				t.width = w;
				t.height = h;

				// Resize container
				st.width = w + 'px';
				st.height = h + 'px';
			},

			resizeBy : function(w, h) {
				var r = t.getSize();

				t.resizeTo(r.w + w, r.h + h);
			},

			show : function() {
				pageDOM.get(s.id + '_w').style.display = 'block';
				pageDOM.get(s.id).style.display = 'none';
				t.load();
			},

			hide : function() {
				// Fixed bug where IE has a blinking cursor left from the editor
				if (isIE)
					t.execCommand('SelectAll');

				pageDOM.get(s.id + '_w').style.display = 'none';
				pageDOM.get(s.id).style.display = t.orgDisplay;
				t.save();
			},

			load : function() {
				var e = pageDOM.get(s.id);

				t.setContent(is(e.value) ? e.value : e.innerHTML, {load : true});
			},

			save : function() {
				var e = pageDOM.get(s.id), h = t.getContent({save : true});

				if (/TEXTAREA|INPUT/.test(e.nodeName))
					e.value = h;
				else
					e.innerHTML = h;
			},

			setUseCSS : function(s) {
				var d = t.getDoc(), e;

				if (isGecko) {
					try {
						// Try new Gecko method
						d.execCommand("styleWithCSS", 0, false);
					} catch (e) {
						// Use old
						d.execCommand("useCSS", 0, true);
					}
				}
			},

			execCommand : function(c, u, v, e) {
				var cl = t.commands, s;

				t.getWin().focus();
				t.setUseCSS(0);

				if (cl[c])
					s = cl[c].call(t, u, v, e);
				else
					s = t.getDoc().execCommand(c, u, v);

				if (s !== false)
					t.nodeChanged();
			},

			getContent : function(o) {
				var h;

				o = o || {};
				o.format = o.format || 'html';
				h = t.serializer.serialize(t.getBody(), o);
				h = h.replace(/^\s*|\s*$/g, '');
				o.content = h;
				t.onGetContent.dispatch(this, o);

				return o.content;
			},

			setContent : function(h, o) {
				o = o || {};
				o.content = h;
				t.onSetContent.dispatch(this, o);
				h = o.content;
				h = pageDOM.keep(h);


				t.getBody().innerHTML = h;

				if (o.format != "raw") {
					t.setContent(h = t.getContent(o), {format : 'raw'});
				} else
					t.getBody().innerHTML = h;

				return h;
			},

			getIfr : function() {
				return pageDOM.get(s.id + "_f");
			},

			getWin : function() {
				return t.getIfr().contentWindow;
			},

			getDoc : function() {
				return t.getWin().document;
			},

			getBody : function() {
				return t.getDoc().body;
			},

			nodeChanged : function() {
				t.setUseCSS(0);
				t.onNodeChange.dispatch(t, t.selection.getNode());
			}
		});

		// Call init when page loads
		Event.add(window, 'init', t.init, this);
	};

	punymce.Selection = Selection = function(ed) {
		var t = this;

		// Public methods
		extend(t, {
			getContent : function(o) {
				var h, r = t.getRng(), e = document.createElement("body");

				o = o || {};

				if (t.isCollapsed())
					return '';

				if (r.cloneContents)
					e.appendChild(r.cloneContents());
				else if (is(r.item) || is(r.htmlText))
					e.innerHTML = r.item ? r.item(0).outerHTML : r.htmlText;
				else
					e.innerHTML = r.toString();

				if (o.format != "raw") {
					o.content = h;
					ed.serializer.serialize(e, o);
					o.content = o.content.replace(/^\s*|\s*$/g, '');
					ed.onGetContent.dispatch(h, o);
					h = o.content;
				} else
					h = e.innerHTML;

				return h;
			},

			getText : function() {
				var r = t.getRng(), s = t.getSel();

				if (isOldWebKit)
					return s;

				return t.isCollapsed() ? '' : r.text || s.toString();
			},

			setContent : function(h, o) {
				var r = t.getRng(), b;

				o = o || {format : 'raw'};
				h = pageDOM.keep(h);

				if (o.format != "raw") {
					o.content = h;
					h = ed.onSetContent.dispatch(this, o);
					h = o.content;
					b = ed.dom.create('body');
					b.innerHTML = h;
				}

				if (r.insertNode) {
					r.deleteContents();
					r.insertNode(r.createContextualFragment(h));
				} else {
					// Handle text and control range
					if (r.pasteHTML)
						r.pasteHTML(h);
					else
						r.item(0).outerHTML = h;
				}
			},

			select : function(n, c) {
				var r = t.getRng(), s = t.getSel();

				if (r.moveToElementText) {
					try {
						r.moveToElementText(n);
						r.select();
					} catch (ex) {
						// Throws illigal agrument in IE some times
					}
				} else if (s.addRange) {
					c ? r.selectNodeContents(n) : r.selectNode(n);
					s.removeAllRanges();
					s.addRange(r);
				} else
					s.setBaseAndExtent(n, 0, n, 1);

				return n;
			},

			isCollapsed : function() {
				var r = t.getRng();

				if (r.item)
					return false;

				return r.boundingWidth == 0 || t.getSel().isCollapsed;
			},

			collapse : function(b) {
				var r = t.getRng(), s = t.getSel();

				if (r.select) {
					r.collapse(b);
					r.select();
				} else {
					if (b)
						s.collapseToStart();
					else
						s.collapseToEnd();
				}
			},

			getSel : function() {
				var w = ed.getWin();

				return w.getSelection ? w.getSelection() : ed.getDoc().selection;
			},

			getRng : function() {
				var s = t.getSel(), d = ed.getDoc(), r, rb, ra, di;

				if (!s)
					return null;

				try {
					return s.rangeCount > 0 ? s.getRangeAt(0) : (s.createRange ? s.createRange() : null);
				} catch (e) {
					// IE bug when used in frameset
					return d.body.createTextRange();
				}
			},

			setNode : function(n) {
				t.setContent(ed.dom.create('div', null, n).innerHTML);
			},

			getNode : function() {
				var r = t.getRng(), s = t.getSel(), e;

				if (!isIE) {
					if (r) {
						e = r.commonAncestorContainer;

						// Handle selection a image or other control like element such as anchors
						if (!r.collapsed) {
							if (r.startContainer == r.endContainer) {
								if (r.startOffset - r.endOffset < 2) {
									if (r.startContainer.hasChildNodes())
										e = r.startContainer.childNodes[r.startOffset];
								}
							}
						}
					}

					return pageDOM.getParent(e, function(n) {
						return n.nodeType == 1;
					});
				}

				return r.item ? r.item(0) : r.parentElement();
			}
		});
	};

	punymce.Serializer = Serializer = function(ed) {
		var xml, key = 0, s;

		// Get XML document
		function getXML() {
			var i = document.implementation;

			if (!i || !i.createDocument) {
				// Try IE objects
				try {return new ActiveXObject('MSXML2.DOMDocument');} catch (ex) {}
				try {return new ActiveXObject('Microsoft.XmlDom');} catch (ex) {}
			} else
				return i.createDocument('', '', null);
		};

		xml = getXML();

		// Default settings
		this.settings = s = extend({
			valid_nodes : 0,
			invalid_nodes : /(BODY)/,
			valid_attrs : 0,
			node_filter : 0,
			root_node : 0,
			pi : 0,
			invalid_attrs : /(^mce_|^_moz_|^contenteditable$)/,
			closed : /(BR|HR|INPUT|META|IMG)/
		}, ed.settings.serializer);

		// Returns only attribites that have values not all attributes in IE
		function getIEAtts(n) {
			var o = [];

			n.cloneNode(false).outerHTML.replace(/([a-z0-9\-_]+)=/gi, function(a, b) {
				o.push({specified : 1, nodeName : b});
			});

			return o;
		};

		// Private methods
		function serializeNode(n, xn) {
			var hc, el, cn, i, l, a, at, no, v;

			if ((!s.valid_nodes || s.valid_nodes.test(n.nodeName)) && (!s.invalid_nodes || !s.invalid_nodes.test(n.nodeName)) && (!s.node_filter || s.node_filter(n, xn))) {
				switch (n.nodeType) {
					case 1: // Element
						// Fix IE content duplication (DOM can have multiple copies of the same node)
						if (isIE) {
							if (n.mce_serialized == key)
								return;

							n.mce_serialized = key;
						}

						hc = n.hasChildNodes();
						el = xml.createElement(n.nodeName.toLowerCase());

						// Add attributes
						at = isIE ? getIEAtts(n) : n.attributes;
						for (i=at.length-1; i>-1; i--) {
							no = at[i];

							if (no.specified) {
								a = no.nodeName.toLowerCase();

								if (s.invalid_attrs && s.invalid_attrs.test(a))
									continue;

								if (s.valid_attrs && !s.valid_attrs.test(a))
									continue;

								v = pageDOM.getAttr(n, a);

								if (v !== '')
									el.setAttribute(a, v);
							}
						}

						if (!hc && !s.closed.test(n.nodeName))
							el.appendChild(xml.createTextNode(""));

						xn = xn.appendChild(el);
						break;

					case 3: // Text
						return xn.appendChild(xml.createTextNode(n.nodeValue));

					case 8: // Comment
						return xn.appendChild(xml.createComment(n.nodeValue));
				}
			} else if (n.nodeType == 1)
				hc = n.hasChildNodes();

			if (hc) {
				cn = n.firstChild;

				while (cn) {
					serializeNode(cn, xn);
					cn = cn.nextSibling;
				}
			}
		};

		// Public methods
		extend(this, {
			serialize : function(n, o) {
				var h;

				key = '' + (parseInt(key) + 1);

				if (xml.firstChild)
					xml.removeChild(xml.firstChild);

				n = o.node = n.cloneNode(1);

				// Call preprocess
				ed.onPreProcess.dispatch(this, o);

				// Serialize HTML DOM into XML DOM
				serializeNode(n, xml.appendChild(xml.createElement("html")));
				h = xml.xml || new XMLSerializer().serializeToString(xml);

				// Remove PI
				if (!s.pi)
					h = h.replace(/<\?[^?]+\?>/g, '');

				// Remove root element
				if (!s.root_node)
					h = h.replace(/<html>|<\/html>/g, '');

				// Call post process
				o.content = h;
				ed.onPostProcess.dispatch(this, o);

				return o.content;
			}
		});
	};

	// Wait for DOM loaded
	Event._wait();
})();