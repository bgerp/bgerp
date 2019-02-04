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
    public $canDelete = 'ceo,docarchMaster';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        //Наименование на архива
        $this->FLD('name', 'varchar(32)', 'caption=Наименование');
        
        //Видове томове/обеми/контейнери за съхранение
        $this->FLD('volType', 'set(folder=Папка,box=Кутия, case=Кашон, pallet=Палет, warehouse=Склад)', 'caption=Видове томове');
        
        //Какъв тип документи ще се съхраняват в този архив
        $this->FLD('documents', 'keylist(mvc=core_Classes, select=title,allowEmpty)', 'caption=Документи,placeholder=Всички');
        
        //Кой може да добавя документи в този архив
        $this->FLD('sharedUsers', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Потребители,mandatory');
        
        
        //Срок за съхранение
        $this->FLD('storageTime', 'time(suggestions=1 година|2 години|3 години|4 години|5 години|10 години)', 'caption=Срок,mandatory');
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
        
        
        $docClasses = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        $docClasses = array_keys($docClasses);
        
        $temp = array();
        
        foreach ($docClasses as $k => $v) {
            $temp[$v] = core_Classes::getTitleById($v);
        }
        
        $docClasses = $temp;
        
        $form->setSuggestions('documents', $docClasses);
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
        // Прави запис в модела на движенията
        $className = get_class();
        $mRec = (object) array('type' => 'creating',
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
        
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
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
     * @return string
     */
    public function act_Action()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('admin');
        $cRec = new stdClass();
        $form = cls::get('core_Form');
        $form->title = 'Форма тест|* Ala Bala|*';
        $form->FNC('test', 'varchar', 'caption=Тест, mandatory, input');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        $cRec = $form->input();
        
        if ($form->isSubmitted()) {
            
            return new Redirect(getRetUrl());
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
}
