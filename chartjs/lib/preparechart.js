function prepareChart( idElem, chartData, charType) {
    var ctx = document.getElementById(idElem);
    if (!$(chartData).length) return;

    // цветове за графиките
    var colors = ["rgba(10,161,255,1)", "rgba(252,162,53,1)", "rgba(112,193,15,1)","rgba(255,33,23,1)", "rgba(51,254,224,1)",
        "rgba(200,200,200,1)" ,"rgba(34,101,10,1)", "rgba(141,12,201,1)","rgba(247,247,17,1)", "rgba(50,35,183,1)","rgba(99,41,22,1)", "rgba(227,113,196,1)", "rgba(203,246,144,1)", "rgba(203,144,246,0.5)"];

    var fillColor = ["rgba(10,161,255,0.5)", "rgba(252,162,53,0.5)", "rgba(112,193,15,0.5)","rgba(255,33,23,0.5)", "rgba(51,254,224,0.5)",
        "rgba(200,200,200,0.5)" ,"rgba(34,101,10,0.5)", "rgba(141,12,201,0.5)","rgba(247,247,17,0.5)", "rgba(50,35,183,0.5)","rgba(99,41,22,0.5)", "rgba(227,113,196,0.5)", "rgba(203,246,144,0.5)", "rgba(203,144,,246,0.5)"];


    // ако чертаем торта
    if (charType == "pie") {

        var title = chartData.legendTitle;
        var suffix = chartData.suffix;
        if (!title) title = "";
        if (!suffix) suffix = "";
        var value = [];
        var label = [];

        jQuery.each(chartData.info, function (key, val) {
            label.push(key);
            value.push(val);
        });

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: label,
                datasets: [{
                    data: value,
                    backgroundColor: fillColor,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                legend: {
                    display: true,
                    position: 'right',
                },
            }
        });
    } else if(charType == "bar" || charType == "line") {
        var res = {};

        res.labels = chartData.labels;
        res.datasets = [];
        var title = chartData.legendTitle;
        var len = Object.keys(chartData.values).length;

        for (var i = 0; i < 3; i++) {
            if (!res.datasets[i])
                res.datasets[i] = {};

            if (!res.datasets[i].label)
                res.datasets[i].label = [];

            if (!res.datasets[i].pointColor)
                res.datasets[i].pointColor = [];

            if (!res.datasets[i].strokeColor)
                res.datasets[i].strokeColor = [];

            if (!res.datasets[i].fillColor)
                res.datasets[i].fillColor = [];

            if (!res.datasets[i].data)
                res.datasets[i].data = [];
        }
        i = 0;

        // от получените данни генериране необходимата структура
        jQuery.each(chartData.values, function (key, value) {
            res.datasets[i].label = key;
            res.datasets[i].data = value;
            res.datasets[i].borderColor = colors[i];
            res.datasets[i].backgroundColor = fillColor[i];
            res.datasets[i].borderWidth = 1;

            // ако графиката е тип Барове, ни трябва и фон
            if (charType == 'bar') {
                res.datasets[i].fillColor = fillColor[i];
            }
            i++;
        });

        new Chart(ctx, {
            type: charType,
            data: res,
            options: {
                scales: {
                    xAxes: [{
                        display: true
                    }]
                }
            }
        });
    }
}
