

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
	
	if (data.class) {
		res += ' class=\"' + data.class + '\"';
	}
	
	res += '>' + data.name + '</span>';
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
  	
	if (data.name) {
		
		return data.name;
	}
	
	return data.text;
}