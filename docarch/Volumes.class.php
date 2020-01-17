<?php
/**
 * Мениджър Видове томове и контейнери в архива
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Томове и контейнери
 */
class docarch_Volumes extends core_Master
{
    public $title = 'Томове и контейнери';
    
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, plg_State2, plg_Rejected,docarch_Wrapper';
    
    public $listFields = 'number,type,inCharge,archive,docCnt,includedVolumes,createdOn=Създаден,modifiedOn=Модифициране';
    
    
    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, title';
    
    
    /**
     * Кой има право да затваря том
     */
    public $canClose = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo,docarchMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,docarch,docarchMaster';
    
    
    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,docarchMaster,docarch';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo,docarchMaster,docarch';
    
    protected function description()
    {
        //Име на тома
        $this->FNC('title', 'varchar', 'caption=Име');
        
        //Определя в кой архив се съхранява конкретния том
        $this->FLD('archive', 'key(mvc=docarch_Archives,allowEmpty)', 'caption=В архив,placeholder=Всички,refreshForm,silent');
        
        //В какъв тип контейнер/том от избрания архив се съхранява документа
        $this->FLD('type', 'varchar(folder=Папка,box=Кутия, case=Кашон, pallet=Палет, warehouse=Склад)', 'caption=Тип,mandatory');
        
        //Това е номера на дадения вид том в дадения архив
        $this->FLD('number', 'int', 'caption=Номер,placeholder=От системата,smartCenter');
        
        //Отговорник на този том/контейнер
        $this->FLD('inCharge', 'key(mvc=core_Users)', 'caption=Отговорник');
        
        //Съдържа ли документи
        $this->FLD('isForDocuments', 'enum(yes,no)', 'caption=Съдържа ли документи,input=none');
        
        //Състояние
        $this->FLD('state', 'enum(active=Активен,rejected=Изтрит,closed=Приключен)', 'caption=Статус,input=none,notSorting');
        
        //Показва в кой по-голям том/контейнер е включен
        $this->FLD('includeIn', 'key(mvc=docarch_Volumes)', 'caption=По-големия том,input=none');
        $this->FLD('position', 'varchar()', 'caption=Позиция в по-големия том,input=none');
        
        //Оща информация
        $this->FLD('firstDocDate', 'date', 'caption=Дата на първия документ в тома,input=none');
        $this->FLD('lastDocDate', 'date', 'caption=Дата на последния документ в тома,input=none');
        $this->FLD('docCnt', 'int', 'caption=Документи,input=none,smartCenter');
        
        
        $this->FNC('includedVolumes', 'varchar', 'caption=Томове,smartCenter');
        
        
        $this->setDbUnique('archive,type,number');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $types = array('folder' => 'Папка','box' => 'Кутия', 'case' => 'Кашон', 'pallet' => 'Палет', 'warehouse' => 'Склад');
        $form->setOptions('type', $types);
        
        
        $currentUser = core_Users::getCurrent();
        $form->setDefault('inCharge', "{$currentUser}");
        
        if ($rec->id) {
            $rec->_isCreated = true;
            
            $mQuery = docarch_Movements::getQuery();
            
            $mQuery->where("#toVolumeId = {$rec->id}");
            
            if ($mQuery->count() > 0) {
                $form->setReadOnly('archive');
                $form->setReadOnly('type');
                $form->setReadOnly('number');
            }
        } else {
            if ($form->cmd == 'refresh' && $rec->archive) {
                $typesArr = arr::make(docarch_Archives::fetch($rec->archive)->volType, true);
                $types = '';
                foreach ($typesArr as $key => $v) {
                    $volName = self::getVolumeTypeName($v);
                    $types .= $key.'='.$volName.',';
                }
                
                $types = arr::make(trim($types, ','), true);
                
                $form->setOptions('type', $types);
            }
            
            
            $form->setDefault('state', 'active');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        //Поставя автоматична номерация на тома, ако не е въведена ръчно
        if ($form->isSubmitted()) {
            $type = $form->rec->type;
            
            if (is_null($form->rec->archive)) {
                $form->rec->archive = 0;
            }
            
            $archive = $form->rec->archive;
            
            if (is_null($form->rec->number)) {
                $form->rec->number = $mvc->getNextNumber($archive, $type);
            }
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $row = &$data->row;
        $rec = &$data->rec;
        
        $rec->includedVolumes = self::getIncludedVolumes($rec);
        
        $row->state = docarch_Volumes::getVerbal($rec, 'state');
        
        if ($rec->includeIn) {
            $row->includeIn = docarch_Volumes::getHyperlink($rec->includeIn);
        }
        
        $row->includedVolumes = '';
        if (is_array($rec->includedVolumes)) {
            foreach ($rec->includedVolumes as $val) {
                $row->includedVolumes .= docarch_Volumes::getHyperlink($val).'</br>';
            }
        }
    }
    
    
    /**
     * Изчисляване на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return void
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = self::getRecTitle($rec);
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (empty(docarch_Archives::getQuery()->fetchAll())) {
            $data->toolbar->removeBtn('btnAdd');
        }
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        $data->toolbar->removeBtn('Вграждане');
        
        
        //Reject = Унищожаване
        if (isset($data->rec->id) && $mvc->haveRightFor('reject', $data->rec)) {
            $data->toolbar->addBtn(
                'Унищожаване',
                array(
                    $mvc,
                    'reject',
                    $data->rec->id,
                    'ret_url' => true
                ),
                'id=btnDelete,class=fright,warning=Наистина ли желаете да унищожите този том?,order=32,row=2,',
                'ef_icon = img/16/reject.png, title=Унищожаване на том'
                );
        }
        
        if ($mvc->haveRightFor('close', $data->rec)) {
            $activeMsg = 'Сигурни ли сте, че искате да отворите този том и да може да се добавят документи в него|*?';
            $closeMsg = 'Сигурни ли сте, че искате да приключите този том да не може да се добавят документи в него|*?';
            $closeBtn = 'Затваряне||Close';
            $includedVolumes = self::getIncludedVolumes($rec);
            
            if ($data->rec->state == 'closed') {
                $data->toolbar->addBtn('Отваряне', array($mvc, 'changeState', $data->rec->id, 'ret_url' => true), 'id=btnActivate');
                $data->toolbar->setWarning('btnActivate', $activeMsg);
            } elseif ($data->rec->state == 'active' && ($data->rec->docCnt > 0) || (!empty($includedVolumes))) {
                $data->toolbar->addBtn($closeBtn, array($mvc, 'changeState', $data->rec->id, 'ret_url' => true), 'order=32,id=btnClose');
                $data->toolbar->setWarning('btnClose', $closeMsg);
            }
        }
        
        //Включване на том в по-голям
        $possibleVolArr = self::getVolumePossibleForInclude($rec);
        
        if ($rec->id && is_null($rec->includeIn) && $rec->state != 'closed' && $rec->type != 'warehouse' && !is_null($possibleVolArr)) {
            $data->toolbar->addBtn('Включване', array('docarch_Movements','Include',$rec->id,'ret_url' => true));
        }
        
        //Изключване на том от по-голям
        
        if ($rec->id && !is_null($rec->includeIn) && $rec->state != 'closed') {
            $data->toolbar->addBtn('Изключване', array('docarch_Movements','Exclude',$rec->id,'ret_url' => true));
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if (!is_null($rec->archive)) {
            if (($rec->type == docarch_Archives::minDefType($rec->archive)) || $rec->archive == 0) {
                $rec->isForDocuments = 'yes';
            }
            if (($rec->type != docarch_Archives::minDefType($rec->archive)) && $rec->archive != 0) {
                $rec->isForDocuments = 'no';
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->_isCreated !== true) {
            
            // Прави запис в модела на движенията
            $className = get_class();
            
            $mRec = (object) array('type' => 'creating',
                'position' => $rec->id.'|'.$className.'|'. self::getRecTitle($rec),
            );
            
            
            docarch_Movements::save($mRec);
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {
            
            // Прави запис в модела на движенията
            $className = get_class();
            $mRec = (object) array('type' => 'deleting',
                'position' => $rec->title,
            );
            
            
            docarch_Movements::save($mRec);
        }
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if (($rec->archive == 0)) {
            $row->archive = 'Сборен';
        }
        
        $row->type = self::getVolumeTypeName($row->type);
        
        if ($rec->docCnt == 0) {
            $row->docCnt = '';
        }
        
        $rec->includedVolumes = self::getIncludedVolumes($rec);
        
        if (is_array($rec->includedVolumes) && !empty($rec->includedVolumes)) {
            $inclCnt = countR($rec->includedVolumes);
            $row->includedVolumes = $inclCnt;
        } else {
            $row->includedVolumes = '';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        //Тома  може да бъде изтрит ако е празен
        if ($rec->id && $action == 'delete') {
            if (!is_null($rec->docCnt)) {
                if (($rec->docCnt != 0)) {
                    $requiredRoles = 'no_one' ;
                }
            }
            
            $rec->includedVolumes = self::getIncludedVolumes($rec);
            if (!empty($rec->includedVolumes)) {
                $requiredRoles = 'no_one' ;
            }
        }
        
        //Reject = Унищожаване
        if ($rec->id && $action == 'reject') {
            $storageTimeMarker = true;
            
            $now = dt::now();
            
            //Срок за съхранение на този том(от срока на архива)
            $storageTime = docarch_Archives::fetchField($rec->archive, 'storageTime');
            
            //Датата на най-късния документ в този том -$latestDocumentDate
            $latestDocumentDate = self::getlatestDocumentDate($rec);
            
            //Ако има зададена продължителност
            if (!is_null($storageTime)) {
                $endDate = dt::addSecs($storageTime, $latestDocumentDate);
                
                // И крайната дата е минала, деактивираме лимита и продължаваме напред
                if ($endDate < $now) {
                    $storageTimeMarker = false;
                }
                
                if ((!is_null($rec->docCnt) || (!$rec->docCnt == 0)) && ($rec->state != 'closed')) {
                    $requiredRoles = 'no_one' ;
                } elseif ((!is_null($rec->docCnt) || (!$rec->docCnt == 0)) && ($rec->state == 'closed') && ($storageTimeMarker == 'true')) {
                    $requiredRoles = 'no_one' ;
                }
            } else {
                //Ако няма определен срок за съхранение
                $requiredRoles = 'no_one' ;
            }
            
            
            // Ако в тома има включени други томове разрешава reject само ако всички включени са разрешени
            $includedVolumes = self::getIncludedVolumes($rec);
            if (is_array($includedVolumes) && !empty($includedVolumes)) {
                $checkStorageArr = array();
                foreach ($includedVolumes as $volume) {
                    $volumeRec = docarch_Volumes::fetch($volume);
                    
                    $storageTime = docarch_Archives::fetchField($volumeRec->archive, 'storageTime');
                    
                    if (is_null($storageTime)) {
                        $requiredRoles = 'no_one' ;
                    }
                    
                    $latestDocumentDate = self::getlatestDocumentDate($volumeRec);
                    $endDate = dt::addSecs($storageTime, $latestDocumentDate);
                    if ($endDate < $now) {
                        $checkStorageArr[$volumeRec->id] = true;
                    } else {
                        $checkStorageArr[$volumeRec->id] = false;
                    }
                }
                
                if (in_array(false, $checkStorageArr)) {
                    $requiredRoles = 'no_one' ;
                }
            }
        }
        
        if ($rec->id && $action == 'edit') {
            if (($rec->state == 'closed')) {
                $requiredRoles = 'no_one' ;
            }
        }
        
        if ($rec->id && (
            $action == 'delete' ||
                         $action == 'reject' ||
                         $action == 'edit' ||
                         $action == 'close' ||
                         $action == 'single' ||
                         $action == 'activate'
                        )) {
            $cu = core_Users::getCurrent();
            
            if (($cu != $rec->inCharge) && (!haveRole('docarchMaster')) && (!haveRole('ceo'))) {
                $requiredRoles = 'no_one' ;
            }
        }
    }
    
    
    /**
     * Намира следващия номер на том
     *
     * @param int    $archive
     * @param string $type
     *
     * @return int
     */
    private function getNextNumber($archive, $type)
    {
        $query = $this->getQuery();
        $cond = "#archive = {$archive} AND";
        $cond .= "#type = '{$type}'";
        $query->where($cond);
        $query->XPR('maxVolNumber', 'int', 'MAX(#number)');
        $number = $query->fetch()->maxVolNumber;
        ++$number;
        
        return $number;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $arch = ($rec->archive == 0) ? 'Сборен' : docarch_Archives::fetch($rec->archive)->name;
        
        $title = self::getVolumeTypeName($rec->type);
        
        $title .= '-No'.$rec->number.' // '.$arch;
        
        
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Връща включените в този том томове
     */
    public static function getIncludedVolumes($rec, $escaped = true)
    {
        $includedVolumes = array();
        
        $volRec = docarch_Volumes::getQuery();
        
        $volRec->where('#includeIn  IS NOT NULL');
        
        $volRec->where("#id != {$rec->id}");
        
        
        while ($volume = $volRec->fetch()) {
            if ($volume->includeIn == $rec->id) {
                $includedVolumes[$volume->id] = $volume->id;
            }
        }
        
        return $includedVolumes;
    }
    
    
    /**
     * Взема името на типа на тома
     *
     * @param string $type -ключа на името на типа
     *
     * @return string
     */
    public static function getVolumeTypeName($type)
    {
        return docarch_Archives::getArchiveTypeName($type);
    }
    
    
    /**
     * Връща възможните томове в които да се инклудне подадения
     *
     * @param string $id -id на тома за инкудване
     *
     * @return array - масив / null ако няма
     */
    public static function getVolumePossibleForInclude($rec)
    {
        $possibleArr = array();
        $possibleVolArr = array();
        
        $volQuery = docarch_Volumes::getQuery();
        
        $volQuery->where("#state != 'rejected' AND #state != 'closed'");
        
        $volQuery->where("#id != {$rec->id} AND #archive = {$rec->archive} AND #type != 'folder'");
        
        switch ($rec->type) {
            
            case 'folder':$possibleArr = array('box'); break;
            
            case 'box':$possibleArr = array('case'); break;
            
            case 'case':$possibleArr = array('pallet'); break;
            
            case 'pallet':$possibleArr = array('warehouse'); break;
            
            case 'warehouse':$possibleArr = array(); break;
        
        }
        
        $volQuery->in('type', $possibleArr);
        
        if (empty($volQuery->fetchAll())) {
            
            return;
        }
        
        while ($vol = $volQuery->fetch()) {
            $possibleVolArr[$vol->id] = $vol->title;
        }
        
        return $possibleVolArr;
    }
    
    
    /**
     * Връща датата на документа с най-късна дата в даден том
     *
     * @param stdClass
     *
     * @return string
     */
    public static function getlatestDocumentDate($rec)
    {
        $vQuery = docarch_Movements::getQuery();
        
        $vQuery->where("#toVolumeId = {$rec->id} AND #type = 'archiving'");
        
        $vQuery->orderBy('documentDate', 'DESC');
        
        $vQuery->limit(1);
        
        while ($vRec = $vQuery->fetch()) {
            $latestDocumentDate = $vRec->documentDate;
        }
        
        return $latestDocumentDate;
    }
    
    
    /**
     * Стартиране от крон-а
     *
     * Прави провека през крона за изтекли томове
     *
     */
    public static function cron_chekOutOfStorage()
    {
        self::notifyForOutOfStorageTimeVolume();
    }
    
    
    /**
     * Нотифицира за томове с изтекъл срок за съхранение и
     * разрешени на унищожаване
     */
    public static function notifyForOutOfStorageTimeVolume()
    {
        $volQuery = docarch_Volumes::getQuery();
        
        $volQuery->where("#state != 'rejected'");
        
        $now = dt::now();
        $checkStorageArr = array();
        
        while ($volumeRec = $volQuery->fetch()) {
            $storageTime = docarch_Archives::fetchField($volumeRec->archive, 'storageTime');
            
            if (is_null($storageTime)) {
                continue;
            }
            
            $latestDocumentDate = self::getlatestDocumentDate($volumeRec);
            $endDate = dt::addSecs($storageTime, $latestDocumentDate);
            
            if ($endDate < $now) {
                $checkStorageArr[$volumeRec->id] = (object) array(
                    'id' => $volumeRec->id,
                    'state' => $volumeRec->state,
                    'inCharge' => $volumeRec->inCharge,
                    'title' => $volumeRec->title,
                );
            }
        }
        
        $roelId = core_Roles::fetchByName('docarchMaster');
        $docarchMasters = core_Users::getByRole($roelId);
        
        foreach ($checkStorageArr as $val) {
            $url = array('docarch_Volumes','single',$val->id);
            
            $msg = 'Срока за съхранение на '."{$val->title}".' е изтекъл и може да бъде унищожен';
            
            bgerp_Notifications::add($msg, $url, $val->inCharge);
            
            if (is_array($docarchMasters)) {
                foreach ($docarchMasters as $v) {
                    bgerp_Notifications::add($msg, $url, $v);
                }
            }
        }
    }
}
