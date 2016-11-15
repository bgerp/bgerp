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
	$.each(data, function(index, nCoord){
		var val = {};
	    // брой координати
		var coords = nCoord.coords;
		
	    var points = nCoord.coords.length;
	    // координати от предната точка, за да можем да съставим път
	    var oldCoord;
	    // изненение на цвета спрямо бр. точки
	    var delta = 510 / (points- 1);
	    
	    // генерираме структурата за пътя, заедно с промяната на цвета от черно към синьо
	    for (var i=1; i < parseInt(points/2); i++) {
	    	blue = Math.round(0 + delta  * i);

	    	// двойки точки, между който ще правим линия
	    	oldCoord = coords[i-1];
	    	value = coords[i];
	    	
	    	if (!oldCoord) {
	    		oldCoord = value;
	    	}
	    	
	    	// генерираме необходимата ни структура
	    	path = {}, options = {};
	    	options.strokeColor ="rgb(0,0," + blue + ")";
	    	options.path = [[oldCoord[0],oldCoord[1]],  [value[0],value[1]]];
	    	path.options = options;
	    	allPaths.push(path);
	    }
	    
    	blue = 0;
	    // генерираме структурата за пътя, заедно с промяната на цвета от синьо към червено
	    for (i=parseInt(points/2); i < points; i++) {
	    	blue = Math.round(255 - (delta  * (i-points/2)));
	    	red = Math.round(0 + (delta * (i-points/2)));
	    	
	    	// двойки точки, между които ще правим линия
	    	oldCoord = coords[i-1];
	    	value = coords[i];
	    	
	    	// генерираме необходимата ни структура
	    	path = {}, options = {};
	    	options.strokeColor ="rgb(" + red + ",0," + blue + ")";
	    	
	    	if (!oldCoord) {
	    		oldCoord = value;
	    	}
	    	
	    	options.path = [[oldCoord[0],oldCoord[1]], [value[0],value[1]]];
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