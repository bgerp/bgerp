/*
 * Keynav - jQuery Keyboard Navigation plugin
 *
 * Copyright (c) 2013 Nick Ostrovsky
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.firedev.com/jquery.keynav
 *
 * Version:  0.1
 *
 */

;(function($, window, document, undefined) {

	$.fn.keynav = function(checkNav) {
		var elements = this;
		var matrix;
		var x;
		var y;
		var current = this.filter('.selected');
		var keyNavigationDisabled=false;
		if (current.length == 0) current = this.first();

		current.addClass('selected');

		function update() {
			var i=0;
			var row = Array();
			var j = -1;
			var oldtop = false;
			var m=Array();

			elements.each(function(){
				if (!oldtop) oldtop = this.offsetTop;
				newtop=this.offsetTop;
				if (newtop != oldtop) {
					oldtop=newtop;
					m[i]=row;
					row = Array();
					i++;
					j=0;
					row[j]=this;
				} else {
					j++;
					row[j]=this;
				}
			});
			m[i]=row;
			matrix = m;
			coordinates=findCurrent();
			x=coordinates[0];
			y=coordinates[1];
			return matrix;
		}

		function findCurrent() {
			i=0; j=0; found = false;
			try {
				for (i=0; i<matrix.length; i++) {
					row=matrix[i];
					for (j=0; j<row.length; j++) {
						if (current[0] == row[j]) {
							throw([i,j]);
						}

					}
				}
			}
			catch (arr)
			{
				found = [i,j]
			}
			return(found);
		}

		function setCurrent(i,j, clicked) {
			if (i<0) i=(matrix.length-1);
			if (matrix && i>=matrix.length) i=0;
			if (j<0) j=(matrix[i].length-1);
            if (matrix && matrix[i] && j>=matrix[i].length) j=matrix[i].length-1;
            
            if(typeof j === 'undefined') j = 0;

			if (matrix && matrix[i] && matrix[i][j]) {
				elements.removeClass('selected');
				current = $(matrix[i][j]);
				current.addClass('selected');
				if (!isItVisible($(current)) && !clicked) {
					$(current)[0].scrollIntoView();
				}

				sessionStorage.setItem('focused', $(current).attr('id'));
				sessionStorage.setItem('focusedOffset', $('#result-holder .withTabs').scrollTop());
				disableOrEnableEnlargeBtn();
				x=i;
				y=j;
			}

		}

		$(window).bind("click",  function(event) {
			var element = $(event.target).closest('.navigable');
			if($(element).length) {
				$('.navigable').removeClass('selected');
				sessionStorage.setItem('focused', $(this).attr('id'));

				current = $(element);
				var currentPosition = findCurrent();
				setCurrent(currentPosition[0], currentPosition[1], true);
			}

		});
		if (isTouchDevice()) {
			$(window).bind("contextmenu", function(event) {
				event.preventDefault();
				current.removeClass('selected');
				var element = $(event.target).closest('.navigable');
				if (element.length) {
					current = $(element);
					var currentPosition = findCurrent();
					setCurrent(currentPosition[0], currentPosition[1], true);
				}
			});
		}


		$(window).bind("resize", function(event) {
			update();
		});

		$(document).ready(function() {
			update();
		});


		$(document).keydown(function(e){
			if (checkNav && checkNav()) return;
			if (e.keyCode == 37) {
				// left
				setCurrent(x,y-1,false);
				e.preventDefault();
			} else if (e.keyCode == 38) {
				// up
				setCurrent(x-1,y, false);
				e.preventDefault();
			} else if (e.keyCode == 39) {
				// right
				setCurrent(x,y+1, false);
				e.preventDefault();
			} else if (e.keyCode == 40) {
				// down
				setCurrent(x+1,y, false);
				e.preventDefault();
			}
		});


		return this;
	}

})(jQuery, window, document);
