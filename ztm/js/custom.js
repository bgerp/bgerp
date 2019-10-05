function prepareDashboard(data){


    $('#navbarCollapse a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    var today = new Date();
    var month ;
    switch (today.getMonth()) {
        case 0: month = "януари"; break;
        case 1: month = "февруари"; break;
        case 2: month = "март"; break;
        case 3: month = "април"; break;
        case 4: month = "май"; break;
        case 5: month = "юни"; break;
        case 6: month = "юли"; break;
        case 7: month = "август"; break;
        case 8: month = "септември"; break;
        case 9: month = "октомври"; break;
        case 10: month = "ноември"; break;
        case 11: month = "декември"; break;


    }
    var date = today.getDate() + " " + month;
    $('.currentDate').html(date);

    var min = today.getMinutes() >= 10 ? today.getMinutes() : '0' + today.getMinutes();
    var time = today.getHours() + "<span>:</span>" + min;
    $('#currentTime').append(time);

    var weatherStat = data.low ? "Температурите ще са в интервала от " + parseInt(data.low) + " до " + parseInt(data.high) + "°C." : "Няма информация за текущата прогноза.";
    $('.weatherStat').html(weatherStat);



    $('#navbarCollapse a:first').tab('show');
    var toggle;
    setInterval(function() {
        $("h1 span").css({ visibility: toggle?"visible":"hidden"});
        toggle=!toggle;
    },1000);

    $(document).on('click', '.dropdown-menu', function (e) {
        e.stopPropagation();
    });
    var icons = new Skycons({"color": "#489fc0"});

    $('.weather-icon').attr('id', data.icon);
    switch (data.icon) {
        case "rain":
            icons.set("rain", Skycons.RAIN);
            break;
        case "cloudy":
            icons.set("cloudy", Skycons.CLOUDY);
            break;
        case "sleet":
            icons.set("sleet", Skycons.SLEET);
            break;
        case "snow":
            icons.set("snow", Skycons.SNOW);
            break;
        case "wind":
            icons.set("wind", Skycons.WIND);
            break;
        case "fog":
            icons.set("fog", Skycons.FOG);
            break;
        case "clear-day":
            icons.set("clear-day", Skycons.CLEAR_DAY);
            break;
        case "clear-night":
            icons.set("clear-night", Skycons.CLEAR_NIGHT);
            break;
        case "partly-cloudy-day":
            icons.set("partly-cloudy-day", Skycons.PARTLY_CLOUDY_DAY);
            break;
        case "partly-cloudy-night":
            icons.set("partly-cloudy-night", Skycons.PARTLY_CLOUDY_NIGHT);
            break;
    }

    icons.play();

    // Попълване на символи от клавиатурата
    $(document.body).on('click touch', ".numPad", function(e){
        var currentAttrValue = $(this).text();
        if($(this).hasClass('large-btn')) {
            var inpVal = "";
        } else {
            var inpVal = $(".formControl").val();
            inpVal += currentAttrValue;
        }
        $(".formControl").val(inpVal);
    });

    var $document = $(document);
    var selector = '[data-rangeslider]';
    var $element = $(selector);

    // For ie8 support
    var textContent = ('textContent' in document) ? 'textContent' : 'innerText';

    // Example functionality to demonstrate a value feedback
    function valueOutput(element) {
        var value = element.value;
        var output = element.parentNode.getElementsByTagName('output')[0] || element.parentNode.parentNode.getElementsByTagName('output')[0];
        var val  = ($(element).attr('step') < 1 ) ? parseFloat(value).toFixed(1) : value;
        output[textContent] = val;
    }

    $document.on('input', 'input[type="range"], ' + selector, function(e) {
        valueOutput(e.target);
    });

    // Example functionality to demonstrate disabled functionality
    $document .on('click', '#js-example-disabled button[data-behaviour="toggle"]', function(e) {
        var $inputRange = $(selector, e.target.parentNode);

        if ($inputRange[0].disabled) {
            $inputRange.prop("disabled", false);
        }
        else {
            $inputRange.prop("disabled", true);
        }
        $inputRange.rangeslider('update');
    });

    // Example functionality to demonstrate programmatic value changes
    $document.on('click', '#js-example-change-value button', function(e) {
        var $inputRange = $(selector, e.target.parentNode);
        var value = $('input[type="number"]', e.target.parentNode)[0].value;

        $inputRange.val(value).change();
    });

    // Example functionality to demonstrate programmatic attribute changes
    $document.on('click', '#js-example-change-attributes button', function(e) {
        var $inputRange = $(selector, e.target.parentNode);
        var attributes = {
            min: $('input[name="min"]', e.target.parentNode)[0].value,
            max: $('input[name="max"]', e.target.parentNode)[0].value,
            step: $('input[name="step"]', e.target.parentNode)[0].value
        };

        $inputRange.attr(attributes);
        $inputRange.rangeslider('update', true);
    });

    // Example functionality to demonstrate destroy functionality
    $document
        .on('click', '#js-example-destroy button[data-behaviour="destroy"]', function(e) {
            $(selector, e.target.parentNode).rangeslider('destroy');
        })
        .on('click', '#js-example-destroy button[data-behaviour="initialize"]', function(e) {
            $(selector, e.target.parentNode).rangeslider({ polyfill: false });
        });

    // Example functionality to test initialisation on hidden elements
    $document
        .on('click', '#js-example-hidden button[data-behaviour="toggle"]', function(e) {
            var $container = $(e.target.previousElementSibling);
            $container.toggle();
        });

    // Basic rangeslider initialization
    $element.rangeslider({

        // Deactivate the feature detection
        polyfill: false,

        // Callback function
        onInit: function() {
            valueOutput(this.$element[0]);
        }
    });
    var p = parseInt($('#temperature').val()).toFixed(1);
  //  console.log(p);
   // $('#temperature').val() ;

}

function sendData() {
    var vent = $("#currentVentPercent").text();
    var temperature = $("#currentTemp").text();
    var lamp = $("#currentLux").text();
    var slope = $("#currentSlope").text();

    var dashboardInfo = {
        "ventPower" : parseInt(vent)  / 10,
        "setTemperature": parseFloat(temperature),
        "lux": parseInt(lamp),
        "blinds"    : parseInt(slope)
    };

    $.ajax({
        type: "POST",
        url: "http://11.0.0.64/jsonReceive.php",
        crossDomain : true,
        success: function (msg) {
            if (msg) {
                console.log("data send");
            } else {
                console.log("error");
            }
        },



        data: JSON.stringify(dashboardInfo)
    });
}
