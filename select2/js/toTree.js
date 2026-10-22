/**
 * Разширения на bgERP към Select2ToTree, които работят с всяка версия от toTree_*:
 * кратки имена, търсене с родителите за контекст, отворени избрани клонове
 * и превъртане до първия избран елемент.
 */
(function ($) {
	var select2ToTree = $.fn.select2ToTree;
	if (!select2ToTree || select2ToTree.bgerp) return;

	$.fn.select2ToTree = function (options) {
		var opts = $.extend({}, options);
		var shortLabels = {}, firstMatch = null, resultContainer;

		// В дървото показваме само името; търсим по пълния път и го пазим в избрания етикет.
		var templateResult = opts.templateResult;
		opts.templateResult = function (data, container) {
			var shortLabel = data.element && shortLabels["$" + data.element.value];
			if (shortLabel !== undefined) {
				data = $.extend({}, data, {text: shortLabel});
			}

			return typeof templateResult === "function" ? templateResult(data, container) : data.text;
		};

		var s2inst = select2ToTree.call(this, opts);
		var select2 = s2inst.data("select2");
		var dataAdapter = select2.dataAdapter, results = select2.results;

		// Select2 4.0rc2 не предава контейнера, който Select2ToTree използва.
		var treeTemplate = select2.options.get("templateResult");
		select2.options.options.templateResult = function (data, container) {
			return treeTemplate.call(this, data, container || resultContainer);
		};
		var renderTemplate = results.template;
		results.template = function (data, container) {
			resultContainer = container;

			return renderTemplate.call(this, data, container);
		};

		var query = dataAdapter.query;
		dataAdapter.query = function (params, callback) {
			var searching = !!(params.term && params.term.trim());
			var optionElements = {};
			shortLabels = {};
			s2inst.find("option").each(function () {
				optionElements["$" + this.value] = this;
			}).each(function () {
				var parent = optionElements["$" + this.getAttribute("data-pup")];
				if (parent && this.text.indexOf(parent.text) === 0) {
					var suffix = this.text.substring(parent.text.length);
					if (/^\s*»\s*/.test(suffix)) {
						shortLabels["$" + this.value] = suffix.replace(/^\s*»\s*/, "");
					}
				}
			});

			return query.call(this, params, function (data) {
				firstMatch = data.results.length ? data.results[0].id : null;
				if (!$.grep(data.results, function (item) { return item.children; }).length) {
					var items = {}, children = {}, ordered = [], visited = {};
					$.each(data.results, function (i, item) {
						items["$" + item.id] = item;
					});
					if (searching) {
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
					var appendItems = function (list) {
						$.each(list || [], function (i, item) {
							var key = "$" + item.id;
							if (visited[key]) return;
							visited[key] = true;
							ordered.push(item);
							appendItems(children[key]);
						});
					};

					// Азбучната подредба на пълните имена може да раздели родител от децата му.
					appendItems(children.$root);
					appendItems(data.results);
					data.results = ordered;
				}
				callback(data);
			});
		};

		// Резултатите се създават след select2:open, включително при изчистване на търсенето.
		select2.on("results:all", function (params) {
			var term = params.query && params.query.term;
			var searching = !!(term && term.trim());
			var $items = results.$results.find(".select2-results__option[data-val]");
			var itemsByValue = {}, selected = {}, firstSelected, firstRoot;
			$items.each(function () {
				itemsByValue["$" + this.getAttribute("data-val")] = this;
			});

			// Родителите до корена, с предпазване от зацикляне при грешни данни.
			var getParents = function (item) {
				var parents = [], visited = {};
				var parent = itemsByValue["$" + item.getAttribute("data-pup")];
				while (parent && !visited["$" + parent.getAttribute("data-val")]) {
					visited["$" + parent.getAttribute("data-val")] = true;
					parents.push(parent);
					parent = itemsByValue["$" + parent.getAttribute("data-pup")];
				}

				return parents;
			};

			if (searching) {
				$items.filter(".non-leaf").addClass("opened");
				selected["$" + firstMatch] = true;
			} else {
				s2inst.find("option:selected").each(function () {
					selected["$" + this.value] = true;
				});
			}

			$items.each(function () {
				if (!selected["$" + this.getAttribute("data-val")]) return;
				var parents = getParents(this);
				$(parents).addClass("opened");
				if (!firstSelected) {
					firstSelected = this;
					firstRoot = parents.length ? parents[parents.length - 1] : this;
				}
			});

			// Елементът се вижда, когато всичките му родители са отворени.
			$items.each(function () {
				$(this).toggleClass("showme", !$(getParents(this)).not(".opened").length);
			});

			if (searching) {
				// Enter трябва да избира намерения елемент, а не добавения за контекст родител.
				$items.filter(function () {
					return this.getAttribute("data-val") === String(firstMatch);
				}).first().trigger("mouseenter");
			}

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

		return s2inst;
	};
	$.fn.select2ToTree.bgerp = true;
})(jQuery);
