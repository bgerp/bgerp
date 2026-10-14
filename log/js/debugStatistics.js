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
        var peak = Math.max(1, Math.max.apply(null, stats.labels.map(function (_, i) {
            return Object.keys(stats.series).reduce(function (value, key) {
                var next = stats.series[key].values[i] || 0;
                return lineChart ? Math.max(value, next) : value + next;
            }, 0);
        })));
        var step = Math.pow(10, Math.floor(Math.log(peak / 5) / Math.LN10));
        var fraction = peak / (5 * step);
        step = Math.max(stats.percent || stats.unit ? 0.01 : 1, step * (fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10));
        var max = Math.ceil(peak / step) * step;
        if (stats.fixedMax) {
            max = stats.fixedMax;
            step = max / 5;
        }
        new Chart(canvas, {
            type: 'bar',
            data: {labels: stats.labels, datasets: datasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {duration: 0},
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
                        {id: 'counts', stacked: !lineChart, scaleLabel: {display: !!stats.unit, labelString: stats.unit || ''}, ticks: {beginAtZero: true, min: 0, max: max, stepSize: step, callback: function (value) { return stats.percent ? value + '%' : value; }}},
                        {id: 'average', display: false, ticks: {beginAtZero: true, min: 0, max: max}}
                    ]
                }
            }
        });
        canvas.setAttribute('data-rendered', 'yes');
    });
}
