<?php


/**
 * Правила за обновяване на себестойностите
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_Updates extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Правила за обновяване на себестойностите';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Правило за обновяване на себестойностите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name=Правило,sourceClass1,sourceClass2,sourceClass3,costAdd,costValue=Сб-ст,appliedOn,updateMode=Обновяване';
    
    /**
     * Кой може да го промени?
     */
    public $canWrite = 'priceMaster,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'priceMaster,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'priceMaster,ceo';
    
    
    /**
     * Кой може ръчно да обновява себестойностите?
     */
    public $canSaveprimecost = 'priceMaster,ceo';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('objectId', 'int', 'caption=Обект,silent,mandatory');
        $this->FLD('type', 'enum(category,product)', 'caption=Обект вид,input=hidden,silent,mandatory');
        
        $this->FLD('sourceClass1', 'class(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Източник 1, mandatory');
        $this->FLD('sourceClass2', 'class(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Източник 2');
        $this->FLD('sourceClass3', 'class(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Източник 3');
        
        $this->FLD('costAdd', 'percent(Min=0,max=1)', 'caption=Добавка');
        $this->FLD('costAddAmount', 'double(Min=0,decimals=2)', "caption=Добавка|* (|Сума|*),unit=|*BGN (|добавя се твърдо|*)");
        $this->FLD('minChange', 'percent(Min=0,max=1)', 'caption=Мин. промяна');
        
        $this->FLD('costValue', 'double', 'input=none,caption=Себестойност');
        $this->FLD('updateMode', 'enum(manual=Ръчно,now=При изчисление,nextDay=Следващия ден,nextWeek=Следващата седмица,nextMonth=Следващия месец)', 'caption=Обновяване');
        $this->FLD('appliedOn', 'datetime', 'input=none,caption=Последно');

        $this->setDbUnique('objectId,type');
    }
    
    
    /**
     * Връща наличните политики за избор
     * 
     * @return array $options
     */
    public static function getCostPoliciesOptions()
    {
        $options = array();
        $policies = core_Classes::getOptionsByInterface('price_CostPolicyIntf');
        foreach ($policies as $policyId => $policy){
            $options[$policyId] = cls::get($policy)->getName(true);
        }
        
        return $options;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('updateMode', 'now');
        
        Mode::push('text', 'plain');
        $form->setField("minChange", "placeholder=" . core_Type::getByName('percent')->toVerbal(price_Setup::get('MIN_CHANGE_UPDATE_PRIME_COST')));
        Mode::pop('text', 'plain');
        
        $policyOptions = self::getCostPoliciesOptions();
        $form->setOptions('sourceClass1', $policyOptions);
        $form->setOptions('sourceClass2', $policyOptions);
        $form->setOptions('sourceClass3', $policyOptions);
        
        if ($rec->type == 'category') {
            $form->setField('objectId', 'caption=Категория');
            $form->setOptions('objectId', array($rec->objectId => cat_Categories::getTitleById($rec->objectId)));
        } else {
            $form->setField('objectId', 'caption=Артикул');
            $form->setOptions('objectId', array($rec->objectId => cat_Products::getTitleById($rec->objectId)));
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        $objectClass = ($rec->type == 'category') ? cat_Categories::getClassId() : cat_Products::getClassId();
        $data->form->title = core_Detail::getEditTitle($objectClass, $rec->objectId, $mvc->singleTitle, $rec->id);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            $rec->sourceClass2 = (!$rec->sourceClass2) ? null : $rec->sourceClass2;
            $rec->sourceClass3 = (!$rec->sourceClass3) ? null : $rec->sourceClass3;
            
            $error = false;
            if ($rec->sourceClass1 == $rec->sourceClass2 || $rec->sourceClass1 == $rec->sourceClass3) {
                $error = true;
            }
            if (isset($rec->sourceClass2) && ($rec->sourceClass2 == $rec->sourceClass1 || $rec->sourceClass2 == $rec->sourceClass3)) {
                $error = true;
            }
            if (isset($rec->sourceClass3) && ($rec->sourceClass3 == $rec->sourceClass1 || $rec->sourceClass3 == $rec->sourceClass2)) {
                $error = true;
            }
            
            // Ако източниците се повтарят, сетваме грешка във формата
            if ($error === true) {
                $form->setError('sourceClass1,sourceClass2,sourceClass3', 'Източниците, не може да се повтарят');
            }
            
            // Попълваме скритите полета с данните от функционалните
            if (!$form->gotErrors()) {
                $rec->costValue = null;
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Показваме името на правилото
        $row->name = ($rec->type == 'category') ? cat_Categories::getHyperlink($rec->objectId, true) : cat_Products::getHyperlink($rec->objectId, true);
        
        if ($rec->type == 'product') {
            if (isset($fields['-list'])) {
                if ($rec->updateMode == 'manual') {
                    if (price_ListRules::haveRightFor('add')) {
                        $row->updateMode = ht::createBtn('Обнови', array('price_ListRules', 'add', 'type' => 'value', 'listId' => price_ListRules::PRICE_LIST_COST, 'price' => $rec->costValue, 'productId' => $rec->objectId, 'priority' => 1,'ret_url' => true), false, false, 'ef_icon=img/16/arrow_refresh.png,title=Ръчно обновяване на себестойностите');
                        $row->updateMode = "<span style='float:right'>{$row->updateMode}</span>";
                    }
                }
            }
        } else {
            if ($mvc->haveRightFor('saveprimecost', $rec)) {
                $row->updateMode = ht::createBtn('Обнови', array($mvc, 'saveprimecost', $rec->id, 'ret_url' => true), '|Сигурни ли сте, че искате да обновите себестойностите на всички артикули в категорията|*?', false, 'ef_icon=img/16/arrow_refresh.png,title=Ръчно обновяване на себестойностите');
                $row->updateMode = "<span style='float:right'>{$row->updateMode}</span>";
            }
        }
        
        foreach (array('sourceClass1', 'sourceClass2', 'sourceClass3') as $fld){
            if(!empty($rec->{$fld})){
                $row->{$fld} = cls::get($rec->{$fld})->getName(true);
            }
        }
        
        $row->ROW_ATTR['class'] = 'state-active';
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'saveprimecost' && isset($rec)) {
            if ($rec->updateMode != 'manual') {
                $requiredRoles = 'no_one';
            }
        }
        
        // Кой може да модифицира
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            
            // Трябва да има тип и ид на обект
            if (empty($rec->type) || empty($rec->objectId)) {
                $requiredRoles = 'no_one';
            } else {
                // Ако потребителя няма достъп до обекта, не може да модифицира
                $masterMvc = ($rec->type == 'product') ? 'cat_Products' : 'cat_Categories';
                if (!$masterMvc::haveRightFor('single', $rec->objectId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Дали можем да добавяме
        if ($action == 'add' && isset($rec->type, $rec->objectId)) {
            if ($mvc->fetchField("#type = '{$rec->type}' AND #objectId = {$rec->objectId}")) {
                $requiredRoles = 'no_one';
            } elseif ($rec->type == 'product') {
                $pRec = cat_Products::fetch($rec->objectId);
                
                // Ако добавяме правило за артикул трябва да е активен,публичен,складируем и купуваем или производим
                if ($pRec->state != 'active' || $pRec->canStore != 'yes' || $pRec->isPublic != 'yes' || !($pRec->canBuy = 'yes' || $pRec->canManifacture = 'yes')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Записва себестойността според правилото с ръчно обновяване
     */
    public function act_Saveprimecost()
    {
        $this->requireRightFor('saveprimecost');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('saveprimecost', $rec);
        
        // Записва себестойността
        $this->savePrimeCost($rec);
        
        // Редирект към списъчния изглед
        return followRetUrl(null, 'Себестойността е променена успешно');
    }
    
    
    /**
     * Намира на кои артикули да се обновят себестойностите
     *
     * @param stdClass $rec - записа
     *
     * @return array $products - артикулите
     */
    private function getProductsToUpdatePrimeCost($rec)
    {
        $products = array();
        
        // Ако е избран продукт, ще обновим само неговата себестойност
        if ($rec->type == 'product') {
            $products[$rec->objectId] = $rec->objectId;
        } else {
            
            // Ако е категория, всички артикули в папката на категорията
            $folderId = cat_Categories::fetchField($rec->objectId, 'folderId');
            $pQuery = cat_Products::getQuery();
            $pQuery->where("#folderId = {$folderId}");
            $pQuery->show('id');
            
            while ($pRec = $pQuery->fetch()) {
                if ($this->fetchField("#objectId = {$pRec->id} AND #type = 'product'")) {
                    continue;
                }
                
                $products[$pRec->id] = $pRec->id;
            }
        }
        
        // Връща намерените артикули
        return $products;
    }
    
    
    /**
     * Обновява всички себестойностти според записа
     *
     * @param stdClass $rec             - запис
     * @param bool     $saveInPriceList - искаме ли да запишем изчислената себестойност в 'Себестойности'
     *
     * @return void
     */
    private function savePrimeCost($rec, $saveInPriceList = true)
    {
        // На кои продукти ще обновяваме себестойностите
        $products = $this->getProductsToUpdatePrimeCost($rec);
        
        // Подготвяме датата от която ще е валиден записа
        $validFrom = $this->getValidFromDate($rec->updateMode);
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($validFrom);

        // За всеки артикул
        foreach ($products as $productId) {
            $pRec = cat_Products::fetch($productId, 'state,canStore,isPublic,canBuy,canManifacture');

            // Обновяване на себестойностите само ако артикула е складируем, публичен, активен, купуваем или производим
            if ($pRec->state != 'active' || $pRec->canStore != 'yes' || $pRec->isPublic != 'yes' || !($pRec->canBuy == 'yes' || $pRec->canManifacture == 'yes')) {
                continue;
            }
            
            // Опит за изчисление на себестойността според източниците
            $primeCost = $this->getPrimeCost($productId, $rec->sourceClass1, $rec->sourceClass2, $rec->sourceClass3, $rec->costAdd);

            // Намира се старата му себестойност (ако има)
            $primeCost = round($primeCost, 5);

            // Ако има изчислена ненулева себестойност
            if ($primeCost > 0) {

                // Добавяме надценката, ако има
                $primeCost = $primeCost * (1 + $rec->costAdd);
                if(!empty($rec->costAddAmount)){
                    $primeCost += $rec->costAddAmount;
                }
               
                $minChange = (isset($rec->minChange)) ? $rec->minChange : price_Setup::get('MIN_CHANGE_UPDATE_PRIME_COST');
                $oldPrimeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId);

                // Ако старата себестойност е различна от новата
                if (empty($oldPrimeCost) || abs(round($primeCost / $oldPrimeCost - 1, 2)) >= $minChange) {
                    
                    // Кешираме себестойността, ако правилото не е за категория
                    if ($rec->type != 'category') {
                        $rec->costValue = $primeCost;
                        self::save($rec, 'costValue');
                    }

                    // Ако е указано, обновява се в ценовите политики (ако цената не е 0)
                    if ($saveInPriceList === true) {

                        // Записваме новата себестойност на продукта
                        price_ListRules::savePrimeCost($productId, $primeCost, $validFrom, $baseCurrencyCode);
                    }
                }
            }
        }
    }
    
    
    /**
     * От коя дата да е валиден записа
     *
     * @param string $updateMode
     *
     * @return datetime $validFrom
     */
    private function getValidFromDate($updateMode)
    {
        // Според избрания начин на обновление
        switch ($updateMode) {
            case 'manual':
            case 'now':
                
                // Влиза в сила веднага
                $date = dt::now();
                break;
            case 'nextDay':
                
                // Влиза в сила от 00:00 на следващия ден
                $date = dt::addDays(1, dt::today());

                break;
            case 'nextWeek':
                
                // Влиза в сила от 00:00 в следващия понеделник
                $date = dt::timestamp2Mysql(strtotime('next Monday'));
                break;
            case 'nextMonth':
                
                // Влиза в сила от 01 на следващия месец
                $date = dt::mysql2verbal(dt::addMonths(1, dt::today()), 'Y-m-01 00:00:00');
                break;
        }
        
        // Връща датата, от която да е валиден записа
        return $date;
    }
    
    
    /**
     * Намира себестойността на един артикул, според зададените приоритети
     *
     * @param int         $productId    - ид на артикул
     * @param string      $sourceClass1 - първи източник
     * @param string|NULL $sourceClass2 - втори източник
     * @param string|NULL $sourceClass3 - трети източник
     * @param float       $costAdd      - процент надценка
     *
     * @return float|FALSE $price - намерената себестойност или FALSE ако няма
     */
    private function getPrimeCost($productId, $sourceClass1, $sourceClass2 = null, $sourceClass3 = null, $costAdd = null)
    {
        $sources = array($sourceClass1, $sourceClass2, $sourceClass3);
        foreach ($sources as $source) {
            if (isset($source)) {
                $price = price_ProductCosts::getPrice($productId, $source);
                if (isset($price)) {
                    
                    return $price;
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    public function act_Recalc()
    {
        expect(haveRole('debug'));
        $this->cron_Updateprimecosts();
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    public function cron_Updateprimecosts()
    {
        core_App::setTimeLimit(1200);
       
        $cronRec = core_Cron::getRecForSystemId("Update primecosts"); 
        $cronPeriod = $cronRec->period * 60;
        $datetime = dt::addSecs(-1 * $cronPeriod);
        
        // Обновяване на себестойностите на всички засегнати артикули от предишното време на обновяване
        price_ProductCosts::saveCalcedCosts($datetime);
        $updateRules = array();

        // Взимаме всички записи
        $now = dt::now();
        $query = $this->getQuery();

        // За всеки
        while ($rec = $query->fetch()) {
            try {
                // Ако не може да се изпълни, пропуска се
                if (!$this->canBeApplied($rec, $now)) continue;

                // Ще обновяваме себестойностите в модела, освен за записите на които ръчно ще трябва да се обнови
                $saveInPriceList = ($rec->updateMode == 'manual') ? false : true;
                $updateRules[$rec->id] = $rec;

                // Изчисляваме и записваме себестойностите
                $this->savePrimeCost($rec, $saveInPriceList);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }

        // Обновяване на времето на изпълнение на последните записи
        if(countR($updateRules)){
            $this->saveArray($updateRules, 'id,appliedOn');
        }
    }
    
    
    /**
     * Дали времето за активиране на условието може да се изпълни
     *
     * @param stdClass $rec  - запис
     * @param datetime $date - към коя дата сме
     *
     * @return bool $res  - може или не може да се изпълни условието
     */
    private function canBeApplied(&$rec, $date)
    {
        $res = false;
        $appliedOn = null;

        switch ($rec->updateMode) {
            case 'manual':
            case 'now':
                $res = true;
                $appliedOn = dt::now();
                break;
            case 'nextDay':
                
                // Дали часа от датата е 23:00
                $normNow = dt::mysql2verbal($date, 'd.m.y');
                $lastAppliedOn = ($rec->appliedOn) ? dt::mysql2verbal($rec->appliedOn, 'd.m.y') : null;
                if($lastAppliedOn != $normNow){
                    $hour = dt::mysql2verbal($date, 'H');
                    if($hour == '23'){
                        $res = true;
                    }
                }

                break;
            case 'nextWeek':
                
                // Дали датата е петък 23:00 часа
                $normNow = dt::mysql2verbal($date, 'D:H', 'en');
                $normDate = dt::mysql2verbal($date, 'd.m.y D:H');
                $normApplied = dt::mysql2verbal($rec->appliedOn, 'd.m.y D:H');

                $res = false;
                if($normApplied != $normDate){
                    if($normNow == 'Fri:23'){
                        $res = true;
                    }
                }

                break;
            case 'nextMonth':
                
                // Дали датата е 5 дена преди края на текущия месец в 23:00 часа
                $lastDayOfMonth = dt::getLastDayOfMonth($date);
                $dateToCompare = dt::addDays(-5, $lastDayOfMonth);
                $dateToCompare = dt::addSecs(60 * 60 * 23, $dateToCompare);
                $dateToCompare = dt::mysql2verbal($dateToCompare, 'd.m');
                $lastAppliedOn = ($rec->appliedOn) ? dt::mysql2verbal($rec->appliedOn, 'd.m') : null;

                // Ако е станала датата на изпълнение
                if($lastAppliedOn != $dateToCompare){
                    $normNow = dt::mysql2verbal($date, 'd.m');
                    if($normNow == $dateToCompare){
                        $res = true;
                        $rec->appliedOn = dt::mysql2verbal(null, 'Y-m-d 23:00:00');
                    }
                }

                break;
        }

        // Ако ще се изпълни правилото на коя дата ще се приложи
        if($res){
            $rec->appliedOn = ($appliedOn) ? $appliedOn : dt::mysql2verbal(null, 'Y-m-d 23:00:00');
        }

        // Връщаме резултата
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'recalc'), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
        }

        if (haveRole('admin')) {
            $cronRec = core_Cron::getRecForSystemId('Update primecosts');
            $url = array('core_Cron', 'ProcessRun', str::addHash($cronRec->id), 'forced' => 'yes');
            $data->toolbar->addBtn('Обновяване', $url, 'title=Обновяване на себестойностите,ef_icon=img/16/arrow_refresh.png,target=cronjob');
        }
    }
    
    
    /**
     * Връща шаблон с правилото за обновяване
     * 
     * @param stdClass $rec
     * 
     * @return core_ET $tpl
     */
    public static function getUpdateTpl($rec)
    {
        $uRow = price_Updates::recToVerbal($rec);
        $arr = array('manual' => tr('Ръчно'), 'nextDay' => tr('Дневно'), 'nextWeek' => tr('Седмично'), 'nextMonth' => tr('Месечно'), 'now' => tr('При изчисление'));
        
        $fromCategoryStr = 'От категорията|* ';
        if(!$rec->_fromCategory){
            $fromCategoryStr = '';
            core_RowToolbar::createIfNotExists($uRow->_rowTools);
            $tools = $uRow->_rowTools->renderHtml(2);
        }
        
        $type = '';
        $tpl = new core_ET(tr("{$fromCategoryStr}|*<b>[#updateMode#]</b> |обновяване на себестойността, последователно по|* [#type#]  <!--ET_BEGIN surcharge-->|с надценка|* <b>[#surcharge#]</b><!--ET_END surcharge-->[#tools#]"));
        
        foreach (array($uRow->sourceClass1, $uRow->sourceClass2, $uRow->sourceClass3) as $cost) {
            if (isset($cost)) {
                $type .= '<b>' . $cost . '</b>, ';
            }
        }
        
        $type = rtrim($type, ', ');
        $tpl->append($arr[$rec->updateMode], 'updateMode');
        $tpl->append($tools, 'tools');
        $surcharge = $uRow->costAdd;
        if(!empty($rec->costAddAmount)){
            $surcharge .= ((!empty($surcharge)) ? tr('|* |и|* ') : '') . $uRow->costAddAmount . " BGN";
        }
        if(!empty($surcharge)){
            $tpl->append($surcharge, 'surcharge');
        }
        
        $tpl->append($type, 'type');
        
        if($rec->_fromCategory){
            $tpl->prepend("<span class='quiet'>");
            $tpl->append("</span>");
        }
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if ($rec->updateMode == 'manual') {
            $mvc->savePrimeCost($rec);
        }
    }
}
