<?php
/**
 * Мениджър описващ движенията на документи и контейнери в архива
 *
 *
 * @category  bgerp
 * @package   docart
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Движения в архива
 */
class docarch_Movements extends core_Master
{
    public $title = 'Движения в архива';
    
    public $loadList = 'plg_Created,plg_Search';
    
    public $listFields = 'type,documentId,toVolumeId,fromVolumeId,userID,createdOn=Създаден,modifiedOn=Модифициране';
    
    protected function description()
    {
        //Избор на типа движение в архива
        $this->FLD('type', 'varchar(set options)', 'caption=Действиe');
        
        //Документ - ако движението е на документ
        $this->FLD('documentId', 'key(mvc=doc_Containers)', 'caption=Документ,input=hidden,silent');
        
        //Изходящ том участващ в движението
        $this->FLD('fromVolumeId', 'key(mvc=docarch_Volumes)', 'caption=Изходящ том');
        
        //Входящ том участващ в движението
        $this->FLD('toVolumeId', 'key(mvc=docarch_Volumes)', 'caption=Входящ том');
        
        //Потребител получил документа или контейнера
        $this->FLD('userID', 'key(mvc=core_Users)', 'caption=Потребител');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $types = array('creating' => 'Създаване', 'archiving' => 'Архивиране', 'taking' => 'Изваждане',
            'destruction' => 'Унищожаване', 'include' => 'Включване', 'exclude' => 'Изключване');
        $form->setOptions('type', $types);
        
        //Архивиране на документ
        if (($rec->documentId && !$rec->id)) {
            $Document = doc_Containers::getDocument($rec->documentId);
            
            $documentClassName = $Document->className;
            
            $documentClassId = core_Classes::getId($documentClassName);
            
            //Подготовка на масива с предложеня на том за архивиране на документа
            $volumeSuggestionsArr = array();
            
            $volQuery = docarch_Volumes::getQuery();
            $currentUser = core_Users::getCurrent();
            $volQuery->where("#isForDocuments = 'yes' AND #state = 'active' AND #inCharge = '{$currentUser}'");
            $cond = '';
            
            while ($vRec = $volQuery->fetch()) {
                $classArrId = explode('|', trim(docarch_Archives::fetch($vRec->archive)->documents, '|'));
                
                if ((!is_null(docarch_Archives::fetch($vRec->archive)->documents)) && (!in_array($documentClassId, $classArrId))) {
                    continue;
                }
                
                $arch = ($vRec->archive == 0) ? 'Сборен' : docarch_Archives::fetch($vRec->archive)->name;
                
                $volName = docarch_Volumes::getVolumeTypeName($vRec->type);
                
                $volumeSuggestionsArr[$vRec->id] = $volName .'-No'.$vRec->number.' / архив: '.$arch;
            }
            
            expect($Document->haveRightFor('single'), 'Недостатъчни права за този документ.');
            
            $form->setOptions('toVolumeId', $volumeSuggestionsArr);
            
            $form->setField('fromVolumeId', 'input=none');
            
            $types = array('archiving' => 'Архивиране');
            $form->setOptions('type', $types);
            
            $form->setField('userID', 'input=none');
        }
        
        if (($rec->documentId && $rec->id)) {
        }
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
        //Брояч на документи в томовете
        //Масив с дейности, които увеличават броя на документите в тома
        $incrementMoves = array('archiving');
        
        //Масив с дейности, които намаляват броя на документите в тома
        $decrementMoves = array('taking');
        
        if (in_array($rec->type, $incrementMoves)) {
            if (!is_null($rec->toVolumeId)) {
                $volRec = docarch_Volumes::fetch($rec->toVolumeId);
            }
            
            $volRec->_isCreated = true;
            
            if (is_null($volRec->docCnt)) {
                $volRec->firstDocDate = $rec->createdOn;
                
                
                docarch_Volumes::save($volRec, 'firstDocDate');
            }
            
            $volRec->docCnt++;
            
            docarch_Volumes::save($volRec, 'docCnt');
        }
        
        if (in_array($rec->type, $decrementMoves)) {
            if (!is_null($rec->fromVolumeId)) {
                $volRec = docarch_Volumes::fetch($rec->fromVolumeId);
            }
            
            $volRec->_isCreated = true;
            
            $volRec->docCnt--;
            
            docarch_Volumes::save($volRec, 'docCnt');
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
        $data->toolbar->addBtn('Бутон', array($mvc, 'Action'));
        
        $documentContainerId = ($data->listFilter->rec->document);
        
        if ($documentContainerId) {
            
            $lastDocMovie=self::getLastDocumentMove($documentContainerId);
            $lastVolume = $lastDocMovie[$documentContainerId]->toVolumeId;
           
        }
       
        if ($data->filterCheck) {
            $Document = doc_Containers::getDocument($documentContainerId);
            $documentName = $Document->singleTitle.'-'.$Document->getHandle();
            $data->title = "Движение в архива на: {$documentName}";
            
            //Извежда бутона "Вземане" ако докумета е в поне един том
            if (!is_null($lastVolume)) {
                $data->toolbar->addBtn('Вземане', array($mvc, 'Taking',
                    'documentId' => $documentContainerId,
                    'fromVolumeId' => $lastVolume,
                    'ret_url' => true));
                
                $data->toolbar->addBtn('Унищожаване', array($mvc, 'Action'));
            }
           
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
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->FNC('toVolume', 'key(mvc=docarch_Volumes,allowEmpty, select=title)', 'caption=Входящ том,placeholder=Входящ том');
        
        $data->listFilter->showFields = 'toVolume';
        
        $data->listFilter->FNC('document', 'key(mvc=doc_Containers)', 'caption=Документ,input=hidden,silent');
        
        $data->listFilter->showFields .= ',search,document';
        
        $data->listFilter->input(null, true);
        
        if ($data->listFilter->isSubmitted() || $data->listFilter->rec->document) {
            if ($data->listFilter->rec->toVolume) {
                $data->query->where(array("#toVolumeId = '[#1#]'", $data->listFilter->rec->toVolume));
            }
            
            if ($data->listFilter->rec->document) {
                $data->query->where(array("#documentId = '[#1#]'", $data->listFilter->rec->document));
                
                $data->filterCheck = true;
            }
            
            // Сортиране на записите по дата на създаване
            $data->query->orderBy('#createdOn', 'DESC');
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->documentId) {
            
            $Document = doc_Containers::getDocument($rec->documentId);
            
            $className = $Document->className;
            
            $handle = $Document->singleTitle.'-'.$Document->getHandle();
            
            $url = toUrl(array("${className}",'single', $Document->that));
            
            $row->documentId = ht::createLink($handle, $url, false, array());
        }
        
        $row->type = self::getMoveName($row->type);
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
        
        bp(docarch_Movements::getQuery()->fetchAll());
        
        return 'action';
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
    }
    
    
    /**
     * Връща типа на възможното движение
     *
     * @param int $archive
     * @param int $document
     *
     * @return string
     */
    public static function getMovingBalance($arhive, $document)
    {
      
        return $possibleMove;
    }
    
    
    /**
     * Връща броя архиви към документа
     *
     * @param int $containerId - ид на контейнера
     *
     * @return string $html - броя документи
     */
    public static function getSummary($containerId)
    {
        $html = '';
        
        $mQuery = self::getQuery();
        
        $mQuery->in('documentId', $containerId);
        
        $mCnt = $mQuery->count();
        
        if ($mCnt > 0) {
            
            $count = cls::get('type_Int')->toVerbal($mCnt);
            $actionVerbal = tr('архиви');
            $document = doc_Containers::getDocument($containerId);
            
            if ($document->haveRightFor('single')) {
                $linkArr = array('docarch_Movements', 'document' => $containerId, 'ret_url' => true);
            }
            
            $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, false, array());
            
            $html .= "<li class=\"action archiveSummary\">{$link}</li>";
        }
        
        return $html;
    }
    
    
    /**
     * @return string
     */
    public function act_Taking()
    {
       
        $takingRec = new stdClass();
        $form = cls::get('core_Form');
        $form->title = 'Вземане на документ';
        
        $form->FLD('type', 'enum(taking=Изваждане)', 'caption=Действие');
        
        $form->FLD('fromVolumeId', 'int', 'input=hidden,silent');
        
        $form->FLD('documentId', 'int', 'input=hidden,silent');
        
        
        //Потребител получил документа или контейнера
        $form->FLD('userID', 'key(mvc=core_Users)', 'caption=Потребител');
        
        $currentUser = core_Users::getCurrent();
        $form->setDefault('userID', "{$currentUser}");
        
        $form->input(null, true);
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        $takingRec = $form->input();
        
        if ($form->isSubmitted()) {
            
            $this->save($takingRec);
            
            return new Redirect(getRetUrl());
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Взема името на действието
     *
     * @param string $type -ключа на името на типа
     *
     * @return string
     */
    public static function getMoveName($type)
    {
        switch ($type) {
            
            case 'creating':$typeName = 'Създаване'; break;
            
            case 'archiving':$typeName = 'Архивиране'; break;
            
            case 'taking':$typeName = 'Изваждане'; break;
            
            case 'destruction':$typeName = 'Унищожаване'; break;
            
            case 'include':$typeName = 'Включване'; break;
            
            case 'exclude':$typeName = 'Изключване'; break;
        
        }
        
        return $typeName;
    }
    
    
    /**
     * Връща последното движение на документ
     *
     * @param string $containerId -контернер Id на документа
     *
     * @return array
     */
    public static function getLastDocumentMove($containerId)
    {
        $lastDocMove = null;
        
        $mQuery = self::getQuery();
        
        $mQuery->where('#documentId IS NOT NULL');
        
        $mQuery->where("#documentId = ${containerId}");
        
        $mQuery->orderBy('createdOn', 'ASC');
        
        while ($move = $mQuery->fetch()) {
            
           
            
            if (!is_null($move->documentId)) {
                
                $archive = (!is_null($move->toVolumeId)) ? docarch_Volumes::fetch($move->toVolumeId):'';
         
                $lastDocMove[$move->documentId] = (object) array(
                    'movingType' => $move->type,
                    'toVolumeId' => $move->toVolumeId,
                    'archive' => $archive
                
                );
            }
        }
        
        return $lastDocMove;
    }
}
