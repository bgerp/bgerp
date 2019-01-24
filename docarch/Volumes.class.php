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
    
    public $listFields = 'number,type,inCharge,archive,docCnt,createdOn=Създаден,modifiedOn=Модифициране';
    
    
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
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo,docarchMaster,docarch';
    
    protected function description()
    {
        //Определя в кой архив се съхранява конкретния том
        $this->FLD('archive', 'key(mvc=docarch_Archives,allowEmpty)', 'caption=В архив,placeholder=Всички,refreshForm,silent');
        
        //В какъв тип контейнер/том от избрания архив се съхранява документа
        $this->FLD('type', 'varchar(folder=Папка,box=Кутия, case=Кашон, pallet=Палет, warehouse=Склад)', 'caption=Тип,mandatory');
        
        //Това е номера на дадения вид том в дадения архив
        $this->FLD('number', 'int', 'caption=Номер,smartCenter');
        
        //Отговорник на този том/контейнер
        $this->FLD('inCharge', 'key(mvc=core_Users)', 'caption=Отговорник');
        
        //Съдържа ли документи
        $this->FLD('isForDocuments', 'enum(yes,no)', 'caption=Съдържа ли документи,input=none');
        
        //Показва в кой по-голям том/контейнер е включен
        $this->FLD('includeIn', 'key(mvc=docarch_Volumes)', 'caption=По-големия том,input=none');
        $this->FLD('position', 'varchar()', 'caption=Позиция в по-големия том,input=none');
        
        //Състояние
        $this->FLD('state', 'enum(active=Активен,rejected=Изтрит,closed=Приключен)', 'caption=Статус,input=none,notSorting');
        
        //Оща информация
        $this->FLD('firstDocDate', 'date', 'caption=Дата на първия документ в тома,input=none');
        $this->FLD('lastDocDate', 'date', 'caption=Дата на последния документ в тома,input=none');
        $this->FLD('docCnt', 'int', 'caption=Брой,input=none');
        
        $this->FNC('title', 'varchar', 'caption=Име');
        
        
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
    
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = self::getRecTitle($rec);
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
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
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Бутон', array($mvc,'Action'));
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        
        //Включване на том в по-голям
        $possibleVolArr = self::getVolumePossibleForInclude($rec);
        
        if ($rec->id && is_null($rec->includeIn) && $rec->type != 'warehouse' && !is_null($possibleVolArr)) {
            $data->toolbar->addBtn('Включване', array('docarch_Movements','Include',$rec->id,'ret_url' => true));
        }
        
        //Изключване на том от по-голям
        
        if ($rec->id && !is_null($rec->includeIn)) {
            $data->toolbar->addBtn('Изключване', array($mvc,'Exclude',$rec->id,'ret_url' => true));
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
            $title = self::getRecTitle($rec);
            // Прави запис в модела на движенията
            $mRec = (object) array('type' => 'creating',
                                   'position' => "$title",
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
     * @return string
     */
    public function act_Action()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('admin');
        
        bp(docarch_Volumes::getQuery()->fetchAll());
        
        return 'action';
    }
    
    /**
     * Изключва един том от по-голям
     */
    public function act_Exclude()
    {
        
        $ExcludeRec = new stdClass();
        $mRec = new stdClass();
        
        $thisVolId = Request::get('id');
        
        $thisVolRec = $this->fetch($thisVolId);
        
        $thisVolName = $this->getVerbal($thisVolRec, 'title');
        $upVolName = docarch_Volumes::fetch($thisVolRec->includeIn)-> title;
        
        $ExcludeRec->includeIn = null;
        
        $ExcludeRec->id = $thisVolId;
        
        $ExcludeRec->_isCreated = true;
        
        $pos =$thisVolName.'|'.$upVolName;
        
        
        $mRec = (object) array(
                                'type' => 'exclude',
                                'position' => $thisVolName,
                               
                                );
            
        docarch_Movements::save($mRec);
        
        $this->save($ExcludeRec);
            
        return new Redirect(getRetUrl());
        
        
       
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
        //Тома не може да бъде reject-нат ако не е празен
        if ($action == 'reject') {
            if (!is_null($rec->docCnt)) {
                $requiredRoles = 'no_one' ;
            } elseif (($rec->docCnt == 0)) {
                // $requiredRoles = 'no_one' ;
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
        
        $title = docarch_Volumes::getVolumeTypeName($rec->type);
        
        $title .= '-No'.$rec->number.' // '.$arch;
        
        
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
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
        $volQuery = docarch_Volumes::getQuery();
        
        $volQuery->where("#state != 'rejected'");
        
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
}
