<?php


/**
 * Списък с листвани артикули за клиента/доставчика
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_ListingDetails extends doc_Detail
{
    /**
     * Кой  може да изтрива?
     */
    public $canDelete = 'listArt, ceo';
    
    
    /**
     * Кой  може да добавя?
     */
    public $canAdd = 'listArt, ceo';
    
    
    /**
     * Кой  може да листва?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой  може да редактира?
     */
    public $canEdit = 'listArt, ceo';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'listId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Артикул,packagingId=Опаковка,reff=Техен код,moq,multiplicity,price,modifiedOn,modifiedBy';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified, cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_RowNumbering, plg_AlignDecimals2, plg_Sorting';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Артикул за листване';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Заглавие
     */
    public $title = 'Артикули за листване';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'moq,multiplicity,price';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Зебрата да не се показва
     *
     * @var int
     */
    public $zebraRows = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=cat_Listings,select=id)', 'caption=Лист,silent,mandatory');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=10,forceAjax)', 'class=w100,caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packagingId,caption=Артикул');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field nowrap,silent,caption=Опаковка,input=hidden,mandatory');
        $this->FLD('reff', 'varchar(32)', 'caption=Техен код,smartCenter');
        $this->FLD('moq', 'double(smartRound,Min=0)', 'caption=МКП||MOQ');
        $this->FLD('multiplicity', 'double(Min=0)', 'caption=Кратност');
        $this->FLD('price', 'double(Min=0)', 'caption=Цена');
        
        $this->setDbUnique('listId,productId,packagingId');
        $this->setDbUnique('listId,reff');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $mvc->currentTab = 'Листване';
        $masterRec = $data->masterRec;
        
        $params = array('hasProperties' => $masterRec->type);
        $Cover = doc_Folders::getCover($masterRec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $params += array('customerClass' => $Cover->getClassId(), 'customerId' => $Cover->that);
        }
        $form->setFieldTypeParams('productId', $params);
        if (isset($rec->id)) {
            $form->setReadOnly('productId');
        }
        
        // Ако е избран артикул, показва се и опаковката му
        if (isset($rec->productId)) {
            $packs = cat_Products::getPacks($rec->productId);
            $form->setField('packagingId', 'input');
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
        }
        
        $unit = $masterRec->currencyId . ' ' . (($masterRec->vat == 'yes') ? 'с ДДС' : 'без ДДС');
        $form->setField('price', "unit={$unit}");
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            $warning = '';
            
            // Ако няма код
            if (empty($rec->reff)) {
                $rec->reff = null;
            }
            
            // Проверка на МКП-то
            if (!empty($rec->moq)) {
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->moq, $warning)) {
                    $form->setError('moq', $warning);
                }
            }
            
            // Проверка на кратноста
            if (!empty($rec->multiplicity)) {
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->multiplicity, $warning)) {
                    $form->setError('multiplicity', $warning);
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Добавяне на бутони
        $masterRec = $data->masterData->rec;
        
        if ($mvc->haveRightFor('add', (object) array('listId' => $masterRec->id))) {
            $data->toolbar->removeBtn('btnAdd');
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', 'listId' => $masterRec->id, 'ret_url' => true), null, 'ef_icon = img/16/shopping.png,title=Добавяне на нов артикул за листване');
            $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $masterRec->id, 'ret_url' => true), null, 'ef_icon=img/16/import.png,title=Импортиране на артикули');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            if (empty($rec->listId)) {
                $requiredRoles = 'no_one';
            } else {
                $state = cat_Listings::fetchField($rec->listId, 'state');
                if ($state == 'rejected') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->productId = cat_Products::getShortHyperlink($rec->productId);
            $row->reff = "<b>{$row->reff}</b>";
            
            $state = cat_Products::fetchField($rec->productId, 'state');
            $row->ROW_ATTR['class'] = "state-{$state}";
            
            $listRec = cat_Listings::fetch($rec->listId, 'folderId,type,currencyId,vat');
            $Cover = doc_Folders::getCover($listRec->folderId);
            
            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                if ($listRec->type == 'canBuy') {
                    $policyInfo = cls::get('purchase_PurchaseLastPricePolicy')->getPriceInfo($Cover->getClassId(), $Cover->that, $rec->productId, $rec->packagingId, 1);
                    $hint = 'Артикулът няма цена по-която е купуван от контрагента';
                    $hint2 = 'Артикулът е купен последно на тази цена';
                } else {
                    $policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($Cover->getClassId(), $Cover->that, $rec->productId, $rec->packagingId, 1);
                    $hint = 'Артикулът няма цена по ценовата политика на контрагента';
                    $hint2 = 'Актуалната цена по политика';
                }
                
                if (!isset($rec->price)) {
                    if (!isset($policyInfo->price)) {
                        $row->productId = ht::createHint($row->productId, $hint, 'warning', false);
                        $row->productId = ht::createElement('span', array('style' => 'color:#755101'), $row->productId);
                    } else {
                        $vat = cat_Products::getVat($rec->productId);
                        $date = null;
                        $rate = currency_CurrencyRates::getRate($date, $listRec->currencyId, null);
                        $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
                        $rec->price = deals_Helper::getDisplayPrice($policyInfo->price, $vat, $rate, $listRec->vat);
                        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                        
                        $rec->price *= $quantityInPack;
                        $row->price = $mvc->getFieldType('price')->toVerbal($rec->price);
                        $row->price = "<span style='color:blue'>{$row->price}</span>";
                        $row->price = ht::createHint($row->price, $hint2, 'notice', false);
                    }
                }
            }
            
            if (!empty($listRec->type)) {
                $type = cat_Products::fetchField($rec->productId, $listRec->type);
                if ($type != 'yes') {
                    $vType = ($listRec->type == 'canBuy') ? 'купуваем' : 'продаваем';
                    $row->productId = "<span class='red'>{$row->productId}</span>";
                    $row->productId = ht::createHint($row->productId, "Артикулът вече не е {$vType}", 'error', false);
                }
            }
            
            $exPack = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, ($exPack->quantity) ? $exPack->quantity : 1);
        }
    }
    
    
    /**
     * Помощна ф-я връщаща намерения артикул и опаковка според кода
     *
     * @param int    $listId - ид на продуктовият лист
     * @param string $reff   - чужд код за търсене
     *
     * @return NULL|stdClass - обект с ид на артикула и опаковката или NULL ако няма
     */
    public static function getProductByReff($listId, $reff)
    {
        // Взимане от кеша на листваните артикули
        $all = cat_Listings::getAll($listId);
        
        // Оставят се само тези записи, в които се среща кода
        $res = array_filter($all, function (&$e) use ($reff) {
            if ($e->reff == $reff) {
                
                return true;
            }
            
            return false;
        });
        
        // Ако има първи елемент, взима се той
        $firstFound = $res[key($res)];
        $reff = (is_object($firstFound)) ? (object) array('productId' => $firstFound->productId, 'packagingId' => $firstFound->packagingId, 'price' => $firstFound->price) : null;
        
        return $reff;
    }
    
    
    /**
     * Екшън за импорт на артикули за листване
     */
    public function act_Import()
    {
        // Проверки за права
        $this->requireRightFor('add');
        expect($listId = Request::get('listId', 'int'));
        expect($listRec = cat_Listings::fetch($listId));
        $this->requireRightFor('add', (object) array('listId' => $listRec->id));
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->method = 'POST';
        $form->title = 'Импортиране на артикули за листване в|* ' . cat_Listings::getHyperlink($listId, true);
        $form->FLD('listId', 'int', 'input=hidden,silent');
        
        $Cover = doc_Folders::getCover($listRec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $form->FLD('from', 'enum(,group=Група,sales=Предишни продажби,purchases=Предишни покупки)', 'caption=Избор,removeAndRefreshForm=fromDate|toDate|selected,silent');
        } else {
            $form->FLD('from', 'enum(,group=Група)', 'caption=Избор,removeAndRefreshForm=fromDate|toDate|selected,silent');
            $form->setDefault('from', 'group');
            $form->setReadOnly('from');
        }
        
        $form->FLD('code', 'enum(noCode=Без код,barcode=Баркод)', 'caption=Техен код');
        $form->FLD('fromDate', 'date', 'caption=От,input=hidden,silent,removeAndRefreshForm=category|selected');
        $form->FLD('toDate', 'date', 'caption=До,input=hidden,silent,removeAndRefreshForm=category|selected');
        $form->FLD('group', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група,input=hidden,silent,removeAndRefreshForm=selected|fromDate|toDate');
        
        // Инпутване на скритите полета
        $form->input(null, 'silent');
        $form->input();
        
        $submit = false;
        
        // Ако е избран източник на импорт
        if (isset($form->rec->from)) {
            $rec = $form->rec;
            
            // И той  е група
            if ($rec->from == 'group') {
                
                // Показваме полето за избор на група и намиране на артикулите във нея
                $form->setField('group', 'input');
                if (isset($rec->group)) {
                    $products = $this->getFromGroup($rec->group, $rec->listId);
                    
                    if (!$products) {
                        $form->setError('from,group', 'Няма артикули за импортиране от групата');
                    }
                }
            } else {
                
                // Ако е избрано от последни продажби, показват се полетата за избор на период
                $form->setField('fromDate', 'input');
                $form->setField('toDate', 'input');
                
                // И се извличат артикулите от продажбите в този период на контрагента
                if (!empty($rec->fromDate) || !empty($rec->toDate)) {
                    $products = $this->getFromSales($rec->fromDate, $rec->toDate, $rec->from, $rec->listId);
                }
            }
            
            // Ако има намерени продукти показват се в друго поле за избор, чекнати по подразбиране
            if (isset($products) && count($products)) {
                $set = cls::get('type_Set', array('suggestions' => $products));
                $form->FLD('selected', 'varchar', 'caption=Артикули,mandatory');
                $form->setFieldType('selected', $set);
                $form->input('selected');
                $form->setDefault('selected', $set->fromVerbal($products));
                
                $submit = true;
            }
        }
        
        // Ако е събмитната формата
        if ($form->isSubmitted()) {
            $products = type_Set::toArray($form->rec->selected);
            expect(count($products));
            
            $error = $toSave = array();
            
            // Проверяване на избраните артикули
            foreach ($products as $productId) {
                $toSave[$productId]['productId'] = $productId;
                
                // Опаковката е основната мярка/опаковка
                $toSave[$productId]['packagingId'] = key(cat_Products::getPacks($productId));
                
                // Ако е избрано кода да е барков се изчлича, ако няма ще се показва грешка
                if ($rec->code == 'barcode') {
                    $pack = cat_products_Packagings::getPack($toSave[$productId]['productId'], $toSave[$productId]['packagingId']);
                    if (isset($pack) && !empty($pack->eanCode)) {
                        $toSave[$productId]['reff'] = $pack->eanCode;
                    } else {
                        $error[] = cat_Products::getTitleById($productId, false);
                    }
                }
            }
            
            // Ако има грешки
            if (count($error)) {
                $error = 'Артикулите|* <b>' . implode(', ', $error) . '</b> |нямат баркод на тяхната основна опаковка/мярка|*';
                $form->setError('selected', $error);
            } else {
                
                // Ако няма се добавят избраните артикули
                $count = 0;
                foreach ($toSave as $r) {
                    $newRec = (object) $r;
                    $newRec->listId = $listRec->id;
                    
                    $this->save($newRec, null, 'REPLACE');
                    $count++;
                }
                
                // Редирект
                followRetUrl(null, "Импортирани са|* '{$count}' |артикула|*");
            }
        }
        
        // Ако няма избрани артикули, бутона за импорт е недостъпен
        if ($submit === true) {
            $form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
        } else {
            $form->toolbar->addBtn('Импорт', array(), 'ef_icon = img/16/import.png, title = Импорт');
        }
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Рендиране на опаковката
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Помощен метод извличащ всички артикули за листване от дадена група
     *
     * @param int $group
     * @param int $listId
     *
     * @return array $products
     */
    private function getFromGroup($group, $listId)
    {
        $products = array();
        $cDescendants = cat_Groups::getDescendantArray($group);
        $all = cat_Listings::getAll($listId);
        
        $alreadyIn = arr::extractValuesFromArray($all, 'productId');
        
        // Извличане на всички активни, продаваеми артикули от дадената група и нейните подгрупи
        $query = cat_Products::getQuery();
        $query->likeKeylist('groups', $cDescendants);
        
        if (is_array($alreadyIn)) {
            $query->notIn('id', $alreadyIn);
        }
        
        $listRec = cat_Listings::fetchRec($listId);
        
        $query->where("#state = 'active'");
        $query->where("#{$listRec->type} = 'yes'");
        $query->show('isPublic,folderId,meta,id,code,name');
        
        while ($rec = $query->fetch()) {
            $products[$rec->id] = static::getRecTitle($rec, false);
        }
        
        // Връщане на намерените артикули
        return $products;
    }
    
    
    /**
     * Помщен метод за намиране на всички продадени артикули на контрагента
     *
     * @param datetime $from
     * @param datetime $to
     * @param int  $listId
     *
     * @return array $products
     */
    public function getFromSales($from, $to, $ext, $listId)
    {
        expect(in_array($ext, array('sales', 'purchases')));
        
        $products = array();
        $alreadyIn = arr::extractValuesFromArray(cat_Listings::getAll($listId), 'productId');
        $listRec = cat_Listings::fetch($listId);
        
        $type = $listRec->type;
        
        if ($ext == 'sales') {
            $query = sales_SalesDetails::getQuery();
            $Class = 'sales_Sales';
            $key = 'saleId';
        } else {
            $query = purchase_PurchasesDetails::getQuery();
            $Class = 'purchase_Purchases';
            $key = 'requestId';
        }
        
        // Извличане на всички продавани артикули на контрагента, които не са листвани все още
        $query->EXT('valior', $Class, "externalName=valior,externalKey={$key}");
        $query->EXT('folderId', $Class, "externalName=folderId,externalKey={$key}");
        $query->EXT('state', $Class, "externalName=state,externalKey={$key}");
        $query->EXT("{$type}", 'cat_Products', "externalName={$type},externalKey=productId");
        $query->where("#folderId = {$listRec->folderId}");
        $query->where("#state = 'active' OR #state = 'closed'");
        $query->where("#{$type} = 'yes'");
        
        if (!empty($from)) {
            $query->where("#valior >= '{$from}'");
        }
        
        if (!empty($to)) {
            $query->where("#valior <= '{$to}'");
        }
        
        $query->notIn('id', $alreadyIn);
        $query->show('productId');
        
        while ($rec = $query->fetch()) {
            $products[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
        }
        
        // Връщане на намерените артикул
        return $products;
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $masterRec = $data->masterData->rec;
        $vat = ($masterRec->vat == 'yes') ? 'с ДДС' : 'без ДДС';
        $data->listFields['price'] = tr('Цена') . "|* <small>({$masterRec->currencyId})</small> |{$vat}|*";
    }
}
