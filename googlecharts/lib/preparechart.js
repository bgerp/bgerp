function prepareGoogleChart( idElem, chartData, charType) {

    if (!$(chartData).length) return;

    google.setOnLoadCallback(drawChart);

    function drawChart() {
        var dataArr = [];

        if (charType === 'pie') {
            dataArr[0] = ['key', 'value'];
            jQuery.each(chartData.info, function (key, val) {
                var temp = [];
                temp.push(key, val);
                dataArr.push(temp);
            });
            var data = google.visualization.arrayToDataTable(dataArr);

            var options = {
                title: chartData.legendTitle
            };
            var chart = new google.visualization.PieChart(document.getElementById(idElem));

        } else if (charType === 'line') {
            var headers = [];
            headers[0] = '';

            jQuery.each(chartData.values, function (key, val) {
                headers.push(key);
                dataArr[0] = headers;
                var temp = [];
                for (var i = 0; i <= chartData.labels.length; i++) {
                    if (!temp[i]) {
                        temp[i] = [];
                    }
                    temp[i].push(chartData.labels.shift());
                    jQuery.each(chartData.values, function (key, val) {
                        temp[i].push(val.shift());
                    });
                    dataArr.push(temp[i]);
                }
            });

            var data = google.visualization.arrayToDataTable(dataArr);

            var options = {
                title: chartData.legendTitle,
                curveType: 'function'
            };

            var chart = new google.visualization.LineChart(document.getElementById(idElem));

        } else if (charType === 'bar' || charType === 'hbar' ) {
            var headers = [];
            headers[0] = '';
            jQuery.each(chartData.values, function (key, val) {
                headers.push(key);
                dataArr[0] = headers;
                var temp = [];
                for(var i=0;i<=chartData.labels.length;i++){
                    if(!temp[i]){
                        temp[i] = [];
                    }
                    temp[i].push(chartData.labels.shift());
                    jQuery.each(chartData.values, function (key, val) {
                        temp[i].push(val.shift());
                    });
                    dataArr.push(temp[i]);
                }
            });

            var data = google.visualization.arrayToDataTable(dataArr);

            var options = {
                title: chartData.legendTitle,
                curveType: 'function'
            };

            if(charType == 'bar'){
                var chart = new google.charts.Bar(document.getElementById(idElem));
            } else {
                var chart = new google.visualization.BarChart(document.getElementById(idElem));
            }
        }

        chart.draw(data, options);

        function resizeHandler () {
            chart.draw(data, options);
        }
        if (window.addEventListener) {
            window.addEventListener('resize', resizeHandler, false);
        }
        else if (window.attachEvent) {
            window.attachEvent('onresize', resizeHandler);
        }
    }
}

