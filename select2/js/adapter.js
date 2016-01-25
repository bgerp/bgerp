

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
		text = getEO().escape(text);
	}
	
	if (data.element && data.element.getAttribute && (color = data.element.getAttribute('data-color'))) {
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
	
	if (data.element && (color = data.element.getAttribute('data-color'))) {
		text = "<span><div class='color-preview' style='background-color:" + color + " !important; margin-bottom: 2px;'> </div>&nbsp;" + text + "</span>";
		
		text = $(text);
	}
	
	return text;
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
