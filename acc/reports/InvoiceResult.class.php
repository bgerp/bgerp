<?php


/**
 * Порционно изчисляване и постоянно съхранение само на „Фактури по контрагент“.
 * Порциите ограничават контрагентите, никога периода на разпределението на плащанията.
 */
class acc_reports_InvoiceResult extends core_BaseClass
{
    const BACKGROUND_ROWS = 30000;
    const BATCH_ROWS = 500;
    const BLOCK_ROWS = 200;


    private static function key($generation, $part = 'job')
    {
        return 'invoiceResult|' . $generation . '|' . $part;
    }


    public static function lockKey($reportId)
    {
        return 'invoiceResultReport|' . $reportId;
    }


    /**
     * Евтина проверка без фактурни суми, плащания или стар записан резултат.
     */
    public static function needsBackground($rec)
    {
        if (empty($rec->id)) return false;
        $remaining = self::BACKGROUND_ROWS;
        foreach (self::sourceQueries($rec) as $query) {
            $query->show('id');
            if (!empty($rec->paymentType)) {
                $query->where(array("IF(#paymentType IS NULL OR #paymentType = '', #autoPaymentType, #paymentType) = '[#1#]'", $rec->paymentType));
            }
            $remaining -= $query->count(null, $remaining + 1);
            if ($remaining < 0) return true;
        }

        return false;
    }


    /**
     * Тези заявки избират само кандидатите. Целите свързани сделки се четат
     * впоследствие от неизменения алгоритъм на справката за съответните папки.
     */
    private static function sourceQueries($rec)
    {
        $classes = ($rec->typeOfInvoice ?? 'out') == 'all'
            ? array('sales_Invoices', 'purchase_Invoices')
            : array(($rec->typeOfInvoice ?? 'out') == 'in' ? 'purchase_Invoices' : 'sales_Invoices');
        if (($rec->typeOfInvoice ?? 'out') != 'in' && !empty($rec->{'seeProformаs'}) && ($rec->unpaid ?? 'all') == 'all') {
            $classes[] = 'sales_Proformas';
        }
        $folders = !empty($rec->contragent) ? keylist::toArray($rec->contragent) : null;
        if (!empty($rec->crmGroup)) {
            $groupFolders = acc_reports_InvoicesByContragent::getFoldersInGroups($rec);
            $folders = isset($folders) ? array_intersect($folders, $groupFolders) : $groupFolders;
        }
        $queries = array();
        foreach ($classes as $class) {
            $query = $class::getQuery();
            $query->where('#number IS NOT NULL');
            $query->in('state', 'rejected,draft', true);
            $query->where(array("#date <= '[#1#]'", $rec->checkDate));
            if (!empty($rec->fromDate)) $query->where(array("#date >= '[#1#]'", $rec->fromDate));
            if (isset($folders)) $query->in('folderId', $folders);
            $query->onReplica = true;
            $queries[] = $query;
        }

        return $queries;
    }


    /**
     * Повторно натиснато Обновяване продължава текущото изчисление на същия филтър.
     */
    public static function start($rec, $filterHash)
    {
        $activeKey = 'invoiceResultActive|' . $rec->id;
        $generation = core_Permanent::get($activeKey);
        $job = $generation ? core_Permanent::get(self::key($generation)) : null;
        if (!$job || $job->filterHash !== $filterHash || !in_array($job->state, array('pending', 'failed'))) {
            $generation = md5(uniqid((string) $rec->id, true));
            $settings = clone $rec;
            unset($settings->data, $settings->log, $settings->driverRec, $settings->_invoiceResultEdit);
            $settings->_invoiceResultToday = dt::today();
            $job = (object) array('reportId' => $rec->id, 'generation' => $generation,
                'filterHash' => $filterHash, 'settings' => $settings, 'state' => 'pending',
                'started' => microtime(true), 'cursor' => 0, 'plan' => null,
                'groups' => array(), 'totals' => array(), 'invoiceTotals' => array());
            core_Permanent::set(self::key($generation), $job, core_Permanent::FOREVER_VALUE);
            core_Permanent::set($activeKey, $generation, core_Permanent::FOREVER_VALUE);
            self::schedule($generation, 0, 0, 5);
        } else {
            if ($job->state === 'failed') {
                $job->state = 'pending';
                $job->attempts = 0;
                core_Permanent::set(self::key($generation), $job, core_Permanent::FOREVER_VALUE);
            }
            // Отделен опит: възстановява и прекъснал cron, без повторно добавяне на редове.
            self::schedule($generation, $job->cursor, time(), 5);
        }
        $rec->resultGeneration = $generation;
        $rec->resultFilterHash = null;
        $registryKey = 'invoiceResultSnapshots|' . $rec->id;
        $registry = core_Permanent::get($registryKey) ?? (object) array('generations' => array());
        $registry->generations[$generation] = true;
        core_Permanent::set($registryKey, $registry, core_Permanent::FOREVER_VALUE);

        return (object) array('recs' => array(), 'resultGeneration' => $generation,
            'resultFilterHash' => null, 'invoiceResultState' => 'pending');
    }


    private static function schedule($generation, $cursor, $attempt, $seconds = 0)
    {
        core_CallOnTime::setOnce(__CLASS__, 'Continue',
            (object) array('generation' => $generation, 'cursor' => $cursor, 'attempt' => $attempt), dt::addSecs($seconds));
    }


    /**
     * Идентични имена в различни папки се обработват заедно, защото старият
     * алгоритъм обобщава по изписаното във фактурите име. Преименувана фирма
     * винаги остава цяла по folderId. Това запазва и старите обобщения 1:1.
     */
    public static function makePlan($rec, $batchRows = self::BATCH_ROWS)
    {
        $parents = $names = $weights = array();
        $root = function ($folder) use (&$parents) {
            while ($parents[$folder] != $folder) {
                $parents[$folder] = $parents[$parents[$folder]];
                $folder = $parents[$folder];
            }
            return $parents[$folder];
        };
        foreach (self::sourceQueries($rec) as $query) {
            $query->show('folderId,contragentName');
            $query->selectOnReplica();
            while ($invoice = $query->fetch()) {
                expect(!empty($invoice->folderId), 'Липсва папка на фактура при подготовка на порциите');
                $folder = $invoice->folderId;
                $parents[$folder] = $parents[$folder] ?? $folder;
                $weights[$folder] = ($weights[$folder] ?? 0) + 1;
                $name = (string) ($invoice->contragentName ?? '');
                if (isset($names[$name])) {
                    $parents[$root($folder)] = $root($names[$name]);
                } else {
                    $names[$name] = $folder;
                }
            }
        }
        $groups = array();
        foreach ($parents as $folder => $unused) {
            $id = $root($folder);
            $groups[$id]['folders'][] = $folder;
            $groups[$id]['rows'] = ($groups[$id]['rows'] ?? 0) + $weights[$folder];
        }
        $plan = $batch = array();
        $rows = 0;
        foreach ($groups as $group) {
            $batch = array_merge($batch, $group['folders']);
            $rows += $group['rows'];
            if ($rows >= $batchRows) {
                $plan[] = $batch;
                $batch = array();
                $rows = 0;
            }
        }
        if ($batch) $plan[] = $batch;

        return $plan;
    }


    /**
     * Само метаданни: дори повреден стар резултат не се десериализира.
     */
    private static function currentReport($job)
    {
        Mode::push(frame2_Reports::LIGHT_FETCH_MODE, true);
        try {
            $rec = frame2_Reports::fetch($job->reportId, '*', false);
        } finally {
            Mode::pop(frame2_Reports::LIGHT_FETCH_MODE);
        }
        if (!$rec || ($rec->resultGeneration ?? null) !== $job->generation
            || acc_reports_InvoicesByContragent::getResultFilterHash($rec) !== $job->filterHash
            || ($rec->state ?? '') === 'rejected'
            || core_Permanent::get('invoiceResultActive|' . $job->reportId) !== $job->generation) return null;
        if (!empty($rec->driverClass) && core_Classes::getName($rec->driverClass) !== 'acc_reports_InvoicesByContragent') return null;

        return $rec;
    }


    /**
     * Един callback обработва цели контрагенти за около 25 секунди, с checkpoint
     * след всяка порция. Не чакаме отделен cron за всяка малка фирма.
     * Записът на checkpoint е след блоковете; повторението ги заменя, не ги дублира.
     */
    public function callback_Continue($call)
    {
        $job = core_Permanent::get(self::key($call->generation));
        if (!$job) return;
        if ($job->state === 'ready') {
            self::publish($job);
            return;
        }
        if ($job->state !== 'pending' || ($call->cursor != -1 && $job->cursor != $call->cursor)) return;
        $lock = 'invoiceResultJob|' . $job->generation;
        if (!core_Locks::obtain($lock, 1800, 1, 1)) {
            self::schedule($job->generation, -1, $call->attempt + 1, 120);
            return;
        }
        try {
            // Може да е напреднало друго извикване, докато сме чакали заключването.
            $job = core_Permanent::get(self::key($call->generation));
            if (!$job || $job->state !== 'pending' || ($call->cursor != -1 && $job->cursor != $call->cursor)) return;
            if (!self::currentReport($job)) return;
            if (($job->attempts ?? 0) >= 3) {
                $job->state = 'failed';
                core_Permanent::set(self::key($job->generation), $job, core_Permanent::FOREVER_VALUE);
                core_Permanent::set(self::key($job->generation, 'progress'), (object) array('error' => true), core_Permanent::FOREVER_VALUE);
                return;
            }
            $job->attempts = ($job->attempts ?? 0) + 1;
            core_Permanent::set(self::key($job->generation), $job, core_Permanent::FOREVER_VALUE);
            // Предварително отложен повторен опит при фатална грешка/спиране на процеса.
            self::schedule($job->generation, -1, $call->attempt + 1, 120);
            if (!isset($job->plan)) $job->plan = self::makePlan($job->settings);
            $deadline = microtime(true) + min(25, max(1, core_Cron::getTimeLeft() - 7));
            while (isset($job->plan[$job->cursor])) {
                if (!self::currentReport($job)) return;
                $batchRec = clone $job->settings;
                $batchRec->contragent = '|' . implode('|', $job->plan[$job->cursor]) . '|';
                $batchRec->crmGroup = null; // Сечението с групите е приложено при плана.
                $batchRec->_invoiceResultFolders = true;
                $driver = cls::get('acc_reports_InvoicesByContragent');
                $started = microtime(true);
                $batch = $driver->calculateResult($batchRec);
                $job->calculationSeconds = ($job->calculationSeconds ?? 0) + microtime(true) - $started;
                $started = microtime(true);
                self::appendBatch($job, $batchRec, $batch);
                $job->storageSeconds = ($job->storageSeconds ?? 0) + microtime(true) - $started;
                unset($batch, $batchRec);
                $job->cursor++;
                $job->attempts = 0;
                core_Permanent::set(self::key($job->generation), $job, core_Permanent::FOREVER_VALUE);
                core_Permanent::set(self::key($job->generation, 'progress'),
                    (object) array('done' => $job->cursor, 'total' => count($job->plan)), core_Permanent::FOREVER_VALUE);
                if (microtime(true) >= $deadline) break;
            }
            if ($job->cursor >= count($job->plan)) {
                $job->result = self::finish($job);
                $job->state = 'ready';
            }
            core_Permanent::set(self::key($job->generation), $job, core_Permanent::FOREVER_VALUE);
            core_Permanent::set(self::key($job->generation, 'progress'),
                (object) array('done' => $job->cursor, 'total' => count($job->plan)), core_Permanent::FOREVER_VALUE);
            if ($job->state === 'ready') {
                self::publish($job);
            } else {
                self::schedule($job->generation, $job->cursor, 0);
            }
        } catch (Throwable $e) {
            $checkpoint = core_Permanent::get(self::key($job->generation));
            if ($checkpoint && $checkpoint->state === 'pending') {
                $checkpoint->state = 'failed';
                core_Permanent::set(self::key($job->generation), $checkpoint, core_Permanent::FOREVER_VALUE);
            }
            core_Permanent::set(self::key($job->generation, 'progress'),
                (object) array('error' => true), core_Permanent::FOREVER_VALUE);
            reportException($e);
            // Запазеният checkpoint позволява Обновяване да повтори само тази порция.
        } finally {
            core_Locks::release($lock);
        }
    }


    public static function appendBatch($job, $rec, $data)
    {
        $groups = array();
        foreach ($data->recs as $key => $row) $groups[$row->contragent ?? ''][$key] = $row;
        // Опис преди запис: и прекъснала по средата порция може после да се почисти.
        $keys = array();
        foreach ($groups as $name => $rows) {
            for ($i = 0; $i < ceil(count($rows) / self::BLOCK_ROWS); $i++) $keys[] = self::blockKey($job, $name, $i);
        }
        core_Permanent::set(self::key($job->generation, 'part|' . $job->cursor), $keys, core_Permanent::FOREVER_VALUE);
        foreach ($groups as $name => $rows) {
            expect(!isset($job->groups[$name]), 'Контрагентът участва в повече от една порция');
            $blocks = $types = array();
            $digest = hash_init('sha256');
            foreach ($rows as $rowKey => $row) {
                $types[$row->className ?? ''] = true;
                hash_update($digest, serialize(array($rowKey, $row)));
            }
            foreach (array_chunk($rows, self::BLOCK_ROWS, true) as $i => $chunk) {
                $key = self::blockKey($job, $name, $i);
                core_Permanent::set($key, $chunk, core_Permanent::FOREVER_VALUE);
                $blocks[] = (object) array('key' => $key, 'count' => count($chunk));
            }
            $job->groups[$name] = (object) array('blocks' => $blocks, 'types' => count($types),
                'last' => array_key_last($rows), 'digest' => hash_final($digest));
        }
        foreach (self::totalFields() as $field) {
            $job->totals[$field] = round(($job->totals[$field] ?? 0) + ($rec->{$field} ?? 0), 2);
        }
        foreach ($data->invoiceTotals ?? array() as $type => $amount) {
            $job->invoiceTotals[$type] = round(($job->invoiceTotals[$type] ?? 0) + $amount, 2);
        }
    }


    private static function blockKey($job, $name, $index)
    {
        return self::key($job->generation, 'block|' . $job->cursor . '|' . $index . '|' . md5($name));
    }


    private static function totalFields()
    {
        return array('totalInvoiceValueAll', 'totalInvoicePayoutAll', 'totalInvoiceNotPaydAll',
            'totalInvoiceOverPaidAll', 'totalInvoiceOverDueAll', 'totalAccountingCorrectionAll',
            'accountingIncompleteGroups', 'accountingUnallocatedGroups');
    }


    public static function finish($job)
    {
        if (($job->settings->typeOfInvoice ?? 'out') == 'all' || ($job->settings->accountingCorrections ?? 'no') == 'yes') {
            uksort($job->groups, 'strnatcasecmp');
        } else {
            uksort($job->groups, function ($a, $b) {
                return strcmp(mb_strtolower($a, 'UTF-8'), mb_strtolower($b, 'UTF-8')) ?: strcmp($a, $b);
            });
        }
        $blocks = $digests = array();
        $count = 0;
        foreach ($job->groups as $group) {
            $digests[] = $group->digest;
            foreach ($group->blocks as $block) {
                $block->start = $count;
                $block->groupLast = $group->last;
                $block->groupTypes = $group->types;
                $blocks[] = $block;
                $count += $block->count;
            }
        }

        $result = (object) array('recs' => array(), 'invoiceResultState' => 'complete',
            'resultGeneration' => $job->generation, 'resultFilterHash' => $job->filterHash,
            'invoiceBlocks' => $blocks, 'invoiceRowCount' => $count, 'invoiceTotals' => $job->invoiceTotals,
            'invoiceContentHash' => hash('sha256', serialize(array($job->filterHash, $digests, $job->totals, $job->invoiceTotals))));
        // Еднакво съдържание запазва старата снимка и стандартното сравнение на версии.
        // Само нов идентификатор на задача не е промяна на счетоводния резултат.
        $previousGeneration = $job->settings->resultGeneration ?? null;
        $previous = $previousGeneration ? core_Permanent::get(self::key($previousGeneration)) : null;
        if (($previous->state ?? null) === 'published'
            && $previous->reportId === ($job->reportId ?? null)
            && ($previous->result->invoiceContentHash ?? null) === $result->invoiceContentHash
            && self::snapshotAvailable($previous->result)) return $previous->result;

        return $result;
    }


    private static function snapshotAvailable($data)
    {
        foreach ($data->invoiceBlocks as $block) {
            $rows = core_Permanent::get($block->key);
            if (!is_array($rows) || count($rows) !== $block->count) return false;
        }

        return true;
    }


    private static function publish($job)
    {
        if (!core_Locks::obtain(self::lockKey($job->reportId), 120, 1, 1)) {
            core_CallOnTime::setOnce(__CLASS__, 'Publish',
                (object) array('generation' => $job->generation, 'attempt' => uniqid()), dt::addSecs(10));
            return;
        }
        try {
            $job = core_Permanent::get(self::key($job->generation));
            if (!$job || $job->state !== 'ready') return;
            if ($rec = self::currentReport($job)) {
                $rec->_invoiceResultReady = $job;
                frame2_Reports::refresh($rec);
                $job->state = 'published';
                unset($job->plan, $job->settings, $job->groups);
                core_Permanent::set(self::key($job->generation), $job, core_Permanent::FOREVER_VALUE);
                $registryKey = 'invoiceResultSnapshots|' . $job->reportId;
                $registry = core_Permanent::get($registryKey);
                if ($registry) {
                    $registry->firstVersionId = $registry->firstVersionId ?? frame2_ReportVersions::getLatestVersionId($job->reportId);
                    core_Permanent::set($registryKey, $registry, core_Permanent::FOREVER_VALUE);
                    core_CallOnTime::setOnce(__CLASS__, 'Cleanup', $job->reportId, dt::addSecs(300));
                }
            }
        } finally {
            core_Locks::release(self::lockKey($job->reportId));
        }
    }


    public function callback_Publish($call)
    {
        $job = core_Permanent::get(self::key($call->generation));
        if ($job && $job->state === 'ready') self::publish($job);
    }


    public static function completedData($rec)
    {
        $job = $rec->_invoiceResultReady;
        unset($rec->_invoiceResultReady);
        foreach (self::totalFields() as $field) $rec->{$field} = $job->totals[$field] ?? 0;
        $rec->resultFilterHash = $job->filterHash;
        $rec->resultGeneration = $job->result->resultGeneration;

        return $job->result;
    }


    /**
     * За екран се четат само блоковете, пресичащи нужния диапазон.
     * Експортът и печатът изрично обхождат целия завършен резултат.
     */
    public static function readRange($data, $start, $length, &$boundaries = null)
    {
        $rows = $boundaries = array();
        $end = $start + $length;
        foreach ($data->invoiceBlocks as $block) {
            if ($block->start >= $end) break;
            if ($block->start + $block->count <= $start) continue;
            $chunk = core_Permanent::get($block->key);
            expect(is_array($chunk) && count($chunk) == $block->count, 'Липсва част от резултата. Обновете справката.');
            $offset = max(0, $start - $block->start);
            $rows += array_slice($chunk, $offset, min($block->count - $offset, $end - $block->start - $offset), true);
            $boundaries[$block->groupLast] = $block->groupTypes;
        }
        expect(count($rows) == min($length, max(0, $data->invoiceRowCount - $start)), 'Непълен резултат на справката');

        return $rows;
    }


    public static function progress($data)
    {
        return core_Permanent::get(self::key($data->resultGeneration, 'progress'));
    }


    /**
     * Частите са постоянни, не изтичащ кеш. Изтриваме единствено снимки, които
     * вече не се използват нито от справката, нито от запазена нейна версия.
     * Старите огромни версии отпреди първата снимка на части не се зареждат.
     */
    public function callback_Cleanup($reportId)
    {
        $registryKey = 'invoiceResultSnapshots|' . $reportId;
        $registry = core_Permanent::get($registryKey);
        if (empty($registry->firstVersionId) || !core_Locks::obtain(self::lockKey($reportId), 120, 1, 1)) return;
        try {
            Mode::push(frame2_Reports::LIGHT_FETCH_MODE, true);
            try {
                $current = frame2_Reports::fetch($reportId, '*', false);
            } finally {
                Mode::pop(frame2_Reports::LIGHT_FETCH_MODE);
            }
            $used = array();
            if (!empty($current->resultGeneration)) $used[$current->resultGeneration] = true;
            $query = frame2_ReportVersions::getQuery();
            $query->where(array("#reportId = '[#1#]'", $reportId));
            $query->where(array("#id >= '[#1#]'", $registry->firstVersionId));
            $query->show('oldRec');
            while ($version = $query->fetch()) {
                $generation = $version->oldRec->data->resultGeneration ?? null;
                if ($generation) $used[$generation] = true;
            }
            foreach ($registry->generations as $generation => $unused) {
                if (isset($used[$generation])) continue;
                $lock = 'invoiceResultJob|' . $generation;
                if (!core_Locks::obtain($lock, 120, 1, 1)) continue;
                try {
                    $job = core_Permanent::get(self::key($generation));
                    for ($i = 0; $i <= ($job->cursor ?? 0); $i++) {
                        $partKey = self::key($generation, 'part|' . $i);
                        foreach (core_Permanent::get($partKey) ?? array() as $key) core_Permanent::remove($key);
                        core_Permanent::remove($partKey);
                    }
                    if (($job->result->resultGeneration ?? null) === $generation) {
                        foreach ($job->result->invoiceBlocks as $block) core_Permanent::remove($block->key);
                    }
                    core_Permanent::remove(self::key($generation, 'progress'));
                    core_Permanent::remove(self::key($generation));
                    unset($registry->generations[$generation]);
                } finally {
                    core_Locks::release($lock);
                }
            }
            core_Permanent::set($registryKey, $registry, core_Permanent::FOREVER_VALUE);
        } finally {
            core_Locks::release(self::lockKey($reportId));
        }
    }
}
