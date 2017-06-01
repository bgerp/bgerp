

/**
 * 
 * 
 * @param object data
 * 
 * @returns string
 */
function formatSelect2Data(data)
{
	var text = data.text;
	
	if (!data.element || !data.element.noEscape) {
		if (text) {
			text = getEO().escape(text);
		}
	}
	
	if (color = getDataAttr(data, 'data-color')) {
		text = "<div class='color-preview' style='background-color:" + color + " !important;'> </div>&nbsp;" + text;
	}
	
	if (data.loading) return text;
	
	var res = '<span';
	
	if (data.element && data.element.className) {
		res += ' class=\"' + data.element.className + '\"';
	}
	
	res += '>' + text + '</span>';
	
	return $(res);
}


/**
 * 
 * 
 * @param object data
 * 
 * @returns Boolean
 */
function formatSelect2DataSelection(data)
{
	var text = data.text;
	
	if (color = getDataAttr(data, 'data-color')) {
		text = "<span><div class='color-preview' style='background-color:" + color + " !important; margin-bottom: 2px;'> </div>&nbsp;" + text + "</span>";
		
		text = $(text);
	}
	
	return text;
}


/**
 * Връща стойността на атрибута
 * 
 * @param data
 * @param attrName
 * @returns {String}
 */
function getDataAttr(data, attrName)
{
	var color = '';
	
	if (data && data.element && data.element.getAttribute) {
		color = data.element.getAttribute(attrName);
	}
	
	if (!color) {
		if (data.attr) {
			color = data.attr[attrName];
		}
	}
	
	return color;
}


/**
 * Функция, която подобрява работата на .find със `:selected` в андроидския браузър
 * 
 * @returns object
 */
$.fn.alternativeFind = function(selector) {
	
	if ((selector != ':selected') || (this.__isIn)) return this.find(selector);
	
	var self = this;
	
	var re = new RegExp("(\s*selected(\s)*\=(\s)*(\"|\')selected(\"|\'))", 'i');
	
	$.map(this[0], function(val, i) {
		if ($(val)[0]['outerHTML'] && re.test($(val)[0]['outerHTML'])) {
			self[0][i]['defaultSelected'] = true;
		}
	});
	
	this.__isIn = true;
	
	return this.find(selector);
}


/**
 * 
 * 
 * @param params
 * @param data
 * 
 * @see https://github.com/select2/select2/issues/3034
 * 
 * @returns
 */
function modelMatcher (params, data)
{
    data.parentText = data.parentText || "";
    
    // Always return the object if there is nothing to compare
    if ($.trim(params.term) === '') {
    	
    	return data;
    }

	// Do a recursive check for options with children
	if (data.children && data.children.length > 0) {
		// Clone the data object if there are children
		// This is required as we modify the object to remove any non-matches
		var match = $.extend(true, {}, data);
		
		// Check each child of the option
		for (var c = data.children.length - 1; c >= 0; c--) {
			var child = data.children[c];
			
			if (typeof child.parentText == 'undefined') {
				child.parentText = '';
			}
			
			child.parentText += data.parentText + " " + data.text;
			
			var matches = modelMatcher(params, child);
			
			// If there wasn't a match, remove the object in the array
			if (matches == null) {
				match.children.splice(c, 1);
			}
		}
		
		  // If any children matched, return the new object
		if (match.children.length > 0) {
			
			return match;
		}
		
		// If there were no matching children, check just the plain object
		return modelMatcher(params, match);
	}

    // If the typed-in term matches the text of this term, or the text from any
    // parent term, then it's a match.
    var original = (data.parentText + ' ' + data.text).toUpperCase();
    var term = params.term.toUpperCase();
    
    have = true;
    
    // Търсене във всяка дума
    var termArr = term.split(" ");
    termArr.forEach(function(element) {
    	if (!element || !element.length) return true;
    	
    	var regExpStr = "[ \"\'\(\[\-\s]" + escapeRegExp(element);
    	var regExp = new RegExp(regExpStr, "gi");
    	
    	if (!original.match(regExp)) {
    		have = false;
    	}
    });
    
    // Търсене с OR
    if (!have) {
    	var termArr = term.split("|");
        termArr.forEach(function(element) {
        	if (have) return true;
        	
        	if (!element || !element.length) return true;
        	
        	var regExpStr = "[ \"\'\(\[\-\s]" + escapeRegExp(element);
        	var regExp = new RegExp(regExpStr, "gi");
        	
        	if (original.match(regExp)) {
        		have = true;
        	}
        });
    }
    
    if (have) {
    	
    	return data;
    }
    
    // If it doesn't contain the term, don't return anything
    return null;
}
