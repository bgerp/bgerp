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
    $('body').removeClass('targetTimePreviewOpen');
}


function showTargetTimesPreview(data, selectedIndex)
{
    const accepted = $('input[name="targetPreviewAccepted"]');
    const form = accepted.closest('form');
    const alternatives = data.alternatives || [];
    const choice = alternatives[selectedIndex];
    const preview = choice && choice.available ? choice.report : data;
    const canApply = !alternatives.length || Boolean(choice && choice.available);
    const correct = function () {
        removeOptimizationIdleReport();
        $('body').css('overflow', '');
        accepted.val('no');
        form.find('input[name="targetResolution"]').val('keep');
        form.find('input[name="targetPreviewHash"]').val('');
    };
    renderOptimizationIdleReport(preview.idleChanges, preview.idleTotals, preview.optimizationMetrics, {}, Boolean(preview.hasNetIdleIncrease), {
        mode: 'savePreview',
        title: 'Предварителен резултат от целевите времена',
        hint: 'Промените още не са записани. Прегледайте новата подредба, сроковете и престоите по всички машини.',
        close: correct,
        cancelLabel: 'Отказ',
        cancel: correct,
        confirmLabel: canApply ? 'Приложи' : null,
        confirm: function () {
            if (!canApply) return;
            if (choice) {
                form.find('input[name="targetResolution"]').val(choice.mode);
                form.find('[name="targetPackagePolicy"]').val(choice.policy);
            }
            form.find('input[name="targetPreviewHash"]').val(preview.hash || '');
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
    $('#optimizationIdleReport').addClass('targetTimeReport');
    const details = $('<div>', {class: 'targetTimeProposal'});
    const firstAvailable = alternatives.find(function (variant) { return variant.available; });
    const referenceGroups = preview.operationOrder || (firstAvailable ? firstAvailable.report.operationOrder : []) || [];
    const referenceNumbers = {};
    const editedOperation = preview.editedOperation || data.editedOperation || '';
    referenceGroups.forEach(function (group) {
        (group.before || []).concat(group.after || []).forEach(function (row) {
            referenceNumbers[row.operation.toLowerCase()] = row.number;
        });
    });
    const withReferenceNumbers = function (text) {
        return text.replace(/\bOpr\d+\b/gi, function (operation) {
            const number = referenceNumbers[operation.toLowerCase()];
            return number == null ? operation : operation + ' (' + number + ')';
        });
    };
    const operationLabel = function (operation) {
        const number = referenceNumbers[operation.toLowerCase()];
        return operation + (number == null ? '' : ' (' + number + ')') + ' | ';
    };
    if ((data.approvalChanges || []).length) {
        const changed = $('<div>', {class: 'targetTimeConflict targetTimeApprovalChanges'});
        changed.append($('<strong>').text('Записът не е извършен. Необходимо е ново потвърждение:'));
        const list = $('<ul>');
        data.approvalChanges.forEach(function (reason) { list.append($('<li>').text(withReferenceNumbers(reason))); });
        details.append(changed.append(list));
    }
    if (data.conflictText) {
        const conflict = data.conflictText.split('\n').map(function (message) {
            const labelled = message.match(/^(Opr\d+|Новата операция):\s*(.*)$/i);
            const operation = labelled ? labelled[1] : editedOperation;
            const reason = (labelled ? labelled[2] : message).replace(/^Желаният час/, 'Желаното начало');
            return (operation ? operationLabel(operation) : '') + withReferenceNumbers(reason);
        }).join('\n');
        details.append($('<div>', {class: 'targetTimeConflict'}).append($('<strong>').text(conflict)));
    }
    if (alternatives.length) {
        details.append($('<strong>', {class: 'targetTimeChoiceHelp'}).text(firstAvailable
            ? 'Изберете вариант, прегледайте всички промени и едва тогава натиснете „Приложи“.'
            : 'Няма допустим вариант при текущите ограничения. Върнете се с „Отказ“ и проверете посочените предходни операции, пакетни връзки и фиксирани начала.'));
        alternatives.forEach(function (variant, index) {
            const box = $('<div>', {class: 'targetTimeChoice' + (index === selectedIndex ? ' isSelected' : '')});
            box.append($('<button>', {type: 'button', disabled: !variant.available})
                .text(variant.title).on('click', function () { showTargetTimesPreview(data, index); }));
            if (!variant.available) box.append($('<div>', {class: 'targetTimeConflict'}).text(variant.reason || 'Няма допустим вариант.'));
            else {
                const rows = variant.report.operationChanges || [];
                const pins = rows.filter(function (row) { return row.pinChanged; }).length;
                box.append($('<div>').text('Променени фиксирани начала: ' + pins + '; операции в подробния преглед: ' + rows.length + '.'));
            }
            details.append(box);
        });
        if (!canApply) $('#optimizationIdleReport .optimizationIdleNoChanges').remove();
    }
    if (canApply) {
        const editedLabel = editedOperation ? operationLabel(editedOperation) : '';
        details.append($('<p>').append($('<strong>').text(editedLabel + 'Въведено начало: ' + (preview.requestedStart || '—')
            + '; предложено за запис: ' + (preview.proposedStart || '—') + '.')));
        if ((preview.operationOrder || []).length) {
            preview.operationOrder.forEach(function (group) {
                const section = $('<section>', {class: 'targetTimeMachine'})
                    .append($('<h3>').text(group.asset).append(data.legendHint || ''));
                const comparison = $('<div>', {class: 'targetTimeComparison'});
                const oldPositions = {};
                (group.before || []).forEach(function (row, index) { oldPositions[row.id] = index; });
                ['before', 'after'].forEach(function (side) {
                    const rows = group[side] || [];
                    const panel = $('<div>', {class: 'targetTimeSide targetTimeSide-' + side});
                    panel.append($('<h4>').text(side === 'before' ? 'Преди (към момента)' : 'След'));
                    const table = $('<table>', {class: 'targetTimeOrderTable'});
                    const head = $('<tr>');
                    ['№', 'Начало', 'Операция', 'Край'].forEach(function (title) { head.append($('<th>').text(title)); });
                    table.append($('<thead>').append(head));
                    const body = $('<tbody>');
                    rows.forEach(function (row, index) {
                        const previous = rows[index - 1];
                        const next = rows[index + 1];
                        const linkedBefore = previous && row.previous === previous.id;
                        const linkedAfter = next && next.previous === row.id;
                        const state = ['draft', 'waiting', 'pending', 'active', 'wakeup', 'stopped', 'closed'].includes(row.state) ? row.state : '';
                        const tr = $('<tr>', {class: 'state-' + state, 'data-operation': row.operation});
                        if (row.operation === editedOperation) tr.addClass('targetTimeEditedOperation');
                        if (linkedBefore || linkedAfter) tr.addClass('targetTimePackage');
                        if (linkedAfter && !linkedBefore) tr.addClass('targetTimePackageStart');
                        if (linkedBefore && !linkedAfter) tr.addClass('targetTimePackageEnd');
                        const number = $('<td>', {class: 'targetTimeNumber', title: row.previous == null ? '' : 'В пакет след Opr' + row.previous}).text(row.number);
                        const oldPosition = oldPositions[row.id];
                        if (side === 'after' && oldPosition != null && oldPosition !== index) {
                            const direction = index < oldPosition ? 'нагоре' : 'надолу';
                            number.append($('<span>', {class: 'targetTimeMove',
                                title: 'Преместена ' + direction + ': място ' + (oldPosition + 1) + ' → ' + (index + 1),
                                'aria-label': 'Преместена ' + direction}).text(index < oldPosition ? ' ↑' : ' ↓'));
                        }
                        tr.append(number);
                        const timeCell = function (field, direction, title) {
                            const cell = $('<td>', {class: 'targetTimeDate', title: title});
                            const value = $('<span>', {class: 'targetTimeValue'});
                            const parts = String(row[field] || '—').split(' ');
                            parts.forEach(function (part) { value.append($('<span>').text(part)); });
                            if (side === 'after' && direction !== 0 && direction != null) {
                                value.addClass(direction < 0 ? 'targetTimeEarlier' : 'targetTimeLater');
                            } else if (side === 'after' && direction == null && row[field] !== '—') {
                                value.addClass('targetTimeNew');
                            }
                            if (field === 'start' && row.pin && row.pin !== '—') {
                                cell.attr('title', side === 'after' && row.pinChanged
                                    ? 'Фиксирано начало: ' + row.pinBefore + ' → ' + row.pin
                                    : 'Фиксирано начало: ' + row.pin);
                                cell.append($('<span>', {class: 'targetTimePin', 'aria-label': 'Фиксирано начало'}).text('📌'));
                            }
                            return cell.append(value);
                        };
                        tr.append(timeCell('start', row.startChange, 'Планирано начало'));
                        tr.append($('<td>', {class: 'targetTimeOperation', title: row.operation}).text(row.operation));
                        tr.append(timeCell('end', row.endChange, 'Планиран край'));
                        body.append(tr);
                    });
                    table.append(body);
                    panel.append(table);
                    comparison.append(panel);
                });
                section.append($('<div>', {class: 'targetTimeComparisonHolder'}).append(comparison));
                details.append(section);
            });
        }
    }
    $('#optimizationIdleReport .optimizationIdleReportHint').after(details);
    $('body').append($('<div>', {id: 'savedOrderReportBackdrop'}));
    $('body').addClass('targetTimePreviewOpen').css('overflow', 'hidden');
}
