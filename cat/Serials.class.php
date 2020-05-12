<?php 

/**
 * Генерирани номера на артикули
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_Serials extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Генерирани номера';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Created, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, serial, sourceObjectId=Източник, createdOn, createdBy';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Опашка от записи за добавяне
     */
    protected static $saveRecs = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('serial', 'bigint', 'caption=Генериран №,mandatory');
        $this->FLD('sourceClassId', 'class(interface=label_SequenceIntf,select=title)', 'caption=Източник->Клас');
        $this->FLD('sourceObjectId', 'int', 'caption=Източник->Обект');
        
        $this->setDbUnique('serial');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->sourceClassId, $rec->sourceObjectId)) {
            $SourceClass = cls::get($rec->sourceClassId);
            $row->sourceObjectId = (cls::haveInterface('doc_DocumentIntf', $SourceClass)) ? $SourceClass->getLink($rec->sourceObjectId, 0) : $SourceClass->getTitleById($rec->sourceObjectId);
        }
        
        $row->serial = core_Type::getByName('varchar')->toVerbal(str_pad($rec->serial, 13, '0', STR_PAD_LEFT));
    }
    
    
    /**
     * Връща генериран номер според източника, и го регистрира в модела
     *
     * @param string $sourceClassId  - клас
     * @param string $sourceObjectId - ид на обект
     *
     * @return int $serial
     */
    public static function generateSerial($sourceClassId = null, $sourceObjectId = null)
    {
        $serial = self::getRand();
        self::assignSerial($serial, $sourceClassId, $sourceObjectId);
        
        return $serial;
    }
    
    
    /**
     * Регистрира дадения генериран номер, към обекта (ако има)
     *
     * @param string   $serial         - генериран номер
     * @param mixed    $sourceClassId  - клас на обекта
     * @param int|NULL $sourceObjectId - ид на обекта
     */
    public static function assignSerial($serial, $sourceClassId = null, $sourceObjectId = null)
    {
        expect((empty($sourceClassId) && empty($sourceObjectId)) || (!empty($sourceClassId) && !empty($sourceObjectId)));
        if (isset($sourceClassId)) {
            $sourceClassId = cls::get($sourceClassId)->getClassId();
        }
        
        $rec = (object) array('serial' => $serial, 'sourceClassId' => $sourceClassId, 'sourceObjectId' => $sourceObjectId);
        $rec->createdOn = dt::now();
        $rec->createdBy = core_Users::getCurrent();
        
        static::$saveRecs[$serial] = $rec;
    }
    
    
    /**
     * Кешира серийните номера до момента
     */
    private function cacheRecs()
    {
        if(empty(static::$cache)){
            core_Debug::log('Начало на кеширане на сер. номера');
            core_Debug::startTimer('cacheSerials');
            
            $query1 = self::getQuery();
            $query1->show('serial');
            static::$cache = arr::extractValuesFromArray($query1->fetchAll(), 'serial');
            
            $query2 = cat_products_Packagings::getQuery();
            $query2->where('#eanCode IS NOT NULL AND #eanCode != ""');
            $query2->show('eanCode');
            static::$cache += arr::extractValuesFromArray($query2->fetchAll(), 'eanCode');
            
            core_Debug::stopTimer('cacheSerials');
            core_Debug::log('Край на кеширане на сер. номера: ' . round(core_Debug::$timers['cacheSerials']->workingTime, 2));
        }
    }
    
    
    /**
     * Връща рандом НЕ-записан генериран номер
     *
     * @return int $serial
     */
    public static function getRand()
    {
        //self::cacheRecs();
        
        $serial = str::getRand('#############');
        static::$cache[$serial] = $serial;
        while(self::fetchField("#serial = '{$serial}'") || cat_products_Packagings::fetchField("#eanCode = '{$serial}'") || array_key_exists($serial, static::$cache)){
            $serial = str::getRand('#############');
        }
        
        return $serial;
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if(countR(static::$saveRecs)){
            core_Debug::log('Начало запис на сер. номера');
            core_Debug::startTimer('saveSerials');
            
            $mvc->saveArray(static::$saveRecs);
            
            core_Debug::stopTimer('saveSerials');
            core_Debug::log('Край запис на сер. номера: ' . round(core_Debug::$timers['saveSerials']->workingTime, 2));
        }
    }
    
    
    /**
     * Запис отговарящ на серийния номер
     *
     * @param int $serial
     *
     * @return stdClass|NULL $res
     */
    public static function getRecBySerial($serial)
    {
        $res = self::fetch(array("#serial = '[#1#]'", $serial));
        
        return (!empty($res)) ? $res : null;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'serial,sourceClassId';
        $data->listFilter->setFieldTypeParams('sourceClassId', 'allowEmpty');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy("#id", "DESC");
        
        if ($fRec = $data->listFilter->rec) {
            if (!empty($fRec->serial)) {
                $data->query->where(array("#serial LIKE '%[#1#]%'", $fRec->serial));
            }
            
            if (!empty($fRec->sourceClassId)) {
                $data->query->where("#sourceClassId = '{$fRec->sourceClassId}'");
            }
        }
    }
    
    
    /**
     * Канонизиране на генерирания номер
     *
     * @param string $serial
     *
     * @return string
     */
    public static function canonize($serial)
    {
        return str_pad($serial, 13, '0', STR_PAD_LEFT);
    }
    
    
    /**
     * Проверяване на серийния номер
     *
     * @param string $serial
     *
     * @return string
     */
    public static function check($serial, &$error)
    {
        if (!str::containOnlyDigits($serial)) {
            $error = 'Номера трябва да съдържа само цифри';
            
            return false;
        }
        
        if (strlen($serial) > 13) {
            $error = 'Надвишава максималния брой цифри|* 13';
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     *               ->title - заглавие на резултата
     *               ->url - линк за хипервръзка
     *               ->comment - html допълнителна информация
     *               ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();
        
        $str = trim($str);
        $oStr = $str;
        $str = ltrim($str, 0);
        
        $cQuery = cat_Serials::getQuery();
        $cQuery->where(array("#serial = '[#1#]'", $str));
        
        while ($catRec = $cQuery->fetch()) {
            if (!$catRec->sourceClassId || !$catRec->sourceObjectId) {
                continue;
            }
            
            $clsInst = cls::get($catRec->sourceClassId);
            $clsRec = $clsInst->fetch($catRec->sourceObjectId);
            
            $res = new stdClass();
            $res->title = tr('СН') . ': ' . $clsInst->getTitleById($catRec->sourceObjectId);
            
            $res->priority = 1;
            if ($clsRec->state == 'active') {
                $res->priority = 2;
            } elseif ($clsRec->state == 'rejected') {
                $res->priority = 0;
            }
            
            if (strlen($catRec->serial) != strlen($oStr)) {
                if ($res->priority) {
                    $res->priority /= 2;
                }
            }
            
            if ($clsInst instanceof core_Master && $clsInst->haveRightFor('single', $clsRec)) {
                $res->url = array($clsInst, 'single', $clsRec->id, 'Q' => $str);
            } elseif ($clsInst instanceof core_Detail) {
                if ($mId = $clsRec->{$clsInst->masterKey}) {
                    $clsInst->Master = cls::get($clsInst->Master);
                    $mRec = $clsInst->Master->fetch($mId);
                    if ($clsInst->Master->haveRightFor('single', $mRec)) {
                        $res->url = array($clsInst->Master, 'single', $mRec->id, 'Q' => $str);
                    }
                }
            }
            
            $resArr[] = $res;
        }
        
        return $resArr;
    }
}
