/*
 * SimpleModal Basic Modal Dialog
 * http://www.ericmmartin.com/projects/simplemodal/
 * http://code.google.com/p/simplemodal/
 *
 * Copyright (c) 2008 Eric Martin - http://ericmmartin.com
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Revision: $Id: basic.js 164 2008-12-02 23:31:54Z emartin24 $
 *
 */

$(document).ready(function () {
	$('#basicModal input.basic, #basicModal a.basic').click(function (e) {
		e.preventDefault();
		$('#basicModalContent').modal();
	});
});