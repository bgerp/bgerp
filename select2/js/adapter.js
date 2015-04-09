

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
	
	return data.text;
}
