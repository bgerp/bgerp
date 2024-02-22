<?php


/**
 * Ценови политики
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
 * @title     Ценови политики
 */
class price_ListVariations extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Вариации на ценови политики';


    /**
     * Заглавие
     */
    public $singleTitle = 'Вариация';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Modified, plg_Created, plg_SaveAndNew';


    /**
     * Детайла, на модела
     */
    public $details = 'price_ListRules';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId, variationId, validFrom, validUntil, repeatInterval, createdOn=Създадено->На, createdBy=Създадено->От, modifiedOn, modifiedBy';


    /**
     * Кой може да го промени?
     */
    public $canEdit = 'price,sales,ceo';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'price,sales,ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'price,sales,ceo';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'listId';


    /**
     * Кеш на текущите вариации
     */
    public static $variationCache = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('variationId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Вариация,mandatory,silent');
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime)', 'caption=В сила от,mandatory');
        $this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime,defaultTime=23:59:59)', 'caption=В сила до,mandatory');
        $this->FLD('repeatInterval', 'time', 'caption=Повторение,mandatory');

        $this->setDbIndex('validFrom');
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $variationOptions = price_Lists::getAccessibleOptions();
        $form->setOptions('variationId', $variationOptions);

        if (!isset($rec->id)) {
            $listRec = price_Lists::fetch($rec->listId);
            if (price_Lists::haveRightFor('add', (object)array('folderId' => $rec->folderId))) {
                $data->form->toolbar->addBtn('Нова вариация', array('price_Lists', 'add', 'folderId' => $listRec->folderId, 'variationOf' => $listRec->id, 'ret_url' => true), null, 'order=10.00015,ef_icon=img/16/page_white_star.png,title=Създаване на нова вариация на ценовата политика');
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            if (empty($rec->repeatInterval)) {
                $rec->repeatInterval = 0;
            }

            if ($rec->listId == $rec->variationId) {
                $form->setError('listId', 'Не може да изберете същата политика');
            }

            $secsBetween = dt::secsBetween($rec->validUntil, $rec->validFrom);
            if ($secsBetween >= $rec->repeatInterval) {
                $form->setError('repeatInterval', 'Интервалът за повторение трябва да е по-голям от този между датите');
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
        $row->variationId = price_Lists::getHyperlink($rec->variationId, true);
        $row->listId = price_Lists::getHyperlink($rec->listId, true);

        $variationState = price_Lists::fetchField($rec->variationId, 'state');
        if ($variationState == 'rejected') {
            $row->variationId = "<div class='state-{$variationState} document-handler'>{$row->variationId}</div>";
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
        $data->query->orderBy('validFrom', 'DESC');
    }


    /**
     * Коя е активната вариация за тази ЦП към посоченото време (кешира в хита)
     *
     * @param int $listId-            - ид на ЦП
     * @param datetime|null $datetime - към коя дата или null за СЕГА
     * @return int|null
     */
    public static function getActiveVariationId($listId, $datetime = null)
    {
        if (!array_key_exists($listId, static::$variationCache)) {
            $variationArr = static::getActiveVariations($listId, $datetime, 1);

            static::$variationCache[$listId] = countR($variationArr) ? $variationArr[key($variationArr)] : null;
        }

        return static::$variationCache[$listId];
    }


    /**
     * Всички активни вариации към посочената дата
     *
     * @param int|null $listId       - ид на ЦП или null за всички
     * @param datetime|int $datetime - към коя дата или null за СЕГА
     * @param int|null $limit        - ограничение или null за всички
     * @return array
     */
    public static function getActiveVariations($listId, $datetime = null, $limit = null)
    {
        $datetime = $datetime ?? dt::now();
        $datetime = strlen($datetime == 10) ? "{$datetime} 23:59:59" : $datetime;

        $res = array();
        $query = static::getQuery();
        $query->EXT('variationState', 'price_Lists', "externalName=state,externalKey=variationId");
        $query->XPR('diff', 'int', "TIME_TO_SEC(TIMEDIFF(#validUntil , #validFrom))");
        $query->XPR('validFromNew', 'datetime', "DATE_ADD(#validFrom, INTERVAL (COALESCE(#repeatInterval, 0) * (FLOOR(TIMESTAMPDIFF(SECOND, #validFrom, '{$datetime}') / COALESCE(#repeatInterval, 0)))) SECOND)");
        $query->XPR('validFromTo', 'datetime', "DATE_ADD((DATE_ADD(#validFrom, INTERVAL (COALESCE(#repeatInterval, 0) * (FLOOR(TIMESTAMPDIFF(SECOND, #validFrom, '{$datetime}') / COALESCE(#repeatInterval, 0)))) SECOND)), INTERVAL TIME_TO_SEC(TIMEDIFF(#validUntil , #validFrom)) SECOND)");
        $query->where("#validFrom <= '{$datetime}' && (#validFromNew <= '{$datetime}' && '{$datetime}' <= #validFromTo)");
        $query->where("#variationState != 'rejected'");
        if(!empty($listId)){
            $query->where("#listId = {$listId}");
        }

        $query->orderBy("diff,id", 'ASC');
        if(isset($limit)){
            $query->limit($limit);
        }
        while($rec = $query->fetch()){
            $res[$rec->id] = $rec->variationId;
        }

        return $res;
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->TabCaption = tr('Вариация');
        $activeVariations = static::getActiveVariations($data->masterId);
        foreach ($data->rows as $id => &$row){
            if(array_key_exists($id, $activeVariations)){
                $row->ROW_ATTR['class'] .= ' state-active';
                $row->variationId = ht::createHint($row->variationId, 'Активна е към момента');
            } else {
                $row->ROW_ATTR['class'] .= ' state-closed';
            }
        }
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $resTpl
     */
    public function renderDetail_($data)
    {
        if($data->hide) return new core_ET("");

        $tpl = parent::renderDetail_($data);

        return $tpl;
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        if($data->masterId == price_ListRules::PRICE_LIST_COST){
            $data->hide = true;
            return;
        }

        $res = parent::prepareDetail_($data);
        $count = countR($data->recs);
        $data->TabCaption = "Вариации|* ({$count})";
        $data->Tab = 'top';

        return $res;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'add' && isset($rec)){
            if($rec->listId == price_ListRules::PRICE_LIST_COST){
                $requiredRoles = 'no_one';
            }
        }
    }
}