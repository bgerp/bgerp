<?php


/**
 * Мениджър за архивиране
 *
 *
 * @category  bgerp
 * @package   incoming
 *
 * @author   Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class incoming_Archiving extends core_Master
{
    /**
     * Заглавие на модела
     */
    public $title = 'Архивиране';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Архивиране';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, doc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'ceo, doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    public $canDoc = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name,archivUnit,responsiblePerson';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_Modified,incoming_Wrapper,plg_Rowtools2';
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('unitNumber', 'int', 'caption=Единица No,mandatory,input');
        $this->FLD('typeOfArhive', 'key(mvc=incoming_TypeOfArchive,select=name,allowEmpty)', 'caption=Архив,mandatory');
        
        
        $this->setDbUnique('unitNumber');
    }
    
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Архивиране', array($mvc, 'Archiving'));
    }
    
    
    /**
     *Архивиране
     *
     * @return core_Et $tpl
     */
    public function act_Archiving()
    {
        $query = incoming_Archiving::getQuery();
        
        
        $form = cls::get('core_Form');
        
        // Prepare form
        $form->title = 'Архивиране';
        
        
        $form->FNC('some', 'varchar', 'caption=Нещо си, mandatory, input');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        
        // END Prepare form
        
        $cRec = $form->input();
        
        return $this->renderWrapping($form->renderHtml());
    }
}
