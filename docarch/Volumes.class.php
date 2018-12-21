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
    
    public $listFields = 'number,type,inCharge,archive,createdOn=Създаден,modifiedOn=Модифициране';
    
    protected function description()
    {
        //Определя в кой архив се съхранява конкретния том
        $this->FLD('archive', 'key(mvc=docarch_Archives,allowEmpty)', 'caption=В архив');
        
        //В какъв тип контейнер/том от избрания архив се съхранява документа
        $this->FLD('type', 'enum(folder=Папка,box=Кутия, case=Нещоси, pallet=Палет, warehouse=Склад)', 'caption=Тип');
        
        //Това е номера на дадения вид том в дадения архив
        $this->FLD('number', 'int', 'caption=Номер');
        
        //Отговорник на този том/контейнер
        $this->FLD('inCharge', 'key(mvc=core_Users)', 'caption=Отговорник');
        
        //Съдържа ли документи
        $this->FLD('isForDocuments', 'enum(yes,no)', 'caption=Съдържа ли документи,input=none');
        
        //Показва в кой по-голям том/контейнер е включен
        $this->FLD('includeIn', 'key(mvc=docarch_Vlolumes)', 'caption=По-големия том,input=hidden');
        $this->FLD('position', 'varchar(32)', 'caption=Позиция в по-големия том,input=hidden');
        
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
        $data->toolbar->addBtn('Бутон', array($mvc, 'Action'));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->id) {
            $form->setReadOnly('archive');
            $form->setReadOnly('type');
        } 
        
    }
    
    
    /**$form->rec->number = self::getNextNumber($archive,$type );
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
            $archive = $form->rec->archive;
            
            if (is_null($form->rec->number)) {
              $form->rec->number = self::getNextNumber($archive,$type );
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
        
        $a = docarch_Volumes::getQuery()->fetchAll();
        bp($a);
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
        $cond = "#archive = {$archive}";
        //$cond .= "#type = $type";
        $query->where($cond);
        $query->XPR('maxVolNumber', 'int', 'MAX(#number)');
        $number = $query->fetch()->maxVolNumber;
        ++$number;
        
        return $number;
    }
    
}
