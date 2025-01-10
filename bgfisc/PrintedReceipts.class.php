<?php


/**
 * Издадени фисклани бонове към документи от системата
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
class bgfisc_PrintedReceipts extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_PrintedReceipts';


    /**
     * Заглавие
     */
    public $title = 'Отпечатани фискални бонове';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper,plg_Created,plg_SelectPeriod,plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'string';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id,objectId,urnId=УНП,string,fh,state,type,createdOn=Отпечатано->На,createdBy=Отпечатано->От';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'sales,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Какво да се запише ако има проблем при генерирането на QR кода?
     */
    const MISSING_QR_CODE = ' ';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас,mandatory');
        $this->FLD('objectId', 'int', 'caption=Източник,mandatory');
        $this->FLD('urnId', 'key(mvc=bgfisc_Register)', 'caption=УНП');
        $this->FLD('string', 'varchar', 'caption=QR');
        $this->FLD('type', 'enum(normal=Обикновена,reverted=Сторно)', 'caption=Вид,mandatory');
        $this->FLD('state', 'enum(waiting=Чакащ,active=Отпечатано)', 'caption=Състояние,mandatory');
        $this->FLD('fh', 'fileman_FileType(bucket=electronicReceipts)', 'caption=Ел. бележка');
        
        $this->setDbIndex('classId,objectId');
        $this->setDbIndex('urnId');
    }
    
    
    /**
     * Връща QR кода на отпечаната предишна бележка
     *
     * @param mixed $class
     * @param int   $objectId
     *
     * @return NULL|string $qrCode
     */
    public static function getQrCode($class, $objectId)
    {
        $rec = self::get($class, $objectId);
        $qrCode = !empty($rec->string) ? $rec->string : null;
        
        return $qrCode;
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $urn = bgfisc_Register::fetchField($rec->urnId, 'urn');
        
        $res .= ' ' . plg_Search::normalizeText($urn);

        if(isset($rec->classId) && isset($rec->objectId)){
            $Class = cls::get($rec->classId);
            $objectName = (cls::haveInterface('doc_DocumentIntf', $Class)) ? $Class->getHandle($rec->objectId) : $Class->getTitleById($rec->objectId);
            $res .= ' ' . plg_Search::normalizeText($objectName);
        }
    }
    
    
    /**
     * Извличане на  записа по документ
     *
     * @param mixed $classId
     * @param mixed $objectId
     *
     * @return false|stdClass
     */
    public static function get($classId, $objectId)
    {
        $Class = cls::get($classId);
        
        return self::fetch("#classId = {$Class->getClassId()} AND #objectId = {$objectId}");
    }
    
    
    /**
     * Изтрива чакащ запис
     *
     * @param mixed $classId
     * @param mixed $objectId
     *
     * @return int
     */
    public static function removeWaitingLog($classId, $objectId)
    {
        $Class = cls::get($classId);
        
        return self::delete("#classId = {$Class->getClassId()} AND #objectId = {$objectId} AND #state = 'waiting' AND (#string IS NULL OR #string = '')");
    }
    
    
    /**
     * Записва номера на отпечатаната бележка
     *
     * @param mixed       $class    - клас
     * @param int         $objectId - ид на обект
     * @param string|null $string   - номера на бележката
     */
    public static function logPrinted($class, $objectId, $string = null, $fh = null)
    {
        // Какво е УНП-то, към което е бележката
        $Class = cls::get($class);
        if ($Class instanceof pos_Receipts) {
            $revertId = $Class->fetchField($objectId, 'revertId');
            if (!empty($revertId) && $revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) {
                $registerRec = bgfisc_Register::forceRec($Class, $revertId);
            }
        }
        
        if (empty($registerRec)) {
            $registerRec = bgfisc_Register::forceRec($Class, $objectId);
        }
        expect($registerRec);
        
        $rec = (object) array('urnId' => $registerRec->id, 'classId' => $Class->getClassId(), 'objectId' => $objectId, 'type' => 'normal');
        if ($Class instanceof cash_Rko) {
            $rec->type = 'reverted';
        } elseif ($Class instanceof pos_Receipts) {
            if ($Class->fetchField($objectId, 'revertId')) {
                $rec->type = 'reverted';
            }
        }
        
        // Ако има съществуващ запис обновява се
        if ($exRec = self::get($Class, $objectId)) {
            $rec->id = $exRec->id;
        }
        
        if (!isset($string)) {
            $rec->state = 'waiting';
            $Class->logInfo('Започване на отпечатване на фискален бон', $objectId);
        } else {
            $rec->string = $string;
            $rec->state = 'active';
            $Class->logInfo('Приключване на отпечатване на фискален бон', $objectId);
        }
        $rec->fh = !empty($fh) ? $fh : null;
        
        self::save($rec);
        
        if (!empty($fh) && cls::haveInterface('doc_DocumentIntf', $Class)) {
            if ($fileId = fileman::fetchByFh($fh, 'id')) {
                doc_Linked::add($Class->fetchField($objectId, 'containerId'), $fileId, 'doc', 'file');
            }
        }
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
        $urnRec = bgfisc_Register::fetch($rec->urnId);
        $row->urnId = bgfisc_Register::getUrlLink($urnRec->urn);
        
        try {
            $Class = cls::get($rec->classId);
            $row->objectId = (cls::haveInterface('doc_DocumentIntf', $Class)) ? $Class->getLink($rec->objectId, 0) : $Class->getHyperlink($rec->objectId, true);
        } catch (core_exception_Expect $e) {
            $row->objectId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
        }
        
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        if ($rec->type == 'reverted') {
            $row->type = "<b class='red'>{$row->type}</b>";
        }
        
        if($rec->string == self::MISSING_QR_CODE){
            $row->string = "<span class='red'>" . tr('Отпечатана, но QR кода не е върнат') . "</span>";
        }
    }
    
    
    /**
     * Подготвя формата за филтриране
     */
    public function prepareListFilter_($data)
    {
        parent::prepareListFilter_($data);
        
        $data->query->orderBy('id', 'DESC');
        $data->listFilter->FLD('from', 'date', 'caption=От,silent');
        $data->listFilter->FLD('to', 'date', 'caption=До,silent');
        $data->listFilter->setField('createdBy', 'placeholder=Потребител,formOrder=11');

        $data->listFilter->class = 'simpleForm';
        $data->listFilter->setFieldTypeParams('createdBy', array('allowEmpty' => 'allowEmpty'));
        
        return $data;
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
        $data->listFilter->showFields = 'selectPeriod,from,to,search,createdBy';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($filterRec = $data->listFilter->rec) {
            if (!empty($filterRec->createdBy)) {
                $data->query->where("#createdBy = {$filterRec->createdBy}");
            }


            if (!empty($filterRec->from)) {
                $data->query->where("#createdOn >= '{$filterRec->from} 00:00:00'");
            }

            if (!empty($filterRec->to)) {
                $data->query->where("#createdOn <= '{$filterRec->to} 23:59:59'");
            }

            // И този стринг отговаря на хендлър на документ в системата
            $doc = doc_Containers::getDocumentByHandle($filterRec->search);
            if (is_object($doc)) {
                $data->query->orWhere("#classId = '{$doc->getClassId()}' AND #objectId = '{$doc->that}'");
            }
        }
    }
    
    
    /**
     * Логване на отпечатана бележка
     */
    public function act_Log()
    {
        // Подсигуряване че няма произволен достъп
        Request::setProtected('hash');
        expect($hash = Request::get('hash', 'varchar'));
        expect(str::checkHash($hash, 4));
        expect($docClassId = Request::get('docClassId', 'int'));
        expect($docId = Request::get('docId', 'int'));
        $fh = Request::get('fh', 'varchar');
        
        // Какъв е върнатия QR код, ако не е върнат все пак се логва, че е записано
        // Този екшън е достъпен само след fpOnSuccess, така че сме сигурни ,че на ФУ е отпечатана бележка
        // Очаква се върнатия стринг да е съдържа 5 *-ти в него
        $res = Request::get('res', 'varchar');
        $resArr = explode('*', $res);
        $res = (count($resArr) != 5) ? self::MISSING_QR_CODE : $res;
        
        self::logPrinted($docClassId, $docId, $res, $fh);
        
        $Class = cls::get($docClassId);
        $Class->logWrite('Отпечатване на фискален бон', $docId);
        if($res === self::MISSING_QR_CODE){
            $Class->logErr("Фискалният бон е отпечатан, но не е записан", $docId);
            wp(Request::get('res', 'varchar'), strlen(Request::get('res', 'varchar')));
        } else {
            $Class->logInfo("Фискален бон: {$res}", $docId);
        }
        core_Locks::release("lock_{$Class->className}_{$docId}");

        followRetUrl(array($Class, 'single', $docId));
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->setFieldType('objectId', 'varchar');
    }
}
