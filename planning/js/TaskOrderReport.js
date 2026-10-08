function renderOptimizationIdleReport(changes, totals, metrics, stats, hasNetIdleIncrease, options)
{
    removeOptimizationIdleReport();

    options = options || {};
    changes = changes || [];
    totals = totals || {};
    metrics = metrics || {};
    stats = stats || {};
    let isSaveReport = options.mode === 'save' || options.mode === 'savePreview';
    let hasImprovement = Boolean(stats.hasImprovement);
    let report = $('<section>', {
        id: 'optimizationIdleReport',
        class: hasNetIdleIncrease ? 'hasNetIdleIncrease' : ((hasImprovement || isSaveReport) ? 'hasOptimizationImprovement' : 'hasNoOptimizationImprovement'),
        role: 'status'
    });
    let header = $('<div>', {class: 'optimizationIdleReportHeader'});
    header.append($('<strong>').text(options.title || 'Резултат от предварителното оптимизиране'));
    let closeReport = typeof options.close === 'function' ? options.close : removeOptimizationIdleReport;
    header.append($('<button>', {
        type: 'button',
        class: 'optimizationIdleReportClose',
        title: 'Затвори',
        'aria-label': 'Затвори'
    }).text('×').on('click', closeReport));
    report.append(header);
    if (!isSaveReport) {
        let testedCandidates = Number(stats.testedCandidates) || 0;
        let duration = Number(stats.duration) || 0;
        let searchResult = hasImprovement
            ? 'Намерен е по-добър вариант според приоритетите: планиране без застъпване, по-малко закъснели задания, по-малко общо закъснение и престои.'
            : 'Не е намерен по-добър безопасен вариант. Текущата подредба е запазена.';
        let searchResultBlock = $('<div>', {class: 'optimizationSearchResult'})
            .append($('<div>').append($('<strong>').text(searchResult)));
        if (hasImprovement && metrics.improved) {
            searchResultBlock.children().first().append($('<div>', {class: 'optimizationImprovedMetrics'})
                .text('Подобрени показатели: ' + metrics.improved + '.'));
        }
        searchResultBlock.append($('<span>').text('Проверени варианти: ' + testedCandidates + '; време: ' + duration + ' сек.'));
        report.append(searchResultBlock);
    }
    report.append($('<div>', {class: 'optimizationIdleReportHint'})
        .text(options.hint || (hasImprovement
            ? 'Прегледайте новата подредба и натиснете „Запис“, за да я приемете, или „Върни оптимизацията“, за да я отмените.'
            : 'Може да продължите с ръчно подреждане или да затворите този отчет.')));

    const createPlanMetric = function (title, before, after, change, changeSeconds, detail) {
        let metricClass = changeSeconds < 0 ? 'optimizationMetricImproved'
            : (changeSeconds > 0 ? 'optimizationMetricWorsened' : 'optimizationMetricUnchanged');
        let metric = $('<div>', {class: 'optimizationPlanMetric'});
        metric.append($('<span>', {class: 'optimizationPlanMetricLabel'}).text(title));
        metric.append($('<span>', {class: 'optimizationPlanMetricValue'}).text((before || '—') + ' → ' + (after || '—')));
        metric.append($('<strong>', {class: 'optimizationPlanMetricChange ' + metricClass})
            .text('Промяна: ' + (change || '0 мин.')));
        if (detail) metric.append($('<small>', {class: 'optimizationPlanMetricDetail'}).text(detail));

        return metric;
    };
    if (Object.keys(metrics).length) {
        let targetDetailParts = [];
        if (metrics.targetLastTask) targetDetailParts.push('последна ' + metrics.targetLastTask);
        if (metrics.targetEnd) targetDetailParts.push('край ' + metrics.targetEnd);
        let globalDetailParts = [];
        if (metrics.globalLastTask) globalDetailParts.push('последна ' + metrics.globalLastTask);
        if (metrics.globalLastAssetTitle) globalDetailParts.push(metrics.globalLastAssetTitle);
        if (metrics.globalEnd) globalDetailParts.push('край ' + metrics.globalEnd);
        let planMetrics = $('<div>', {class: 'optimizationGlobalMetrics'});
        let missingJobCompletionsChange = Number(metrics.missingJobCompletionsChange) || 0;
        planMetrics.append(createPlanMetric(
            'Задания без изчислен край',
            String(Number(metrics.missingJobCompletionsBefore) || 0),
            String(Number(metrics.missingJobCompletionsAfter) || 0),
            (missingJobCompletionsChange > 0 ? '+' : '') + missingJobCompletionsChange + ' задания',
            missingJobCompletionsChange,
            ''
        ));
        let lateJobsChange = (Number(metrics.lateJobsAfter) || 0) - (Number(metrics.lateJobsBefore) || 0);
        planMetrics.append(createPlanMetric(
            'Закъснели задания',
            String(Number(metrics.lateJobsBefore) || 0),
            String(Number(metrics.lateJobsAfter) || 0),
            (lateJobsChange > 0 ? '+' : '') + lateJobsChange + ' задания',
            lateJobsChange,
            ''
        ));
        planMetrics.append(createPlanMetric(
            'Общо закъснение по всички задания',
            metrics.tardinessBefore,
            metrics.tardinessAfter,
            metrics.tardinessChange,
            Number(metrics.tardinessChangeSeconds) || 0,
            ''
        ));
        planMetrics.append(createPlanMetric(
            'Избрана машина: ' + (metrics.targetAssetTitle || ''),
            metrics.targetBefore,
            metrics.targetAfter,
            metrics.targetChange,
            Number(metrics.targetChangeSeconds) || 0,
            targetDetailParts.join(' — ')
        ));
        planMetrics.append(createPlanMetric(
            'Целият производствен план (всички машини)',
            metrics.before,
            metrics.after,
            metrics.change,
            Number(metrics.changeSeconds) || 0,
            globalDetailParts.join(' — ')
        ));
        report.append(planMetrics);
    }

    if (!changes.length) {
        report.append($('<div>', {class: 'optimizationIdleNoChanges'})
            .text('Няма промяна в престоите по машините.'));
    } else {
        let increasedCount = changes.filter((change) => Number(change.changeSeconds) > 0).length;
        let decreasedCount = changes.filter((change) => Number(change.changeSeconds) < 0).length;
        let summary = $('<div>', {class: 'optimizationIdleSummary'});
        summary.append($('<span>', {class: 'idleIncreaseCount'})
            .text('Увеличени: ' + increasedCount + ' машини — общо ' + (totals.increased || '0 мин.')));
        summary.append($('<span>', {class: 'idleDecreaseCount'})
            .text('Намалени: ' + decreasedCount + ' машини — общо ' + (totals.decreased || '0 мин.')));
        let netSeconds = Number(totals.netSeconds) || 0;
        let netClass = netSeconds > 0 ? 'idleNetIncrease' : (netSeconds < 0 ? 'idleNetDecrease' : 'idleNetUnchanged');
        summary.append($('<span>', {class: netClass})
            .text('Нетна промяна на престоите: ' + (totals.net || '0 мин.')));
        report.append(summary);

        let table = $('<table>', {class: 'optimizationIdleTable'});
        table.append($('<thead>').append($('<tr>')
            .append($('<th>').text('Машина'))
            .append($('<th>').text('Преди'))
            .append($('<th>').text('След'))
            .append($('<th>').text('Промяна'))));
        let body = $('<tbody>');
        changes.forEach((change) => {
            let directionClass = Number(change.changeSeconds) > 0 ? 'idleIncreased' : 'idleDecreased';
            body.append($('<tr>', {class: directionClass})
                .append($('<td>', {class: 'optimizationIdleAsset'}).text(change.assetTitle || ''))
                .append($('<td>').text(change.before || '0 мин.'))
                .append($('<td>').text(change.after || '0 мин.'))
                .append($('<td>', {class: 'optimizationIdleDifference'}).text(change.change || '')));
        });
        table.append(body);
        report.append($('<div>', {class: 'optimizationIdleTableHolder'}).append(table));
    }

    if (options.confirmLabel || options.cancelLabel) {
        let actions = $('<div>', {class: 'optimizationIdleReportActions'});
        if (options.cancelLabel) {
            actions.append($('<button>', {type: 'button', class: 'optimizationIdleReportCancel'})
                .text(options.cancelLabel)
                .on('click', typeof options.cancel === 'function' ? options.cancel : closeReport));
        }
        if (options.confirmLabel) {
            actions.append($('<button>', {type: 'button', class: 'optimizationIdleReportConfirm'})
                .text(options.confirmLabel)
                .on('click', typeof options.confirm === 'function' ? options.confirm : closeReport));
        }
        report.append(actions);
    }

    $('body').append(report);
}


function removeOptimizationIdleReport()
{
    $('#optimizationIdleReport').remove();
    $('#savedOrderReportBackdrop').remove();
}


function showTargetTimesPreview(data)
{
    const accepted = $('input[name="targetPreviewAccepted"]');
    const form = accepted.closest('form');
    const correct = function () {
        removeOptimizationIdleReport();
        $('body').css('overflow', '');
        accepted.val('no');
    };
    renderOptimizationIdleReport(data.idleChanges, data.idleTotals, data.optimizationMetrics, {}, Boolean(data.hasNetIdleIncrease), {
        mode: 'savePreview',
        title: 'Предварителен резултат от целевите времена',
        hint: 'Промените още не са записани. Прегледайте новата подредба, сроковете и престоите по всички машини.',
        close: correct,
        cancelLabel: 'Корекция',
        cancel: correct,
        confirmLabel: 'Приложи',
        confirm: function () {
            accepted.val('yes');
            if (data.ignoreWarnings) {
                const ignore = form.find('input[name="Ignore"]');
                if (ignore.length) ignore.prop('checked', true).val('1');
                else form.append($('<input>', {type: 'hidden', name: 'Ignore', value: '1'}));
            }
            form.append($('<input>', {type: 'hidden', name: 'Cmd[' + data.command + ']', value: '1'}));
            form.trigger('submit');
        }
    });
    if (data.operationText) {
        $('#optimizationIdleReport .optimizationIdleReportHint').after(
            $('<div>', {class: 'optimizationIdleReportHint'}).append($('<strong>').text(data.operationText))
        );
    }
    $('body').append($('<div>', {id: 'savedOrderReportBackdrop'}));
    $('body').css('overflow', 'hidden');
    // Отказът от редакцията е отделен от връщането към корекция.
    $('#optimizationIdleReport .optimizationIdleReportActions').prepend(
        $('<button>', {type: 'button', class: 'optimizationIdleReportCancel'}).text('Отказ').on('click', function () {
            if (data.cancelUrl) window.location.href = data.cancelUrl;
            else correct();
        })
    );
}
