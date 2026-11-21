<?php


/**
 * Клас 'cat_EanPrefixes' - Диапазоните (GS1 префиксите) на нашите EAN13 номера
 *
 * Номерата, дадени на артикули, се пазят само в опаковките (cat_products_Packagings).
 * Заетите номера извън опаковките са в cat_EanCodes.
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_EanPrefixes extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Диапазони на нашите EAN номера';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'EAN диапазон';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_Created, plg_Modified, plg_State2, plg_Sorting';


    /**
     * Детайли
     */
    public $details = 'InProducts=cat_EanCodes,cat_EanCodes';


    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'prefix,capacity,inProducts,occupied,free,nextCode,purchasedOn,state,createdOn,createdBy';


    /**
     * Полета в единичния изглед
     */
    public $singleFields = 'prefix,purchasedOn,note,capacity,inProducts,occupied,free,nextCode,state,createdOn,createdBy';


    /**
     * Кой може да вижда списъка
     */
    public $canList = 'ceo,packEdit,sales,purchase';


    /**
     * Кой може да разглежда
     */
    public $canSingle = 'ceo,packEdit,sales,purchase';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,admin';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,admin';


    /**
     * Кой може да закрива
     */
    public $canChangestate = 'ceo,admin';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';


    /**
     * Шаблон за заглавието на записа
     */
    public $recTitleTpl = '[#prefix#]';


    /**
     * Кеш на всички префикси
     */
    protected static $prefixes;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('prefix', 'varchar(12)', 'caption=Префикс,mandatory');
        $this->FLD('purchasedOn', 'date', 'caption=Закупен на');
        $this->FLD('note', 'text(rows=2)', 'caption=Бележка');
        $this->FNC('capacity', 'int', 'caption=Номера,single=show,input=none');
        $this->FNC('inProducts', 'int', 'caption=В артикулите,single=show,input=none');
        $this->FNC('occupied', 'int', 'caption=Заети извън тях,single=show,input=none');
        $this->FNC('free', 'int', 'caption=Свободни,single=show,input=none');
        $this->FNC('nextCode', 'varchar', 'caption=Следващ,single=show,input=none');

        $this->setDbUnique('prefix');
    }


    /**
     * Преди показване на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Сменен префикс би откъснал вече дадените номера
        if (isset($data->form->rec->id)) {
            $data->form->setReadOnly('prefix');
        }
    }


    /**
     * Проверка на въведения префикс
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {

            return;
        }

        $rec = $form->rec;
        $prefix = trim($rec->prefix ?? '');
        if (!preg_match('/^[0-9]{6,12}$/', $prefix)) {
            $form->setError('prefix', 'Префиксът е от 6 до 12 цифри');

            return;
        }
        $rec->prefix = $prefix;

        foreach (self::getAll() as $pRec) {
            if ($pRec->id == ($rec->id ?? null)) {
                continue;
            }

            if (strpos($prefix, $pRec->prefix) === 0 || strpos($pRec->prefix, $prefix) === 0) {
                $form->setError('prefix', "Застъпва се с префикс|* {$pRec->prefix}");
            }
        }
    }


    /**
     * След запис
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        self::$prefixes = null;
    }


    /**
     * Всички префикси, вкл. закритите
     *
     * @return array
     */
    public static function getAll()
    {
        if (!isset(self::$prefixes)) {
            self::$prefixes = array();
            $query = self::getQuery();
            $query->orderBy('id', 'ASC');
            while ($rec = $query->fetch()) {
                self::$prefixes[$rec->id] = $rec;
            }
        }

        return self::$prefixes;
    }


    /**
     * Дали има активен диапазон, от който да се дават номера
     *
     * @return bool
     */
    public static function haveActive()
    {
        foreach (self::getAll() as $rec) {
            if ($rec->state == 'active') {

                return true;
            }
        }

        return false;
    }


    /**
     * Префиксът, към който принадлежи кодът
     *
     * @param string|null $code
     *
     * @return stdClass|null
     */
    public static function getByCode($code)
    {
        $code = trim($code ?? '');
        if (!preg_match('/^[0-9]{13}$/', $code)) {

            return null;
        }

        foreach (self::getAll() as $rec) {
            if (strpos($code, $rec->prefix) === 0) {

                return $rec;
            }
        }

        return null;
    }


    /**
     * Броят номера в диапазона
     *
     * @param stdClass $rec
     *
     * @return int
     */
    public static function getCapacity($rec)
    {
        return (int) pow(10, 12 - strlen($rec->prefix));
    }


    /**
     * Колко номера от диапазона стоят в опаковките на артикулите
     *
     * @param stdClass $rec
     *
     * @return int
     */
    public static function countInProducts($rec)
    {
        $query = cat_products_Packagings::getQuery();
        $query->where(array("#eanCode LIKE '[#1#]%' AND CHAR_LENGTH(#eanCode) = 13", $rec->prefix));

        return $query->count();
    }


    /**
     * Дали номерът вече е зает - в опаковка или в регистъра извън тях
     *
     * @param string $code
     *
     * @return bool
     */
    public static function isTaken($code)
    {
        if (cat_products_Packagings::fetchField(array("#eanCode = '[#1#]'", $code), 'id')) {

            return true;
        }

        return (bool) cat_EanCodes::fetchField(array("#code = '[#1#]' AND #state != 'rejected'", $code), 'id');
    }


    /**
     * Следващият свободен номер в диапазона
     *
     * Продължава след най-големия зает номер; празнините не се запълват,
     * защото в тях може да има стари номера, които не са записани.
     *
     * @param stdClass $rec
     *
     * @return string|null - null, ако диапазонът е изчерпан
     */
    public static function getNextCode($rec)
    {
        $prefix = $rec->prefix;
        $refLen = 12 - strlen($prefix);

        $max = '';
        $pQuery = cat_products_Packagings::getQuery();
        $pQuery->XPR('maxCode', 'varchar', 'MAX(#eanCode)');
        $pQuery->where(array("#eanCode LIKE '[#1#]%' AND CHAR_LENGTH(#eanCode) = 13", $prefix));
        $pQuery->show('maxCode');
        $pRec = $pQuery->fetch();
        if (is_object($pRec) && !empty($pRec->maxCode)) {
            $max = $pRec->maxCode;
        }

        $cQuery = cat_EanCodes::getQuery();
        $cQuery->XPR('maxCode', 'varchar', 'MAX(#code)');
        $cQuery->where(array("#prefixId = '[#1#]' AND #state != 'rejected'", $rec->id));
        $cQuery->show('maxCode');
        $cRec = $cQuery->fetch();
        if (is_object($cRec) && !empty($cRec->maxCode) && strcmp($cRec->maxCode, $max) > 0) {
            $max = $cRec->maxCode;
        }

        $ref = ($max === '') ? 0 : (int) substr($max, strlen($prefix), $refLen);
        $capacity = self::getCapacity($rec);
        $Ean = cls::get('gs1_TypeEan');

        while (++$ref < $capacity) {
            $code = $Ean->eanCheckDigit($prefix . str_pad((string) $ref, $refLen, '0', STR_PAD_LEFT));
            if (!self::isTaken($code)) {

                return $code;
            }
        }

        return null;
    }


    /**
     * Следващият свободен номер от активните диапазони
     *
     * @return string|null
     */
    public static function getNextFreeCode()
    {
        foreach (self::getAll() as $rec) {
            if ($rec->state != 'active') {
                continue;
            }

            if ($code = self::getNextCode($rec)) {

                return $code;
            }
        }

        return null;
    }


    /**
     * Проверка на наш номер, въведен в опаковка на артикул
     *
     * @param string   $code
     * @param int      $productId
     * @param int|null $packagingId
     * @param string|null $warning - предупреждение, ако има
     *
     * @return string|null - грешка, ако номерът не може да се даде
     */
    public static function checkProductCode($code, $productId, $packagingId, &$warning = null)
    {
        $warning = null;
        if (!self::getByCode($code)) {

            return null;
        }

        $cRec = cat_EanCodes::fetch(array("#code = '[#1#]' AND #state != 'rejected'", $code));
        if (is_object($cRec)) {
            $otherProduct = !empty($cRec->productId) && $cRec->productId != $productId;
            if ($otherProduct) {
                $link = cat_Products::getHyperlink($cRec->productId, true);
                $msg = array('reserved' => 'Номерът е запазен за', 'legacy' => 'Номерът е даден на', 'retired' => 'Номерът е бил на');

                $tail = ($cRec->status == 'retired') ? '. |Номерата на GS1 не се дават повторно' : '';

                return ($msg[$cRec->status] ?? 'Номерът е зает от') . "|* {$link}" . $tail;
            }

            if ($cRec->status == 'retired' && empty($cRec->productId)) {

                return 'Номерът е оттеглен. Номерата на GS1 не се дават повторно';
            }

            if ($cRec->status == 'legacy' && empty($cRec->productId)) {
                $note = str::limitLen(trim($cRec->note ?? ''), 200);
                $warning = "Стар номер без артикул|*: {$note}. |Същото изделие ли е?";
            }
        }

        if (!$warning && cat_Products::fetchField($productId, 'canManifacture') != 'yes') {
            $warning = 'Артикулът не е производим. Нашите номера са само за наши изделия. Да се даде ли?';
        }

        return null;
    }


    /**
     * След преобразуване към вербални стойности
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $Int = core_Type::getByName('int');
        $capacity = self::getCapacity($rec);
        $inProducts = self::countInProducts($rec);
        $occupied = cat_EanCodes::count(array("#prefixId = '[#1#]' AND #state != 'rejected'", $rec->id));

        if (isset($fields['-list']) && $mvc->haveRightFor('single', $rec)) {
            $row->prefix = ht::createLink($row->prefix, array($mvc, 'single', $rec->id));
        }

        $row->capacity = $Int->toVerbal($capacity);
        $row->inProducts = $Int->toVerbal($inProducts);
        $row->occupied = $Int->toVerbal($occupied);
        $row->free = $Int->toVerbal(max(0, $capacity - $inProducts - $occupied));

        if ($rec->state == 'active') {
            $next = self::getNextCode($rec);
            $row->nextCode = $next ? $next : ht::createHint('', 'Диапазонът е изчерпан', 'warning');
        }
    }
}
