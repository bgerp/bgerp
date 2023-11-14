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
    public $title = 'Общи отстъпки на ценови политики';


    /**
     * Заглавие
     */
    public $singleTitle = 'Обща отстъпка на ценова политика';


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

        $vatUnit = ($listRec->vat == 'yes') ? tr('с ДДС') : tr('без ДДС');
        $form->setField('amountFrom', array('unit' => "|*{$listRec->currency}, {$vatUnit}"));
        $form->setField('amountTo', array('unit' => "|*{$listRec->currency},  {$vatUnit}"));
        $form->setField('discountAmount', array('unit' => "|*{$listRec->currency},  {$vatUnit}"));
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

            $from = $rec->amountFrom ?? 0;
            $to = $rec->amountTo ?? 999999999999;
            if($from >= $to){
                $form->setError('amountFrom,amountTo', 'Сума от трябва да е по-малка от сума до|*');
            }

            if(!$form->gotErrors()){
                $query = static::getQuery();
                $query->XPR('amountToCalc', 'int', "COALESCE(#amountTo, 999999999999)");
                $query->where("#id != '{$rec->id}' AND #listId = {$rec->listId}");
                $query->where("!('{$from}' > #amountToCalc || '{$to}' < #amountFrom)");
                if($query->count()){
                    $form->setError('amountFrom,amountTo', 'Посоченият интервал се засича с вече зададен|*!');
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
                $row->amountTo = "<i style='color:blue'>" . tr('Без лимит') . "</i class>";
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

        $vatUnit = ($data->masterData->rec->vat == 'yes') ? tr('с ДДС') : tr('без ДДС');
        $data->listFields['amountFrom'] = "Сума|* <small>($vatUnit)</small>->От";
        $data->listFields['amountTo'] = "Сума|* <small>($vatUnit)</small>->До";
        $data->listFields['discountAmount'] = "Отстъпка->Твърда|* <small>($vatUnit)</small>";

        return parent::renderDetail_($data);
    }
}