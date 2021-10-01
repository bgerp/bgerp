<?php
/**
 * Мениджър Видове томове и контейнери в архива
 *
 *
 * @category  bgerp
 * @package   docarch2
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Томове и контейнери
 */
class docarch2_Volumes extends core_Master
{
    public $title = 'Томове и контейнери';
    
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, plg_State2, plg_Rejected,docarch2_Wrapper';
    
    public $listFields = 'name,number,registerId,createdOn=Създаден,modifiedOn=Модифициране';
    
    
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

        //Към кой архив регистър се отнася ако е '0' означава, че тома се използва за архивиране на томове от различни други регистри
        $this->FLD('registerId', 'key(mvc=docarch2_Registers,allowEmpty)', 'caption=В регистър,placeholder=Всички,refreshForm,silent');
        
        //В какъв тип контейнер/том от избрания архив се съхранява документа
        $this->FLD('type', 'key(mvc=docarch2_ContainerTypes)', 'caption=Тип,mandatory');
        
        //Това е номера на дадения вид том в дадения архив
        $this->FLD('number', 'int', 'caption=Номер,placeholder=От системата,smartCenter');

        //Дата на активиране на най-стария документ
        $this->FLD('dateMin', 'date', 'caption=Най-стара дата');

        //Дата на активиране на най-новия документ
        $this->FLD('dateMax', 'date', 'caption=Най-нова дата');

        //Показва в кой по-голям том/контейнер е включен
        $this->FLD('parentId', 'key(mvc=docarch2_Volumes)', 'caption=Включен в ,input=none');

        $this->FNC('name', 'varchar(32)', 'caption=Име,input=hidden');

        $this->setDbUnique('number,type,registerId');
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

            $register = $form->rec->registerId;
            
            if (is_null($form->rec->number)) {
                $form->rec->number = $mvc->getNextNumber($register, $type);
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

        if ($row->parentId){
            $row->parentId = docarch2_Volumes::getHyperlink($rec->parentId);
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
        if (empty(docarch2_Registers::getQuery()->fetchAll())) {
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

        if ($mvc->haveRightFor('close', $data->rec)) {
            $activeMsg = 'Сигурни ли сте, че искате да отворите този том и да може да се добавят документи в него|*?';
            $closeMsg = 'Сигурни ли сте, че искате да приключите този том да не може да се добавят документи в него|*?';
            $closeBtn = 'Затваряне||Close';
            
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

        if ($rec->id && is_null($rec->parentId) && $rec->state != 'closed'  && !empty($possibleVolArr)) {
            $data->toolbar->addBtn('Включване', array('docarch2_Movements','VolIn',$rec->id,'ret_url' => true));
        }

        //Изключване на том от по-голям
        
        if ($rec->id && !is_null($rec->parentId) && $rec->state != 'closed') {
            $data->toolbar->addBtn('Изключване', array('docarch2_Movements','VolOut',$rec->id,'ret_url' => true));
        }

        //Преместване на том

        $condArr = self::getVolumePossibleForInclude($rec);
        if ($rec->id && !is_null($rec->parentId) && $rec->state != 'closed' && !empty($condArr)) {
            $data->toolbar->addBtn('Преместване', array('docarch2_Movements','VolRelocation',$rec->id,'ret_url' => true));
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
       if(!$rec->registerId){
           $rec->registerId = '0';
       };

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
        if (($rec->registerId == 0)) {
            $row->registerId = 'Сборен';
        }else{
            $row->registerId = docarch2_Registers::getHyperlink($rec->registerId);
        }
        $name = self::getRecTitle($rec);

        $row->name = ht::createLink($name, array('docarch2_Volumes', 'single', 'id' => $rec->id));



        //$row->type = docarch2_Volumes::getHyperlink($rec->type);

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
        //Тома не може да бъде изтрит ако е празен
        if ($rec->id && $action == 'delete') {
            $requiredRoles = 'no_one' ;
        }
        
        //Reject = Унищожаване
        if ($rec->id && $action == 'reject') {
            $requiredRoles = 'no_one' ;
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
            $rUsers = keylist::toArray(docarch2_Registers::fetch($rec->registerId)->users);
            if ((!in_array($cu,$rUsers)) && (!haveRole('docarchMaster')) && (!haveRole('ceo'))) {
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
    private function getNextNumber($register, $type)
    {
        $query = $this->getQuery();

        if($register) {
            $cond = "#registerId = {$register} AND";
        }
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

         if($rec->registerId == '0'){
        $register ='Сборен';
    } elseif ($rec->registerId){
             $register =docarch2_Registers::fetch($rec->registerId)->name;
         }
        $title = '';
         if ($rec->type){
             $title .= docarch2_ContainerTypes::fetch($rec->type)->name;
         }

        $title .= '-No'.$rec->number.' // '.$register;
;
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }

        return $title;
    }

    /**
     * Връща възможните томове в които да се инклудне или премести подадения
     *
     * @param string $id -id на тома за инкудване
     *
     * @return array - масив / null ако няма
     */
    public static function getVolumePossibleForInclude($rec)
    {
        $possibleArr = array();
        $possibleVolArr = array();

        $volQuery = docarch2_Volumes::getQuery();

        $volQuery->EXT('canInclude', 'docarch2_ContainerTypes', 'externalName=canInclude,externalKey=type');

        $volQuery->where("#state != 'rejected' AND #state != 'closed'");

        $volQuery->where("#canInclude = $rec->type");

        while ($vol = $volQuery->fetch()) {

            $thisVolName = docarch2_Volumes::getRecTitle($vol);

            $possibleVolArr[$vol->id] = $thisVolName;
        }
        if (!empty($possibleVolArr) && $rec->parentId){
            foreach ($possibleVolArr as $k => $v){

                if ($k == $rec->parentId){
                    unset($possibleVolArr[$k]);
                }
            }
        }

        return $possibleVolArr;
    }

    /**
     * Връща възможните томове в които да се архивира документ
     *
     * @param string $id -id на тома за инкудване
     *
     * @return array - масив / null ако няма
     */
    public static function getVolumePossibleForArhiveDocument($rec)
    {
        $possibleArr = array();
        $possibleVolArr = array();

        // има ли регистри дефинирани за документи от този клас , или за всякакви документи
        $docClassId = doc_Containers::getClassId($rec->containerId);

        $documentContainerId = ($rec->containerId);

        $registersQuery = docarch2_Registers::getQuery();

        $registersQuery->show('documents');

        $registersQuery->likeKeylist('documents', $docClassId);        //дали може да се архивира документ от този тип

        $registersQuery->orWhere('#documents IS NULL');                // ако регистъра е от общ тип

        //Ако има такива регистри
        if ($registersQuery->count() > 0) {

            $posibleRegistersArr = arr::extractValuesFromArray($registersQuery->fetchAll(), 'id');

            //Проверявам дали е архивиран в момента
            $isArchive = docarch2_State::getDocumentState($documentContainerId)->volumeId;;

            // Има ли във възможните регистри томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch2_Volumes::getQuery();

            $volQuery->EXT('regUsers', 'docarch2_Registers', 'externalName=users,externalKey=registerId');

            $volQuery->in('registerId', $posibleRegistersArr);

            $volQuery->likeKeylist('regUsers', $currentUser);
            bp($docClassId, $rec);
            $volQuery = docarch2_Volumes::getQuery();

            $volQuery->EXT('canInclude', 'docarch2_ContainerTypes', 'externalName=canInclude,externalKey=type');

            $volQuery->where("#state != 'rejected' AND #state != 'closed'");

            $volQuery->where("#canInclude = $rec->type");

            while ($vol = $volQuery->fetch()) {

                $thisVolName = docarch2_Volumes::getRecTitle($vol);

                $possibleVolArr[$vol->id] = $thisVolName;
            }
            if (!empty($possibleVolArr) && $rec->parentId) {
                foreach ($possibleVolArr as $k => $v) {

                    if ($k == $rec->parentId) {
                        unset($possibleVolArr[$k]);
                    }
                }
            }

            return $possibleVolArr;
        }
    }
}
