<?php


/**
 * Клас 'voucher_Types'
 *
 * Мениджър за Типове ваучери
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class voucher_Types extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Типове ваучери';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Тип ваучер';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, voucher_Wrapper, plg_State2, label_plg_Print';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, voucher';


    /**
     * Кой има право да разглежда?
     */
    public $canSingle = 'ceo, voucher';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, voucher';


    /**
     * Кой има право да разглежда?
     */
    public $canList = 'ceo, voucher';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, voucher';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,count=Карти,referrer,priceListId,state,createdOn,createdBy';


    /**
     * Интерфейсни методи
     */
    public $interfaces = 'label_SequenceIntf=voucher_interface_TypeLabelSource';


    /**
     * Детайла, на модела
     */
    public $details = 'voucher_Cards';


    /**
     * Работен кеш
     */
    protected $generateOnShutdown = array();


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име,mandatory');
        $this->FLD('referrer', 'enum(,no=Без,yes=Да)', 'caption=Препоръчител,mandatory');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика');
        $this->FNC('count', 'int', 'single=none');

        $this->setdbUnique('name');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $parentOptions = price_Lists::getAccessibleOptions();
        $form->setOptions('priceListId', array('' => '') + $parentOptions);

        if(empty($rec->id)){
            $form->FLD('createCount', 'int(Min=1)', 'caption=Брой,mandatory,after=typeId');
        } else {
            if(voucher_Cards::count("#typeId = {$rec->id}")){
                $form->setReadOnly('referrer');
            }
        }
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $mvc->generateOnShutdown[$rec->id] = $rec;
    }


    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if(countR($mvc->generateOnShutdown)){
            foreach ($mvc->generateOnShutdown as $rec){
                voucher_Cards::generateCards($rec);
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(isset($rec->priceListId)){
            $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
        }

        $row->count = core_Type::getByName('int')->toVerbal(voucher_Cards::count("#typeId = {$rec->id}"));
    }


    /**
     * Заглавие на източника на етикета
     */
    public function getLabelSourceLink($id)
    {
        return static::fetchRec($id)->name;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)){
            if(voucher_Cards::count("#typeId = {$rec->id}")){
                $requiredRoles = 'no_one';
            }
        }
    }
}
