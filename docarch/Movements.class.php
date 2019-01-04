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
    
    public $loadList = 'plg_Created, plg_RowTools2,plg_Modified,plg_Search';
    
    public $listFields = 'type,documentId,userID,fromVolumeId,toVolumeId,createdOn=Създаден,modifiedOn=Модифициране';
    
    protected function description()
    {
        //Избор на типа движение в архива
        $this->FLD('type', 'enum(creating=Създаване, archiving=Архивиране, taking=Изваждане, 
                                      destruction=Унищожаване, include=Включване, exclude=Изключване)', 'caption=Действиe');
        
        //Документ - ако движението е на документ
        $this->FLD('documentId', 'key(mvc=doc_Containers)', 'caption=Документ,input=hidden,silent');
        
        //Изходящ том участващ в движението
        $this->FLD('fromVolumeId', 'key(mvc=docarch_Volumes,select=title,allowEmpty)', 'caption=Изходящ том');
        
        //Входящ том участващ в движението
        $this->FLD('toVolumeId', 'key(mvc=docarch_Volumes,select=title,allowEmpty)', 'caption=Входящ том');
        
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
            $form->setOptions('toVolumeId', $volumeSuggestionsArr);
            
            $form->setField('fromVolumeId', 'input=none');
            
            $form->setFieldType('type', 'enum(archiving=Архивиране)');
            
            $form->setField('userID', 'input=none');
        }
        
        if (($rec->documentId && $rec->id)) {
            $form->setOptions('toVolumeId', $volumeSuggestionsArr);
            
            $form->setField('fromVolumeId', 'input=none');
            
            $form->setFieldType('type', 'enum(archiving=Архивиране)');
            
            $form->setField('userID', 'input=none');
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
            }
            
            // Сортиране на записите по дата на създаване
            $data->query->orderBy('#createdOn', 'DESC');
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
        
        bp(docarch_Volumes::getQuery()->fetchAll());
        
        return 'action';
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
}
