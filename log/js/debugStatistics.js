function render_debugStatistics() {
    document.querySelectorAll('.debug-stat-chart').forEach(function (canvas) {
        if (canvas.getAttribute('data-rendered') || !canvas.offsetWidth) return;
        var stats = JSON.parse(canvas.getAttribute('data-stats'));
        var lineChart = stats.type === 'line';
        var datasets = [];
        if (typeof stats.average === 'number') datasets.push({
            type: 'line',
            label: stats.averageLabel,
            data: stats.labels.map(function () { return stats.average; }),
            borderColor: '#3267af',
            borderDash: [6, 4],
            borderWidth: 2,
            pointRadius: stats.labels.length === 1 ? 3 : 0,
            fill: false,
            lineTension: 0,
            yAxisID: 'average'
        });
        Object.keys(stats.series).forEach(function (key) {
            var series = stats.series[key];
            datasets.push({type: lineChart ? 'line' : 'bar', label: series.label, data: series.values,
                backgroundColor: series.color, borderColor: series.color, borderWidth: lineChart ? 2 : 0,
                pointRadius: lineChart ? 2 : 0, pointHitRadius: 6, fill: false, lineTension: 0, spanGaps: false, yAxisID: 'counts'});
        });
        function getScale(chart) {
            var visible = datasets.filter(function (dataset, index) {
                return chart ? chart.isDatasetVisible(index) : !dataset.hidden;
            });
            var peak = 1;
            stats.labels.forEach(function (_, i) {
                var stacked = 0;
                visible.forEach(function (dataset) {
                    var value = dataset.data[i] || 0;
                    if (dataset.type === 'bar') {
                        stacked += value;
                    } else {
                        peak = Math.max(peak, value);
                    }
                });
                peak = Math.max(peak, stacked);
            });
            var step = Math.pow(10, Math.floor(Math.log(peak / 5) / Math.LN10));
            var fraction = peak / (5 * step);
            step = Math.max(stats.percent || stats.unit ? 0.01 : 1, step * (fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10));

            return stats.fixedMax ? {max: stats.fixedMax, step: stats.fixedMax / 5} : {max: Math.ceil(peak / step) * step, step: step};
        }
        var scale = getScale();
        new Chart(canvas, {
            type: 'bar',
            data: {labels: stats.labels, datasets: datasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {duration: 0},
                legend: {
                    onClick: function (event, item) {
                        var chart = this.chart;
                        var meta = chart.getDatasetMeta(item.datasetIndex);
                        meta.hidden = meta.hidden === null ? !chart.data.datasets[item.datasetIndex].hidden : null;
                        var scale = getScale(chart);
                        // Линията за средното и колоните трябва да останат на еднаква скала.
                        chart.options.scales.yAxes.forEach(function (axis) {
                            axis.ticks.max = scale.max;
                            axis.ticks.stepSize = scale.step;
                        });
                        chart.update();
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function (item, data) {
                            var suffix = stats.percent ? '%' : (stats.unit ? ' ' + stats.unit : '');
                            var value = Number(item.yLabel).toLocaleString(undefined, {maximumFractionDigits: 2}) + suffix;
                            return data.datasets[item.datasetIndex].label + ': ' + value;
                        }
                    }
                },
                scales: {
                    xAxes: [{stacked: !lineChart, ticks: {maxTicksLimit: 24, maxRotation: 45}}],
                    yAxes: [
                        {id: 'counts', stacked: !lineChart, scaleLabel: {display: !!stats.unit, labelString: stats.unit || ''}, ticks: {beginAtZero: true, min: 0, max: scale.max, stepSize: scale.step, callback: function (value) { return stats.percent ? value + '%' : value; }}},
                        {id: 'average', display: false, ticks: {beginAtZero: true, min: 0, max: scale.max, stepSize: scale.step}}
                    ]
                }
            }
        });
        canvas.setAttribute('data-rendered', 'yes');
    });
}
