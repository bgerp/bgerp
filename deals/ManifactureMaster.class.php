<?php


/**
 * Абстрактен клас за наследяване на протоколи свързани с производството
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_ManifactureMaster extends core_Master
{
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, note, folderId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, storeId, folderId, deadline, createdOn, createdBy';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'deadline';


    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'ceo,consumption,store';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    public $changableFields = 'note,sender,receiver';


    /**
     * Скриване на полето за споделени потребители
     */
    public $hideSharedUsersFld = true;


    /**
     * Кои са задължителните полета за модела
     */
    protected static function setDocumentFields($mvc)
    {
        $mvc->FLD('valior', 'date', 'caption=Вальор');
        $mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,silent');
        $mvc->FLD('deadline', 'datetime', 'caption=Срок до');
        $mvc->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Забележки');
        $mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка)', 'caption=Статус, input=none');

        $mvc->setDbIndex('valior');
        $mvc->setDbIndex('storeId');
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            if (isset($rec->storeId)) {
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }
        }
        
        if ($fields['-single']) {
            if (isset($rec->storeId)) {
                $storeLocation = store_Stores::fetchField($rec->storeId, 'locationId');
                if ($storeLocation) {
                    $row->storeId = ht::createHint($row->storeId, crm_Locations::getAddress($storeLocation));
                }
            }

            if($jobRec = static::getJobRec($rec)){
                $row->jobId = planning_Jobs::getHyperlink($jobRec->id, true);
            }
        }
        
        if ($fields['-list']) {
            $row->title = $mvc->getLink($rec->id, 0);
        }
    }


    /**
     * Към кое задание е документа
     *
     * @param $rec
     * @return mixed|null
     */
    public static function getJobRec($rec)
    {
        $rec = static::fetchRec($rec);
        $threadId = isset($rec->originId) ? doc_Containers::fetchField($rec->originId, 'threadId') : $rec->threadId;
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if(isset($firstDoc) && $firstDoc->isInstanceOf('deals_ManifactureMaster')) return;

        $Origin = isset($rec->originId) ? doc_Containers::getDocument($rec->originId) : $firstDoc;
        if($Origin){
            if($Origin->isInstanceOf('planning_Jobs')) return $Origin->fetch();
            if($Origin->isInstanceOf('planning_ConsumptionNotes')) return $Origin->getJobRec();

            if($Origin->isInstanceOf('planning_Tasks')){
                $jobDoc = doc_Containers::getDocument($Origin->fetchField('originId'));
                return $jobDoc->fetch();
            }
        }

        return null;
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $folderCover = doc_Folders::getCover($data->form->rec->folderId);
        if ($folderCover->haveInterface('store_AccRegIntf')) {
            $form->setDefault('storeId', $folderCover->that);
        }

        if($form->getField('useResourceAccounts', false)){
            if(isset($rec->id)){
                if(core_Packs::isInstalled('batch')){
                    if(batch_BatchesInDocuments::count("#containerId = {$rec->containerId}")){
                        $form->setReadOnly('useResourceAccounts');
                        $form->setField('useResourceAccounts', array('hint' => 'За да смените типа на влагането, трябва да се изтрият вече разписаните партиди'));
                    }
                }
            }
        }
    }


    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * Връща масив от използваните нестандартни артикули в протокола
     *
     * @param int $id - ид на протокола
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate' && empty($rec->id)) {
            $requiredRoles = 'no_one';
        }
        
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            if(($action == 'reject' && $rec->state == 'pending') || ($action == 'restore' && $rec->brState == 'pending')) return;
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        // Записваме документа за да му се обновят полетата
        $rec = $this->fetchRec($id);
        if ($rec !== false) {
            $this->save($rec);
        }
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        // Може да добавяме като начало на тред само в папка на склад
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return ($folderClass == 'store_Stores' || $folderClass == 'planning_Centers');
    }





    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        // Може да добавяме или към нишка в която има задание
        if (planning_Jobs::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')")) {

            return true;
        }

        // Може да добавяме или към нишка в която има задание
        if (planning_Tasks::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup' || #state = 'closed' || #state = 'pending')")) {

            return true;
        }

        // Ако корицата е папка на склад
        $folderId = doc_Threads::fetchField($threadId, 'folderId');
        $Cover = doc_Folders::getCover($folderId);
        if($Cover->isInstanceOf('store_Stores')) return true;

        // Ако не е ПП и е в нишка на сигнал за поддръжка
        $me = cls::get(get_called_class());
        if(!($me instanceof planning_ProductionDocument)){
            if($Cover->isInstanceOf('planning_Centers')) return true;

            if(cal_Tasks::fetchField("#threadId = {$threadId} AND #state IN ('active','stopped','wakeup','closed','pending')")) return true;
        }

        return false;
    }


    /**
     * Помощна ф-я дали документа може да се добавя с подадения ориджин
     *
     * @param int $containerId
     * @param int|null $userId
     * @return bool
     */
    protected function canAddToOriginId($containerId, $userId = null)
    {
        $origin = doc_Containers::getDocument($containerId);

        if($origin->isInstanceOf('planning_Tasks')){
            $state = $origin->fetchField('state');
            if (in_array($state, array('rejected', 'draft', 'waiting', 'stopped'))) {
                return false;
            } elseif ($state == 'closed') {
                if (!planning_Tasks::isProductionAfterClosureAllowed($origin->that, $userId, 'taskPostProduction,ceo,consumption')) {
                    return false;
                }
            }
        } elseif(($this instanceof planning_ConsumptionNotes || $this instanceof planning_ReturnNotes) && $origin->isInstanceOf('cal_Tasks')){
            $originRec = $origin->fetch('driverClass,state');
            if (in_array($originRec->state, array('rejected', 'draft', 'waiting', 'stopped'))) return false;
        } elseif(($this instanceof planning_ReturnNotes) && $origin->isInstanceOf('planning_DirectProductionNote')){
            $originRec = $origin->fetch('state');
            if(!planning_DirectProductNoteDetails::count("#noteId = {$originRec->id} AND #type = 'pop' AND #quantity != 0")) return false;
            if($originRec->state != 'active') return false;
        } elseif(!$origin->isInstanceOf('planning_ConsumptionNotes')){
            return false;
        }

        return true;
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $data->form->toolbar->setBtnOrder('btnPending', 10);
    }


    /**
     * Дали документа може да бъде възстановен/оттеглен/контиран, ако в транзакцията му има
     * поне едно затворено перо връща FALSE
     */
    protected static function on_AfterCanRejectOrRestore($mvc, &$res, $id, $action, $ignoreArr = array())
    {
        $rec = $mvc->fetchRec($id);
        $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
        if(is_object($firstDocument)){
            if($action == 'conto'){
                if($firstDocument->isInstanceOf('planning_Tasks')){
                    $state = $firstDocument->fetchField('state');
                    if($state == 'closed'){
                        $roles = $mvc->getRequiredRoles('conto', $rec);
                        if(!planning_Tasks::isProductionAfterClosureAllowed($firstDocument->that, core_Users::getCurrent(), $roles, $roles)){
                            $msg = "Документът не може да бъде контиран, защото операцията е приключена|*!";
                            core_Statuses::newStatus($msg, 'error');
                            $res = false;
                        }
                    }
                }
            } elseif($firstDocument->isInstanceOf('planning_Jobs') || $firstDocument->isInstanceOf('planning_Tasks')){
                $state = $firstDocument->fetchField('state');
                if($state == 'closed'){
                    $msg = "Документът не може да бъде оттеглен/възстановен, докато първият документ в нишката е затворен|*!";
                    core_Statuses::newStatus($msg, 'error');
                    $res = false;
                }
            }
        }
    }


    /**
     * Задаване на служителите на фирмата за избор
     *
     * @param core_Form $form
     * @return void
     */
    protected function setEmployeesOptions(&$form)
    {
        // Възможност за избор на служителите в полетата за получил/предал
        $options = crm_Persons::getEmployeesOptions(false, null, true);
        if(countR($options)){
            $options = array('' => '') + $options;
            $form->setSuggestions('sender', $options);
            $form->setSuggestions('receiver', $options);
        }
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }

        if($jobRec = static::getJobRec($rec)){
            $res .= ' ' . plg_Search::normalizeText(planning_Jobs::getRecTitle($jobRec));
        }
    }
}
