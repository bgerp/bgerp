<?php


/**
 * Клас 'voucher_Types'
 *
 * Мениджър за транспортни средства
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
class voucher_Types extends core_Manager
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
    public $loadList = 'plg_RowTools2, plg_Created, voucher_Wrapper, plg_State2, label_plg_Print';


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
    public $listFields = 'name,referrer,priceListId,state,createdOn,createdBy';


    /**
     * Интерфейсни методи
     */
    public $interfaces = 'label_SequenceIntf=voucher_interface_TypeLabelSource';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име,mandatory');
        $this->FLD('referrer', 'enum(no=Без,yes=Да)', 'caption=Препоръчител,mandatory,notNull,value=no');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика');

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

        $parentOptions = price_Lists::getAccessibleOptions();
        $form->setOptions('priceListId', array('' => '') + $parentOptions);
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

        $row->name = ht::createLink($row->name, array('voucher_Cards', 'list', 'typeId' => $rec->id));
    }


    /**
     * Заглавие на източника на етикета
     */
    public function getLabelSourceLink($id)
    {
        return static::fetchRec($id)->name;
    }
}
