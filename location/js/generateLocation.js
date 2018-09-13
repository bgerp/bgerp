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
	var colors = [ '00ced1', '8b008b', '00008b', 'a52a2a', '6495ed', '797979',
		 '7fffd4', '7fff00', '5f9ea0', '9966cc', '2f4f4f', 'ff8c00', '483d8b'
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