<?php


/**
 * Документ за Корекция на стойности
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_ValueCorrections extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=acc_transaction_ValueCorrection,hr_IndicatorsSourceIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Корекции на стойности';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, plg_Sorting,acc_plg_Contable,doc_DocumentPlg, plg_Printing,acc_plg_DocumentSummary,plg_Search,doc_plg_HidePrices';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '6.9|Счетоводни';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, amount, currencyId, dealOriginId=Сделка->Основна, correspondingDealOriginId=Сделка->Кореспондент, state, createdOn, createdBy';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, acc';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Корекция на стойности';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Vcr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutValueCorrections.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId,notes,action';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('amount', 'double(decimals=2,Min=0,maxAllowedDecimals=2)', 'caption=Сума,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,removeAndRefreshForm=rate,silent');
        $this->FLD('rate', 'double(decimals=5)', 'caption=Курс');
        
        $this->FLD('action', 'enum(increase=Увеличаване,decrease=Намаляване)', 'caption=Корекция,notNull,value=increase,maxRadio=2');
        $this->FLD('allocateBy', 'enum(value=Стойност,quantity=Количество,weight=Тегло,volume=Обем)', 'caption=Разпределяне по,notNull,value=value');
        $this->FLD('correspondingDealOriginId', 'int', 'input=hidden,tdClass=leftColImportant');
        
        // Кеш поле за цялата информация на възможните артикули
        $this->FLD('productsData', 'blob(serialize, compress)', 'input=none');
        $this->FLD('notes', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
        
        // Поставяне на уникален индекс
        $this->setDbIndex('correspondingDealOriginId');
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
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($firstDoc->fetchField('containerId') != $rec->correspondingDealOriginId) {
            try{
                $row->correspondingDealOriginId = doc_Containers::getDocument($rec->correspondingDealOriginId)->getLink(false);
            } catch(core_exception_Expect $e){
                $row->correspondingDealOriginId = "<span class='red'>" . tr('Проблем при показването') . "</span>";
            }
        } else {
            unset($row->correspondingDealOriginId);
        }
        
        $row->title = $mvc->getLink($rec->id, 0);
        $row->dealOriginId = $firstDoc->getLink(0);
        $row->baseCurrencyCode = $rec->currencyId;
        
        $chargeVat = $firstDoc->fetchField('chargeVat');
        
        $rec->amount /= $rec->rate;
        $row->realAmount = $mvc->getFieldType('amount')->toVerbal($rec->amount);
        
        if ($chargeVat == 'yes' || $chargeVat == 'separate') {
            $amount = $rec->amount * (1 + acc_Periods::fetchByDate($rec->valior)->vatRate);
            $row->vatType = tr('с ДДС');
        } else {
            $row->vatType = tr('без ДДС');
            $amount = $rec->amount;
        }
        
        $row->amount = $mvc->getFieldType('amount')->toVerbal($amount);
        
        if ($rec->amount < 0) {
            $row->amount = "<span class='red'>{$row->amount}</span>";
        } elseif ($rec->action == 'decrease') {
            $row->amount = "<span class='red'>-{$row->amount}</span>";
        }
    }
    
    
    /**
     * Връща вербалното представяне за артикула с коригирана стойност
     */
    private function getVerbalDetail($pRec, $rec)
    {
        $row = new stdClass();
        $row->name = cat_Products::getShortHyperlink($pRec->productId);
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        $pRec->amount /= $rec->rate;
        $pRec->allocated /= $rec->rate;
        
        foreach (array('amount', 'allocated', 'quantity', 'allocated') as $fld) {
            if (isset($pRec->{$fld})) {
                $row->{$fld} = $Double->toVerbal($pRec->{$fld});
            }
        }
        
        if (isset($pRec->transportWeight)) {
            $row->transportWeight = cls::get('cat_type_Weight')->toVerbal($pRec->transportWeight);
        }
        
        if (isset($pRec->transportVolume)) {
            $row->transportVolume = cls::get('cat_type_Volume')->toVerbal($pRec->transportVolume);
        }
        
        if ($pRec->allocated < 0) {
            $row->allocated = "<span class='red'>{$row->allocated}</span>";
        } elseif ($rec->action == 'decrease') {
            $row->allocated = "<span class='red'>-{$row->allocated}</span>";
        } else {
            $row->allocated = "<span class='green'>+{$row->allocated}</span>";
        }
        
        $measureShort = cat_UoM::getShortName(cat_Products::fetchField($pRec->productId, 'measureId'));
        $row->quantity = "{$row->quantity} {$measureShort}";
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (!countR($data->rec->productsData)) {
            
            return;
        }
        
        // Подговяме кешираната информация за артикулите във вербален вид
        $productRows = array();
        $count = 1;
        foreach ($data->rec->productsData as $pRec) {
            $row = $mvc->getVerbalDetail($pRec, $data->rec);
            $row->count = cls::get('type_Int')->toVerbal($count);
            $productRows[] = $row;
            $count++;
        }
        
        $listFields = arr::make("count=№,name=Артикул,amount=Сума,allocated=|Разпределени|* ({$data->row->baseCurrencyCode}) |без ДДС|*", true);
        
        // Взависимост от признака на разпределяне, показваме колоната възоснова на която е разпределено
        switch ($data->rec->allocateBy) {
            case 'weight':
                arr::placeInAssocArray($listFields, array('transportWeight' => 'Тегло'), 'allocated');
                break;
            case 'volume':
                arr::placeInAssocArray($listFields, array('transportVolume' => 'Обем'), 'allocated');
                break;
            case 'quantity':
                arr::placeInAssocArray($listFields, array('quantity' => 'К-во'), 'allocated');
                break;
        }
        
        // Показваме таблица със артикулите и разпределените им суми
        $fs = new core_FieldSet();
        $fs->FNC('amount', 'double');
        $fs->FNC('allocated', 'double');
        $fs->FNC('transportWeight', 'double');
        $fs->FNC('transportVolume', 'double');
        $fs->FNC('quantity', 'double');
        $table = cls::get('core_TableView', array('mvc' => $fs));
        $details = $table->get($productRows, $listFields);
        
        // Показваме под таблицата обобщената информация
        $colspan = countR($listFields) - 1;
        $lastRowTpl = new core_ET(tr("|*<tr style='background-color: #eee'><td colspan='[#colspan#]' style='text-align:right'>|Общо|*</td><td style='text-align:right'><b>[#amount#]</b></td></tr>"));
        $lastRowTpl->replace($colspan, 'colspan');
        
        if ($data->rec->amount < 0) {
            $data->row->realAmount = "<span class='red'>{$data->row->realAmount}</span>";
        } elseif ($data->rec->action == 'decrease') {
            $data->row->realAmount = "<span class='red'>-{$data->row->realAmount}</span>";
        }
        
        $lastRowTpl->replace($data->row->realAmount, 'amount');
        $details->append($lastRowTpl, 'ROW_AFTER');
        
        $tpl->append($details, 'PRODUCTS_TABLE');
    }
    
    
    /**
     * Добавя полета към формата за избор на артикули
     *
     * @param core_Form            $form
     * @param core_ObjectReference $origin
     * @param string               $dataField
     */
    public static function addProductsFromOriginToForm(&$form, core_ObjectReference $origin, $Master, $dataField = 'productsData')
    {
        // Запомняне на всички експедирани/заскладени артикули от оридижина
        $products = $origin->getCorrectableProducts($Master);
        $form->allProducts = $products;

        if (countR($products)) {
            
            // Добавяме всички възможни артикули като опции в SET поле
            $nProducts = array();
            foreach ($products as $p) {
                $measureId = cat_Products::getProductInfo($p->productId)->productRec->measureId;
                $suffix = $p->quantity . ' ' . cat_UoM::getShortName($measureId);
                $nProducts[$p->productId] = "{$p->name} / {$suffix}";
            }
            
            $form->FNC('chosenProducts', 'set', 'caption=Корекция на стойността на->Артикули,mandatory,input,columns=1');
            $form->setSuggestions('chosenProducts', $nProducts);
            
            // Ако има запис остават само тези, които са в кешираното блоб поле
            if ($form->rec->id && $form->rec->{$dataField}) {
                $products = array_intersect_key($products, $form->rec->{$dataField});
            }
            
            // Задаване на избран дефолт, така двете полета се синхронизирват
            $defaults = cls::get('type_Set')->fromVerbal($products);
            $form->setDefault('chosenProducts', $defaults);
            $form->input('chosenProducts');
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('valior', dt::today());
        
        // Намираме ориджина и подготвяме опциите за избор на папки на контрагенти
        expect($firstDoc = doc_Threads::getFirstDocument($rec->threadId));
        self::addProductsFromOriginToForm($form, $firstDoc, $mvc);
        
        $chargeVat = $firstDoc->fetchField('chargeVat');
        $form->setDefault('currencyId', $firstDoc->fetchField('currencyId'));
        
        // Ако избраната валута е основната за периода, не се показва курса
        if($rec->currencyId == acc_Periods::getBaseCurrencyCode()){
            $form->setField('rate', "input=hidden");
        }
        
        // Курса е винаги актуалния
        if(isset($rec->currencyId)){
            $form->setDefault('rate', currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null));
        }
        
        if (isset($rec->id, $rec->rate)) {
            $rec->amount /= $rec->rate;
            $rec->amount = round($rec->amount, 2);
        }
        
        if ($chargeVat == 'yes' || $chargeVat == 'separate') {
            if ($form->rec->amount) {
                $form->rec->amount = $form->rec->amount * (1 + acc_Periods::fetchByDate($rec->valior)->vatRate);
                $form->rec->amount = round($form->rec->amount, 2);
            }
            $form->setField('amount', 'unit=с ДДС');
        } else {
            $form->setField('amount', 'unit=без ДДС');
        }
        
        $data->form->origin = $firstDoc;
        $data->form->chargeVat = $chargeVat;
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
        
        // Ако е събмитната формата
        if ($form->isSubmitted()) {
            if (!isset($rec->rate)) {
                // Изчисляваме курса към основната валута ако не е дефиниран
                $rec->rate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null);
                if (!$rec->rate) {
                    $form->setError('rate', 'Не може да се изчисли курс');
                }
            }
            
            // Намираме контейнера на първия документ в нишката
            $doc = doc_Threads::getFirstDocument($rec->threadId);
            $correpspondingContainerId = $doc->fetchField('containerId');
            
            if (!$form->gotErrors()) {
                $rec->correspondingDealOriginId = $correpspondingContainerId;
                $rec->amount *= $rec->rate;
                
                if ($form->chargeVat == 'yes' || $form->chargeVat == 'separate') {
                    $rec->amount /= 1 + acc_Periods::fetchByDate($rec->valior)->vatRate;
                    $rec->amount = round($rec->amount, 2);
                }
                
                // Кешираме от всички възможни продукти, тези които са били избрани във функционалното поле
                $rec->productsData = array_intersect_key($form->allProducts, type_Set::toArray($rec->chosenProducts));
                $error = self::allocateAmount($rec->productsData, $rec->amount, $rec->allocateBy);
                if (!empty($error)) {
                    $form->setError('allocateBy', $error);
                }
            }
        }
    }
    
    
    /**
     * Разпределяне на разходите според посочения метод
     *
     * Цените на продуктите: P1, P2, ..., Pn
     * Количествата на продуктите: Q1, Q2, ..., Qn
     * Единичния транспортен обем: V1, V2, ..., Vn
     * Единичните транспортни тегла: M1, M2, ...., Mn
     *
     * Тогава коефициентите за разпределение на разходите/излишъците са:
     * 1. По стойност:
     * 		K(i) = P(i)*Q(i) / SUM(P*Q);
     * 		Ако общата сума е 0, не може да се разпредели разхода
     * 2. По количество:
     * 		K(i) = Q(i) / SUM(Q);
     * 3. По обем:
     * 		K(i) = V(i)*Q(i) / SUM(V*Q);
     * 		Ако някой артикул няма Транспортен обем не може да се разпредели
     * 4. По тегло:
     * 		K(i) = М(i)*Q(i) / SUM(М*Q);
     * 		Ако някой артикул няма тегло не може да се разпредели
     *
     * @param array $products - масив с информация за артикули
     *                        o productId       - ид на артикул
     *                        o name            - име на артикула
     *                        o quantity        - к-во
     *                        o amount          - сума на артикула
     *                        o transportWeight - транспортно тегло на артикула
     *                        o transportVolume - транспортен обем на артикула
     * @param float $amount   - сумата за разпределяне
     * @param auto|value|quantity|volume|weight - режим на разпределяне
     *
     * @return mixed
     */
    public static function allocateAmount(&$products, $amount, $allocateBy)
    {
        $denominator = 0;
        $errorArr = array();
        $allocateBy = ($allocateBy != 'auto') ? $allocateBy : 'value';
        
        // Първо обхождаме записите и изчисляване на знаменателя, чрез който ще изчислим коефициента
        switch ($allocateBy) {
            case 'value':
                
                // Ако се разпределя по стойност изчисляване на общата сума
                foreach ($products as $p) {
                    if (!isset($p->amount)) {
                        $errorArr[$p->productId] = $p->name;
                    } else {
                        $denominator += $p->amount;
                    }
                }
                break;
            case 'quantity':
                $measures = array();
                
                // Ако се разпределя по к-во изчисляване на общото к-во
                foreach ($products as $p) {
                    $denominator += $p->quantity;
                    $measureId = cat_Products::getProductInfo($p->productId)->productRec->measureId;
                    $measures[$measureId] = $measureId;
                }
                
                // Ако има повече от една мярка
                if (countR($measures) != 1) {
                    $errorArr = array(1 => true);
                }
                break;
            case 'weight':
                
                // Изчисляване на общото транспортно тегло
                foreach ($products as $p) {
                    if (empty($p->transportWeight)) {
                        $errorArr[$p->productId] = $p->name;
                    } else {
                        $denominator += $p->transportWeight * $p->quantity;
                    }
                }
                break;
            case 'volume':
                
                // Изчисляване на общия транспортен обем
                foreach ($products as $p) {
                    if (empty($p->transportVolume)) {
                        $errorArr[$p->productId] = $p->name;
                    } else {
                        $denominator += $p->transportVolume * $p->quantity;
                    }
                }
                break;
        }
        
        // Ако има намерени артикули без транспортен обем, тегло или к-во, при съответното разпределяне
        if (countR($errorArr)) {
            if ($allocateBy == 'quantity') {
                $msg = 'Не може да се избере разпределяне по количество, защото артикулите са в различни мерки';
            } elseif ($allocateBy == 'value') {
                $msg = 'Не може да се избере разпределяне по стойност, защото артикулите нямат стойност в документа';
            } else {
                $string = implode(', ', $errorArr);
                $type = ($allocateBy == 'weight') ? 'тегло' : 'обем';
                
                // Връщане на информация, кои артикули имат липсващи параметри
                $msg = "Не може да се избере разпределяне по {$type}, защото|* <b>{$string}</b> |нямат {$type}|*";
            }
            
            return $msg;
        }
        
        $values = array_values($products);
        $restAmount = $amount;
        $count = countR($values);
        
        // Обхождане на артикулите
        for ($i = 0; $i <= $count - 1; $i++) {
            $p = $values[$i];
            $next = $values[$i + 1];
            
            if (is_object($next)) {
                switch ($allocateBy) {
                    case 'value':
                        $coefficient = $p->amount / $denominator;
                        break;
                    case 'quantity':
                        $coefficient = $p->quantity / $denominator;
                        break;
                    case 'weight':
                        $coefficient = ($p->transportWeight * $p->quantity) / $denominator;
                        break;
                    case 'volume':
                        $coefficient = ($p->transportVolume * $p->quantity) / $denominator;
                        break;
                }
                
                // Изчисляване на сумата за разпределяне (коефициент * сума за разпределение)
                $p->allocated = core_Math::roundNumber($coefficient * $amount);
                $restAmount -= $p->allocated;
                $restAmount = core_Math::roundNumber($restAmount);
            } else {
                
                // Ако няма следващ елемент значи този е последен, и да няма грешки от закръгляне
                // Оставащата сума е остатъка
                $p->allocated = $restAmount;
            }
        }
        
        // Подсигуряване че артикулите са със същите ключове
        $products = array_combine(array_keys($products), $values);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if ($firstDoc) {
                if (!$firstDoc->haveInterface('acc_AllowArticlesCostCorrectionDocsIntf')) {
                    $requiredRoles = 'no_one';
                } elseif ($firstDoc->fetchField('state') != 'active') {
                    $requiredRoles = 'no_one';
                } else {
                    $products = $firstDoc->getCorrectableProducts($mvc);
                    if (!countR($products)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        // Може да се добави само към тред на покупка/продажба
        if ($firstDoc->isInstanceOf('sales_Sales') || $firstDoc->isInstanceOf('purchase_Purchases')) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        
        $row->title = $this->singleTitle . " №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (countR($rec->productsData)) {
            $detailsKeywords = '';
            foreach ($rec->productsData as $product) {
                $detailsKeywords .= ' ' . plg_Search::normalizeText($product->name);
            }
            
            $res = ' ' . $res . ' ' . $detailsKeywords;
        }
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        
        // Индикатор за делта на търговеца
        $rec = hr_IndicatorNames::force('priceCorrection', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        return $result;
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param $ents1 $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        
        // Кои са последно променяните документи, в нишка на продажба
        $query = self::getQuery();
        $query->EXT('firstDocClass', 'doc_Threads', 'externalName=firstDocClass,externalKey=threadId');
        $query->where("#state = 'active' || (#state = 'rejected' && (#brState = 'active' || #brState = 'closed'))");
        $query->where("#modifiedOn >= '{$timeline}' AND #firstDocClass = " . sales_Sales::getClassId());
        $query->show('state,action,amount,valior,threadId,productsData');
        
        $classId = self::getClassId();
        $iRec = hr_IndicatorNames::force('priceCorrection', __CLASS__, 1);
        
        // За всеки
        while ($rec = $query->fetch()) {
            
            $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
            $firstDocumentRec = $firstDocument->fetch();
            
            $isRejected = ($rec->state == 'rejected');
            $sign = ($rec->action == 'decrease') ? -1 : 1;
            
            // Ако продажбата не е обединяваща, стойноста се начислява на търговеца
            // Ако продажбата е обединяваща, за всеки артикул коригираната сума се начислява на търговеца му
            if(empty($firstDocumentRec->closedDocuments)){
                
                // Опит за намиране на търговеца от продажбата
                $dealers = sales_PrimeCostByDocument::getDealerAndInitiatorId($firstDocumentRec->containerId);
                if (empty($dealers['dealerId'])) continue;
                
                // Кое е лицето от визитника
                $personId = crm_Profiles::fetchField("#userId = '{$dealers['dealerId']}'", 'personId');
                $isRejected = ($rec->state == 'rejected');
                
                $sign = ($rec->action == 'decrease') ? -1 : 1;
                $value = round($rec->amount * $sign, 2);
                
                // Добавяне на записа за индикатора
                hr_Indicators::addIndicatorToArray($result, $rec->valior, $personId, $rec->id, $classId, $iRec->id, $value, $isRejected);
            } else {
                if(!is_array($rec->productsData)) continue;
                
                // Вземат се данните от делтите, за това кои са търговците на артикулите от обединения договор
                $sQuery = sales_PrimeCostByDocument::getQuery();
                $sQuery->where("#threadId = {$rec->threadId}");
                $sQuery->orderBy('id', 'ASC');
                $sQuery->groupBy('productId');
                $sQuery->show('productId,dealerId');
                $primeCostArr = $sQuery->fetchAll();
                
                // За всеки артикул
                foreach ($rec->productsData as $prodRec){
                    
                    // Намира се кой е отбелязан за негов търговец от модела с делтите
                    $dealerId = null;
                    array_walk($primeCostArr, function($a) use (&$dealerId, $prodRec){if($prodRec->productId == $a->productId){$dealerId = $a->dealerId;}});
                    if (empty($dealerId)) continue;
                    
                    // Коригираното от документа се отнася към точно този търговец
                    $personId = crm_Profiles::fetchField("#userId = '{$dealerId}'", 'personId');
                    $value = round($prodRec->allocated * $sign, 2);
                    
                    // Добавяне на записа за индикатора
                    hr_Indicators::addIndicatorToArray($result, $rec->valior, $personId, $rec->id, $classId, $iRec->id, $value, $isRejected);
                }
            }  
        }
        
        return $result;
    }
}
