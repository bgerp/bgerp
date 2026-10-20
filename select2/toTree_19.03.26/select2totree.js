/*!
 * Select2-to-Tree 1.1.1
 * https://github.com/clivezhg/select2-to-tree
 */
(function ($) {
	$.fn.select2ToTree = function (options) {
		var opts = $.extend({}, options);

		if (opts.treeData) {
			buildSelect(opts.treeData, this);
		}

		opts._templateResult = opts.templateResult;
		opts.templateResult = function (data, container) {
			container = container || opts.resultContainer;
			if (data.element && opts.shortLabels &&
				opts.shortLabels["$" + data.element.value] !== undefined) {
				data = $.extend({}, data, {text: opts.shortLabels["$" + data.element.value]});
			}
			var label = data.text;
			if (typeof opts._templateResult === "function") {
				label = opts._templateResult(data, container);
			}
			var $iteme = $("<span class='item-label'></span>").append(label);
			if (data.element) {
				var ele = data.element;
				container.setAttribute("data-val", ele.value);
				if (ele.className) container.className += " " + ele.className;
				if (ele.getAttribute("data-pup")) {
					container.setAttribute("data-pup", ele.getAttribute("data-pup"));
				}
				if ($(container).hasClass("non-leaf")) {
					return $.merge($('<span class="expand-collapse" onmouseup="expColMouseupHandler(event);"></span>'), $iteme);
				}
			}
			return $iteme;
		};

		window.expColMouseupHandler = function (evt) {
			toggleSubOptions(evt.target || evt.srcElement);
			/* prevent Select2 from doing "select2:selecting","select2:unselecting","select2:closing" */
			evt.stopPropagation ? evt.stopPropagation() : evt.cancelBubble = true;
			evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;
		}

		var s2inst = this.select2(opts);

		// В дървото показваме само името; търсим по пълния път и го пазим в избрания етикет.
		var dataAdapter = s2inst.data("select2").dataAdapter;
		var query = dataAdapter.query;
		dataAdapter.query = function (params, callback) {
			opts.searching = !!(params.term && params.term.trim());
			opts.shortLabels = {};
			var optionElements = {};
			s2inst.find("option").each(function () {
				optionElements["$" + this.value] = this;
			}).each(function () {
				var parent = optionElements["$" + this.getAttribute("data-pup")];
				if (parent && this.text.indexOf(parent.text) === 0) {
					var suffix = this.text.substring(parent.text.length);
					if (/^\s*»\s*/.test(suffix)) {
						opts.shortLabels["$" + this.value] = suffix.replace(/^\s*»\s*/, "");
					}
				}
			});
			return query.call(this, params, function (data) {
				opts.firstMatch = data.results.length ? data.results[0].id : null;
				if (!$.grep(data.results, function (item) { return item.children; }).length) {
					var items = {}, children = {}, ordered = [], visited = {};
					$.each(data.results, function (i, item) {
						items["$" + item.id] = item;
					});
					if (opts.searching) {
						// Добавяме родителите за контекст, без да повтаряме пътя във всеки ред.
						$.each(data.results.slice(), function (i, item) {
							var parent = item.element && optionElements["$" + item.element.getAttribute("data-pup")];
							while (parent && !items["$" + parent.value]) {
								var parentItem = dataAdapter.item($(parent));
								items["$" + parent.value] = parentItem;
								data.results.push(parentItem);
								parent = optionElements["$" + parent.getAttribute("data-pup")];
							}
						});
					}
					$.each(data.results, function (i, item) {
						var parent = item.element && item.element.getAttribute("data-pup");
						var key = parent && items["$" + parent] ? "$" + parent : "$root";
						(children[key] = children[key] || []).push(item);
					});
					function appendItems(list) {
						$.each(list || [], function (i, item) {
							var key = "$" + item.id;
							if (visited[key]) return;
							visited[key] = true;
							ordered.push(item);
							appendItems(children[key]);
						});
					}
					// Азбучната подредба на пълните имена може да раздели родител от децата му.
					appendItems(children.$root);
					appendItems(data.results);
					data.results = ordered;
				}
				callback(data);
			});
		};

		// Select2 4.0rc2 не предава контейнера на templateResult.
		var results = s2inst.data("select2").results;
		var renderTemplate = results.template;
		results.template = function (data, container) {
			opts.resultContainer = container;
			return renderTemplate.call(this, data, container);
		};

		// Резултатите се създават след select2:open, включително при изчистване на търсенето.
		s2inst.data("select2").on("results:all", function (params) {
			var term = params.query && params.query.term;
			var searching = !!(term && term.trim());
			var $items = results.$results.find(".select2-results__option[data-val]");
			if (searching) {
				$items.addClass("showme").filter(".non-leaf").addClass("opened");
				// Enter трябва да избира намерения елемент, а не добавения за контекст родител.
				$items.filter(function () {
					return this.getAttribute("data-val") === String(opts.firstMatch);
				}).first().trigger("mouseenter");
			}

			var itemsByValue = {}, selected = {}, roots = {}, firstSelected, firstRoot;
			if (searching) {
				selected["$" + opts.firstMatch] = true;
			} else {
				s2inst.find("option:selected").each(function () {
					selected["$" + this.value] = true;
				});
			}
			$items.each(function () {
				itemsByValue["$" + this.getAttribute("data-val")] = this;
			});
			$items.each(function () {
				if (!selected["$" + this.getAttribute("data-val")]) return;
				if (!firstSelected) firstSelected = this;
				var current = this, visited = {};
				var parent = itemsByValue["$" + current.getAttribute("data-pup")];
				while (parent && !visited["$" + parent.getAttribute("data-val")]) {
					visited["$" + parent.getAttribute("data-val")] = true;
					$(parent).addClass("opened");
					current = parent;
					parent = itemsByValue["$" + current.getAttribute("data-pup")];
				}
				roots["$" + current.getAttribute("data-val")] = current;
				if (!firstRoot) firstRoot = current;
			});
			$.each(roots, function (value, root) {
				showHideSub(root);
			});
			if (firstSelected) {
				// Запазваме видим родителя, когато клонът се побира в списъка.
				var top = results.$results.offset().top;
				var scrollTop = results.$results.scrollTop();
				results.$results.scrollTop(Math.max(
					scrollTop + $(firstRoot).offset().top - top,
					scrollTop + $(firstSelected).offset().top - top +
						$(firstSelected).outerHeight() - results.$results.height()));
			}
		});

		s2inst.on("select2:open", function (evt) {
			var s2data = s2inst.data("select2");
			s2data.$dropdown.addClass("s2-to-tree");
		});

		return s2inst;
	};

	function buildSelect(treeData, $el) { // build Select options according to Select-to-Tree specification
		function buildOptions(dataArr, curLevel, pup) {
			for (var i = 0; i < dataArr.length; i++) {
				var data = dataArr[i] || {};
				var $opt = $("<option></option>");
				$opt.text(data[treeData.labelFld || "text"]);
				$opt.val(data[treeData.valFld || "id"]);
				if (data[treeData.selFld || "selected"] && String(data[treeData.selFld || "selected"]) === "true") {
					$opt.prop("selected", data[treeData.selFld || "selected"]);
				}
				if($opt.val() === "") {
					$opt.prop("disabled", true);
					$opt.val(getUniqueValue());
				}
				$opt.addClass("l" + curLevel);
				if (pup) $opt.attr("data-pup", pup);
				$el.append($opt);
				var inc = data[treeData.incFld || "inc"];
				if (inc && inc.length > 0) {
					$opt.addClass("non-leaf");
					buildOptions(inc, curLevel+1, $opt.val());
				}
			}
		}
		buildOptions(treeData.dataArr, 1, "");
		if (treeData.dftVal) $el.val(treeData.dftVal);
	}

	var uniqueIdx = 1;
	function getUniqueValue() {
		return "autoUniqueVal_" + uniqueIdx++;
	}

	function toggleSubOptions(target) {
		$(target.parentNode).toggleClass("opened");
		showHideSub(target.parentNode);
	}

	function showHideSub(ele) {
		var curEle = ele;
		var $options = $(ele).parent(".select2-results__options");
		var shouldShow = true;
		do {
			var pup = ($(curEle).attr("data-pup") || "").replace(/'/g, "\\'");
			curEle = null;
			if (pup) {
				var pupEle = $options.find(".select2-results__option[data-val='" + pup + "']");
				if (pupEle.length > 0) {
					if (!pupEle.eq(0).hasClass("opened")) { // hide current node if any parent node is collapsed
						$(ele).removeClass("showme");
						shouldShow = false;
						break;
					}
					curEle = pupEle[0];
				}
			}
		} while (curEle);
		if (shouldShow) $(ele).addClass("showme");

		var val = ($(ele).attr("data-val") || "").replace(/'/g, "\\'");
		$options.find(".select2-results__option[data-pup='" + val + "']").each(function () {
			showHideSub(this);
		});
	}
})(jQuery);
