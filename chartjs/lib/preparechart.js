function prepareChart( idElem, chartData, charType) {

    if (!$(chartData).length) return;

    // цветове за графиките
    var colors = ["rgba(10,161,255,1)", "rgba(252,162,53,1)", "rgba(112,193,15,1)","rgba(255,33,23,1)", "rgba(51,254,224,1)",
        "rgba(200,200,200,1)" ,"rgba(34,101,10,1)", "rgba(141,12,201,1)","rgba(247,247,17,1)", "rgba(50,35,183,1)","rgba(99,41,22,1)", "rgba(227,113,196,1)"];

    var fillColor = ["rgba(10,161,255,0.5)", "rgba(252,162,53,0.5)", "rgba(112,193,15,0.5)","rgba(255,33,23,0.5)", "rgba(51,254,224,0.5)",
        "rgba(200,200,200,0.5)" ,"rgba(34,101,10,0.5)", "rgba(141,12,201,0.5)","rgba(247,247,17,0.5)", "rgba(50,35,183,0.5)","rgba(99,41,22,0.5)", "rgba(227,113,196,0.5)"];

    // ако чертаем торта
    if (charType == "pie") {
        var data = [];
        // от получените данни генериране необходимата структура
        var title = chartData.legendTitle;
        var i = 0;
        jQuery.each(chartData.info, function (key, val) {
            var temp = {};
            temp.value = val;
            temp.label = key;
            temp.color = colors[i];
            data.push(temp);
            i++;
        });

        // чертаем тортата с легенда към нея
        var pie = $('#' + idElem).get(0).getContext('2d');
        var myNewChart = new Chart(pie).Pie(data, {
            responsive: true, animation: false,
            legendTemplate: "<ul class=\"chart-legend <%=name.toLowerCase()%>-legend\"><li class=\"legend-title\">" + title + "</li>" +
            "<% for (var i=0; i<segments.length; i++){%><li>" +
            "<span style=\"background-color:<%=segments[i].fillColor%> !important\"></span>" +
            "<%if(segments[i].label){%><%=segments[i].label%><%}%>: <%if(segments[i].value){%><%=segments[i].value%><%}%></li><%}%></ul>"
        });
        // добавяме легендата
        $('#' + idElem).parent().append(myNewChart.generateLegend());

    } else if(charType == "bar" || charType == "line") {
        var res = {};
        res.labels = chartData.labels;
        res.datasets = [];
        var title = chartData.legendTitle;
        var len = Object.keys(chartData.values).length;

        for(var i=0; i < len; i++) {
            if(!res.datasets[i])
                res.datasets[i]= {};

            if(!res.datasets[i].label)
                res.datasets[i].label = [];

            if(!res.datasets[i].pointColor)
                res.datasets[i].pointColor = [];

            if(!res.datasets[i].strokeColor)
                res.datasets[i].strokeColor = [];

            if(!res.datasets[i].fillColor)
                res.datasets[i].fillColor = [];

            if(!res.datasets[i].data)
                res.datasets[i].data = [];
        }
        i = 0;
        // от получените данни генериране необходимата структура
        jQuery.each(chartData.values, function (key, value) {
                res.datasets[i].label = key;
                res.datasets[i].data = value;
                res.datasets[i].pointColor = colors[i];
                res.datasets[i].strokeColor = colors[i];

                // ако графиката е тип Барове, ни трябва и фон
                if(charType == 'bar'){
                    res.datasets[i].fillColor = fillColor[i];
                }
            i++;
        });

        // чертаем графиката, в зависимост от типа
        var chart = $('#' + idElem).get(0).getContext('2d');
        if(charType == "bar" ){
            var myNewChart = new Chart(chart).Bar(res, {
                responsive: true, animation: false,
                tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>",
                multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>",
                legendTemplate: "<ul class=\" chart-legend <%=name.toLowerCase()%>-legend\"><li class=\"legend-title\">" + title + "</li>" +
                "<% for (var i=0; i<datasets.length; i++){%><li>" +
                "<span style=\"background-color:<%=datasets[i].fillColor%> !important; border: 2px solid <%=datasets[i].strokeColor%> !important\"></span>" +
                "<%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"

            });

        } else {
            var myNewChart = new Chart(chart).Line(res, {
                responsive: true, animation: false, datasetFill : false,
                tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>",
                multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>",
                legendTemplate: "<ul class=\" chart-legend <%=name.toLowerCase()%>-legend\"><li class=\"legend-title\">" + title + "</li>"  +
                "<% for (var i=0; i<datasets.length; i++){%><li>" +
                "<span style=\"background-color:<%=datasets[i].strokeColor%> !important;\"></span>" +
                "<%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"
        }
        $('#' + idElem).parent().append(myNewChart.generateLegend());
    }
}