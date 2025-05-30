/**
 * Базови екшъни
 */
function cashActions() {

    // При натискане на бутона за банково плащане
    $(document.body).on('click', ".cardPaymentBtn", function(e){
        let element = $(this);
        let amount = parseFloat(element.attr("data-amount")).toFixed(2);
        console.log("SEND " + amount);
        $(".fullScreenCardPayment").css("display", "block");

        sessionStorage.setItem('pressed', element.attr('id'));
        let deviceUrl = element.attr("data-deviceUrl");
        let deviceComPort = element.attr("data-deviceComPort");

        getAmount(amount, deviceUrl, deviceComPort);
    });

    // При натискане на бутона за банково плащане
    $(document.body).on('click', ".closePaymentModal", function(e){
        $(".fullScreenCardPayment").css("display", "none");

        // Показване на статуса за отказ
        let element = getPressedContoBtn();
        let msg = element.attr("data-oncancel");
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
        let params = {param:'manual', redirectUrl:returnUrl};
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
    let pressed = sessionStorage.getItem('pressed');
    let element = $("#" + pressed);

    return element;
}


/**
 * Връща резултат при успешно свързване с банковия терминал
 *
 * @param res
 */
function getAmountRes(res, sendAmount)
{
    let element = getPressedContoBtn();
    let successUrl = element.attr("data-successUrl");

    console.log("RES: " + res + " S " + sendAmount);
    let resString = String(res);
    if (resString.startsWith("OK")) {

        let returnUrl = element.attr("data-returnUrl");
        console.log("RES IS OK");

        let parts = resString.split("|");
        let rightPart = parts.slice(1).join("|"); // всичко след първото "|"
        console.log("RIGHT PART: " + rightPart);

        // Вземаме само първия елемент от rightPart, ако има няколко
        let firstNumberStr = rightPart.split("|")[0];

        // Парсираме като число и форматираме до 2 дес. знака
        let resAmount = parseFloat(firstNumberStr).toFixed(2);
        let sendAmountFormatted = parseFloat(sendAmount).toFixed(2);

        if(resAmount === sendAmountFormatted){
            let resObj = new Object();
            resObj['url'] = successUrl;

            let params = {param:'card', redirectUrl:returnUrl};
            console.log("SUCCESS CALL TO: " + successUrl);
            console.log(params);
            getEfae().process(resObj, params);
        } else {
            console.log("DIFF AMOUNT");
            let error = pressedCardPayment.attr("data-diffamount");
            error += " " + resAmount;
            render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});
        }
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