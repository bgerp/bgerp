var icons = new Skycons({"color": "#489fc0"});

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
    var time = today.getHours() + "<span class='blink05'>:</span>" + min;
    $('#currentTime').append(time);

    var weatherStat = data.low ? "Температурите ще са в интервала от " + parseInt(data.low) + " до " + parseInt(data.high) + "°C." : "Няма информация за текущата прогноза.";
    $('.weatherStat').html(weatherStat);



    $('#navbarCollapse a:first').tab('show');
 

    $(document).on('click', '.dropdown-menu', function (e) {
        e.stopPropagation();
    });

    setIcon("weatherNow",  data.icon);
    setIcon("weather-icon-3", 'clear-day');
    setIcon("weather-icon-6", 'wind');

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


function setIcon(el, icon) {
    switch (icon) {
        case "rain":
            icons.add(el, Skycons.RAIN);
            break;
        case "cloudy":
            icons.add(el, Skycons.CLOUDY);
            break;
        case "sleet":
            icons.add(el, Skycons.SLEET);
            break;
        case "snow":
            icons.add(el, Skycons.SNOW);
            break;
        case "wind":
            icons.add(el, Skycons.WIND);
            break;
        case "fog":
            icons.add(el, Skycons.FOG);
            break;
        case "clear-day":
            icons.add(el, Skycons.CLEAR_DAY);
            break;
        case "clear-night":
            icons.add(el, Skycons.CLEAR_NIGHT);
            break;
        case "partly-cloudy-day":
            icons.add(el, Skycons.PARTLY_CLOUDY_DAY);
            break;
        case "partly-cloudy-night":
            icons.add(el, Skycons.PARTLY_CLOUDY_NIGHT);
            break;
    }
}

/**
 * Scalar scaling function.
 * @param {Target value for rescaling.} target
 * @param {Input max and min limits.} in_limit
 * @param {Output max and min limits.} out_limit
 * @returns Rescaled input value.
 */
function l_scale(target, in_limit, out_limit) {
    return (target - in_limit[0]) * (out_limit[1] - out_limit[0]) / (in_limit[1] - in_limit[0]) + out_limit[0]
}

/**
 * Get registers from Zontromat software.
 * @param {Array of registers to be read from the Zontromat software.} registers
 */
function getRegisters(registers) {

    // URL
    // var url = "http://176.33.1.164:8890/api/v1/bgerp/registers/get";
    var url = "http://127.0.0.1:8890/api/v1/bgerp/registers/get";

    // Request body.
    var requestBody = {
        token: "5LtbUH2ZU615hdyQ",
        registers: registers,

    }

    // Stringified request body.
    var strRequestBody = JSON.stringify(requestBody);

    // Request
    var request = {
        url: url,
        type: "POST",
        async: true,
        crossDomain: true,
        data: {params: strRequestBody},
        success: function(data) {
            console.log(data);
        },
        complete: function() {
            console.log("Complete");
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log(xhr);
        },
    }

    $.ajax(request);
}

/**
 * Set registers to Zontromat software.
 * @param {Array of registers to be write to the Zontromat software.} registers
 */
function setRegisters(registers) {
    // URL
    // var url = "http://176.33.1.164:8890/api/v1/bgerp/registers/set";
    var url = "http://127.0.0.1:8890/api/v1/bgerp/registers/set";

    // Request body.
    var requestBody = {
        token: "5LtbUH2ZU615hdyQ",
        registers: registers,
    }

    // Stringified request body.
    var strRequestBody = JSON.stringify(requestBody);

    // Request
    var request = {
        url: url,
        type: "POST",
        async: true,
        crossDomain: true,
        data: {params: strRequestBody},
        success: function(data) {
            console.log(data);
        },
        complete: function() {
            console.log("Complete");
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log(xhr);
        },
    }

    $.ajax(request);
}

/**
 * Temperature slider on change event callback.
 * @param {Temperature slider from the frontend.} input
 */
function setTemperature(input) {
    const slider_value = parseInt(input.value, 10);
    // var scaled_value = l_scale(slider_value, [23.0, 28.0], [0.0, 10000.0]);

    // TODO: Front-end animation.

    setRegisters({"hvac.adjust_temp_1": slider_value});
}

/**
 * Ventilation slider on change event callback.
 * @param {Lights slider from the frontend.} input
 */
function setVentilation(input) {
    const vent = parseInt(input.value, 10);

    const speed = (270 - 2 * vent) / 100;
    let root = document.documentElement;
    root.style.setProperty('--rotation-speed', speed + "s");

    // getRegisters(["sys.disc.used"]);
    setRegisters({"vent.op_setpoint_1": vent});
}

/**
 * Lights slider on change event callback.
 * @param {Lights slider from the frontend.} input
 */
function setLights(input) {
    const slider_value = parseInt(input.value, 10);
    var scaled_value = l_scale(slider_value, [0.0, 100.0], [0.0, 10000.0]);

    // TODO: Front-end animation.

    setRegisters({"light.target_illum": scaled_value});
}

/**
 * Blinds slider on change event callback.
 * @param {Blinds slider from the frontend.} input
 */
function setBlinds(input) {
    const slider_value = parseInt(input.value, 10);

    // TODO: Front-end animation.

    setRegisters({"blinds.position": slider_value});
}


/**
 *
 */
function fixTimer()
{
    var today = new Date();
    var min = today.getMinutes() >= 10 ? today.getMinutes() : '0' + today.getMinutes();
    var time = today.getHours() + "<span class='blink05'>:</span>" + min;
    $('#currentTime').html(time);
}