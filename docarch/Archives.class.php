<?php
/**
 * Мениджър Архиви
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
 * @title     Архиви
 */
class docarch_Archives extends core_Master
{
    public $title = 'Архив';
    
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2,plg_Modified,docarch_Wrapper';
    
    public $listFields = 'name,volType,documents,createdOn=Създаден,modifiedOn=Модифициране';
    
    
    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo,docarchMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,docarch,docarchMaster';
    
    
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
    public $canEdit = 'ceo,docarchMaster';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,docarchMaster';
    
    
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
    public $canDelete = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        //Наименование на архива
        $this->FLD('name', 'varchar(32)', 'caption=Наименование');
        
        //Видове томове/обеми/контейнери за съхранение
        $this->FLD('volType', 'set(folder=Папка,box=Кутия, case=Кашон, pallet=Палет, warehouse=Склад)', 'caption=Видове томове,maxColumns=3');
        
        //Какъв тип документи ще се съхраняват в този архив
        $this->FLD('documents', 'keylist(mvc=core_Classes, select=title,allowEmpty)', 'caption=Документи,placeholder=Всички');
        
        //Срок за съхранение
        $this->FLD('storageTime', 'time(suggestions=1 година|2 години|3 години|4 години|5 години|10 години)', 'caption=Срок');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->id) {
            $rec->typeArr = explode(',', $rec->volType);
        }
        
        $docClasses = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        $docClasses = array_keys($docClasses);
        
        $temp = array();
        
        foreach ($docClasses as  $v) {
            $temp[$v] = core_Classes::getTitleById($v);
        }
        
        $docClasses = $temp;
        
        $form->setSuggestions('documents', $docClasses);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            $typesArr = explode(',', $rec->volType);
            
            if ($rec->id) {
                foreach ($rec->typeArr as $v) {
                if ((!in_array($v, $typesArr)) && ($rec->typeArr[0] != '')) {
                        $form->setError('volType', 'Не може да се премахват дефинирани типове');
                    }
                }
            }
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
        $rec->isCreated = $rec->id ? true : false;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Прави запис в модела на движенията
        
        $type = $rec->isCreated ? 'edit' : 'creating';
        
        $className = get_class();
        $mRec = (object) array('type' => $type,
            'position' => $rec->id.'|'.$className,
        );
        
        docarch_Movements::save($mRec);
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // $data->toolbar->addBtn('Бутон', array($mvc,'Action','ret_url' => true));
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // $rec = &$data->rec;
    }
    
    
    /**
     * Най-малкия дефиниран тип за архива
     *
     * @param int $id -id на архива
     *
     * @return string
     */
    public static function minDefType($id)
    {
        $typesArr = arr::make(self::fetch($id)->volType, false);
        
        $minDefType = $typesArr[0];
        
        return $minDefType;
    }
    
    
    /**
     * Взема името на типа на архива
     *
     * @param string $type -ключа на името на типа
     *
     * @return string
     */
    public static function getArchiveTypeName($type)
    {
        switch ($type) {
            
            case 'folder':$typeName = 'Папка'; break;
            
            case 'box':$typeName = 'Кутия'; break;
            
            case 'case':$typeName = 'Кашон'; break;
            
            case 'pallet':$typeName = 'Палет'; break;
            
            case 'warehouse':$typeName = 'Склад'; break;
        
        }
        
        return $typeName;
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
        if ($rec->id && $action == 'edit') {
            if (($rec->state == 'closed')) {
                $requiredRoles = 'no_one' ;
            }
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
        
        $text = 'Това е съобщение за изтекъл срок';
        $msg = new core_ET($text);
        
        $url = array(
            'docarch_Volumes',
            'single',
            109
        );
        
        $msg = $msg->getContent();
        
        
        bgerp_Notifications::add($msg, $url, 1219);
    }
}
