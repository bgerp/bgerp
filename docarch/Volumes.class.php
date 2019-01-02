<?php
/**
 * Мениджър Видове томове и контейнери в архива
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
 * @title     Томове и контейнери
 */
class docarch_Volumes extends core_Master
{
    public $title = 'Томове и контейнери';
    
    public $loadList = 'plg_Created, plg_RowTools2,plg_Modified';
    
    public $types = '';
    
    public $listFields = 'number,type,inCharge,archive,createdOn=Създаден,modifiedOn=Модифициране';
    
    protected function description()
    {
        //Определя в кой архив се съхранява конкретния том
        $this->FLD('archive', 'key(mvc=docarch_Archives,allowEmpty)', 'caption=В архив,placeholder=Всички,refreshForm,silent');
        
        //В какъв тип контейнер/том от избрания архив се съхранява документа
        $this->FLD('type', "enum(folder=Папка,box=Кутия, case=Кашон, pallet=Палет, warehouse=Склад)", 'caption=Тип');
        
        //Това е номера на дадения вид том в дадения архив
        $this->FLD('number', 'int', 'caption=Номер,smartCenter');
        
        //Отговорник на този том/контейнер
        $this->FLD('inCharge', 'key(mvc=core_Users)', 'caption=Отговорник');
        
        //Съдържа ли документи
        $this->FLD('isForDocuments', 'enum(yes,no)', 'caption=Съдържа ли документи,input=none');
        
        //Показва в кой по-голям том/контейнер е включен
        $this->FLD('includeIn', 'key(mvc=docarch_Volumes)', 'caption=По-големия том,input=none');
        $this->FLD('position', 'varchar(32)', 'caption=Позиция в по-големия том,input=none');
        
        //Състояние
        $this->FLD('state', 'enum(active=Активен,rejected=Изтрит,closed=Приключен)', 'caption=Статус,input=none,notSorting');
        
        //Оща информация
        $this->FLD('firstDocDate', 'date', 'caption=Дата на първия документ в тома,input=none');
        $this->FLD('lastDocDate', 'date', 'caption=Дата на последния документ в тома,input=none');
        $this->FLD('docCnt', 'int', 'caption=Дата на първия документ,input=none');
        
        
        $this->setDbUnique('archive,type,number');
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Бутон', array('docarch_Movements', 'Add'));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * 
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
       
        if ($rec->id) {
            
            $rec->isCreated = true;
            
            $form->setReadOnly('archive');
            $form->setReadOnly('type');
            $form->setReadOnly('number');
            
            
        }else{
            
            
             if ($form->cmd == 'refresh' && $rec->archive) {
                 
                  $typesArr= arr::make(docarch_Archives::fetch($rec->archive)->volType,true);
               
                  foreach ($typesArr as $key => $v){
                      $volName = self::getVolumeTypeName($v);
                      $types .=$key.'='.$volName.','; 
                  }
                  
                  $types = (trim($types,',')); 
                  $form->setFieldType('type',"enum($types)");
              }
              
              
            $form->setDefault('state', 'active');
            
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
           
            $type = $form->rec->type;
            if (is_null($form->rec->archive)) {
                $form->rec->archive = 0;
            }
            $archive = $form->rec->archive;
            
            if (is_null($form->rec->number)) {
              $form->rec->number = $mvc->getNextNumber($archive,$type );
            }
           
        }else{
           
            
            
        }
    }
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        
        if($rec->type != docarch_Archives::minDefType($rec->archive) || $rec->archive == 0){
            $rec->isForDocuments = 'no';
        }else{
                $rec->isForDocuments = 'yes';
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
    
        
        if ($rec->isCreated !== true) {
            
            // Прави запис в модела на движенията
            $mRec = (object) array('type' => 'creating',);
            
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
        
        docarch_Movements::Add();
        
        return 'action';
    }
    
    /**
     * Намира следващия номер на том
     *
     * @param int $archive
     * @param string $type
     *
     * @return int
     */
    private function getNextNumber($archive,$type)
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
     * Взема името на типа на тома
     *
     * @param string $type -ключа на името на типа
     * @return string
     */
    public static function getVolumeTypeName($type)
    {
        return docarch_Archives::getArchiveTypeName($type);
    }
    
}
