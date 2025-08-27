<?php


/**
 * Регистър на УНП-та по 'Наредба 18'
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_Register extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_Register';


    /**
     * Заглавие
     */
    public $title = 'Регистър на касови плащания';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, plg_Search, plg_Sorting, plg_Created';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'urn=УНП,objectId=Източник,cashRegNum,userId,number,receiptNums=Бележки,createdOn=Създаване';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Регистър на УНП-та';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'sales,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'cashRegNum,number,urn';
    
    
    /**
     * Сериен номер, който да се задава на стари продажби
     */
    const OLD_SERIAL_NUM = 'OO000000';
    
    
    /**
     * Сериен номер, който да се задава на стари продажби
     */
    const OLD_SALE_NUM = '0000000';


    /**
     * Константа за без касов ордер
     */
    const WITHOUT_REG_NUM = -1;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас,mandatory');
        $this->FLD('objectId', 'int(align=left)', 'caption=Ид на обект,mandatory,tdClass=leftCol wrapText');
        $this->FLD('cashRegNum', 'varchar', 'caption=Касов апарат №,mandatory,smartCenter');
        $this->FLD('userId', 'user', 'caption=Потребител,mandatory');
        $this->FLD('number', 'varchar', 'caption=Номер,mandatory,smartCenter');
        $this->FNC('urn', 'varchar', 'caption=УНП,smartCenter');
        
        $this->setDbUnique('classId,objectId');
        $this->setDbIndex('cashRegNum,number');
        $this->setDbIndex('userId');
    }
    
    
    /**
     * Изчисление на "синтетичните" (1 и 2 разрядни) сметки
     */
    protected static function on_CalcUrn($mvc, &$rec)
    {
        $rec->urn = self::buildUrn($rec->cashRegNum, $rec->userId, $rec->number);
    }
    
    
    /**
     * Сформиране на УНО от записа
     *
     * @param stdClass $rec
     *
     * @return string $urn
     */
    public static function buildUrn($cashRegNum, $userId, $number)
    {
        if(empty($cashRegNum) && empty($userId) && empty($number)){
            
            // Генериране на УНП на стара продажба (всичките стари продажби са с едно УНП)
            $urn = self::OLD_SERIAL_NUM . '-' . '0000' . '-' . self::OLD_SALE_NUM;
        } else {
            
            // Генериране на УНП
            $urn = strtoupper($cashRegNum) . '-' . self::getUserHash($userId) . '-' . $number;
        }
        
        return $urn;
    }
    
    
    /**
     * Генерира уникален хеш по ид на потребител
     * 
     * @param int $userId
     * @return string
     */
    public static function getUserHash($userId)
    {
        return substr(abs(crc32($userId)), 0, 4);
    }
    
    
    /**
     * Какъв е следващия свободен номер на ФУ
     *
     * @param stdClass $deviceRec - запис на ФУ
     *
     * @return string|mixed|bool - следващия свободен номер
     */
    private static function getNextSaleNum($deviceRec)
    {
        $firstNum = !empty($deviceRec->startNumber) ? $deviceRec->startNumber : '1';
        $firstNum = str_pad($firstNum, 7, '0', STR_PAD_LEFT);
        
        $query = self::getQuery();
        $query->where(array("#cashRegNum = '[#1#]'", $deviceRec->serialNumber));
        $query->orderBy('number', 'DESC');
        $query->useIndex('cash_reg_num_number');
        $query->limit(1);
        
        $number = $query->fetch()->number;
        $number = isset($number) ? str::increment($number) : $firstNum;

        return $number;
    }
    
    
    /**
     * Какво е УНП-то на обекта
     *
     * @param mixed $class    - клас
     * @param int   $objectId - ид на обект
     *
     * @return string
     */
    public static function getSaleNumber($class, $objectId)
    {
        if(!$rec = self::forceRec($class, $objectId)){
            throw new core_exception_Expect('Проблем при генериране на УНП', 'Несъответствие');
        }
        
        return $rec->urn;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $Class = cls::get($rec->classId);
        $row->objectId = $Class->getHyperlink($rec->objectId, true);
        
        $receiptNum = bgfisc_PrintedReceipts::count("#urnId = {$rec->id}");
        $row->receiptNums = core_Type::getByName('int')->toVerbal($receiptNum);
        $row->urn = ht::createLink($row->urn, array('bgfisc_PrintedReceipts', 'list', 'search' => $rec->urn));
        $row->cashRegNum = ht::createLink($row->cashRegNum, array('bgfisc_PrintedReceipts', 'list', 'search' => $rec->cashRegNum));
        
        $state = $Class->fetchField($rec->objectId, 'state');
        $row->objectId = "<span class='state-{$state} document-handler'>{$row->objectId}</span>";
    }
    
    
    /**
     * Намира записа отговарящ на обекта
     *
     * @param mixed $class
     * @param int   $objectId
     *
     * @return stdClass|false
     */
    public static function getRec($class, $objectId)
    {
        $Class = cls::get($class);
        $rec = $Class->fetchRec($objectId);
        
        return self::fetch("#classId = {$Class->getClassId()} AND #objectId = '{$rec->id}'");
    }
    
    
    /**
     * Форсира УНП на обекта
     *
     * @param mixed $class    - клас
     * @param int   $objectId - ид на обекта
     *
     * @return stdClass $rec - запис в регистъра
     */
    public static function forceRec($class, $objectId)
    {
        // Ако има същесъвуващ запис взима се той
        $rec = self::getRec($class, $objectId);
        if(is_object($rec)){
            
            return $rec;
        }
        
        // Ако няма се създава ново УНП 
        $rec = self::createUrn($class, $objectId);
        
        return $rec;
    }
    
    
    /**
     * Създава ново УНП на обекта, ако обекта е ПКО/РКО се създава УНП на продажбата към която е
     * 
     * @param mixed $class - клас на обекта
     * @param int $objectId - ид на обекта
     * @param boolean $createNewUrn - дали да се създаде ново УНП, или УНП за стара продажба
     * @return stdClass $rec - запис на УНП-то
     */
    public static function createUrn($class, $objectId, $createNewUrn = false)
    {
        $Class = cls::get($class);
        $classId = $Class->getClassId();
        $rec = (object) array('classId' => $classId, 'objectId' => $objectId);

        // Ако е платежен документ
        $serialNum = null;
        if ($Class instanceof cash_Document) {
            
            // Форсира се УНП-то на продажбата му
            $threadId = $Class->fetchRec($objectId, 'threadId')->threadId;
            expect($firstDoc = doc_Threads::getFirstDocument($threadId));
            
            // Това УНП ще се използва
            $rec = self::forceRec($firstDoc->getInstance(), $firstDoc->that);
        } else {
            if ($Class instanceof sales_Sales) {
                $saleRec = $Class->fetch($objectId, 'bankAccountId,caseId');
                $deviceRec = bgfisc_Register::getFiscDevice($saleRec->caseId, $serialNum);
            } else {
                $caseId = pos_Points::fetchField($Class->fetchField($objectId, 'pointId'), 'caseId');
                $deviceRec = bgfisc_Register::getFiscDevice($caseId, $serialNum);
            }

            if($serialNum == static::WITHOUT_REG_NUM){
                $createNewUrn = false;
            }

            // Генериране на данните за УНП-то
            if($createNewUrn === true){
                if(empty($deviceRec)){
                    throw new core_exception_Expect('Не е закачено фискално устройство', 'Несъответствие');
                }
                
                $rec->cashRegNum = $deviceRec->serialNumber;
                $rec->userId = core_Users::getCurrent();
                $rec->number = self::getNextSaleNum($deviceRec);
            } else {
                $rec->cashRegNum = null;
                $rec->userId = null;
                $rec->number = null;
            }
            
            $rec->urn = self::buildUrn($rec->cashRegNum, $rec->userId, $rec->number);
            
            // Логване в обекта
            if($createNewUrn === true){
                $Class->logWrite('Генериране на УНП', $objectId);
                $Class->logInfo('Създадено УНП: ' . $rec->urn, $objectId);
            } else {
                $Class->logWrite('Генериране на УНП на стара продажба', $objectId);
            }
        }
        
        self::save($rec);

        return $rec;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
        $data->listFilter->showFields .= 'search,userId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->setFieldTypeParams('userId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->input();
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        if ($filterRec = $data->listFilter->rec) {
            if (!empty($filterRec->userId)) {
                $data->query->where("#userId = {$filterRec->userId}");
            }
        }
    }
    
    
    /**
     * Намира запис по подаденото УНП
     *
     * @param string $urn - УНП на продажба
     *
     * @return stdClass|false - записа в регистъра
     */
    public static function getRecByUrn($urn)
    {
        $urn = trim($urn);
        expect($urn);
        
        // Ако няма тирета, прави се опит все пак да се нормализира УНП-то
        if (strpos($urn, '-') === false) {
            $urn = str::removeWhiteSpace($urn, ' ');
            $arr = explode(' ', $urn);
        } else {
            $arr = explode('-', $urn); 
        }
        
        $cashRegNum = strtolower($arr[0]);
        $userHash = $arr[1];
        $number = $arr[2];
        
        // @todo временно 
        // @todo да се направи да работи със УНП на стара продажба
        $query = self::getQuery();
        $query->XPR('cashRegNumLower', 'varchar', 'LOWER(#cashRegNum)');
        $query->where(array("#cashRegNumLower = '[#1#]' AND #number = '[#2#]'", $cashRegNum, $number));
        
        while($rec = $query->fetch()){
            if(self::getUserHash($rec->userId) == $userHash){
                
                return $rec;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща текущото дефолтното Фискално устройство
     *
     * @param int         $caseId
     * @param string|null $serialNum
     *
     * @return stdClass|null $res
     */
    public static function getFiscDevice($caseId = null, &$serialNum = null)
    {
        $serialNum = null;
        
        // Ако има подадена каса, взема се ФУ от нея
        if (isset($caseId)) {
            $serialNum = cash_Cases::fetchField($caseId, 'cashRegNum');
        }

        if($serialNum == bgfisc_Register::WITHOUT_REG_NUM) return;

        // Ако не е определено ФУ взима се някое от дефолтните
        if (empty($serialNum)) {
            $serialNum = null;
            $serialNum1 = bgfisc_Setup::get('DEFAULT_FISC_DEVICE_1');
            $serialNum2 = bgfisc_Setup::get('DEFAULT_FISC_DEVICE_2');
            setIfNot($serialNum, $serialNum1, $serialNum2);
        }
        
        // Извличане на ФУ с подадения номер ако съществува
        $res = peripheral_Devices::getDevice('peripheral_FiscPrinterIntf', false, array('serialNumber' => $serialNum));
        
        return $res;
    }
    
    
    /**
     * Линк към подаденото ФУ. Ако не е подаден използва дефолтния
     *
     * @param string $serial - сериен номер, ако има
     * @param bool   $short  - само серийния номер или да се показва и с името
     *
     * @return core_ET - линка към фискалното устройство
     */
    public static function getFuLinkBySerial($serial, $short = true)
    {
        $makeHint = false;
        if (empty($serial)) {
            if ($lRec = bgfisc_Register::getFiscDevice(null)) {
                $serial = $lRec->serialNumber;
                $makeHint = true;
            }
        }

        if($serial == bgfisc_Register::WITHOUT_REG_NUM){

            return "<i style='color:blue;'>" . tr('Без') . "</i>";
        } else {
            $fuRec = peripheral_Devices::getDevice('peripheral_FiscPrinterIntf', false, array('serialNumber' => $serial));
            if (!empty($fuRec)) {
                $link = ($short === true) ? $fuRec->serialNumber : "{$fuRec->name} ( {$fuRec->serialNumber} )";
                $link = ht::createLink($link, peripheral_Devices::getSingleUrlArray($fuRec->id));
                if ($makeHint === true) {
                    $link = ht::createHint($link, 'Фискално устройство по подразбиране', 'notice', false);
                }
            } else {
                $link = ht::createHint("<span class='red'></span>", 'Няма избрано фискално устройство', 'error');
            }

            return $link;
        }
    }
    
    
    /**
     * Връща линк за търсене на отпечатаните бележки към това УНП
     * 
     * @param string $urn
     * 
     * @return core_ET
     */
    public static function getUrlLink($urn)
    {
        $res = core_Type::getByName('varchar')->toVerbal($urn);
        if (!Mode::isReadOnly() && bgfisc_PrintedReceipts::haveRightFor('list')) {
            $res = ht::createLink($res, array('bgfisc_PrintedReceipts', 'list', 'search' => $urn));
        }
        
        return $res;
    }


    public static function doRequireFiscForConto($mvc, $rec)
    {
        $serialNum = null;
        $caseId = ($mvc instanceof sales_Sales) ? $rec->caseId : (($rec->peroCase) ? $rec->peroCase : $mvc->getDefaultCase($rec));
        bgfisc_Register::getFiscDevice($caseId, $serialNum);

        return $serialNum != bgfisc_Register::WITHOUT_REG_NUM;
    }


    /**
     * До коя дата може да се издава КБ за сторно с основание Операторска грешка
     *
     * @param datetime $valior
     * @return false|string
     */
    public static function getMaxDateForStornoOperationError($valior)
    {
        $dayBefore = bgfisc_Setup::get('REVERT_OPERATION_ERROR_ALLOWED_BEFORE');
        $dayBeforePadded = str_pad($dayBefore, 2, '0', STR_PAD_LEFT);

        return date("Y-m-{$dayBeforePadded}", strtotime(dt::addMonths(1, $valior)));
    }
}
