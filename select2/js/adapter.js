

/**
 * 
 * 
 * @param object data
 * 
 * @returns string
 */
function formatSelect2Data(data)
{
	if (data.loading) return data.text;
	
	var res = '<span';
	
	if (data.element && data.element.className) {
		res += ' class=\"' + data.element.className + '\"';
	}
	
	if (!data.element || !data.element.noEscape) {
		data.text = getEO().escape(data.text);
	}
	
	res += '>' + data.text + '</span>';
	
	return res;
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
