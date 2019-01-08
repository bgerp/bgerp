<?php
/**
 * Мениджър Описващ движенията на документи и контейнери в архива
 *
 *
 * @category  bgerp
 * @package   docart
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
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
        $this->FLD('type', 'enum(creating=Създаване, archiving=Архивиране, taking=Изваждане, 
                                      destruction=Унищожаване, include=Включване, exclude=Изключване)', 'caption=Действиe');
        
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
          
            $mQuery = $mvc->getQuery();
            $mQuery->where("#documentId = $documentContainerId");
            $mQuery->orderBy('createdOn', 'ASC');
            while ($move = $mQuery->fetch()) {
                $lastVolume = $move->toVolumeId;
            }
        }
        if ($data->filterCheck) {
            $data->toolbar->addBtn('Вземане', array($mvc, 'Taking',
                'documentId' => $documentContainerId,
                'ret_url' => true));
            $data->toolbar->addBtn('Унищожаване', array($mvc, 'Action'));
        }
        
        
        //  $data->toolbar->addBtn('Бутон', array($mvc, 'Action'));
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
        
        
        $volumeSuggestionsArr = array();
        
        $volQuery = docarch_Volumes::getQuery();
        $currentUser = core_Users::getCurrent();
        $volQuery->where("#isForDocuments = 'yes' AND #state = 'active' AND #inCharge = '{$currentUser}'");
        
        while ($vRec = $volQuery->fetch()) {
            if ($vRec->archive == 0) {
                $arch = 'Сборен';
            } else {
                $arch = docarch_Archives::fetch($vRec->archive)->name;
            }
            
            $volName = docarch_Volumes::getVolumeTypeName($vRec->type);
            
            $volumeSuggestionsArr[$vRec->id] = $volName .'-No'.$vRec->number.' / архив: '.$arch;
        }
        if (($rec->documentId && !$rec->id)) {
            
            $document = doc_Containers::getDocument($rec->documentId);
            
            expect($document->haveRightFor('single'),'Недостатъчни права за този документ.');
            
            $form->setOptions('toVolumeId', $volumeSuggestionsArr);
            
            $form->setField('fromVolumeId', 'input=none');
            
            $form->setFieldType('type', 'enum(archiving=Архивиране)');
            
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
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->FNC('toVolume', 'key(mvc=docarch_Volumes,allowEmpty)', 'caption=Входящ том,placeholder=Входящ том');
        
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
        if ($rec->documentId){
            
        $Document = doc_Containers::getDocument($rec->documentId);
        $className = $Document->className;
        $handle =$Document->singleTitle.'-'.$Document->getHandle();
        
        $url = toUrl(array("$className",'single', $Document->that));
        
        $row->documentId = ht::createLink($handle, $url, false, array());
        
        }
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
     * Връща възможно типа на възможното движение
     *
     * @param int $archive
     * @param int $document
     *
     * @return string
     */
    public static function getMovingBalance($arhive, $document)
    {
        $mQuery = docarch_Movements::getQuery();
        $mQuery->orderBy('modifiedOn', 'ASC');
        
        while ($move = $mQuery->fetch()) {
            if (!is_null($move->documentId)) {
                $lastDocMove[$move->documentId] = $move->type;
            }
        }
        
        switch ($lastDocMove[$document]) {
            
            case 'archiving':$possibleMove = 'taking'; break;
            
            case 'taking':$possibleMove = 'include'; break;
            
            case 'include':$possibleMove = 'taking'; break;
            
            case 'exclude':$possibleMove = 'destruction'; break;
        
        }
        
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
            $actionTitle = 'Показване на архивите към документа';
            $document = doc_Containers::getDocument($containerId);
            
            if ($document->haveRightFor('single')) {
                $linkArr = array('docarch_Movements', 'document' => $containerId, 'ret_url' => true);
            }
            $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, false, array('title' => $actionTitle));
            
            $html .= "<li class=\"action expenseSummary\">{$link}</li>";
        }
        
        return $html;
    }
    
    /**
     * @return string
     */
    public function act_Taking()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
       // requireRole('admin');
        $cRec = new stdClass();
        $form = cls::get('core_Form');
        $form->title = "Вземане на документ";
        
        $form->FLD('type', 'enum(taking=Изваждане)', 'caption=Действие');
        
        $form->FLD('toVolumeId', 'key(mvc=docarch_Volumes)', 'caption=Входящ том');
        
        $form->FLD('fromVolumeId', 'int', 'input=hidden,silent');
        
        $form->FLD('documentId', 'int', 'input=hidden,silent');
       
       
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        $cRec = $form->input();
        
        if ($form->isSubmitted()) {
            
            return new Redirect(getRetUrl());
        }
        
        return $this->renderWrapping($form->renderHtml());
    }


}
