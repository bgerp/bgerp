function editFloorplan()
{
	$(".floor-object").draggable({"stop": 
            function(event) {
                $.post( "/floor_Plans/UpdatePossition",  {
					objId: event.target.id,  
					x: $("#"+event.target.id).offset().left - $("#"+event.target.id).parent().offset().left,  
					y:  $("#"+event.target.id).offset().top - $("#"+event.target.id).parent().offset().top})
				}, 
            containment: "parent"});

	$(".floor-object").draggable({"start": 
            function(event) {
				$(".floor-object").removeClass("selected");
				$(this).addClass('selected');
			}, 
            containment: "parent"});


	$(".floor-object").each(function () {

		this.addEventListener("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(".floor-object").removeClass("selected");
			$(this).addClass('selected');
		}); 
	});

	$('html').on("click", function() {
		$(".floor-object").removeClass("selected");
	});

	$('html').keydown(function(e) {
		$('.selected').each(function () {

			var floorObject = $(this);

			if(e.keyCode == 46) {
				e.preventDefault();
				e.stopPropagation();
				$('.selected').each(function () {
					alert('Наистина ли желаете да изтриете обекта?');
					$.post( "/floor_Plans/DeleteObject",  {objId: floorObject.attr('id')}, function() {location.reload();});
					return false;
				});
			}

			var width = wSave = floorObject.outerWidth();
			var height = floorObject.outerHeight();
			var leftOffset = parseInt(floorObject.css("left"));
			var topOffset = parseInt(floorObject.css("top"));


			if (e.ctrlKey == 1) {
				var flag = true;
				switch(e.which) {
					case 37: // left
						width--;
						break;

					case 38: // up
						height--;
						break;

					case 39: // right
						width++;
						break;

					case 40: // down
						height++;
						break;

					default:
						flag = false;
						break;
				}
			} else if (e.altKey == 1){
				var flag = true;
				switch(e.which) {
					case 37: // left
						width -= 10;
						break;

					case 38: // up
						height -= 10;
						break;

					case 39: // right
						width += 10;
						break;

					case 40: // down
						height += 10;
						break;

					default:
						flag = false;
						break;
				}
			} else if (e.shiftKey == 1){
				var flag = true;
				switch(e.which) {
					case 37: // left
						leftOffset -= 1;
						break;

					case 38: // up
						topOffset -= 1;
						break;

					case 39: // right
						leftOffset +=1;
						break;

					case 40: // down
						topOffset +=1;
						break;

					default:
						flag = false;
						break;
				}
			} else {
				var flag = true;
				switch(e.which) {
					case 37: // left
						leftOffset -= 10;
						break;

					case 38: // up
						topOffset -= 10;
						break;

					case 39: // right
						leftOffset += 10;
						break;

					case 40: // down
						topOffset += 10;
						break;

					default:
						flag = false;
						break;
				}
			}

			if(flag) {
				e.preventDefault();
				e.stopPropagation();
				$.post( "/floor_Plans/ChangeSize",  {
					"objId": floorObject.attr('id'), 
					"w": width, 
					"h": height, 
					"x":leftOffset, 
					"y":topOffset}, 
					function(data) {
						floorObject.outerWidth(data.w);
						floorObject.outerHeight(data.h);
						floorObject.css("left",data.x);
						floorObject.css("top", data.y);
				});
			}
			return false;
		});
	});
}

function refreshFloor() {
  var floorId = $("#floor").attr('data-id');
  $.post("/floor_Plans/RefreshFloor",  {'floorId': floorId}, function(result) {
						$("body").html(result.html);
					});
  setTimeout(refreshFloor, 3000);
}