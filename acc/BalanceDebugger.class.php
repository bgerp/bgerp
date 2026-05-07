<?php
/**
 * Дебъгер за изчисляване на счетоводен баланс
 *
 * Формат: CSV с header ред, разделител запетая, обграждане с кавички.
 * Всяко събитие → един или повече редове. Без RAM буфериране.
 *
 * Колони:
 *   type         – тип на събитието
 *   iter         – номер на calc() итерацията
 *   source       – initial / journal (за SF)
 *   valior       – дата на журналния запис
 *   acc          – номер на сметката
 *   acc_type     – active / passive
 *   from_acc     – контра-сметка при изписване
 *   e1_id        – ид на перо 1
 *   e1_lbl       – четимо описание на перо 1
 *   e2_id        – ид на перо 2
 *   e2_lbl       – четимо описание на перо 2
 *   e3_id        – ид на перо 3
 *   e3_lbl       – четимо описание на перо 3
 *   qty          – количество (SF, SC)
 *   amount       – сума (SF, JE)
 *   amount_before– сума преди стратегия (SC)
 *   amount_after – сума след стратегия (SC)
 *   diff         – разлика (SC)
 *   base_qty     – начално к-во (LB, FB)
 *   base_amount  – начална сума (LB, FB)
 *   bl_qty       – крайно к-во (LB, FB)
 *   bl_amount    – крайна сума (LB, FB)
 *   is_middle    – 1 ако е междинен баланс (LB)
 *   debit_acc    – дебитна сметка (JE)
 *   de1_id       – дебитно перо 1 ид (JE)
 *   de1_lbl      – дебитно перо 1 текст (JE)
 *   de2_id       – дебитно перо 2 ид (JE)
 *   de2_lbl      – дебитно перо 2 текст (JE)
 *   de3_id       – дебитно перо 3 ид (JE)
 *   de3_lbl      – дебитно перо 3 текст (JE)
 *   debit_qty    – дебитно к-во (JE, FB)
 *   debit_amount – дебитна сума (JE, FB)
 *   credit_acc   – кредитна сметка (JE)
 *   ce1_id       – кредитно перо 1 ид (JE)
 *   ce1_lbl      – кредитно перо 1 текст (JE)
 *   ce2_id       – кредитно перо 2 ид (JE)
 *   ce2_lbl      – кредитно перо 2 текст (JE)
 *   ce3_id       – кредитно перо 3 ид (JE)
 *   ce3_lbl      – кредитно перо 3 текст (JE)
 *   credit_qty   – кредитно к-во (JE, FB)
 *   credit_amount– кредитна сума (JE, FB)
 *   price_updated– 1 ако е обновена цената (JE)
 *   bal_from     – начало на баланса (CS)
 *   bal_to       – край на баланса (CS)
 *   currency     – валута (CS)
 *   prev_bal_id  – ид на предходния баланс (PB)
 *   prev_from    – начало на предходния (PB)
 *   prev_to      – край на предходния (PB)
 *   journal_from – начало на журналния диапазон (JR)
 *   journal_to   – край на журналния диапазон (JR)
 *   changed      – дали е имало промяна (SR)
 *
 * @category  bgerp
 * @package   acc
 */
class acc_BalanceDebugger
{
    // ── Колони в реда на CSV хедъра ───────────────────────────────────────────
    private static $COLUMNS = [
        'type', 'iter', 'source', 'valior',
        'acc', 'acc_type', 'from_acc',
        'e1_id', 'e1_lbl', 'e2_id', 'e2_lbl', 'e3_id', 'e3_lbl',
        'qty', 'amount', 'amount_before', 'amount_after', 'diff',
        'base_qty', 'base_amount', 'bl_qty', 'bl_amount', 'is_middle',
        'debit_acc',
        'de1_id', 'de1_lbl', 'de2_id', 'de2_lbl', 'de3_id', 'de3_lbl',
        'debit_qty', 'debit_amount',
        'credit_acc',
        'ce1_id', 'ce1_lbl', 'ce2_id', 'ce2_lbl', 'ce3_id', 'ce3_lbl',
        'credit_qty', 'credit_amount',
        'price_updated',
        'bal_from', 'bal_to', 'currency',
        'prev_bal_id', 'prev_from', 'prev_to', 'is_middle_bal',
        'journal_from', 'journal_to',
        'changed',
    ];

    private static $logFile   = null;
    private static $filterAcc = '';
    private static $iter      = 0;
    private static $entCache  = [];


    // =========================================================================
    //  Инициализация
    // =========================================================================

    public static function clear(string $filterAcc = ''): void
    {
        self::$filterAcc = ltrim(trim($filterAcc), '0');
        self::$entCache  = [];
        self::$iter      = 0;

        $dir  = EF_TEMP_PATH;
        $name = 'balance_debug_' . date('Ymd_His') . '.csv';
        self::$logFile = rtrim($dir, '/') . '/' . $name;

        // Записваме header реда
        file_put_contents(self::$logFile, self::csvRow(self::$COLUMNS));
    }


    public static function getLogFile(): ?string
    {
        return self::$logFile;
    }


    // =========================================================================
    //  Основни log методи
    // =========================================================================

    public static function log(string $type, $data): void
    {
        switch ($type) {
            case 'calc_start':
                self::$iter++;
                self::writeRow([
                    'type'     => 'CS',
                    'iter'     => self::$iter,
                    'bal_from' => $data['balance_from'] ?? '',
                    'bal_to'   => $data['balance_to']   ?? '',
                    'currency' => $data['period_currency'] ?? '',
                ]);
                break;

            case 'prev_balance_found':
                self::writeRow([
                    'type'         => 'PB',
                    'iter'         => self::$iter,
                    'prev_bal_id'  => $data['prev_balance_id'] ?? '',
                    'prev_from'    => $data['prev_from']       ?? '',
                    'prev_to'      => $data['prev_to']         ?? '',
                    'is_middle_bal'=> $data['is_middle_balance'] ?? ($data['note'] ?? ''),
                    'currency'     => $data['prev_currency']   ?? '',
                ]);
                break;

            case 'journal_range':
                self::writeRow([
                    'type'         => 'JR',
                    'iter'         => self::$iter,
                    'journal_from' => $data['journal_from'] ?? '',
                    'journal_to'   => $data['journal_to']   ?? '',
                ]);
                break;

            case 'load_balance_rows':
                foreach ($data as $r) {
                    if (!self::accMatch($r['acc_num'])) continue;
                    list($e1id, $e1lbl) = self::entPair($r['ent1']);
                    list($e2id, $e2lbl) = self::entPair($r['ent2']);
                    list($e3id, $e3lbl) = self::entPair($r['ent3']);
                    self::writeRow([
                        'type'        => 'LB',
                        'iter'        => self::$iter,
                        'acc'         => $r['acc_num'],
                        'e1_id'       => $e1id,  'e1_lbl' => $e1lbl,
                        'e2_id'       => $e2id,  'e2_lbl' => $e2lbl,
                        'e3_id'       => $e3id,  'e3_lbl' => $e3lbl,
                        'base_qty'    => $r['base_quantity'],
                        'base_amount' => $r['base_amount'],
                        'bl_qty'      => $r['bl_quantity'],
                        'bl_amount'   => $r['bl_amount'],
                        'is_middle'   => $r['is_middle'] ? 1 : '',
                    ]);
                }
                break;

            case 'journal_entries':
                foreach ($data as $r) {
                    $dm = self::accMatch($r['debit_acc_num']);
                    $cm = self::accMatch($r['credit_acc_num']);
                    if (!$dm && !$cm) continue;
                    list($de1id, $de1lbl) = self::entPair($r['debit_item1']);
                    list($de2id, $de2lbl) = self::entPair($r['debit_item2']);
                    list($de3id, $de3lbl) = self::entPair($r['debit_item3']);
                    list($ce1id, $ce1lbl) = self::entPair($r['credit_item1']);
                    list($ce2id, $ce2lbl) = self::entPair($r['credit_item2']);
                    list($ce3id, $ce3lbl) = self::entPair($r['credit_item3']);
                    self::writeRow([
                        'type'          => 'JE',
                        'iter'          => self::$iter,
                        'valior'        => $r['valior'],
                        'debit_acc'     => $r['debit_acc_num'],
                        'de1_id'        => $de1id, 'de1_lbl' => $de1lbl,
                        'de2_id'        => $de2id, 'de2_lbl' => $de2lbl,
                        'de3_id'        => $de3id, 'de3_lbl' => $de3lbl,
                        'debit_qty'     => $r['debit_quantity'],
                        'credit_acc'    => $r['credit_acc_num'],
                        'ce1_id'        => $ce1id, 'ce1_lbl' => $ce1lbl,
                        'ce2_id'        => $ce2id, 'ce2_lbl' => $ce2lbl,
                        'ce3_id'        => $ce3id, 'ce3_lbl' => $ce3lbl,
                        'credit_qty'    => $r['credit_quantity'],
                        'amount'        => $r['amount'],
                        'price_updated' => $r['price_updated'] === 'да' ? 1 : '',
                    ]);
                }
                break;

            case 'final_balance':
                foreach ($data as $r) {
                    if (!self::accMatch($r['acc_num'])) continue;
                    list($e1id, $e1lbl) = self::entPair($r['ent1']);
                    list($e2id, $e2lbl) = self::entPair($r['ent2']);
                    list($e3id, $e3lbl) = self::entPair($r['ent3']);
                    self::writeRow([
                        'type'          => 'FB',
                        'iter'          => self::$iter,
                        'acc'           => $r['acc_num'],
                        'e1_id'         => $e1id,  'e1_lbl' => $e1lbl,
                        'e2_id'         => $e2id,  'e2_lbl' => $e2lbl,
                        'e3_id'         => $e3id,  'e3_lbl' => $e3lbl,
                        'base_qty'      => $r['base_quantity'],
                        'base_amount'   => $r['base_amount'],
                        'debit_qty'     => $r['debit_quantity'],
                        'debit_amount'  => $r['debit_amount'],
                        'credit_qty'    => $r['credit_quantity'],
                        'credit_amount' => $r['credit_amount'],
                        'bl_qty'        => $r['bl_quantity'],
                        'bl_amount'     => $r['bl_amount'],
                    ]);
                }
                break;

            case 'save_result':
                self::writeRow([
                    'type'    => 'SR',
                    'iter'    => self::$iter,
                    'changed' => $data['changed'] ?? '',
                ]);
                break;
        }
    }


    // =========================================================================
    //  Стратегийни методи
    // =========================================================================

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
        if (!self::accMatch($accNum)) return;

        list($e1id, $e1lbl) = self::entPair($ent1);
        list($e2id, $e2lbl) = self::entPair($ent2);
        list($e3id, $e3lbl) = self::entPair($ent3);

        self::writeRow([
            'type'     => 'SF',
            'iter'     => self::$iter,
            'source'   => $source === 'initial' ? 'initial' : 'journal',
            'valior'   => $valior,
            'acc'      => $accNum,
            'acc_type' => $accType,
            'e1_id'    => $e1id,  'e1_lbl' => $e1lbl,
            'e2_id'    => $e2id,  'e2_lbl' => $e2lbl,
            'e3_id'    => $e3id,  'e3_lbl' => $e3lbl,
            'qty'      => $qty,
            'amount'   => $amount,
        ]);
    }


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
        if (!self::accMatch($accNum)) return;

        $diff = round((float)$amountAfter - (float)$amountBefore, 8);
        if ($diff == 0) return;

        list($e1id, $e1lbl) = self::entPair($ent1);
        list($e2id, $e2lbl) = self::entPair($ent2);
        list($e3id, $e3lbl) = self::entPair($ent3);

        self::writeRow([
            'type'         => 'SC',
            'iter'         => self::$iter,
            'valior'       => $valior,
            'acc'          => $accNum,
            'acc_type'     => $accType,
            'from_acc'     => $fromAccNum,
            'e1_id'        => $e1id,  'e1_lbl' => $e1lbl,
            'e2_id'        => $e2id,  'e2_lbl' => $e2lbl,
            'e3_id'        => $e3id,  'e3_lbl' => $e3lbl,
            'qty'          => $qty,
            'amount_before'=> $amountBefore,
            'amount_after' => $amountAfter,
            'diff'         => $diff,
        ]);
    }


    public static function logStrategyProbe(
        string $accNum,
               $accId,
        string $ent1,
        string $ent2,
        string $ent3,
               $blQuantity,
        bool $notNullOrFalse,
        bool $isTruthy
    ): void {
        if ($isTruthy) return;
        if (!self::accMatch($accNum)) return;

        list($e1id, $e1lbl) = self::entPair($ent1);
        list($e2id, $e2lbl) = self::entPair($ent2);

        self::writeRow([
            'type'     => 'SP',
            'iter'     => self::$iter,
            'acc'      => $accNum,
            'e1_id'    => $e1id,  'e1_lbl' => $e1lbl,
            'e2_id'    => $e2id,  'e2_lbl' => $e2lbl,
            'qty'      => $blQuantity,
            'changed'  => $notNullOrFalse ? 'not_null' : 'null_or_false',
        ]);
    }


    // =========================================================================
    //  Сервиране
    // =========================================================================

    public static function download($rec, string $filterAcc = ''): void
    {
        $path = self::$logFile;

        if (!$path || !file_exists($path) || filesize($path) === 0) {
            followRetUrl(null, '|Лог файлът е празен или не е създаден.', 'warning');
        }

        $filterEsc = $filterAcc ? "_acc{$filterAcc}" : '';
        $filename  = 'balance_debug_' . $rec->fromDate . '_' . $rec->toDate . $filterEsc . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');

        // BOM за Excel да разпознае UTF-8
        echo "\xEF\xBB\xBF";
        readfile($path);
        exit;
    }


    // =========================================================================
    //  Четимо представяне на пера
    // =========================================================================

    public static function entLabel($entId): string
    {
        if (empty($entId)) return '';
        if (!isset(self::$entCache[$entId])) {
            $iRec = acc_Items::fetch($entId, 'num,title');
            self::$entCache[$entId] = $iRec ? "[{$iRec->num}] {$iRec->title}" : "#{$entId}";
        }
        return self::$entCache[$entId];
    }


    // =========================================================================
    //  Вътрешни помощни методи
    // =========================================================================

    /**
     * От label стринг "[6 b] EUR..." извлича [id, label].
     * Ако label е '' връща ['', ''].
     */
    private static function entPair(string $label): array
    {
        if ($label === '') return ['', ''];

        $entId = array_search($label, self::$entCache, true);
        if ($entId === false) return ['', $label];

        return [(int)$entId, $label];
    }


    private static function accMatch(string $accNum): bool
    {
        if (self::$filterAcc === '') return true;
        return ltrim($accNum, '0') === self::$filterAcc;
    }


    /**
     * Записва един ред в CSV файла.
     * $data е асоц. масив с имена на колони → стойности.
     * Незададените колони излизат празни.
     */
    private static function writeRow(array $data): void
    {
        if (!self::$logFile) return;

        $row = [];
        foreach (self::$COLUMNS as $col) {
            $row[] = isset($data[$col]) ? (string)$data[$col] : '';
        }

        file_put_contents(self::$logFile, self::csvRow($row), FILE_APPEND | LOCK_EX);
    }


    /**
     * Форматира масив от стойности като CSV ред.
     * Всяко поле е оградено с кавички; вградените кавички се удвояват.
     */
    private static function csvRow(array $fields): string
    {
        $escaped = array_map(
            fn($v) => '"' . str_replace('"', '""', (string)$v) . '"',
            $fields
        );
        return implode(',', $escaped) . "\r\n";
    }
}