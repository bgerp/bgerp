<?php


/**
 * Драйвер за готовност за експедиция на документи
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Ценоразписи
 */
class price_reports_PriceList extends frame2_driver_TableData
{
    

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'sales, priceDealer, ceo';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'productId';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'groupName';
    
    
    /**
     * Закръгляне на цените по подразбиране
     */
    const DEFAULT_ROUND = 5;
    
    
    /**
     * Какъв да е класа на групирания ред
     */
    protected $groupByFieldClass = 'pricelist-group-label';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('date', 'date(smartTime)', 'caption=Към дата,mandatory,after=title');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandatory,after=date');
        $fieldset->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,input,after=policyId,single=none');
        $fieldset->FLD('vat', 'enum(yes=с включено ДДС,no=без ДДС)', 'caption=ДДС,after=currencyId,single=none');
        $fieldset->FLD('displayDetailed', 'enum(no=Съкратен,yes=Разширен)', 'caption=Изглед,after=vat,single=none');
        $fieldset->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Групи,columns=2,placeholder=Всички,after=displayDetailed,single=none');
        $fieldset->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки,columns=3,placeholder=Всички,after=productGroups,single=none');
        $fieldset->FLD('lang', 'enum(auto=Текущ,bg=Български,en=Английски)', 'caption=Допълнително->Език,after=packagings');
        $fieldset->FLD('showMeasureId', 'enum(yes=Показване,no=Скриване)', 'caption=Допълнително->Основна мярка,after=lang');
        $fieldset->FLD('showEan', 'enum(yes=Показване ако има,no=Да не се показва)', 'caption=Допълнително->EAN|*?,after=lang');
        $fieldset->FLD('round', 'int', 'caption=Допълнително->Точност,autohide,after=showMeasureId');
   }
    
   
   /**
    * Връща заглавието на отчета
    *
    * @param stdClass $rec - запис
    *
    * @return string|NULL - заглавието или NULL, ако няма
    */
   public function getTitle($rec)
   {
       $policyName = price_Lists::getTitleById($rec->policyId);
       $title = "Ценоразпис \"{$policyName}\"";
       
       return $title;
   }
   
   
   /**
    * Преди показване на форма за добавяне/промяна.
    *
    * @param frame2_driver_Proto $Driver   $Driver
    * @param embed_Manager       $Embedder
    * @param stdClass            $data
    */
   protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
   {
       $form = &$data->form;
       $form->setDefault('date', dt::now());
       $form->setField('round', "placeholder=" . self::DEFAULT_ROUND);
       $form->setDefault('lang', 'auto');
       $form->setDefault('showEan', 'yes');
       $form->setDefault('showMeasureId', 'yes');
       $form->setDefault('displayDetailed', 'no');
       
       $suggestions = cat_UoM::getPackagingOptions();
       $form->setSuggestions('packagings', $suggestions);
       $form->setOptions('policyId', price_ListDocs::getDefaultPolicies($form->rec));
       
       $Cover = doc_Folders::getCover($form->rec->folderId);
       if($Cover->haveInterface('crm_ContragentAccRegIntf')){
           $defaultList = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
           $form->setDefault('policyId', $defaultList);
           $form->setDefault('vat', deals_Helper::getDefaultChargeVat($form->rec->folderId));
           $form->setDefault('currencyId', $Cover->getDefaultCurrencyId());
       }
       
       if($Cover->haveInterface('doc_ContragentDataIntf')){
           $cData = doc_Folders::getContragentData($form->rec->folderId);
           $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
           $lang = (!empty($cData->countryId) && $cData->countryId != $bgId) ? 'en' : 'bg';
           $form->setDefault('lang', $lang);
       }
   }
    
   
   /**
    * Кои записи ще се показват в таблицата
    *
    * @param stdClass $rec
    * @param stdClass $data
    *
    * @return array
    */
   protected function prepareRecs($rec, &$data = null)
   {
       $date = ($rec->date == dt::today()) ? dt::now() : "{$rec->date} 23:59:59";
       
       $params = array('onlyPublic' => true);
       if(!empty($rec->productGroups)){
           $params['groups'] = $rec->productGroups;
       }
       $sellableProducts = array_keys(price_ListRules::getSellableProducts($params));
       
       $recs = array();
       if(is_array($recs)) {
           $currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, acc_Periods::getBaseCurrencyCode($rec->date));
           $packArr = (!empty($rec->packagings)) ? keylist::toArray($rec->packagings) : arr::make(array_keys(cat_UoM::getPackagingOptions(), true));
           
           foreach ($sellableProducts as $id) {
               $productRec = cat_Products::fetch($id, 'groups,code,measureId,name,isPublic');
               
               $obj = (object) array('productId' => $productRec->id,
                                           'code' => (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}",
                                           'measureId' => $productRec->measureId,
                                           'vat' => cat_Products::getVat($productRec->id, $date),
                                           'packs' => array(),
                                           'groups' => $productRec->groups);
               
               $obj->name = cat_Products::getVerbal($productRec, 'name');
               $priceByPolicy = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $date);
               $obj->price = deals_Helper::getDisplayPrice($priceByPolicy, $obj->vat, $currencyRate, $rec->vat);
               
               if(!empty($priceByPolicy)) {
                   $packQuery = cat_products_Packagings::getQuery();
                   $packQuery->where("#productId = {$productRec->id}");
                   $packQuery->in('packagingId', $packArr);
                   $packQuery->show("eanCode,quantity,packagingId");
                   while($packRec = $packQuery->fetch()){
                       $packRec->price = $packRec->quantity * $obj->price;
                       $packRec->price = deals_Helper::getDisplayPrice($packRec->price, $obj->vat, $currencyRate, $rec->vat);
                       $obj->packs[$packRec->packagingId] = $packRec;
                   }
                   
                   if($rec->showMeasureId != 'yes' && !count($obj->packs)) continue;
                   
                   $recs[$id] = $obj;
               }
           }
       }
     
       if(count($recs)){
           $productGroups = $rec->productGroups;
           if(empty($productGroups)){
               $productGroups = arr::extractValuesFromArray(cat_Groups::getQuery()->fetchAll(), 'id');
               $productGroups = keylist::fromArray($productGroups);
           }
           
           store_InventoryNoteSummary::filterRecs($productGroups, $recs, 'code', 'name');
       }
       
       return $recs;
   }
    
   
   /**
    * Вербализиране на редовете, които ще се показват на текущата страница в отчета
    *
    * @param stdClass $rec  - записа
    * @param stdClass $dRec - чистия запис
    *
    * @return stdClass $row - вербалния запис
    */
   protected function detailRecToVerbal($rec, &$dRec)
   {
       $row = new stdClass();
       
       if($rec->displayDetailed == 'yes'){
           $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, 'detailed', 'public', $rec->lang, null, false);
       } else {
           $row->productId = cat_Products::getShortHyperlink($dRec->productId);
       }
       
       $row->groupName = core_Type::getByName('varchar')->toVerbal($dRec->groupName);
       $row->code = core_Type::getByName('varchar')->toVerbal($dRec->code);
       $row->measureId = tr(cat_UoM::getShortName($dRec->measureId));
       
       $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
       $row->price = core_Type::getByName("double(decimals={$decimals})")->toVerbal($dRec->price);
       
       if(count($dRec->packs)){
           $row->packs = $this->getPackTable($rec, $dRec);
       }
       
       return $row;
   }
    
   
   /**
    * Рендиране на таблицата с опаковките
    * 
    * @param stdClass $rec
    * @param stdClass $dRec
    * @return core_ET $tpl
    */
   private function getPackTable($rec, $dRec)
   {
       $rows = array();
       
       // Вербализиране на опаковките ако има
       foreach ($dRec->packs as $packRec){
           $packName = cat_UoM::getVerbal($packRec->packagingId, 'name');
           deals_Helper::getPackInfo($packName, $dRec->productId, $packRec->packagingId, $packRec->quantity);
           $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
           $rows[$packRec->packagingId] = (object)array('packagingId' => $packName, 'price' => core_Type::getByName("double(decimals={$decimals})")->toVerbal($packRec->price));
           if(!empty($packRec->eanCode)){
               $eanCode = core_Type::getByName('varchar')->toVerbal($packRec->eanCode);
               if(!Mode::isReadOnly() && barcode_Search::haveRightFor('list')){
                   $eanCode = ht::createLink($eanCode, array('barcode_Search', 'search' => $eanCode));
               }
               $rows[$packRec->packagingId]->eanCode = $eanCode;
           }
       }
       
       $fieldset = new core_FieldSet();
       $fieldset->FLD('eanCode', 'varchar', 'tdClass=small');
       $fieldset->FLD('price', 'varchar', 'smartCenter');
       
       // Рендиране на таблицата, в която ще се показват опаковките
       $table = cls::get('core_TableView', array('mvc' => $fieldset));
       $table->tableClass = 'pricelist-report-pack-table';
       $table->thHide = true;
       $listFields = arr::make('eanCode=ЕАН,packagingId=Опаковка,price=Цена', true);
       if($rec->showEan != 'yes'){
           unset($listFields['eanCode']);
       }
       
       $tpl = $table->get($rows, $listFields);
       
       return $tpl;
   }
   
   
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        if($export === true){
            $fld->FLD('groups', 'key(mvc=cat_Groups,select=name)', 'caption=Група,tdClass=centered');
        }
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        
        if($rec->showMeasureId == 'yes'){
            $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered nowrap');
            $fld->FLD('price', "double(decimals={$decimals})", 'caption=Цена');
        }
        
        if($export === false){
            $fld->FLD('packs', 'html', 'caption=Опаковки');
        }
        
        return $fld;
    }
    
    
    /**
     * Какъв ще е езика с който ще се рендират данните на шаблона
     *
     * @param stdClass $rec
     *
     * @return string|null езика с който да се рендират данните
     */
    public function getRenderLang($rec)
    {
        return ($rec->lang == 'auto') ? null : $rec->lang;
    }
    
    
    /**
     * рендиране на таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        $tpl = parent::renderTable($rec, $data);
        $vatRow = core_Type::getByName('enum(yes=с включено ДДС,no=без ДДС)')->toVerbal($rec->vat);
        $beforeRow = tr("Всички цени са в|* {$rec->currencyId}, |{$vatRow}|*");
        $tpl->prepend($beforeRow, 'TABLE_BEFORE');
        
        return $tpl;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->policyId = price_Lists::getHyperlink($rec->policyId, true);
        $row->productGroups = (!empty($rec->productGroups)) ? implode(', ', cat_Groups::getLinks($rec->productGroups)) : tr('Всички');
        $row->packagings = (!empty($rec->packagings)) ? core_Type::getByName('keylist(mvc=cat_UoM,select=name)')->toVerbal($rec->packagings): tr('Всички');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if(Mode::is('printing')) return;
        
        $fieldTpl = new core_ET(tr("|*<fieldset class='detail-info'>
                                <legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div>|Групи|*: [#productGroups#]<!--ET_END productGroups--></div><div>|Опаковки|*: [#packagings#]</div></small>"));
    
        $fieldTpl->replace($data->row->productGroups, 'productGroups');
        $fieldTpl->replace($data->row->packagings, 'packagings');
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * При събмитване на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (cat_Groups::checkForNestedGroups($form->rec->productGroups)) {
                $form->setError('productGroups', 'Избрани са вложени групи');
            }
        }
    }
}