<?php


/**
 * Ценови политики Общи отстъпки към
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Общи отстъпки
 */
class price_ListBasicDiscounts extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Общи отстъпки към ценови политики';


    /**
     * Заглавие
     */
    public $singleTitle = 'Обща отстъпка към ценова политика';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Modified, plg_Created, plg_SaveAndNew, plg_AlignDecimals2';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId,amountFrom,amountTo,discountPercent,discountAmount,currencyId=Валута,modifiedOn,modifiedBy';


    /**
     * Кой може да го промени?
     */
    public $canEdit = 'debug';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'listId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');

        $this->FLD('amountFrom', 'double(min=0,minDecimals=2)', 'caption=Сума->От');
        $this->FLD('amountTo', 'double(Min=0,minDecimals=2)', 'caption=Сума->До');
        $this->FLD('discountPercent', 'percent', 'caption=Отстъпка->Процент');
        $this->FLD('discountAmount', 'double(minDecimals=2)', 'caption=Отстъпка->Твърда');
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $listRec = price_Lists::fetch($rec->listId);
        $form->setField('amountFrom', "unit={$listRec->currency}");
        $form->setField('amountTo', "unit={$listRec->currency}");
        $form->setField('discountAmount', "unit={$listRec->currency}");
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            if (empty($rec->discountPercent) && empty($rec->discountAmount)) {
                $form->setError('discountPercent,discountAmount', 'Трябва поне едно от полетата да е попълнено');
            }

            if(isset($rec->amountFrom)){
                if(static::fetch("#amountTo >= {$rec->amountFrom} AND #id != '{$rec->id}' AND #listId = {$rec->listId}")){
                    $form->setError('amountFrom', 'Сума:От вече присъства в друг интервал1');
                }
            }

            if(isset($rec->amountTo)){
                if(static::fetch("#amountFrom >= {$rec->amountTo} AND #id != '{$rec->id}' AND #listId = {$rec->listId}")){
                    $form->setError('amountTo', 'Сума:До вече присъства в друг интервал2');
                }
            }

            if(!$form->gotErrors()){
                if(empty($rec->amountFrom)){
                    $rec->amountFrom = 0;
                }
            }
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FLD('currencyId', 'varchar', 'smartCenter');
        foreach ($data->rows as $id => $row){
            $rec = $data->recs[$id];
            $listRec = price_Lists::fetch($rec->listId);
            $row->listId = price_Lists::getHyperlink($rec->listId, true);
            $row->currencyId = $listRec->currency;
            if(empty($rec->amountTo)){
                $row->amountTo = "<i style='color:blue'>" . tr('Безкрай') . "</i class>";
            }
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        if (isset($data->masterMvc)) {
            unset($data->listFields['listId']);
        }
        $data->query->orderBy('listId', 'ASC');
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $discountClass = $data->masterData->rec->discountClass;

        if(empty($discountClass) || !(cls::get($discountClass) instanceof price_interface_BasicDiscountImpl)){
            $data->hide = true;
            return null;
        }

        $res = parent::prepareDetail_($data);
        $count = countR($data->recs);
        $data->TabCaption = "Общи отстъпки|* ({$count})";
        $data->Tab = 'top';

        return $res;
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        // Ако не се иска да се показва детайла - да се скрива
        if($data->hide) return null;

        return parent::renderDetail_($data);
    }
}