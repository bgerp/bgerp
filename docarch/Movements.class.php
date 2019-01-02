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
    
    public $loadList = 'plg_Created, plg_RowTools2,plg_Modified';
    
    public $listFields = 'type,userID,fromVolumeId,toVolumeId,createdOn=Създаден,modifiedOn=Модифициране';
    
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
        

    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if (($rec->documentId)) {
        $volumeSuggestionsArr=array();
        
        $volQuery = docarch_Volumes::getQuery();
        $currentUser = core_Users::getCurrent();
        $volQuery->where("#isForDocuments = 'yes' AND #state = 'active' AND #inCharge = '{$currentUser}'"); 
        
        while ($vRec = $volQuery->fetch()){
            if ($vRec->archive == 0) {
                $arch = 'Сборен';
            }else {
                 $arch = docarch_Archives::fetch($vRec->archive)->name;
            }
            
            $volName = docarch_Volumes::getVolumeTypeName($vRec->type);
       
            $volumeSuggestionsArr[$vRec->id]=$volName .'-No'.$vRec->number.' / архив: '.$arch;
        }
       
        $form->setOptions('toVolumeId',$volumeSuggestionsArr);
        
     //  if (($rec->documentId)) {
           $form->setField('fromVolumeId','input=none');
           $form->setFieldType('type','enum(archiving=Архивиране)');
           $form->setField('userID','input=none');
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
}
