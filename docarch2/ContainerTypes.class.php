<?php
/**
 * Мениджър Видове контейнери
 *
 *
 * @category  bgerp
 * @package   docarch2
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Видове контейнери
 */
class docarch2_ContainerTypes extends core_Master
{
    public $title = 'Видове контейнери';
    
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2,plg_Modified,docarch2_Wrapper';
    
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
        //Наименование на типа контейнер
        $this->FLD('name', 'varchar(32)', 'caption=Наименование');

        //Какъв тип тип контейнери може да включва. Ако това поле е null, значи може да включва само документи.
        $this->FLD('canInclude', 'key(mvc=docarch2_ContainerTypes,allowEmpty)', 'caption=Какъв тип контейнери включва,placeholder=Само документи');

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
        //$rec->isCreated = $rec->id ? true : false;
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

}
