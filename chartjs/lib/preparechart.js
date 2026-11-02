/** Изчертава новите или променените диаграми, включително след AJAX. */
function prepareChart(animation) {
    // цветове за графиките
    var colors = ["rgba(10,161,255,1)", "rgba(252,162,53,1)", "rgba(112,193,15,1)","rgba(255,33,23,1)", "rgba(51,254,224,1)",
        "rgba(200,200,200,1)" ,"rgba(34,101,10,1)", "rgba(141,12,201,1)","rgba(247,247,17,1)", "rgba(50,35,183,1)","rgba(99,41,22,1)", "rgba(227,113,196,1)", "rgba(203,246,144,1)", "rgba(203,144,246,0.5)"];

    var fillColor = ["rgba(10,161,255,0.5)", "rgba(252,162,53,0.5)", "rgba(112,193,15,0.5)","rgba(255,33,23,0.5)", "rgba(51,254,224,0.5)",
        "rgba(200,200,200,0.5)" ,"rgba(34,101,10,0.5)", "rgba(141,12,201,0.5)","rgba(247,247,17,0.5)", "rgba(50,35,183,0.5)","rgba(99,41,22,0.5)", "rgba(227,113,196,0.5)", "rgba(203,246,144,0.5)", "rgba(203,144,246,0.5)"];
    var modern = parseInt(Chart.version || '2', 10) >= 3;

    // Освобождаваме собствените графики, чиито контейнери AJAX е заменил.
    jQuery.each(Chart.instances, function (key, instance) {
        var canvas = instance.canvas || (instance.chart && instance.chart.canvas);
        if (canvas && canvas.bgerpChart === instance && !document.documentElement.contains(canvas) && (modern || canvas.parentNode)) {
            instance.destroy();
            delete canvas.bgerpChart;
        }
    });

    $('.diagramCanvas').each(function(){
        var source = $(this).attr('data-data');
        var charType = $(this).attr('data-type');
        var signature = charType + ':' + source;
        if (this.bgerpChart && this.bgerpChartSource === signature) return;
        var chartData = JSON.parse(source);
        if (this.bgerpChart) {
            this.bgerpChart.destroy();
            delete this.bgerpChart;
        }
        if (!chartData) return;
        var config;
        var animationDuration = animation ? 1000 : 0;

        if (charType == 'pie') {
            var value = [];
            var label = [];
            jQuery.each(chartData.info, function (key, val) {
                label.push(key);
                value.push(val);
            });
            config = {
                type: 'pie',
                data: {
                    labels: label,
                    datasets: [{data: value, backgroundColor: fillColor, borderColor: colors, borderWidth: 1}]
                },
                options: {animation: {duration: animationDuration}}
            };
            var legend = {display: true, position: 'right'};
            if (modern) config.options.plugins = {legend: legend};
            else config.options.legend = legend;
        } else if (charType == 'bar' || charType == 'line') {
            var res = {labels: chartData.labels, datasets: []};
            jQuery.each(chartData.values, function (key, value) {
                var i = res.datasets.length % colors.length;
                var dataset = {
                    label: key,
                    data: value,
                    borderColor: colors[i],
                    backgroundColor: fillColor[i],
                    borderWidth: 1
                };
                // В 4.x попълването и кривината вече не съвпадат с defaults на 2.x.
                if (modern && charType == 'line') {
                    dataset.fill = true;
                    dataset.tension = 0.4;
                }
                res.datasets.push(dataset);
            });
            config = {
                type: charType,
                data: res,
                options: {
                    scales: modern ? {x: {display: true}} : {xAxes: [{display: true}]},
                    animation: {duration: animationDuration}
                }
            };
        }
        if (!config) return;
        if (modern) config.options.aspectRatio = 700 / 570;
        this.bgerpChart = new Chart(this, config);
        this.bgerpChartSource = signature;
    });
}

/** Обработчик на стандартното AJAX обновяване. */
function render_prepareChart(){
    prepareChart(false);
}
