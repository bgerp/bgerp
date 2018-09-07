/**
 * Функция за изчертаване на път, по зададени точки
 * @param data - координати на точки, по които строим пътя
 * @param el - елемента, в който ще показваме картата
 */
function generatePath(data,el){
	// масив, който ще записваме пътищата, заедно с необходимите опции
	var allPaths = [];

	// номер на поредния път
	var currentPath = 0;
	var markers = [];
	var colors = ['f0f8ff', '9966cc', 'faebd7', '00ffff', '7fffd4', 'f0ffff',
		'8a2be2', 'a52a2a', 'deb887', '7fff00', 'd2691e', 'ff7f50', '5f9ea0',
		'6495ed', 'fff8dc', 'dc143c', '00ffff', '00008b', '008b8b', 'f5f5dc',
		'b8860b', 'a9a9a9', '006400', 'bdb76b', '8b008b', '556b2f', '00ced1',
		'ff8c00', '9932cc', '8b0000', 'e9967a', '8fbc8f', '483d8b', '2f4f4f',
		'ff1493', '00bfff', '696969', '1e90ff', 'd19275', 'b22222', '9400d3'
	];


	$.each(data, function(index, nCoord){
		var val = {};
	    // брой координати
		var coords = nCoord.coords;
		var value = nCoord.coords[0];
	    var points = nCoord.coords.length;
	    // координати от предната точка, за да можем да съставим път
	    var oldCoord;
	    // вземане на цвят от масива
	    var color= colors[index % colors.length];

	    // генерираме структурата за пътя
	    for (var i=1; i < points; i++) {

	    	// двойки точки, между който ще правим линия
	    	oldCoord = coords[i-1];
	    	value = coords[i];

	    	if (!oldCoord) {
	    		oldCoord = value;
	    	}

	    	// генерираме необходимата ни структура
	    	path = {}, options = {};
	    	options.strokeColor ="#" + color;
	    	options.path = [[oldCoord[0],oldCoord[1]],  [value[0],value[1]]];
	    	path.options = options;
	    	allPaths.push(path);
	    }

	    currentPath += points - 1;

	    // записваме координатите на последната точка от всеки път и поставяме маркер с информацията за нея
	    if(nCoord.info){
	    	val.latLng = [value[0],value[1]];
	 	    val.data = nCoord.info;
	 		markers.push(val);
	    }

	});

	// генерираме картата
    $(el).gmap3({marker:{
        values:markers,
              options:{ draggable: false},
              events:{
                mouseover: function(marker, event, context){
                  var map = $(this).gmap3("get"),
                    infowindow = $(this).gmap3({get:{name:"infowindow"}});
                  if (infowindow){
                    infowindow.open(map, marker);
                    infowindow.setContent(context.data);
                  } else {
                    $(this).gmap3({
                      infowindow:{
                        anchor:marker,
                        options:{content: context.data}
                      }
                    });
                  }
                },
                mouseout: function(){
                  var infowindow = $(this).gmap3({get:{name:"infowindow"}});
                  if (infowindow){
                    infowindow.close();
                  }
                }
              }
            }},{polyline:{values: allPaths}},'autofit'); ;
}