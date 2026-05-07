<?php
/**
 * Дебъгер за изчисляване на счетоводен баланс
 *
 * Формат: JSON Lines (NDJSON) – един обект на ред.
 *
 * Оптимизации за минимален размер:
 *  - Филтриране при запис (само релевантните редове влизат)
 *  - Легенда за пера: ID → label еднократно; в събитията само числото
 *  - Кратки ключове (a=acc_num, e1=ent1_id, q=qty, m=amount ...)
 *  - strategy_probe само при !isTruthy
 *  - strategy_consume само при реална разлика в сумата
 *  - source кодиран като 1 буква: i=initial, j=journal
 *
 * Типове редове:
 *   L   – легенда за перо:        {"t":"L","id":6,"v":"[6 b] EUR - BNP..."}
 *   CS  – calc_start
 *   PB  – prev_balance_found
 *   JR  – journal_range
 *   LB  – load_balance_rows      масив
 *   JE  – journal_entries        масив
 *   SF  – strategy_feed
 *   SC  – strategy_consume       само при разлика
 *   SP  – strategy_probe         само при !isTruthy
 *   FB  – final_balance          масив
 *   SR  – save_result
 *
 * @category  bgerp
 * @package   acc
 */
class acc_BalanceDebugger
{
    // ── Статични полета ───────────────────────────────────────────────────────

    /** @var string|null Път до лог файла */
    private static $logFile = null;

    /** @var string Филтър по номер на сметка ('' = всички) */
    private static $filterAcc = '';

    /** @var array Кеш: entId → label (за entLabel()) */
    private static $entCache = [];

    /** @var array Вече записани легенди: entId → true */
    private static $legendWritten = [];


    // =========================================================================
    //  Инициализация
    // =========================================================================

    /**
     * Нулира лог файла. Извиква се в act_ForceCalc.
     *
     * @param string $filterAcc Системен номер на сметка за филтриране ('' = всички)
     */
    public static function clear(string $filterAcc = ''): void
    {
        self::$filterAcc    = ltrim(trim($filterAcc), '0');
        self::$entCache     = [];
        self::$legendWritten = [];

        $dir  = EF_TEMP_PATH;
        $name = 'balance_debug_' . date('Ymd_His') . '.json';
        self::$logFile = rtrim($dir, '/') . '/' . $name;

        file_put_contents(self::$logFile, '');
    }


    public static function getLogFile(): ?string
    {
        return self::$logFile;
    }


    // =========================================================================
    //  Основни log методи (извикват се от acc_Balances::calc)
    // =========================================================================

    public static function log(string $type, $data): void
    {
        // Картиране на тип → кратък тип
        $map = [
            'calc_start'        => 'CS',
            'prev_balance_found'=> 'PB',
            'journal_range'     => 'JR',
            'load_balance_rows' => 'LB',
            'journal_entries'   => 'JE',
            'final_balance'     => 'FB',
            'save_result'       => 'SR',
        ];

        $t = $map[$type] ?? $type;

        switch ($type) {
            case 'load_balance_rows':
                self::writeLoadBalance($data);
                break;

            case 'journal_entries':
                self::writeJournalEntries($data);
                break;

            case 'final_balance':
                self::writeFinalBalance($data);
                break;

            default:
                // Простите key-value блокове – само кратките ключове
                self::write($t, self::shortenKeys($data));
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
        // Филтриране по сметка
        if (!self::accMatch($accNum)) return;

        self::write('SF', array_filter([
            's'  => $source === 'initial' ? 'i' : 'j',
            'v'  => $valior ?: null,
            'a'  => $accNum,
            'at' => $accType,
            'e1' => self::entId($ent1),
            'e2' => self::entId($ent2),
            'e3' => self::entId($ent3),
            'q'  => $qty,
            'm'  => $amount,
        ], fn($v) => $v !== null && $v !== '' && $v !== 0));
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

        // Записваме само при реална разлика
        $diff = round((float)$amountAfter - (float)$amountBefore, 8);
        if ($diff == 0) return;

        self::write('SC', array_filter([
            'v'  => $valior ?: null,
            'a'  => $accNum,
            'at' => $accType,
            'fa' => $fromAccNum ?: null,
            'e1' => self::entId($ent1),
            'e2' => self::entId($ent2),
            'e3' => self::entId($ent3),
            'q'  => $qty,
            'mb' => $amountBefore,
            'ma' => $amountAfter,
            'df' => $diff,
        ], fn($v) => $v !== null && $v !== '' && $v !== 0));
    }


    /**
     * Probe – записва само проблемните редове (is_truthy = false).
     */
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
        // Записваме само при проблем – когато стратегията не се открива
        if ($isTruthy) return;
        if (!self::accMatch($accNum)) return;

        self::write('SP', array_filter([
            'a'   => $accNum,
            'ai'  => $accId,
            'e1'  => self::entId($ent1),
            'e2'  => self::entId($ent2),
            'e3'  => self::entId($ent3),
            'bq'  => $blQuantity,
            'nnf' => $notNullOrFalse,
        ], fn($v) => $v !== null && $v !== '' && $v !== 0));
    }


    // =========================================================================
    //  Сервиране на файла
    // =========================================================================

    public static function download($rec, string $filterAcc = ''): void
    {
        $path = self::$logFile;

        if (!$path || !file_exists($path) || filesize($path) === 0) {
            followRetUrl(null, '|Лог файлът е празен или не е създаден.', 'warning');
        }

        $filterEsc = $filterAcc ? "_acc{$filterAcc}" : '';
        $filename  = 'balance_debug_' . $rec->fromDate . '_' . $rec->toDate . $filterEsc . '.json';

        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');

        readfile($path);
        exit;
    }


    // =========================================================================
    //  Четимо представяне на пера – кеш + легенда
    // =========================================================================

    /**
     * Връща четимото описание на перо (за вътрешна употреба при зареждане).
     */
    public static function entLabel($entId): string
    {
        if (empty($entId)) return '';
        if (!isset(self::$entCache[$entId])) {
            $iRec = acc_Items::fetch($entId, 'num,title');
            self::$entCache[$entId] = $iRec ? "[{$iRec->num}] {$iRec->title}" : "#{$entId}";
        }
        return self::$entCache[$entId];
    }


    /**
     * Извлича числовото ID от label стринг "[6 b] EUR - BNP..."
     * и осигурява легендата да е записана преди да се ползва ID-то.
     * Връща числото (int) или null ако няма.
     */
    private static function entId(string $label): ?int
    {
        if ($label === '') return null;

        // Обратно търсене: label → id от кеша
        $entId = array_search($label, self::$entCache, true);

        if ($entId === false) return null;

        // Записваме легендата еднократно
        if (!isset(self::$legendWritten[$entId])) {
            self::writeDirect(['t' => 'L', 'id' => $entId, 'v' => $label]);
            self::$legendWritten[$entId] = true;
        }

        return (int)$entId;
    }


    // =========================================================================
    //  Специализирани write методи за масивни секции
    // =========================================================================

    private static function writeLoadBalance(array $rows): void
    {
        $out = [];
        foreach ($rows as $r) {
            if (!self::accMatch($r['acc_num'])) continue;

            $out[] = array_filter([
                'a'  => $r['acc_num'],
                'e1' => self::entId($r['ent1']),
                'e2' => self::entId($r['ent2']),
                'e3' => self::entId($r['ent3']),
                'bq' => $r['bl_quantity']   ?: null,
                'ba' => $r['bl_amount']     ?: null,
                'sq' => $r['base_quantity'] ?: null,
                'sa' => $r['base_amount']   ?: null,
                'mi' => $r['is_middle'] ? 1 : null,
            ], fn($v) => $v !== null);
        }
        if (!empty($out)) {
            self::write('LB', $out);
        }
    }


    private static function writeJournalEntries(array $rows): void
    {
        $out = [];
        foreach ($rows as $r) {
            $dm = self::accMatch($r['debit_acc_num']);
            $cm = self::accMatch($r['credit_acc_num']);
            if (!$dm && !$cm) continue;

            $row = array_filter([
                'v'   => $r['valior'],
                'da'  => $r['debit_acc_num'],
                'de1' => self::entId($r['debit_item1']),
                'de2' => self::entId($r['debit_item2']),
                'de3' => self::entId($r['debit_item3']),
                'dq'  => $r['debit_quantity']  ?: null,
                'ca'  => $r['credit_acc_num'],
                'ce1' => self::entId($r['credit_item1']),
                'ce2' => self::entId($r['credit_item2']),
                'ce3' => self::entId($r['credit_item3']),
                'cq'  => $r['credit_quantity'] ?: null,
                'm'   => $r['amount']          ?: null,
                'pu'  => $r['price_updated'] === 'да' ? 1 : null,
            ], fn($v) => $v !== null);

            $out[] = $row;
        }
        if (!empty($out)) {
            self::write('JE', $out);
        }
    }


    private static function writeFinalBalance(array $rows): void
    {
        $out = [];
        foreach ($rows as $r) {
            if (!self::accMatch($r['acc_num'])) continue;

            $out[] = array_filter([
                'a'   => $r['acc_num'],
                'e1'  => self::entId($r['ent1']),
                'e2'  => self::entId($r['ent2']),
                'e3'  => self::entId($r['ent3']),
                'bsq' => $r['base_quantity']   ?: null,
                'bsa' => $r['base_amount']     ?: null,
                'dq'  => $r['debit_quantity']  ?: null,
                'da'  => $r['debit_amount']    ?: null,
                'cq'  => $r['credit_quantity'] ?: null,
                'ca'  => $r['credit_amount']   ?: null,
                'blq' => $r['bl_quantity']     ?: null,
                'bla' => $r['bl_amount']       ?: null,
            ], fn($v) => $v !== null);
        }
        if (!empty($out)) {
            self::write('FB', $out);
        }
    }


    // =========================================================================
    //  Помощни методи
    // =========================================================================

    /** Съкращава ключовете на прост key-value масив */
    private static function shortenKeys(array $data): array
    {
        $map = [
            'balance_from'    => 'bf',
            'balance_to'      => 'bt',
            'period_currency' => 'cur',
            'prev_balance_id' => 'pid',
            'prev_from'       => 'pf',
            'prev_to'         => 'pt',
            'prev_period_id'  => 'ppid',
            'is_middle_balance' => 'mid',
            'prev_currency'   => 'pcur',
            'convert_to_date' => 'conv',
            'note'            => 'n',
            'journal_from'    => 'jf',
            'journal_to'      => 'jt',
            'changed'         => 'ch',
        ];

        $out = [];
        foreach ($data as $k => $v) {
            $out[$map[$k] ?? $k] = $v;
        }
        return $out;
    }


    /** Проверява дали acc_num съвпада с филтъра (точно, без водещи нули) */
    private static function accMatch(string $accNum): bool
    {
        if (self::$filterAcc === '') return true;
        return ltrim($accNum, '0') === self::$filterAcc;
    }


    /** Записва JSON ред с тип */
    private static function write(string $type, $data): void
    {
        self::writeDirect(['t' => $type, 'd' => $data]);
    }


    /** Записва произволен масив като JSON ред */
    private static function writeDirect(array $obj): void
    {
        if (!self::$logFile) return;
        file_put_contents(
            self::$logFile,
            json_encode($obj, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}