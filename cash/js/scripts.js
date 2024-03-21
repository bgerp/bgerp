/**
 * Базови екшъни
 */
function cashActions() {

    // При натискане на бутона за банково плащане
    $(document.body).on('click', ".cardPaymentBtn", function(e){
        var element = $(this);
        var amount = parseFloat(element.attr("data-amount")).toFixed(2);
        console.log("SEND " + amount);
        $(".fullScreenCardPayment").css("display", "block");

        sessionStorage.setItem('pressed', element.attr('id'));
        getAmount(amount);
    });

    // При натискане на бутона за банково плащане
    $(document.body).on('click', ".closePaymentModal", function(e){
        $(".fullScreenCardPayment").css("display", "none");

        // Показване на статуса за отказ
        var element = getPressedContoBtn();
        var msg = element.attr("data-oncancel");
        render_showToast({timeOut: 800, text: msg, isSticky: true, stayTime: 8000, type: "error"});
    });

    // При натискане на бутона за потвърждаване на банковото плащане
    $(document.body).on('click', ".confirmPayment", function(e){
        let element = getPressedContoBtn();

        let successUrl = element.attr("data-successUrl");
        let returnUrl = element.attr("data-returnUrl");


        let resObj = new Object();
        resObj['url'] = successUrl;

        // Директно обръщане към успешното плащане - без да се чака отговор от терминала
        var params = {param:'manual',redirectUrl:returnUrl};
        console.log("MANUAL CALL TO: " + successUrl);
        getEfae().process(resObj, params);
        $(".fullScreenCardPayment").css("display", "none");
    });
}


/**
 * Кой е натиснатия бутон
 */
function getPressedContoBtn()
{
    var pressed = sessionStorage.getItem('pressed');
    var element = $("#" + pressed);

    return element;
}


/**
 * Връща резултат при успешно свързване с банковия терминал
 *
 * @param res
 */
function getAmountRes(res)
{
    let element = getPressedContoBtn();
    let successUrl = element.attr("data-successUrl");

    if(res == 'OK'){
        let returnUrl = element.attr("data-returnUrl");
        console.log("RES IS OK");

        let resObj = new Object();
        resObj['url'] = successUrl;

        let params = {param:'card',redirectUrl:returnUrl};
        console.log("SUCCESS CALL TO: " + successUrl);
        console.log(params);
        getEfae().process(resObj, params);
    } else {
        let error = element.attr("data-onerror");
        render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});
        console.log("RES ERROR/" + res + "/");
    }

    $(".fullScreenCardPayment").css("display", "none");
}


/**
 * Връща резултат при грешка със свързването с банковия терминал
 *
 * @param res
 */
function getAmountError(err)
{
    $(".fullScreenCardPayment").css("display", "none");
    let element = getPressedContoBtn();
    let error = element.attr("data-onerror");
    render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});
    console.log("ERR:" + err);
}


/**
 * След успешно маркиране на банковото плащане
 *
 * @param res
 */
function render_successfullCardPayment(data)
{
    $(".fullScreenCardPayment").css("display", "none");
    console.log(data);
    if(data.redirect){
        document.location = data.url;
    } else {
        let resObj = new Object();
        resObj['url'] = data.url;

        $('.fullScreenBg').css('display', 'block');
        console.log("PRINT TO: " + data.url);
        getEfae().process(resObj);
    }
}