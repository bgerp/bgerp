<?php
/**
 * Дебъгер за изчисляване на счетоводен баланс
 *
 * @category  bgerp
 * @package   acc
 */
class acc_BalanceDebugger
{
    // =========================================================================
    //  Статични буфери
    // =========================================================================

    /** Основен лог – секциите в реда на изпълнение */
    private static $log = [];

    /** Хронологичен лог на всички стратегийни събития */
    private static $strategyEvents = [];


    // =========================================================================
    //  Трейс колектор – основен
    // =========================================================================

    public static function clear(): void
    {
        self::$log            = [];
        self::$strategyEvents = [];
        self::$strategyProbes = [];
    }

    public static function log(string $type, $data): void
    {
        self::$log[] = ['type' => $type, 'data' => $data];
    }

    public static function getLog(): array
    {
        return self::$log;
    }


    // =========================================================================
    //  Стратегийни събития
    //
    //  Извикват се директно от acc_BalanceDetails, само когато Mode::is('traceBalance').
    //  Не проверяват Mode сами – отговорността е на викащия.
    // =========================================================================

    /**
     * Захранване на стратегията.
     *
     * @param string $source   'initial' (от предходен баланс) | 'journal' (от журнален запис)
     * @param string $accNum   номер на сметката
     * @param string $accType  'active' | 'passive'
     * @param string $ent1     четимо перо 1
     * @param string $ent2     четимо перо 2
     * @param string $ent3     четимо перо 3
     * @param float  $qty      захранено количество
     * @param float  $amount   захранена сума
     * @param string $valior   вальор (само за journal)
     */
    public static function logStrategyFeed(
        string $source,
        string $accNum,
        string $accType,
        string $ent1,
        string $ent2,
        string $ent3,
               $qty,
               $amount,
        string $valior = ''
    ): void {
        self::$strategyEvents[] = [
            'event'    => 'feed',
            'source'   => $source,   // 'initial' | 'journal'
            'valior'   => $valior,
            'acc_num'  => $accNum,
            'acc_type' => $accType,
            'ent1'     => $ent1,
            'ent2'     => $ent2,
            'ent3'     => $ent3,
            'qty'      => $qty,
            'amount'   => $amount,
        ];
    }


    /**
     * Изписване от стратегията (consume).
     *
     * @param string $accNum        номер на сметката
     * @param string $accType       'active' | 'passive'
     * @param string $ent1          четимо перо 1
     * @param string $ent2          четимо перо 2
     * @param string $ent3          четимо перо 3
     * @param float  $qty           изписано количество
     * @param float  $amountBefore  сума преди стратегията (оригинална в журнала)
     * @param float  $amountAfter   сума след стратегията (изчислена по стратегия)
     * @param string $valior        вальор на записа
     */
    public static function logStrategyConsume(
        string $accNum,
        string $accType,
        string $ent1,
        string $ent2,
        string $ent3,
               $qty,
               $amountBefore,
               $amountAfter,
        string $valior = '',
        string $fromAccNum = ''
    ): void {
        self::$strategyEvents[] = [
            'event'         => 'consume',
            'source'        => 'journal',
            'valior'        => $valior,
            'acc_num'       => $accNum,
            'acc_type'      => $accType,
            'from_acc'      => $fromAccNum,
            'ent1'          => $ent1,
            'ent2'          => $ent2,
            'ent3'          => $ent3,
            'qty'           => $qty,
            'amount_before' => $amountBefore,
            'amount_after'  => $amountAfter,
        ];
    }


    // =========================================================================
    //  Сонда – проверява дали getStrategyFor връща стратегия за всеки ред
    // =========================================================================

    /** Буфер за probe резултатите */
    private static $strategyProbes = [];

    /**
     * Логва резултата от getStrategyFor при loadBalance.
     * Използва се само за диагностика – показва кои редове имат стратегия и кои нямат.
     */
    public static function logStrategyProbe(
        string $accNum,
               $accId,
        string $ent1,
        string $ent2,
        string $ent3,
               $blQuantity,
        bool $notNullOrFalse,  // $strategy !== null && $strategy !== false
        bool $isTruthy         // (bool)$strategy  – това проверява if($strategy)
    ): void {
        self::$strategyProbes[] = [
            'acc_num'           => $accNum,
            'acc_id'            => $accId,
            'ent1'              => $ent1,
            'ent2'              => $ent2,
            'ent3'              => $ent3,
            'bl_quantity'       => $blQuantity,
            'not_null_or_false' => $notNullOrFalse,
            'is_truthy'         => $isTruthy,
        ];
    }


    // =========================================================================
    //  Четимо представяне на пера (с кеш)
    // =========================================================================

    public static function entLabel($entId): string
    {
        if (empty($entId)) return '';
        static $cache = [];
        if (!isset($cache[$entId])) {
            $iRec = acc_Items::fetch($entId, 'num,title');
            $cache[$entId] = $iRec ? "[{$iRec->num}] {$iRec->title}" : "#{$entId}";
        }
        return $cache[$entId];
    }


    // =========================================================================
    //  Download
    // =========================================================================

    public static function download($rec, string $filterAcc = ''): void
    {
        $filterAcc = trim($filterAcc);
        $content   = self::buildText($rec, $filterAcc);
        $filename  = self::buildFilename($rec, $filterAcc);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache');

        echo $content;
        exit;
    }


    // =========================================================================
    //  Изграждане на текстовия файл
    // =========================================================================

    private static function buildFilename($rec, string $filterAcc): string
    {
        $base = 'balance_trace_' . $rec->fromDate . '_' . $rec->toDate;
        if ($filterAcc !== '') {
            $base .= '_acc' . $filterAcc;
        }
        return $base . '.txt';
    }


    private static function buildText($rec, string $filterAcc): string
    {
        $lines = [];

        self::separator($lines, '=', 80);
        self::line($lines, '  БАЛАНС ДЕБЪГ ТРЕЙС');
        self::line($lines, '  Период    : ' . $rec->fromDate . ' -> ' . $rec->toDate);
        self::line($lines, '  Сметка    : ' . ($filterAcc !== '' ? $filterAcc . ' (само тази сметка)' : 'всички'));
        self::line($lines, '  Генериран : ' . date('Y-m-d H:i:s'));
        self::separator($lines, '=', 80);
        self::line($lines, '');

        // Групираме стратегийните събития по итерации на calc()
        // (calc() може да се извика 2 пъти – всяка итерация има свой блок events)
        $strategyChunks = self::splitStrategyByIteration();

        $iteration     = 0;
        $strategyIndex = 0;

        foreach (self::$log as $entry) {

            if ($entry['type'] === 'calc_start') {
                $iteration++;
            }

            self::renderEntry($lines, $entry, $filterAcc);

            // Стратегийният блок се вмъква веднъж – веднага след журналните записи
            if ($entry['type'] === 'journal_entries') {
                self::renderStrategySection(
                    $lines,
                    $strategyChunks[$iteration - 1] ?? [],
                    $filterAcc
                );
                // Probe секцията – само при първата итерация, само ако има филтър
                if ($iteration === 1 && $filterAcc !== '') {
                    self::renderProbeSection($lines, $filterAcc);
                }
            }
        }

        // Суров дамп на целия strategyEvents буфер – за диагностика
        self::renderRawStrategyDump($lines, $filterAcc);

        return implode("\n", $lines) . "\n";
    }


    /**
     * Разделя стратегийните събития по итерации на calc().
     * Всяка итерация на calc() захранва стратегията и после изписва от нея.
     * Разделяме по 'initial' feed – той маркира началото на нова итерация.
     */
    private static function splitStrategyByIteration(): array
    {
        $chunks         = [];
        $current        = [];
        $iteration      = 0;
        $seenNonInitial = false;

        foreach (self::$strategyEvents as $ev) {
            $isInitial = ($ev['event'] === 'feed' && $ev['source'] === 'initial');

            if ($isInitial && $seenNonInitial) {
                // Нова итерация на calc() – всички initial идват след journal/consume
                $chunks[$iteration] = $current;
                $iteration++;
                $current        = [];
                $seenNonInitial = false;
            }

            if (!$isInitial) {
                $seenNonInitial = true;
            }

            $current[] = $ev;
        }

        if (!empty($current)) {
            $chunks[$iteration] = $current;
        }

        return $chunks;
    }


    // =========================================================================
    //  Рендиране на секции от основния лог
    // =========================================================================

    private static function renderEntry(array &$lines, array $entry, string $filterAcc): void
    {
        $d = $entry['data'];

        switch ($entry['type']) {

            case 'calc_start':
                self::heading($lines, '1. НАЧАЛО НА ИЗЧИСЛЕНИЕТО');
                self::kvBlock($lines, $d);
                break;

            case 'prev_balance_found':
                self::heading($lines, '2. ПРЕДХОДЕН БАЛАНС (БАЗА)');
                self::kvBlock($lines, $d);
                break;

            case 'journal_range':
                self::heading($lines, '3. ДИАПАЗОН ОТ ЖУРНАЛА');
                self::kvBlock($lines, $d);
                break;

            case 'load_balance_rows':
                $rows = self::filterByAcc($d, $filterAcc, 'acc_num');
                self::heading($lines, '4. НАЧАЛНИ САЛДА (ОТ ПРЕДХОДЕН БАЛАНС)');
                self::countInfo($lines, $d, $rows, $filterAcc);
                self::loadTable($lines, $rows);
                break;

            case 'journal_entries':
                $rows = $filterAcc
                    ? array_values(array_filter($d, fn($r) =>
                        self::accEq($r['debit_acc_num'],  $filterAcc) ||
                        self::accEq($r['credit_acc_num'], $filterAcc)))
                    : $d;
                self::heading($lines, '5. ОБРАБОТЕНИ ЗАПИСИ ОТ ЖУРНАЛА');
                self::countInfo($lines, $d, $rows, $filterAcc);
                self::journalTable($lines, $rows, $filterAcc);
                break;

            case 'final_balance':
                $rows = self::filterByAcc($d, $filterAcc, 'acc_num');
                self::heading($lines, '7. КРАЙНО САЛДО (ПРЕДИ ЗАПИС В БД)');
                self::countInfo($lines, $d, $rows, $filterAcc);
                self::finalTable($lines, $rows);
                break;

            case 'save_result':
                self::heading($lines, '8. РЕЗУЛТАТ ОТ ЗАПИСА');
                self::kvBlock($lines, $d);
                break;
        }
    }


    // =========================================================================
    //  Секция 6 – Стратегия (захранване и изписване)
    // =========================================================================

    private static function renderStrategySection(array &$lines, array $events, string $filterAcc): void
    {
        self::heading($lines, '6. СТРАТЕГИЯ – ЗАХРАНВАНЕ И ИЗПИСВАНЕ');

        // Показваме кои сметки имат стратегийни събития (преди филтриране)
        if (!empty($events)) {
            $summary = [];
            foreach ($events as $ev) {
                $acc = $ev['acc_num'];
                if (!isset($summary[$acc])) {
                    $summary[$acc] = ['feed' => 0, 'consume' => 0];
                }
                $summary[$acc][$ev['event']]++;
            }
            ksort($summary);
            $parts = [];
            foreach ($summary as $acc => $counts) {
                $parts[] = $acc . ' (захр:' . $summary[$acc]['feed'] . ' изп:' . $summary[$acc]['consume'] . ')';
            }
            self::line($lines, '  Всички сметки в стратегията: ' . implode(', ', $parts));
            self::line($lines, '');
        }

        // Ако е зададен филтър по сметка – показваме само events за тази сметка
        if ($filterAcc !== '') {
            $events = array_values(array_filter($events,
                fn($ev) => self::accEq($ev['acc_num'], $filterAcc)
            ));
        }

        if (empty($events)) {
            self::line($lines, '  (няма стратегийни събития' . ($filterAcc ? " за сметка {$filterAcc}" : '') . ')');
            self::line($lines, '');
            return;
        }

        $cols = [
            'event'    => ['label' => 'Събитие',      'w' => 12],
            'source'   => ['label' => 'Източник',     'w' => 10],
            'valior'   => ['label' => 'Вальор',       'w' => 11],
            'acc'      => ['label' => 'Сметка',       'w' => 8],
            'from_acc' => ['label' => 'Изписва от',   'w' => 10],
            'type'     => ['label' => 'Тип',          'w' => 8],
            'ent1'     => ['label' => 'Перо 1',       'w' => 28],
            'ent2'     => ['label' => 'Перо 2',       'w' => 20],
            'qty'      => ['label' => 'Количество',   'w' => 12, 'r' => true],
            'amt_b'    => ['label' => 'Сума преди',   'w' => 13, 'r' => true],
            'amt_a'    => ['label' => 'Сума след',    'w' => 13, 'r' => true],
            'diff'     => ['label' => 'Разлика',      'w' => 13, 'r' => true],
        ];

        $data = [];
        foreach ($events as $ev) {
            if ($ev['event'] === 'feed') {
                $data[] = [
                    'event'    => 'ЗАХРАНВАНЕ',
                    'source'   => $ev['source'] === 'initial' ? 'нач.салдо' : 'журнал',
                    'valior'   => $ev['valior'],
                    'acc'      => $ev['acc_num'],
                    'from_acc' => '-',
                    'type'     => $ev['acc_type'],
                    'ent1'     => $ev['ent1'],
                    'ent2'     => $ev['ent2'],
                    'qty'      => self::n($ev['qty']),
                    'amt_b'    => '-',
                    'amt_a'    => self::n($ev['amount']),
                    'diff'     => '-',
                ];
            } else {
                // consume
                $diff = (float)$ev['amount_after'] - (float)$ev['amount_before'];
                $data[] = [
                    'event'    => 'ИЗПИСВАНЕ',
                    'source'   => 'журнал',
                    'valior'   => $ev['valior'],
                    'acc'      => $ev['acc_num'],
                    'from_acc' => $ev['from_acc'] ?: '-',
                    'type'     => $ev['acc_type'],
                    'ent1'     => $ev['ent1'],
                    'ent2'     => $ev['ent2'],
                    'qty'      => self::n($ev['qty']),
                    'amt_b'    => self::n($ev['amount_before']),
                    'amt_a'    => self::n($ev['amount_after']),
                    'diff'     => self::n($diff),
                ];
            }
        }

        self::table($lines, $cols, $data);
    }


    // =========================================================================
    //  Суров дамп на всички strategy events (за диагностика)
    // =========================================================================

    private static function renderRawStrategyDump(array &$lines, string $filterAcc): void
    {
        $events = self::$strategyEvents;

        self::separator($lines, '=', 80);
        self::line($lines, '  ДИАГНОСТИКА – ВСИЧКИ СТРАТЕГИЙНИ СЪБИТИЯ (' . count($events) . ' общо)');
        self::separator($lines, '=', 80);

        if (empty($events)) {
            self::line($lines, '  Буферът е ПРАЗЕН – logStrategyFeed/logStrategyConsume не са извикани нито веднъж.');
            self::line($lines, '');
            self::line($lines, '  Възможни причини:');
            self::line($lines, '  1. acc_BalanceDetails.patch.php не е напълно приложен (feedStrategy/calcAmount)');
            self::line($lines, '  2. Mode::is("traceBalance") връща false в тези методи');
            self::line($lines, '  3. getStrategyFor никога не връща truthy стойност');
            self::line($lines, '');
            return;
        }

        // Всички уникални сметки в буфера
        $accCounts = [];
        foreach ($events as $ev) {
            $acc = $ev['acc_num'] ?: '(empty)';
            $type = $ev['event'] . '/' . ($ev['source'] ?? '?');
            if (!isset($accCounts[$acc])) $accCounts[$acc] = [];
            $accCounts[$acc][$type] = ($accCounts[$acc][$type] ?? 0) + 1;
        }
        ksort($accCounts);

        self::line($lines, '  Сметки в буфера:');
        foreach ($accCounts as $acc => $types) {
            $typeParts = [];
            foreach ($types as $t => $n) $typeParts[] = $t . ':' . $n;
            self::line($lines, '    ' . $acc . ' → ' . implode(', ', $typeParts));
        }
        self::line($lines, '');

        // Показваме само events за търсената сметка
        if ($filterAcc !== '') {
            $filtered = array_values(array_filter($events,
                fn($ev) => self::accEq($ev['acc_num'], $filterAcc)
            ));

            self::line($lines, '  Events за сметка ' . $filterAcc . ': ' . count($filtered) . ' от ' . count($events));
            self::line($lines, '');

            if (empty($filtered)) {
                self::line($lines, '  !! Няма events за ' . $filterAcc . ' в буфера !!');
                self::line($lines, '  Сравнение: ltrim("' . $filterAcc . '", "0") = "' . ltrim($filterAcc, '0') . '"');
                self::line($lines, '  Налични acc_num стойности: ' . implode(', ', array_keys($accCounts)));
            } else {
                $cols = [
                    'event'  => ['label' => 'Събитие',  'w' => 10],
                    'source' => ['label' => 'Източник', 'w' => 10],
                    'valior' => ['label' => 'Вальор',   'w' => 11],
                    'acc'    => ['label' => 'acc_num',  'w' => 8],
                    'ent1'   => ['label' => 'Перо 1',   'w' => 30],
                    'qty'    => ['label' => 'К-во',     'w' => 12, 'r' => true],
                    'amt'    => ['label' => 'Сума',     'w' => 13, 'r' => true],
                ];
                $data = array_map(fn($ev) => [
                    'event'  => $ev['event'],
                    'source' => $ev['source'] ?? '',
                    'valior' => $ev['valior'] ?? '',
                    'acc'    => $ev['acc_num'],
                    'ent1'   => $ev['ent1'] ?? '',
                    'qty'    => self::n($ev['qty'] ?? null),
                    'amt'    => self::n($ev['amount'] ?? $ev['amount_after'] ?? null),
                ], $filtered);
                self::table($lines, $cols, $data);
            }
        }
    }


    // =========================================================================
    //  Probe секция – диагностика на getStrategyFor по ред от баланса
    // =========================================================================

    private static function renderProbeSection(array &$lines, string $filterAcc): void
    {
        // Взимаме само редовете за филтрираната сметка
        $rows = array_values(array_filter(
            self::$strategyProbes,
            fn($p) => self::accEq($p['acc_num'], $filterAcc)
        ));

        self::heading($lines, '6b. ДИАГНОСТИКА – getStrategyFor за сметка ' . $filterAcc);

        if (empty($rows)) {
            self::line($lines, '  (няма probe данни – сметката не присъства в предходния баланс)');
            self::line($lines, '');
            return;
        }

        $withStrategy    = array_filter($rows, fn($p) => $p['is_truthy']);
        $withoutStrategy = array_filter($rows, fn($p) => !$p['is_truthy']);

        self::line($lines, '  Редове С стратегия   : ' . count($withStrategy));
        self::line($lines, '  Редове БЕЗ стратегия : ' . count($withoutStrategy));
        self::line($lines, '');

        $cols = [
            'acc'      => ['label' => 'Сметка',          'w' => 8],
            'acc_id'   => ['label' => 'ID',              'w' => 6],
            'ent1'     => ['label' => 'Перо 1',          'w' => 35],
            'ent2'     => ['label' => 'Перо 2',          'w' => 22],
            'bl_q'     => ['label' => 'Кр.к-во',        'w' => 14, 'r' => true],
            'not_nf'   => ['label' => '!==null/false',  'w' => 14],
            'truthy'   => ['label' => 'if($strategy)',  'w' => 14],
        ];

        $data = array_map(fn($p) => [
            'acc'    => $p['acc_num'],
            'acc_id' => $p['acc_id'],
            'ent1'   => $p['ent1'],
            'ent2'   => $p['ent2'],
            'bl_q'   => self::n($p['bl_quantity']),
            'not_nf' => $p['not_null_or_false'] ? 'ДА' : 'НЕ',
            'truthy' => $p['is_truthy']         ? 'ДА' : '!! НЕ !!',
        ], $rows);

        self::table($lines, $cols, $data);
    }


    // =========================================================================
    //  Текстови таблици за останалите секции
    // =========================================================================

    private static function loadTable(array &$lines, array $rows): void
    {
        if (empty($rows)) {
            self::line($lines, '  (няма записи за тази сметка в предходния баланс)');
            self::line($lines, '');
            return;
        }

        $cols = [
            'acc'    => ['label' => 'Сметка',     'w' => 8],
            'ent1'   => ['label' => 'Перо 1',      'w' => 30],
            'ent2'   => ['label' => 'Перо 2',      'w' => 25],
            'ent3'   => ['label' => 'Перо 3',      'w' => 15],
            'bl_q'   => ['label' => 'Кр.к-во',    'w' => 13, 'r' => true],
            'bl_a'   => ['label' => 'Кр.сума',    'w' => 13, 'r' => true],
            'base_q' => ['label' => '->База к-во', 'w' => 13, 'r' => true],
            'base_a' => ['label' => '->База сума', 'w' => 13, 'r' => true],
            'type'   => ['label' => 'Зареждане',  'w' => 10],
        ];

        $data = array_map(fn($r) => [
            'acc'    => $r['acc_num'],
            'ent1'   => $r['ent1'],
            'ent2'   => $r['ent2'],
            'ent3'   => $r['ent3'],
            'bl_q'   => self::n($r['bl_quantity']),
            'bl_a'   => self::n($r['bl_amount']),
            'base_q' => self::n($r['base_quantity']),
            'base_a' => self::n($r['base_amount']),
            'type'   => $r['is_middle'] ? 'Междинен' : 'Нормален',
        ], $rows);

        self::table($lines, $cols, $data);
    }


    private static function journalTable(array &$lines, array $rows, string $filterAcc): void
    {
        if (empty($rows)) {
            self::line($lines, '  (няма журнални записи за тази сметка в периода)');
            self::line($lines, '');
            return;
        }

        if ($filterAcc) {
            self::line($lines, '  (* = търсената сметка участва от тази страна)');
        }

        $cols = [
            'valior' => ['label' => 'Вальор',    'w' => 11],
            'd_acc'  => ['label' => 'Д-сметка',  'w' => 10],
            'd_pera' => ['label' => 'Д-пера',    'w' => 32],
            'd_q'    => ['label' => 'Д к-во',    'w' => 12, 'r' => true],
            'c_acc'  => ['label' => 'К-сметка',  'w' => 10],
            'c_pera' => ['label' => 'К-пера',    'w' => 32],
            'c_q'    => ['label' => 'К к-во',    'w' => 12, 'r' => true],
            'amount' => ['label' => 'Сума',      'w' => 14, 'r' => true],
            'price'  => ['label' => 'Цена?',     'w' => 5],
        ];

        $data = array_map(function($r) use ($filterAcc) {
            $dm    = $filterAcc && self::accEq($r['debit_acc_num'],  $filterAcc);
            $cm    = $filterAcc && self::accEq($r['credit_acc_num'], $filterAcc);
            $dPera = implode(' / ', array_filter([$r['debit_item1'],  $r['debit_item2'],  $r['debit_item3']]));
            $cPera = implode(' / ', array_filter([$r['credit_item1'], $r['credit_item2'], $r['credit_item3']]));
            return [
                'valior' => $r['valior'],
                'd_acc'  => ($dm ? '*' : ' ') . ' ' . $r['debit_acc_num'],
                'd_pera' => $dPera,
                'd_q'    => self::n($r['debit_quantity']),
                'c_acc'  => ($cm ? '*' : ' ') . ' ' . $r['credit_acc_num'],
                'c_pera' => $cPera,
                'c_q'    => self::n($r['credit_quantity']),
                'amount' => self::n($r['amount']),
                'price'  => $r['price_updated'] === 'да' ? 'да' : '',
            ];
        }, $rows);

        self::table($lines, $cols, $data);
    }


    private static function finalTable(array &$lines, array $rows): void
    {
        if (empty($rows)) {
            self::line($lines, '  (няма записи за тази сметка в крайния баланс)');
            self::line($lines, '');
            return;
        }

        $cols = [
            'acc'    => ['label' => 'Сметка',      'w' => 8],
            'ent1'   => ['label' => 'Перо 1',       'w' => 28],
            'ent2'   => ['label' => 'Перо 2',       'w' => 22],
            'ent3'   => ['label' => 'Перо 3',       'w' => 14],
            'base_q' => ['label' => 'База к-во',   'w' => 12, 'r' => true],
            'base_a' => ['label' => 'База сума',   'w' => 12, 'r' => true],
            'deb_q'  => ['label' => 'Дебит к-во',  'w' => 12, 'r' => true],
            'deb_a'  => ['label' => 'Дебит сума',  'w' => 12, 'r' => true],
            'cre_q'  => ['label' => 'Кредит к-во', 'w' => 12, 'r' => true],
            'cre_a'  => ['label' => 'Кредит сума', 'w' => 12, 'r' => true],
            'bl_q'   => ['label' => 'КРАЙНО к-во', 'w' => 13, 'r' => true],
            'bl_a'   => ['label' => 'КРАЙНА сума', 'w' => 13, 'r' => true],
        ];

        $data = array_map(fn($r) => [
            'acc'    => $r['acc_num'],
            'ent1'   => $r['ent1'],
            'ent2'   => $r['ent2'],
            'ent3'   => $r['ent3'],
            'base_q' => self::n($r['base_quantity']),
            'base_a' => self::n($r['base_amount']),
            'deb_q'  => self::n($r['debit_quantity']),
            'deb_a'  => self::n($r['debit_amount']),
            'cre_q'  => self::n($r['credit_quantity']),
            'cre_a'  => self::n($r['credit_amount']),
            'bl_q'   => self::n($r['bl_quantity']),
            'bl_a'   => self::n($r['bl_amount']),
        ], $rows);

        self::table($lines, $cols, $data);
    }


    // =========================================================================
    //  Ниско ниво – форматиране на текст
    // =========================================================================

    private static function table(array &$lines, array $cols, array $data): void
    {
        $header  = '';
        $divider = '';
        foreach ($cols as $col) {
            $w        = $col['w'];
            $header  .= self::cell($col['label'], $w, $col['r'] ?? false) . ' ';
            $divider .= str_repeat('-', $w) . ' ';
        }
        self::line($lines, '  ' . rtrim($header));
        self::line($lines, '  ' . rtrim($divider));

        foreach ($data as $row) {
            $out = '';
            foreach ($cols as $key => $col) {
                $out .= self::cell((string)($row[$key] ?? ''), $col['w'], $col['r'] ?? false) . ' ';
            }
            self::line($lines, '  ' . rtrim($out));
        }

        self::line($lines, '');
    }


    private static function cell(string $text, int $width, bool $rightAlign = false): string
    {
        $len = mb_strlen($text, 'UTF-8');
        if ($len > $width) {
            $text = mb_substr($text, 0, $width - 1, 'UTF-8') . '…';
            $len  = $width;
        }
        $pad = $width - $len;
        return $rightAlign
            ? str_repeat(' ', $pad) . $text
            : $text . str_repeat(' ', $pad);
    }


    private static function kvBlock(array &$lines, array $data): void
    {
        $maxKey = max(array_map('strlen', array_keys($data)));
        foreach ($data as $k => $v) {
            $v = is_null($v) ? '(null)' : (string)$v;
            self::line($lines, '  ' . str_pad($k, $maxKey) . ' : ' . $v);
        }
        self::line($lines, '');
    }


    private static function heading(array &$lines, string $text): void
    {
        self::separator($lines, '-', 80);
        self::line($lines, '  ' . $text);
        self::separator($lines, '-', 80);
    }


    private static function separator(array &$lines, string $char, int $len): void
    {
        self::line($lines, str_repeat($char, $len));
    }


    private static function line(array &$lines, string $text): void
    {
        $lines[] = $text;
    }


    private static function countInfo(array &$lines, array $all, array $filtered, string $filter): void
    {
        $total = count($all);
        $shown = count($filtered);
        if ($filter !== '' && $total !== $shown) {
            self::line($lines, "  Показани {$shown} от {$total} записа (филтър: сметка {$filter})");
        } else {
            self::line($lines, "  Общо записи: {$total}");
        }
        self::line($lines, '');
    }


    private static function n($v): string
    {
        if (is_null($v) || $v === '') return '-';
        $n = (float)$v;
        if ($n == 0) return '-';
        return number_format($n, 4, '.', ' ');
    }


    // =========================================================================
    //  Филтриране по сметка
    // =========================================================================

    private static function accEq(string $accNum, string $filter): bool
    {
        return ltrim($accNum, '0') === ltrim($filter, '0');
    }


    private static function filterByAcc(array $rows, string $filter, string $accField): array
    {
        if ($filter === '') return $rows;
        return array_values(array_filter($rows, fn($r) => self::accEq($r[$accField], $filter)));
    }
}