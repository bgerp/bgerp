<?php


/**
 * Базов клас за наследяване на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_InvoiceMaster extends core_Master
{
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'date';
    
    
    /**
     * Може ли да се принтират оттеглените документи?
     */
    public $printRejected = true;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();


    /**
     * Каква да е максималната дължина на стринга за пълнотекстово търсене
     *
     * @see plg_Search
     */
    public $maxSearchKeywordLen = 13;
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'dueDate';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,date,dueDate';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'number,date,dueDate,vatDate,vatReason,additionalConditions,username,issuerId,state';
    
    
    /**
     * Поле за забележките
     */
    public $notesFld = 'additionalInfo';
    
    
    /**
     * Дефолтен брой копия при печат
     *
     * @var int
     */
    public $defaultCopiesOnPrint = 2;
    
    
    /**
     * Кои полета да могат да се експортират в CSV формат
     *
     * @see bgerp_plg_CsvExport
     */
    public $exportableCsvFields = 'date,number=Фактура №,contragentName=Контрагент,contragentVatNo=ДДС №,uicNo=ЕИК,dealValue=Сума общо,dealValueWithoutDiscount=Без ДДС,vatAmount=ДДС,currencyId=Валута,accountId=Банкова сметка,paymentType,state';


    /**
     * Кои полета да се канонизират и запишат в друг модел
     *
     * @see drdata_plg_Canonize
     */
    public $canonizeFields = 'uicNo=uic';


    /**
     * По кое състояние да се филтрира в лист изгледа по дефолт
     *
     * @see acc_plg_DocumentSummary
     */
    public $defaultListFilterState = 'active';


    /**
     * Да се рефрешват ли дефолтните данни при рефреш
     */
    public $dontReloadDefaultsOnRefresh = false;


    /**
     * Кои полета да могат да се променят след активация
     */
    public $changableFields = 'responsible,contragentCountryId, contragentPCode, contragentPlace, contragentAddress, dueTime, dueDate, additionalInfo,accountId,paymentType,template,detailOrderBy,deliveryId';


    /**
     * Кое поле ще се оказва за подредбата на детайла
     */
    public $detailOrderByField = 'detailOrderBy';


    /**
     * След описанието на полетата
     */
    protected static function setInvoiceFields(core_Master &$mvc)
    {
        $mvc->FLD('date', 'date(format=d.m.Y)', 'caption=Дата');
        $mvc->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');

        $mvc->FLD('displayContragentClassId', 'enum(crm_Companies=Фирма,crm_Persons=Лице,newCompany=Нова фирма)', 'input,silent,removeAndRefreshForm=displayContragentId|selectInvoiceText,caption=Друг контрагент->Източник');
        $mvc->FLD('displayContragentId', 'int', 'input=none,silent,removeAndRefreshForm=contragentName|contragentCountryId|contragentVatNo|contragentEori|uicNo|contragentPCode|additionalInfo|contragentPlace|contragentAddress|place,caption=Друг контрагент->Избор');

        $mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент,silent');
        $mvc->FLD('contragentId', 'int', 'input=hidden,silent');
        $mvc->FLD('contragentName', 'varchar', 'caption=Контрагент->Име, mandatory, class=contactData,silent');
        $mvc->FLD('responsible', 'varchar(255)', 'caption=Контрагент->Отговорник, class=contactData');
        $mvc->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty,silent)', 'caption=Контрагент->Държава,mandatory,contragentDataField=countryId,silent');
        $mvc->FLD('contragentVatNo', 'drdata_VatType', 'caption=Контрагент->VAT №,contragentDataField=vatNo,silent');
        $mvc->FLD('contragentEori', 'drdata_type_Eori', 'caption=Контрагент->EORI №,contragentDataField=eori,silent');
        $mvc->FLD('uicNo', 'varchar', 'caption=Контрагент->Национален №,contragentDataField=uicId,silent');
        $mvc->FLD('contragentPCode', 'varchar(16)', 'caption=Контрагент->П. код,recently,class=pCode,contragentDataField=pCode,silent');
        $mvc->FLD('contragentPlace', 'varchar(64)', 'caption=Контрагент->Град,class=contactData,contragentDataField=place,silent');
        $mvc->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес,class=contactData,contragentDataField=address,silent');
        $mvc->FLD('detailOrderBy', 'enum(auto=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Артикули->Подреждане по,notNull,value=auto');
        $mvc->FLD('changeAmount', 'double(decimals=2)', 'input=none');
        $mvc->FLD('dcReason', 'richtext(rows=2)', 'input=none,after=dcReason');
        $mvc->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
        
        $mvc->FLD('dueTime', 'time(suggestions=3 дена|5 дена|7 дена|14 дена|30 дена|45 дена|60 дена)', 'caption=Плащане->Срок');
        $mvc->FLD('dueDate', 'date', 'caption=Плащане->Краен срок');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
        $mvc->FLD('rate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime,input=hidden,silent');
        $mvc->FLD('displayRate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime');
        $mvc->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие');
        $mvc->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Доставка->Място,hint=Избор измежду въведените обекти на контрагента');
        $mvc->FLD('vatReason', 'varchar(255)', 'caption=Данъчни параметри->Основание,recently,Основание за размера на ДДС');
        $mvc->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъчни параметри->Дата на ДС,hint=Дата на възникване на данъчното събитие');
        $mvc->FLD('vatRate', 'enum(separate=Отделен ред за ДДС, yes=Включено ДДС в цените, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=Данъчни параметри->ДДС,input=hidden');
        $mvc->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6, passage)', 'caption=Допълнително->Бележки');
        $mvc->FLD('issuerId', 'user(roles=ceo|salesMaster,allowEmpty)', 'caption=Допълнително->Съставил,removeAndRefreshForm=username');
        $mvc->FLD('username', 'varchar', 'caption=Допълнително->Съставил име', 'input=none');
        $mvc->FLD('dealValue', 'double(decimals=2)', 'caption=Без ДДС, input=hidden,summary=amount');
        $mvc->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
        $mvc->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
        $mvc->FLD('sourceContainerId', 'key(mvc=doc_Containers,allowEmpty)', 'input=hidden,silent');
        $mvc->FLD('paymentMethodId', 'int', 'input=hidden,silent');
        
        $mvc->FLD('paymentType', 'enum(,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,postal=Пощенски паричен превод)', 'caption=Плащане->Начин,before=accountId,mandatory');
        $mvc->FLD('autoPaymentType', 'enum(,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,mixed=Смесено)', 'placeholder=Автоматично,caption=Плащане->Начин,input=none');
        $mvc->setDbIndex('dueDate');
    }


    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        // Ако е указано да се кешират допълни данни
        setIfNot($mvc->cacheAdditionalConditions, false);
        if($mvc->cacheAdditionalConditions){
            $mvc->FLD('additionalConditions', 'blob(serialize, compress)', 'caption=Допълнително->Условия (Кеширани),notChangeableByContractor,input=none');
        }
    }


    /**
     * Метод по подразбиране допълващ полетата за филтриране в съмърито в лист изгледа
     * @see acc_plg_DocumentSummary
     */
    public function fillSummaryRec(&$rec, &$summaryFields)
    {
        unset($summaryFields['dealValue']);
        unset($summaryFields['discountAmount']);

        arr::placeInAssocArray($summaryFields, array('dealValueWithoutDiscount' => (object)array('name' => 'dealValueWithoutDiscount', 'summary' => 'amount', 'caption' => 'Дан. основа')), 'vatAmount');
        arr::placeInAssocArray($summaryFields, array('totalValue' => (object)array('name' => 'totalValue', 'summary' => 'amount', 'caption' => 'Общо')), null, 'vatAmount');

        $rec->dealValueWithoutDiscount = $rec->dealValue - $rec->discountAmount;
        $rec->totalValue = $rec->dealValue - $rec->discountAmount + $rec->vatAmount;
    }


    /**
     * Метод по подразбиране за взимане на полетата за канонизиране
     */
    protected static function on_AfterGetCanonizedFields($mvc, &$res, $rec)
    {
        if($rec->contragentClassId == crm_Persons::getClassId()){
            unset($res['uicNo']);
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('countryGroups', 'key(mvc=drdata_CountryGroups,select=name,allowEmpty)', 'caption=Държави,input');

        if ($mvc->getField('type', false)) {
            $typeEnumOptions = "enum(all=Всички,invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие,accrued=Фактура с начислен аванс,deducted=Фактура с приспаднат аванс)";
        } else {
            $typeEnumOptions = "enum(all=Всички,accrued=Проформа фактура с начислен аванс,deducted=Проформа фактура с приспаднат аванс)";
        }
        $data->listFilter->FNC('invType', $typeEnumOptions, 'caption=Вид,input,silent');
        $data->listFields['paymentType'] = 'Плащане';
        $data->listFilter->FNC('payType', 'enum(all=Всички,cash=В брой,bank=По банка,intercept=С прихващане,card=С карта,factoring=Факторинг,postal=Пощенски паричен превод)', 'caption=Начин на плащане,input');
        $data->listFilter->showFields .= ",payType,invType,countryGroups";
        $data->listFilter->input(null, 'silent');
        
        if ($rec = $data->listFilter->rec) {
            if ($rec->invType) {
                if ($rec->invType != 'all') {
                    if ($rec->invType == 'invoice') {
                        $data->query->where("#type = '{$rec->invType}'");
                    } elseif(in_array($rec->invType, array('accrued', 'deducted'))){
                        $data->query->where("#dpOperation = '{$rec->invType}'");
                        if ($mvc->getField('type', false)) {
                            $data->query->where("#type = 'invoice'");
                        }
                    } else {
                        $sign = ($rec->invType == 'credit_note') ? '<=' : '>';
                        $data->query->where("(#type = 'dc_note' AND #dealValue {$sign} 0) || #type = '{$rec->invType}'");
                    }
                }
            }
            
            if ($rec->payType) {
                if ($rec->payType != 'all') {
                    $data->query->where("#paymentType = '{$rec->payType}' OR (#paymentType IS NULL AND #autoPaymentType = '{$rec->payType}')");
                }
            }
            
            if (!empty($rec->countryGroups)) {
                $groupCountries = drdata_CountryGroups::fetchField($rec->countryGroups, 'countries');
                $groupCountries = keylist::toArray($groupCountries);
                $data->query->in('contragentCountryId', $groupCountries);
            }

            $data->query->orWhere("#state = 'rejected'");
        }

        $data->query->orderBy('#number', 'DESC');
    }
    
    
    /**
     * Изпълнява се след обновяване на информацията за потребител
     */
    public static function on_AfterUpdate($mvc, $rec, $fields = null)
    {
        if ($rec->type === 'dc_note') {
            
            // Ако е известие и има поне един детайл обновяваме мастъра
            $Detail = $mvc->mainDetail;
            $query = $mvc->{$Detail}->getQuery();
            $query->where("#{$mvc->{$Detail}->masterKey} = '{$rec->id}'");
            if ($query->fetch()) {
                $mvc->updateQueue[$rec->id] = $rec->id;
            }
        }
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id, $save = true)
    {
        $rec = $this->fetchRec($id);
        $Detail = cls::get($this->mainDetail);
        
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = '{$rec->id}'");
        $recs = $query->fetchAll();
        
        if (countR($recs)) {
            foreach ($recs as &$dRec) {
                $dRec->price = $dRec->price * $dRec->quantityInPack;
            }
        }
        
        $Detail->calculateAmount($recs, $rec);
        $rate = ($rec->displayRate) ? $rec->displayRate : $rec->rate;

        $rec->dealValue = $this->_total->amount * $rate;
        $rec->vatAmount = $this->_total->vat * $rate;
        $rec->discountAmount = $this->_total->discount * $rate;

        if ($save === true) {
            return $this->save($rec);
        }
    }

    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_BeforePrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = &$data->form->rec;
        if ($rec->type == 'dc_note') {
            $data->singleTitle = ($rec->dealValue <= 0) ? 'кредитно известие' : 'дебитно известие';
        } else {
            $data->singleTitle = $mvc->singleTitle;
        }
    }
    
    
    /**
     * Валидиране на полето 'vatDate' - дата на данъчно събитие (ДС)
     *
     * Грешка ако ДС е след датата на фактурата или на повече от 5 дни преди тази дата.
     */
    public static function on_ValidateVatDate(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->vatDate)) {
            return;
        }
        
        // Датата на ДС не може да бъде след датата на фактурата, нито на повече от 5 дни преди нея.
        if ($rec->vatDate > $rec->date || dt::addDays(5, $rec->vatDate) < $rec->date) {
            $form->setError('vatDate', '|Данъчното събитие трябва да е до 5 дни|* <b>|преди|*</b> |датата на фактурата|*');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public function renderSingleLayout($data)
    {
        $tpl = parent::renderSingleLayout($data);
        
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
        
        return $tpl;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        if ($rec->type == 'invoice' && $rec->state == 'active') {
            if ($mvc->haveRightFor('add', (object) array('type' => 'dc_note','threadId' => $rec->threadId)) && $mvc->canAddToThread($rec->threadId)) {
                $data->toolbar->addBtn('Известие||D/C note', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'dc_note', 'ret_url' => true), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно или кредитно известие към документа,rows=2');
            }
        }

        if ($rec->type == 'dc_note' && $rec->state == 'active'){
            if(acc_ValueCorrections::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
                $data->toolbar->addBtn('Корекция на стойност', array('acc_ValueCorrections', 'add', 'threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/page_white_text.png,title=Корекция на стойност към сделката');
            }
        }
    }
    
    
    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    protected function populateNoteFromInvoice(core_Form &$form, core_ObjectReference $origin)
    {
        $originRec = $origin->fetch();
        $invArr = (array) $originRec;
        if(!($this instanceof sales_Proformas)){
            $form->setField('displayContragentClassId', 'input=hidden');
            $form->setField('displayContragentId', 'input=hidden');
        }

        // Трябва фактурата основание да не е ДИ или КИ
        expect($invArr['type'] == 'invoice');
        $unsetArr = array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue', 'vatAmount', 'state', 'discountAmount', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'vatDate', 'dpAmount', 'dpOperation', 'sourceContainerId', 'dueDate', 'type', 'originId', 'changeAmount', 'activatedOn', 'activatedBy', 'journalDate', 'dcReason', 'fileHnd', 'responsible', 'numlimit', 'username', 'issuerId', 'dpReason');

        if ($invArr['type'] != 'dc_note') {
            $cache = $this->getInvoiceDetailedInfo($form->rec->originId);

            if (countR($cache->vats) == 1) {
                $form->setField('changeAmount', "unit={$invArr['currencyId']} без ДДС");
                $form->setField('changeAmount', 'input,caption=Задаване на увеличение/намаление на фактура->Промяна');

                $min = $invArr['dealValue'] / (($invArr['displayRate']) ? $invArr['displayRate'] : $invArr['rate']);
                $min = round($min, 2);
                $form->rec->changeAmountVat = key($cache->vats);
                $form->setFieldTypeParams('changeAmount', array('min' => -1 * $min));
              ;
                if ($invArr['dpOperation'] == 'accrued') {
                    // Ако е известие към авансова ф-ра поставяме за дефолт сумата на фактурата
                    $form->setField('dcReason', 'input');
                    $form->setField('changeAmount', 'caption=Промяна на авансово плащане|*->|Аванс|*,mandatory');
                    $form->setField('dcReason', 'input,caption=Промяна на авансово плащане|*->Пояснение');
                } elseif($invArr['dpOperation'] == 'deducted') {
                    if(isset($form->rec->dpAmount)){
                        $form->setDefault('dcChangeAmountDeducted', round($form->rec->dpAmount / $form->rec->rate, 4));
                    }
                    $form->FLD('dcChangeAmountDeducted', 'double', "input,unit={$invArr['currencyId']} без ДДС,caption=Задаване на увеличение/намаление на фактура->|Приспаднат аванс|*,after=changeAmount");
                    $form->setFieldTypeParams('dcChangeAmountDeducted', array('min' => $invArr['dpAmount']));
                    $form->setField('dcReason', 'input,caption=Промяна на авансово плащане|*->Пояснение,after=changeAmountDownpayment');
                    $unsetArr[] = 'dcChangeAmountDeducted';
                }

                $form->setField('dcReason', 'input,caption=Задаване на увеличение/намаление на фактура->Пояснение');

            } else {
                $form->info = tr("|*<div class='richtext-message richtext-warning'>|В оригиналната фактура има артикули с различни ставки на ДДС. Сумата на известието трябва да се отрази за съответните артикули|*Ф-р</div>");
            }
        }

        if($form->rec->type == 'dc_note') {
            if(empty($originRec->displayContragentId)){
                $unsetArr += arr::make('contragentName,contragentPCode,contragentPlace,contragentAddress,uicNo,contragentEori,contragentVatNo,contragentCountryId', true);
            }
        }

        if ($this instanceof purchase_Invoices) {
            $unsetArr[] = 'journalDate';
        }
        
        foreach ($unsetArr as $key) {
            unset($invArr[$key]);
        }
        
        if ($form->rec->type == 'credit_note') {
            unset($invArr['dueDate']);
        }

        if ($invArr['type'] == 'dc_note') {
            foreach (array() as $fld){

                unset($invArr[$fld]);
            }
        }

        // Копиране на повечето от полетата на фактурата
        foreach ($invArr as $field => $value) {
            $form->rec->{$field} = $value;
        }

        $form->setDefault('date', dt::today());
        
        $form->setField('vatRate', 'input=hidden');
        $form->setField('deliveryId', 'input=none');
        $form->setField('deliveryPlaceId', 'input=none');
        $form->setField('displayRate', 'input=hidden');

        $readOnlyContragentData = ($form->rec->type == 'dc_note' && empty($originRec->displayContragentId));
        if($readOnlyContragentData){
            foreach (array('contragentName', 'contragentEori', 'contragentVatNo', 'uicNo', 'contragentCountryId', 'contragentPCode', 'contragentPlace', 'contragentAddress', 'displayContragentClassId', 'displayContragentId') as $name) {
                if ($form->rec->{$name}) {
                    $form->setReadOnly($name);
                }
            }
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        
        if ($this->getField('type', false)) {
            $rec = static::fetch($id);
            switch ($rec->type) {
                case 'invoice':
                    $type = 'приложената фактура';
                    break;
                case 'debit_note':
                    $type = 'приложеното дебитно известие';
                    break;
                case 'credit_note':
                    $type = '';
                    
                    // no break
                case 'dc_note':
                    $type = ($rec->dealValue <= 0) ? 'приложеното кредитно известие' : 'приложеното дебитно известие';
                    break;
            }
        } else {
            $type = 'приложената проформа фактура';
        }
        
        // Създаване на шаблона
        $tpl = new ET(tr('Моля, запознайте се с') . " [#type#]: #[#handle#]");
        $tpl->append($handle, 'handle');
        $tpl->append(tr($type), 'type');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        
        $template = $this->getTemplate($id);
        $lang = doc_TplManager::fetchField($template, 'lang');
        
        if ($lang) {
            core_Lg::push($lang);
        }
        
        $row->title = static::getRecTitle($rec);
        
        if ($lang) {
            core_Lg::pop();
        }
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * Връща масив от използваните нестандартни артикули в фактурата
     *
     * @param int $id - ид на фактура
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
        if ($rec->type == 'dc_note' && (isset($rec->changeAmount) || isset($rec->dpAmount))) {
            $res = true;
            return;
        }
        
        // Ако няма ид, не може да се активира документа
        if (empty($rec->id) && !isset($rec->dpAmount)) {
            $res = false;
            return;
        }
        
        // Ако има Авансово плащане може да се активира
        if (isset($rec->dpAmount)) {
            $res = !((round($rec->dealValue, 2) < 0 || is_null($rec->dealValue)));
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if(isset($rec->threadId)){
            doc_DocumentCache::threadCacheInvalidation($rec->threadId);
        }

        if($rec->_changedCondition){
            $mvc->logWrite('Променено условие от сметка', $rec->id);
        }

        $Source = $mvc->getSourceOrigin($rec);
        if (!$Source) {
            return;
        }

        if ($rec->_isClone === true) {
            return;
        }

        // Само ако записа е след редакция
        if ($rec->_edited !== true) {
            return;
        }

        // И не се начислява аванс
        if ($rec->dpAmount && $rec->dpOperation == 'accrued') {
            return;
        }
        
        // Инвалидираме кеша на документа
        doc_DocumentCache::cacheInvalidation($Source->fetchField('containerId'));

        // Ако е ДИ или КИ и има зададена сума не се  зарежда нищо
        if ($rec->type != 'invoice' && isset($rec->changeAmount)) {
            
            // Изтриване на детайлите на известието, ако е въведена сума на известието
            $Detail = cls::get($mvc->mainDetail);
            $deletedCount = $Detail->delete("#{$Detail->masterKey} = {$rec->id}");
            if ($deletedCount > 0) {
                unset($mvc->updateQueue[$rec->id]);
            }
            
            return;
        }
        
        // И няма детайли
        $Detail = cls::get($mvc->mainDetail);
        if ($Detail->fetch("#{$Detail->masterKey} = '{$rec->id}'")) {
            return;
        }

        if($rec->importProducts){
            if($rec->importProducts == 'fromSource'){
                $Source = doc_Containers::getDocument($rec->sourceContainerId);
                $handle = "#" . $Source->getHandle();
                if(strpos($rec->additionalInfo, $handle) === false){
                    $rec->additionalInfo .= "\n" . $handle;
                    $mvc->save_($rec, 'additionalInfo');
                }
            } elseif($rec->importProducts == 'none') {
                unset($Source);
            } else {
                $Source = static::getOrigin($rec);
            }
        }

        if ($Source && $Source->haveInterface('deals_InvoiceSourceIntf')) {
            $detailsToSave = $Source->getDetailsFromSource($mvc, $rec->importProducts);

            if (is_array($detailsToSave)) {
                foreach ($detailsToSave as $det) {
                    $det->_importBatches = $rec->importBatches;
                    $det->{$Detail->masterKey} = $rec->id;
                    unset($det->batches);
                    unset($det->autoDiscount);
                    $Detail->save($det);
                }
            }
        }
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
        parent::prepareSingle_($data);
        $rec = &$data->rec;

        if (empty($data->noTotal)) {
            $rate = !empty($rec->displayRate) ? $rec->displayRate : $rec->rate;
            if (isset($rec->type) && $rec->type != 'invoice' && isset($rec->changeAmount)) {
                $this->_total = new stdClass();
                $this->_total->amount = $rec->dealValue / $rate;
                $this->_total->vat = $rec->vatAmount / $rate;
                @$percent = round($this->_total->vat / $this->_total->amount, 2);
                $percent = is_nan($percent) ? 0 : $percent;
                $this->_total->vats["{$percent}"] = (object) array('amount' => $this->_total->vat, 'sum' => $this->_total->amount);
            }

            $this->invoke('BeforePrepareSummary', array($this->_total));
            $data->summary = deals_Helper::prepareSummary($this->_total, $rec->date, $rate, $rec->currencyId, $rec->vatRate, true, $rec->tplLang);

            $data->row = (object) ((array) $data->row + (array) $data->summary);
            $data->row->vatAmount = $data->summary->vatAmount;
        } elseif(!doc_plg_HidePrices::canSeePriceFields($this, $rec)) {
            $data->row->value = doc_plg_HidePrices::getBuriedElement();
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $rec = &$data->rec;
        
        $myCompany = crm_Companies::fetchOwnCompany();
        if ($rec->contragentCountryId != $myCompany->countryId) {
            $data->row->place = str::utf2ascii($data->row->place);
        }
    }
    
    
    /**
     * След подготовката на навигацията по сраници
     */
    public static function on_AfterPrepareListPager($mvc, &$data)
    {
        if (Mode::is('printing')) {
            unset($data->pager);
        }
    }


    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->contragentClassId == crm_Persons::getClassId()){
            $mvc->setFieldType('uicNo', 'bglocal_EgnType(onlyString)');
        } else {
            $mvc->setFieldType('uicNo', "drdata_type_Uic(countryId={$rec->contragentCountryId})");
        }
    }


    /**
     * След подготовка на формата
     */
    protected static function prepareInvoiceForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        if (empty($form->rec->id)) {
            $form->rec->contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
            $form->rec->contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        }

        // Ако ф-та не е към служебен аванс не искаме да се сменя контрагента
        $firstDocument = doc_Threads::getFirstDocument($form->rec->threadId);
        $form->setDefault('displayContragentClassId', 'crm_Companies');
        if (!$firstDocument->isInstanceOf('findeals_AdvanceDeals')) {
            if($form->cmd != 'refresh'){
                if($mvc instanceof sales_Invoices){
                    $form->setField('displayContragentClassId', 'autohide=any');
                    $form->setField('displayContragentId', 'autohide=any');
                }
            }
        } else {
            if (isset($rec->displayContragentClassId) && empty($rec->displayContragentId) && $rec->displayContragentClassId != 'newCompany') {
                foreach (array('contragentName', 'contragentCountryId', 'contragentVatNo', 'uicNo', 'contragentPCode', 'contragentPlace', 'contragentAddress')  as $fld) {
                    $form->setReadOnly($fld);
                }
            }
        }

        $form->setDefault('place', $mvc->getDefaultPlace($rec));
        // Ако има избрано поле за източник на контрагента
        if (isset($rec->displayContragentClassId)) {
            if (in_array($rec->displayContragentClassId, array('crm_Companies', 'crm_Persons'))) {
                $form->setField('displayContragentId', 'input');
                $form->setFieldType('displayContragentId', core_Type::getByName("key2(mvc={$rec->displayContragentClassId},select=name,allowEmpty)"));
            }
        }

        // При създаване на нова ф-ра зареждаме полетата на формата с разумни стойности по подразбиране.
        expect($firstDocument = doc_Threads::getFirstDocument($form->rec->threadId), $form->rec);
        $coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
        $coverId = doc_Folders::fetchCoverId($form->rec->folderId);

        if ($form->rec->template) {
            $mvc->pushTemplateLg($form->rec->template);
        }

        Mode::push('htmlEntity', 'none');
        $form->setDefault('contragentName', $coverClass::getVerbal($coverId, 'name'));
        Mode::pop('htmlEntity');

        if ($form->rec->template) {
            core_Lg::pop();
        }

        if ($form->cmd == 'refresh') {

            $arr = array();

            // Ако е избран контрагент замества ме му данните
            if (isset($rec->displayContragentId)) {
                if (in_array($rec->displayContragentClassId, array('crm_Companies', 'crm_Persons'))) {
                    $cData = cls::get($rec->displayContragentClassId)->getContragentData($rec->displayContragentId);
                    $nameField = ($rec->displayContragentClassId == 'crm_Companies') ? 'company' : 'person';
                    foreach (array('contragentName' => $nameField, 'contragentCountryId' => 'countryId', 'contragentVatNo' => 'vatNo', 'uicNo' => 'uicId', 'contragentPCode' => 'pCode', 'contragentPlace' => 'place', 'contragentAddress' => 'address') as $k => $v) {
                        $arr[$k] = $cData->{$v};
                    }
                }

                if (countR($arr)) {
                    foreach (array('contragentName', 'contragentCountryId', 'contragentVatNo', 'uicNo', 'contragentPCode', 'contragentPlace', 'contragentAddress')  as $fld) {
                        $form->rec->{$fld} = $arr[$fld];
                    }
                }
            } else {
                // Ако е сменен контрагента за показване, но преди е имало такъв да се заредят дефолтните стойности
                if(isset($rec->id)){
                    $exRec = $mvc->fetch($rec->id, '*', false);
                    if(!empty($exRec->displayContragentId)){
                        foreach (arr::make('contragentCountryId,contragentVatNo,contragentEori,uicNo,contragentPCode,contragentPlace,contragentAddress', true) as $fld){
                            $cloneRec = (object)array('folderId' => $rec->folderId);
                            $form->rec->{$fld} = cond_plg_DefaultValues::getDefValueByStrategy($mvc, $cloneRec, $fld, 'clientData|lastDocUser|lastDoc');
                        }
                    }
                }
            }
        }

        $form->setFieldType('uicNo', 'drdata_type_Uic');
        if(($rec->displayContragentClassId == 'crm_Persons' && isset($rec->displayContragentId)) || (doc_Folders::fetchCoverClassName($form->rec->folderId) == 'crm_Persons' && empty($rec->displayContragentId) && $rec->displayContragentClassId != 'newCompany')){
            $form->setField('uicNo', 'caption=Контрагент->ЕГН');
            $form->setFieldType('uicNo', 'bglocal_EgnType');
        }
        
        $type = Request::get('type');
        if (empty($type)) {
            $type = 'invoice';
        }
        $form->setDefault('type', $type);
        
        if ($firstDocument->haveInterface('bgerp_DealAggregatorIntf') && !$firstDocument->isInstanceOf('findeals_AdvanceDeals')) {
            $aggregateInfo = $firstDocument->getAggregateDealInfo();

            $form->setDefault('detailOrderBy', $aggregateInfo->get('detailOrderBy'));
            $form->rec->vatRate = $aggregateInfo->get('vatType');
            $form->rec->currencyId = $aggregateInfo->get('currency');
            $form->rec->rate = $aggregateInfo->get('rate');
            $form->setSuggestions('displayRate', array('' => '', $aggregateInfo->get('rate') => $aggregateInfo->get('rate')));
            
            if ($aggregateInfo->get('paymentMethodId') && !($mvc instanceof sales_Proformas)) {
                $paymentMethodId = $aggregateInfo->get('paymentMethodId');
                $plan = cond_PaymentMethods::getPaymentPlan($paymentMethodId, $aggregateInfo->get('amount'), $form->rec->date);

                if($plan['eventBalancePayment'] != 'invEndOfMonth'){
                    if (!isset($form->rec->id)) {
                        $form->setDefault('dueTime', $plan['timeBalancePayment']);
                    }
                } else {
                    $timeVerbal = core_Type::getByName('time')->toVerbal($plan['timeBalancePayment']);
                    $form->setField('dueTime', "placeholder={$timeVerbal} след края на месеца,class=w50");
                }
                
                $paymentType = ($aggregateInfo->get('paymentType')) ? $aggregateInfo->get('paymentType') : cond_PaymentMethods::fetchField($paymentMethodId, 'type');
                $form->setDefault('paymentType', $paymentType);
            }

            $form->setDefault('deliveryId', $aggregateInfo->get('deliveryTerm'));
            if ($aggregateInfo->get('deliveryLocation')) {
                $form->setDefault('deliveryPlaceId', $aggregateInfo->get('deliveryLocation'));
            }
            $form->setDefault('paymentMethodId', $aggregateInfo->paymentMethodId);
            
            $data->aggregateInfo = $aggregateInfo;
            $form->aggregateInfo = $aggregateInfo;
        }

        // Ако ориджина също е фактура
        $origin = $mvc->getSourceOrigin($form->rec);
        if ($origin->className == $mvc->className) {
            $mvc->populateNoteFromInvoice($form, $origin);

            $data->flag = true;
        } elseif ($origin->className == 'store_ShipmentOrders') {
            $originValior = $origin->fetchField('valior');
            if ($originValior < $form->rec->date) {
                $form->setDefault('vatDate', $originValior);
            }
        }
        
        if (empty($data->flag)) {
            $locations = crm_Locations::getContragentOptions($coverClass, $coverId);
            $form->setOptions('deliveryPlaceId', array('' => '') + $locations);
        }
        
        // Метод който да бъде прихванат от deals_plg_DpInvoice
        $mvc->prepareDpInvoicePlg($data);
        
        if ($form->rec->currencyId == acc_Periods::getBaseCurrencyCode($form->rec->date)) {
            $form->setField('displayRate', 'input=hidden');
        }
        
        $noReason1 = acc_Setup::get('VAT_REASON_OUTSIDE_EU');
        $noReason2 = acc_Setup::get('VAT_REASON_IN_EU');
        $noReason3 = acc_Setup::get('VAT_REASON_MY_COMPANY_NO_VAT');
        $suggestions = array('' => '', $noReason1 => $noReason1, $noReason2 => $noReason2, $noReason3 => $noReason3);
        $form->setSuggestions('vatReason', $suggestions);

        if(empty($rec->id) && $rec->type == 'invoice'){
            $types = $mvc->autoAddProductStrategies;
            if(isset($rec->sourceContainerId)){
                $types += array('fromSource' => "Артикулите от #" . doc_Containers::getDocument($rec->sourceContainerId)->getHandle());
            }

            $data->form->FNC('importProducts', "enum(" . arr::fromArray($types) . ")", 'caption=Артикули->Избор, input,after=contragentAddress');
            if(core_Packs::isInstalled('batch') && $mvc instanceof sales_Invoices){
                $data->form->FNC('importBatches', "enum(yes=Да,no=Не)", 'caption=Артикули->Партиди, input,maxRadio=2,after=importProducts');
                $data->form->setDefault('importBatches', batch_Setup::get('SHOW_IN_INVOICES'));
            }

            if(isset($rec->sourceContainerId)){
                $form->setDefault('importProducts', 'fromSource');
            }
        }

        if($data->action == 'changefields'){
            // При промяна да се показва поле за редакция на кешираните допълнителни условия от банковата сметка
            if($mvc->cacheAdditionalConditions){
                $exRec = $mvc->fetch($rec->id, 'additionalConditions,accountId', false);
                $defaultCondition = $exRec->additionalConditions[0];
                if($rec->accountId != $exRec->accountId){
                    if($rec->accountId){
                        $ownBankAccountId = bank_OwnAccounts::fetchField($rec->accountId, 'bankAccountId');
                        $lang = $rec->tplLang ?? doc_TplManager::fetchField($rec->template, 'lang');
                        $defaultCondition = bank_Accounts::getDocumentConditionFor($ownBankAccountId, 'sales_Sales', $lang);
                    } else {
                        $defaultCondition = null;
                    }
                }

                $form->FLD('additionalConditionsInput', "text(rows=3)", 'caption=Допълнително->Условия,input,changable,after=additionalInfo');
                $form->setDefault('additionalConditionsInput', $defaultCondition);
            }
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function inputInvoiceForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if (isset($rec->dueDate) && $rec->dueDate < $rec->date) {
                $form->setError('date,dueDate', 'Крайната дата за плащане трябва да е след вальора');
            }

            if (!$rec->displayRate) {
                $rec->displayRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, null);
                if (!$rec->displayRate) {
                    $form->setError('rate', 'Не може да се изчисли курс');
                }
            } else {
                if ($msg = currency_CurrencyRates::hasDeviation($rec->displayRate, $rec->date, $rec->currencyId, null)) {
                    $form->setWarning('displayRate', $msg);
                }
            }

            if(isset($rec->id) && isset($rec->displayRate) && $rec->type != 'dc_note'){
                // Предупреждение ако вальора е сменен, но курса е различен от очаквания
                $expectedRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, null);
                if(round($expectedRate, 5) != round($rec->displayRate, 5)){
                    $displayDate = dt::mysql2verbal($rec->date, 'd.m.Y');
                    $form->setWarning('displayRate', "Курсът е различен от очаквания за дата|* {$displayDate} : <b>{$expectedRate}</b>");
                }
            }

            $Vats = cls::get('drdata_Vats');
            $rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
            
            foreach ($mvc->fields as $fName => $field) {
                $mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
            }
            
            if (strlen($rec->contragentVatNo) && !strlen($rec->uicNo) && $rec->contragentClassId == crm_Companies::getClassId()) {
                $rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
            } elseif (!strlen($rec->contragentVatNo) && !strlen($rec->uicNo)) {
                if ($rec->contragentClassId != crm_Persons::getClassId()) {
                    $form->setError('contragentVatNo,uicNo', 'Трябва да е въведен поне един от номерата');
                } else {
                    $form->setWarning('contragentVatNo,uicNo', 'Сигурни ли сте, че не трябва да въведете поне един от номерата|*?');
                }
            }

            if (!empty($rec->contragentVatNo)) {
                if (!preg_match('/^[a-zA-Zа-яА-Я0-9_]*$/iu', $rec->contragentVatNo)) {
                    $form->setError('contragentVatNo', 'Лоши символи в номера');
                }
            }

            if ($rec->displayContragentClassId == 'newCompany') {
                $cRec = (object) array('name' => $rec->contragentName, 'country' => $rec->contragentCountryId, 'vatId' => $rec->contragentVatNo, 'uicId' => $rec->uicNo, 'pCode' => $rec->contragentPCode, 'place' => $rec->contragentPlace, 'address' => $rec->contragentAddress);
                $resStr = crm_Companies::getSimilarWarningStr($cRec);
                if ($resStr) {
                    $form->setWarning('contragentName,contragentCountryId,contragentVatNo,uicNo,contragentPCode,contragentPlace,contragentAddress', $resStr);
                }
            }

            // Проверка дали националния номер е валиден за държавата
            if ($rec->contragentClassId == crm_Companies::getClassId() && !empty($rec->uicNo)) {
                drdata_type_Uic::check($form, $rec->uicNo, $rec->contragentCountryId, 'uicNo');
            }

            // Ако е ДИ или КИ
            if ($rec->type != 'invoice') {
                if (isset($rec->changeAmount)) {
                    if ($rec->changeAmount == 0) {
                        $form->setError('changeAmount', 'Не може да се създаде известие с нулева стойност');
                        
                        return;
                    }
                    
                    if (isset($rec->id)) {
                        $Detail = cls::get($mvc->mainDetail);
                        if ($dCount = $Detail->count("#{$Detail->masterKey} = {$rec->id}")) {
                            $form->setWarning('changeAmount', "Към известието има|* <b>{$dCount}</b> |ред/а. Те ще бъдат изтрити ако оставите конкретна сума|*.");
                        }
                    }
                }
                
                if ((empty($rec->changeAmount) && empty($rec->dcChangeAmountDeducted)) && !empty($rec->dcReason)) {
                    $form->setError('changeAmount,dcReason', 'Не може да се зададе основание за увеличение/намаление ако не е посочена сума');
                }

                if(!empty($rec->changeAmount) && !empty($rec->dcChangeAmountDeducted)){
                    $form->setError('changeAmount,dcChangeAmountDeducted', 'Не може едновременно да се променя приспаднат аванс и да се задава обща сума');
                }

                $origin = doc_Containers::getDocument($rec->originId);
                $originRec = $origin->fetch('dpAmount,dpOperation,dealValue,date,dpVatGroupId');

                if (isset($rec->changeAmountVat)) {
                    $vat = $rec->changeAmountVat;
                } else {
                    if (($originRec->dpOperation == 'accrued' || $originRec->dpOperation == 'deducted') && isset($originRec->dpVatGroupId)){
                        $vat = acc_VatGroups::fetchField($originRec->dpVatGroupId, 'vat');
                    } else {
                        $vat = acc_Periods::fetchByDate()->vatRate;
                    }

                    // Ако не трябва да се начислява ддс, не начисляваме
                    if ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') {
                        $vat = 0;
                    }
                }
                
                if ($rec->date < $originRec->date) {
                    $oDate = dt::mysql2verbal($originRec->date, 'd.m.Y');
                    $form->setError('date', "Датата трябва да е по-голяма или равна на тази от оригиналната фактура|* <b>{$oDate}</b>");
                }
                
                if ($originRec->dpOperation == 'accrued' || isset($rec->changeAmount)) {
                    $rate = !empty($rec->displayRate) ? $rec->displayRate : $rec->rate;
                    $diff = ($rec->changeAmount * $rate);
                    $rec->vatAmount = $diff * $vat;
                    
                    // Стойността е променената сума
                    $rec->dealValue = $diff;
                }
            }
            
            if (!empty($rec->dueDate) && !empty($rec->dueTime)) {
                $cDate = dt::addSecs($rec->dueTime, $rec->date);
                $cDate = dt::verbal2mysql($cDate, false);
                if ($cDate != $rec->dueDate) {
                    $form->setError('date,dueDate,dueTime', 'Невъзможна стойност на датите');
                }
            }
            
            if (!empty($rec->vatReason)) {
                if (mb_strlen($rec->vatReason) < 15) {
                    $form->setError('vatReason', 'Основанието за ДДС трябва да е поне|* <b>15</b> |символа|*');
                } elseif (!preg_match('/[a-zA-Zа-яА-Я]/iu', $rec->vatReason)) {
                    $form->setError('vatReason', 'Основанието за ДДС трябва да съдържа букви');
                }
            }

            if(isset($rec->contragentClassId) && isset($rec->contragentId)){
                $cData = cls::get($rec->contragentClassId)->getContragentData($rec->contragentId);
                $ukCountryId = drdata_Countries::fetchField("#commonName = 'United Kingdom'");

                if($cData->countryId == $ukCountryId && empty($rec->contragentEori)){
                    $form->setWarning('contragentEori', 'За Великобритания, е препоръчително да има EORI №');
                }
            }
        }
        
        // Метод който да бъде прихванат от deals_plg_DpInvoice
        $form->rec->_edited = true;
        $mvc->inputDpInvoice($form);
    }
    
    
    /**
     * Кое е мястото на фактурата по подразбиране
     *
     * @param stdClass $rec
     *
     * @return string|null $place
     */
    public static function getDefaultPlace($rec)
    {
        $place = $countryId = null;
        $inCharge = doc_Folders::fetchField($rec->folderId, 'inCharge');
        $inChargeRec = crm_Profiles::getProfile($inCharge);

        // 1. От локацията на "Моята Фирма", избрана в Служебните данни на визитката на Отговорника на папката
        // 2. От избраното за екипа на отговорника на папката в "Персонализиране" на профила
        $locationId = !empty($inChargeRec->buzLocationId) ? $inChargeRec->buzLocationId : sales_Setup::get('DEFAULT_LOCATION_FOR_INVOICE');
        if (!empty($locationId)) {
            $locationRec = crm_Locations::fetch($locationId, 'place,countryId');
            $place = $locationRec->place;
            $countryId = $locationRec->countryId;
        }

        if(isset($rec->displayContragentClassId) && isset($rec->displayContragentId)){
            $cData = cls::get($rec->displayContragentClassId)->getContragentData($rec->displayContragentId);
        } else {
            $cData = doc_Folders::getContragentData($rec->folderId);
        }

        $contragentCountryId = $cData->countryId;
        if(!empty($place)){
            if ($contragentCountryId != $countryId) {
                $cCountry = drdata_Countries::fetchField($countryId, 'commonNameBg');
                $place .= ", {$cCountry}";
            }
        }

        // 3. От адреса на "Моята фирма"
        if(empty($place)){
            $ownCompanyId = core_Packs::isInstalled('holding') ? holding_plg_DealDocument::getOwnCompanyIdFromThread($rec) : null;
            $myCompany = crm_Companies::fetchOwnCompany($ownCompanyId);
            $place = $myCompany->place;
            if ($contragentCountryId != $myCompany->countryId) {
                $cCountry = drdata_Countries::fetchField($myCompany->countryId, 'commonNameBg');
                $place .= ", {$cCountry}";
            }
        }

        return $place;
    }
    
    
    /**
     * Преди запис в модела
     */
    protected static function beforeInvoiceSave($mvc, $rec)
    {
        if ($rec->type == 'dc_note') {
            if(!empty($rec->dcChangeAmountDeducted)){
                $rec->dpAmount = $rec->dcChangeAmountDeducted * $rec->rate;
                $rec->dpOperation = 'deducted';
            } elseif(isset($rec->changeAmount)) {
                $rec->dpAmount = null;
                $rec->dpOperation = 'none';
            }
        }

        if (!empty($rec->folderId)) {
            if (empty($rec->contragentClassId)) {
                $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
            }
            if (empty($rec->contragentId)) {
                $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
            }
        }

        if ($rec->state == 'active') {
            if (empty($rec->dueDate)) {

                if(isset($rec->paymentMethodId)){
                    if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
                        $aggregateInfo = $firstDocument->getAggregateDealInfo();
                        $plan = cond_PaymentMethods::getPaymentPlan($rec->paymentMethodId, $aggregateInfo->get('amount'), $rec->date);
                        if($plan['eventBalancePayment'] == 'invEndOfMonth' && !empty($plan['deadlineForBalancePayment'])){
                            $rec->dueDate = $plan['deadlineForBalancePayment'];
                        }
                    }
                }

                if (empty($rec->dueDate)) {
                    $dueTime = ($rec->dueTime) ? $rec->dueTime : sales_Setup::get('INVOICE_DEFAULT_VALID_FOR');

                    if ($dueTime) {
                        $rec->dueDate = dt::verbal2mysql(dt::addSecs($dueTime, $rec->date), false);
                    }
                }
            }
        }
        
        // Първоначално изчислен начин на плащане
        if (empty($rec->id)) {
            $rec->autoPaymentType = cls::get(get_called_class())->getAutoPaymentType($rec, false);
        }

        // Форсиране на нова фирма, ако е указано
        if ($rec->state == 'draft') {
            if ($rec->displayContragentClassId == 'newCompany') {
                $cRec = (object) array('name' => $rec->contragentName, 'country' => $rec->contragentCountryId, 'vatId' => $rec->contragentVatNo, 'uicId' => $rec->uicNo, 'pCode' => $rec->contragentPCode, 'place' => $rec->contragentPlace, 'address' => $rec->contragentAddress);
                crm_Companies::save($cRec);
                core_Statuses::newStatus("Добавена е нова фирма|* '{$rec->contragentName}'");

                $rec->displayContragentClassId = 'crm_Companies';
                $rec->displayContragentId = $cRec->id;
            }
        }

        // Ако е променено условието от банковата сметка - записва се
        if($mvc->cacheAdditionalConditions){
            if($rec->__isBeingChanged){
                if(md5(str::removeWhiteSpace($rec->additionalConditions[0])) != md5(str::removeWhiteSpace($rec->additionalConditionsInput))){
                    $rec->_changedCondition = true;
                }
                $rec->additionalConditions[0] = $rec->additionalConditionsInput;
            }
        }
    }
    
    
    /**
     * Намира автоматичния метод на плащане
     *
     * Проверява се какъв тип документи за плащане (активни) имаме в нишката.
     * Ако е бърза продажба е в брой.
     * Ако имаме само ПКО - полето е "В брой", ако имаме само "ПБД" - полето е "По банков път", ако имаме само Прихващания - полето е "С прихващане".
     * ако във фактурата имаме плащане с по-късна дата от сегашната - "По банка"
     * каквото е било плащането в предишната фактура на същия контрагент
     * ако по никакъв начин не може да се определи
     *
     * @param stdClass $rec - запис
     *
     * @return string - дефолтния начин за плащане в брой, по банка, с прихващане
     *                или NULL ако не може да бъде намерено
     */
    public function getAutoPaymentType($rec, $fromCache = true)
    {
        if ($this instanceof sales_Proformas) {
            return;
        }
        
        $rec = $this->fetchRec($rec);
        if ($fromCache === true) {
            $invoicePayments = core_Cache::get('threadInvoices1', "t{$rec->threadId}");
            if ($invoicePayments === false) {
                $invoicePayments = deals_Helper::getInvoicePayments($rec->threadId);
            }
        } else {
            $invoicePayments = deals_Helper::getInvoicePayments($rec->threadId);
        }
        
        $containerId = ($rec->type != 'dc_note') ? $rec->containerId : $rec->originId;
        
        $payments = $invoicePayments[$containerId]->payments;
        
        if (countR($payments) && isset($payments)) {
            $hasCash = array_key_exists('cash', $payments);
            $hasBank = array_key_exists('bank', $payments);
            $hasIntercept = array_key_exists('intercept', $payments);
            
            if ($hasCash === true && $hasBank === false && $hasIntercept === false) {
                return 'cash';
            }
            if ($hasBank === true && $hasCash === false && $hasIntercept === false) {
                return 'bank';
            }
            if ($hasIntercept === true && $hasCash === false && $hasBank === false) {
                return 'intercept';
            }
            if ($hasBank === true || $hasCash === true || $hasIntercept === true) {
                return 'mixed';
            }
        }
    }
    
    
    /**
     * Вербално представяне на фактурата
     */
    protected static function getVerbalInvoice($mvc, $rec, $row, $fields)
    {
        $row->rate = ($rec->displayRate) ? $row->displayRate : $row->rate;
        
        if ($rec->type == 'dc_note') {
            core_Lg::push($rec->tplLang);
            $row->type = ($rec->dealValue <= 0) ? tr('Кредитно известие') : tr('Дебитно известие');
            core_Lg::pop();
        }
        
        if (isset($fields['-list'])) {
            $row->number = ($rec->number) ? ht::createLink($row->number, $mvc->getSingleUrlArray($rec->id), null, "ef_icon={$mvc->getIcon()}") : $mvc->getLink($rec->id, 0);
            $total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
            $noVat = $rec->dealValue - $rec->discountAmount;

            $displayRate = !empty($rec->displayRate) ? $rec->displayRate : $rec->rate;
            $totalToVerbal = (!empty($displayRate)) ? $total / $displayRate : $total;
            $novatToVerbal = (!empty($displayRate)) ? $noVat / $displayRate : $noVat;
            $amountToVerbal = (!empty($displayRate)) ? $rec->vatAmount / $displayRate : $rec->vatAmount;
            
            $row->dealValue = $mvc->getFieldType('dealValue')->toVerbal($totalToVerbal);
            $row->valueNoVat = $mvc->getFieldType('dealValue')->toVerbal($novatToVerbal);
            $row->vatAmount = $mvc->getFieldType('dealValue')->toVerbal($amountToVerbal);
            
            $row->dealValue = ht::styleNumber($row->dealValue, $total);
            $row->valueNoVat = ht::styleNumber($row->valueNoVat, $total);
            $row->vatAmount = ht::styleNumber($row->vatAmount, $total);
        }
        
        if (empty($rec->paymentType) && isset($rec->autoPaymentType)) {
            $row->paymentType = $mvc->getFieldType('paymentType')->toVerbal($rec->autoPaymentType);
        }
        
        if ($fields['-single']) {
            $row->reff = deals_Helper::getYourReffInThread($rec->threadId);

            if(!in_array($rec->vatRate, array('yes', 'separate'))){
                if(empty($rec->vatReason)){
                    $vatReason = $mvc->getNoVatReason($rec);
                    if(!empty($vatReason)){
                        $row->vatReason = $vatReason;

                        if($rec->state == 'draft'){
                            if(!Mode::isReadOnly()){
                                $row->vatReason = "<span style='color:blue'>{$vatReason}</span>";
                            }
                            $row->vatReason = ht::createHint($row->vatReason, 'Основанието е определено автоматично. Ще бъде записано при активиране|*!', 'notice', false);
                        }
                    } else {
                        $bgId = drdata_Countries::getIdByName('Bulgaria');
                        if($rec->contragentCountryId == $bgId){
                            $row->vatReason = ht::createHint($row->vatReason, 'При неначисляване на ДДС на контрагент от "България", трябва да е посочено основание|*!', 'error');
                        }
                    }
                }
            }
            
            core_Lg::push($rec->tplLang);
            
            if ($rec->originId && $rec->type != 'invoice') {
                unset($row->deliveryPlaceId, $row->deliveryId);
            }

            if ($rec->displayContragentClassId == 'crm_Persons' || (doc_Folders::fetchCoverClassName($rec->folderId) == 'crm_Persons') && empty($rec->displayContragentId)) {
                $row->contragentUicCaption = tr('|ЕГН|*');
            } else {
                $row->contragentUicCaption = tr('ЕИК||TAX ID');
            }

            // Кой е съставителя и какъв е неговия ПИК
            $issuerId = $rec->issuerId;
            $row->username = deals_Helper::getIssuerRow($rec->username, $rec->createdBy, $rec->activatedBy, $rec->state, $issuerId);
            if (!empty($issuerId)) {
                $row->userCode = abs(crc32("{$issuerId}"));
                $row->userCode = substr($row->userCode, 0, 6);
            }
            
            if ($rec->type != 'invoice' && !($mvc instanceof sales_Proformas)) {
                $originRec = $mvc->getSourceOrigin($rec)->fetch();
                $originRow = $mvc->recToVerbal($originRec);
                $row->originInv = $originRow->number;
                if(!Mode::isReadOnly()){
                    $singleUrlArray = $mvc->getSingleUrlArray($originRec->id);
                    if(countR($singleUrlArray)){
                        $row->originInv = ht::createLink($originRow->number, $singleUrlArray);
                    }
                }

                $row->originInvDate = $originRow->date;
            }
            
            if ($rec->rate == 1) {
                unset($row->rate);
            }
            
            if (!$row->vatAmount) {
                $coreConf = core_Packs::getConfig('core');
                $pointSign = $coreConf->EF_NUMBER_DEC_POINT;
                $row->vatAmount = "<span class='quiet'>0" . $pointSign . '00</span>';
            }
            
            if ($rec->deliveryPlaceId) {
                $row->deliveryPlaceId = crm_Locations::getHyperlink($rec->deliveryPlaceId);
                if ($gln = crm_Locations::fetchField($rec->deliveryPlaceId, 'gln')) {
                    $row->deliveryPlaceId .= ', ' . $gln;
                }
            }
            
            // Ако не е въведена дата на даначно събитие, приема се, че е текущата
            if (empty($rec->vatDate)) {
                $row->vatDate = $mvc->getFieldType('vatDate')->toVerbal($rec->date);
            }
            
            foreach (array('contragentPlace', 'contragentAddress') as $cfld) {
                if (!empty($rec->{$cfld})) {
                    $row->{$cfld} = core_Lg::transliterate($row->{$cfld});
                }
            }
            
            if (empty($rec->dueDate)) {
                if(!empty($rec->dueTime)){
                    $dueDate = dt::addSecs($rec->dueTime, $rec->date);
                    $row->dueDate = $mvc->getFieldType('dueDate')->toVerbal($dueDate);
                } else {
                    if(isset($rec->paymentMethodId)){
                        $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
                        $aggregateInfo = $firstDocument->getAggregateDealInfo();

                        $plan = cond_PaymentMethods::getPaymentPlan($rec->paymentMethodId, $aggregateInfo->get('amount'), $rec->date);
                        if($plan['eventBalancePayment'] == 'invEndOfMonth'){
                            $rec->dueDate = $plan['deadlineForBalancePayment'];
                            $row->dueDate = $mvc->getFieldType('dueDate')->toVerbal($rec->dueDate);
                            $row->dueDate = ht::createHint("<span style='color:blue'>{$row->dueDate}</span>", "Според избрания метод на плащане. Ще бъде записан при контиране");
                        }
                    }

                    if (empty($rec->dueDate)) {
                        $defTime = ($mvc instanceof purchase_Invoices) ? purchase_Setup::get('INVOICE_DEFAULT_VALID_FOR') : sales_Setup::get('INVOICE_DEFAULT_VALID_FOR');
                        $dueTime = (isset($rec->dueTime)) ? $rec->dueTime : $defTime;
                        if ($dueTime) {
                            $dueDate = dt::verbal2mysql(dt::addSecs($dueTime, $rec->date), false);
                            $row->dueDate = $mvc->getFieldType('dueDate')->toVerbal($dueDate);
                            if (!$rec->dueTime) {
                                $time = cls::get('type_Time')->toVerbal($defTime);
                                $row->dueDate = ht::createHint("<span style='color:blue'>{$row->dueDate}</span>", "Според срока за плащане по подразбиране|*: {$time}. Ще бъде записан при контиране", 'notice', false);
                            }
                        }
                    }
                }
            }
            
            // Вербална обработка на данните на моята фирма и името на контрагента
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->containerId, $rec->contragentClassId, $rec->contragentId, $row->contragentName);
            foreach (array('MyCompany', 'MyAddress', 'MyCompanyEori', 'MyCompanyVatNo', 'uicId', 'contragentName') as $fld) {
                $row->{$fld} = $headerInfo[$fld];
            }
            
            if ($rec->paymentType == 'factoring') {
                $row->accountId = mb_strtoupper(tr('факторинг'));
                unset($row->bank);
                unset($row->bic);
            }
            
            if (!empty($rec->paymentType)) {
                $arr = array('cash' => 'в брой', 'bank' => 'по банков път', 'card' => 'с карта', 'factoring' => 'факторинг', 'intercept' => 'с прихващане');
                if ($rec->paymentType == 'postal') {
                    $row->paymentType = tr('Пощенски паричен превод');
                } else {
                    $row->paymentType = tr('Плащане ' . $arr[$rec->paymentType]);
                }

                if(in_array($rec->paymentType, array('postal', 'cash', 'card'))){
                    $row->BANK_BLOCK_CLASS = 'quiet saleBankBlock';
                }
            }
            
            if (haveRole('debug')) {
                if (isset($rec->autoPaymentType, $rec->paymentType) && ($rec->paymentType != $rec->autoPaymentType && !($rec->paymentType == 'card' && $rec->autoPaymentType == 'cash') && !($rec->paymentType == 'postal' && $rec->autoPaymentType == 'bank'))) {
                    $row->paymentType = ht::createHint($row->paymentType, 'Избрания начин на плащане не отговаря на реалния', 'warning');
                }
                $row->paymentType = ht::createHint($row->paymentType, "Автоматично '{$rec->autoPaymentType}'", 'img/16/bug.png');
            }

            if($rec->type == 'dc_note' && $rec->state != 'rejected'){
                if(!Mode::isReadOnly()){
                    $documents = deals_InvoicesToDocuments::getDocumentsToInvoices($rec->containerId, 'acc_ValueCorrections,store_Receipts,store_ShipmentOrders,sales_Services,purchase_Services');
                    if(!countR($documents)){
                        $string = "Към Дебитно/Кредитно известие ЗАДЪЛЖИТЕЛНО трябва да се създаде и втори документ: Корекция на стойности (при промяна само на стойността) или ЕН/СР/ПП (при промяна на количества)!
                           В полето \"Към фактура\" на създадения втори документ изберете настоящото Известие, за да премахнете това съобщение!";
                        $row->additionalInfo .= "<div class='invoiceNoteWarning'>" . tr($string) . "</div>";
                    }
                }
            }

            core_Lg::pop();

            // Показване на допълнителните условия от банковата сметка
            if($mvc->cacheAdditionalConditions){
                $conditions = $rec->additionalConditions;
                if (empty($conditions)) {
                    if (in_array($rec->state, array('pending', 'draft'))) {
                        if(!empty($rec->accountId)){
                            $ownBankAccountId = bank_OwnAccounts::fetchField($rec->accountId, 'bankAccountId');
                            $condition = bank_Accounts::getDocumentConditionFor($ownBankAccountId, 'sales_Sales', $rec->tplLang);
                            if (!empty($condition)) {
                                if (!Mode::isReadOnly()) {
                                    $condition = core_Type::getByName('richtext')->toVerbal($condition);
                                    $condition = "<span style='color:blue'>{$condition}</span>";
                                }
                                $condition = ht::createHint($condition, 'Ще бъде записано при активиране');
                                $conditions = array($condition);
                            }
                        }
                    }
                }
            }

            if (is_array($conditions)) {
                foreach ($conditions as $cond) {
                    if(!is_object($cond)){
                        $cond = core_Type::getByName('richtext')->toVerbal($cond);
                    }
                    $row->additionalInfo .= "\n" . $cond;
                }
            }
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $row = new stdClass();
        $me = cls::get(get_called_class());
        
        $singleTitle = $me->singleTitle;
        if ($me->getField('type', false)) {
            $singleTitle = $me->getVerbal($rec, 'type');
            if ($rec->type == 'dc_note') {
                $singleTitle = ($rec->dealValue <= 0) ? 'Кредитно известие' : 'Дебитно известие';
            }
        }
        
        $row->number = $me->getVerbal($rec, 'number');
        $num = ($row->number) ? $row->number : $rec->id;
        
        return tr("|{$singleTitle}|* №{$num}");
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return bgerp_iface_DealAggregator
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = $this->fetchRec($id);
        $total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
        $total = ($rec->type == 'credit_note') ? -1 * $total : $total;
        $dueDate = null;
        setIfNot($dueDate, $rec->dueDate, $rec->date);
        $aggregator->push('invoices', array('dueDate' => $dueDate, 'total' => $total, 'type' => $rec->type));
        $displayRate = ($rec->displayRate) ? $rec->displayRate : $rec->rate;
        $totalInDealRate = ($total / $displayRate) * $rec->rate;
        $aggregator->sum('invoicedAmount', $totalInDealRate);
        $aggregator->setIfNot('invoicedValior', $rec->date);

        if (isset($rec->dpAmount)) {
            $vat = acc_Periods::fetchByDate($rec->date)->vatRate;
            if(isset($rec->dpVatGroupId)){
                $vat = acc_VatGroups::fetchField($rec->dpVatGroupId, 'vat');
            }
            $dpVatId = $rec->dpVatGroupId ?? acc_VatGroups::getDefaultIdByDate($rec->date);
            if ($rec->dpOperation == 'accrued') {
                $aggregator->sum('downpaymentInvoiced', $totalInDealRate);

                $aggregator->sumByArrIndex('downpaymentAccruedByVats', $totalInDealRate, $dpVatId);
            } elseif ($rec->dpOperation == 'deducted') {

                // Колко е приспаднатото плащане с ддс
                $deducted = $rec->type == 'dc_note' ? $rec->dpAmount : abs($rec->dpAmount);
                $vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($deducted) * $vat : 0;
                $aggregator->sum('downpaymentDeducted', $deducted + $vatAmount);
                $aggregator->sumByArrIndex('downpaymentDeductedByVats', $deducted + $vatAmount, $dpVatId);
            }
        } else {

            // Ако е ДИ и КИ към ф-ра за начисляване на авансово плащане, променяме платения аванс по сделката
            if ($rec->type == 'dc_note') {
                $originRec = doc_Containers::getDocument($rec->originId)->fetch('dpOperation,dpVatGroupId,date');

                if ($originRec->dpOperation == 'accrued') {
                    $aggregator->sum('downpaymentInvoiced', $totalInDealRate);
                    $dpVatId = $originRec->dpVatGroupId ?? acc_VatGroups::getDefaultIdByDate($originRec->date);
                    $aggregator->sumByArrIndex('downpaymentAccruedByVats', $totalInDealRate, $dpVatId);
                }
            }
        }
        
        $Detail = $this->mainDetail;
        
        $dQuery = $Detail::getQuery();
        $dQuery->where("#invoiceId = '{$rec->id}'");
        
        // Намираме всички фактурирани досега продукти
        $invoiced = $aggregator->get('invoicedProducts');
        while ($dRec = $dQuery->fetch()) {
            $p = new stdClass();
            $p->productId = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->quantity = $dRec->quantity * $dRec->quantityInPack;
            
            // Добавяме към фактурираните продукти
            $update = false;
            if (countR($invoiced)) {
                foreach ($invoiced as &$inv) {
                    if ($inv->productId == $p->productId) {
                        $inv->quantity += $p->quantity;
                        $update = true;
                        break;
                    }
                }
            }
            
            if (!$update) {
                $invoiced[] = $p;
            }
        }
        
        $aggregator->set('invoicedProducts', $invoiced);
    }
    
    
    /**
     * След подготовка на авансова ф-ра
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
    }
    
    
    /**
     * Кешира информация за оригиналните стойностти на детайлите на известието
     */
    public function getInvoiceDetailedInfo($containerId, $applyDiscount = false)
    {
        expect($document = doc_Containers::getDocument($containerId));
        expect($document->isInstanceOf($this), $document->className, $this->className);
        $vatExceptionId = cond_VatExceptions::getFromThreadId($document->fetchField('threadId'));

        $vats = $cacheIds = array();
        $Detail = $this->mainDetail;
        $query = $Detail::getQuery();
        $docRec = $document->fetch('dpAmount,dpVatGroupId,vatRate,rate');

        $query->where("#{$this->{$Detail}->masterKey} = '{$document->that}'");
        $query->orderBy('id', 'ASC');

        $count = 1;
        while ($dRec = $query->fetch()) {
            if($applyDiscount){
                $price = empty($dRec->discount) ? $dRec->packPrice : ($dRec->packPrice * (1 - $dRec->discount));
            } else {
                $price = $dRec->packPrice;
            }
            $price = round($price, 5);

            $cacheIds[$dRec->id] = array('quantity' => $dRec->quantity, 'price' => $price, 'count' => $count, 'productId' => $dRec->productId, 'packagingId' => $dRec->packagingId);
            $v = 0;
            if ($docRec->vatRate != 'no' && $docRec->vatRate != 'exempt') {
                $v = cat_Products::getVat($dRec->productId, $document->fetchField('date'), $vatExceptionId);
            }
            $vats["{$v}"] = $v;
            $count++;
        }

        if (!empty($docRec->dpAmount)) {
            $vRate = isset($docRec->dpVatGroupId) ? acc_VatGroups::fetchField($docRec->dpVatGroupId, 'vat') : 0.2;
            $v = ($docRec->vatRate == 'yes' || $docRec->vatRate == 'separate') ? $vRate : 0;
            $vats["{$v}"] = $v;
        }

        $res = (object) array('vats' => $vats, 'recWithIds' => $cacheIds);

        return $res;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Не може да се оттеглят документи, към които има създадени КИ и ДИ
        if ($action == 'reject' && isset($rec)) {
            if (!($mvc instanceof sales_Proformas)) {
                if ($mvc->fetch("#originId = '{$rec->containerId}' AND #state = 'active'")) {
                    $res = 'no_one';
                }
            }

            if ($rec->state == 'active' && !($mvc instanceof sales_Proformas)) {
                $dayForInvoice = acc_Setup::get('DATE_FOR_INVOICE_DATE');
                $monthValior = dt::mysql2verbal($rec->date, 'm.y');
                $monthNow = dt::mysql2verbal(dt::today(), 'm.y');
                $dateNow = dt::mysql2verbal(dt::today(), 'd');

                // вальорът на фактурата не от текущия месец
                // в текущия месец текущата дата е >= на датата от константата "Ден от месеца за изчисляване на Счетоводна дата на входяща фактура" в пакета асс
                if($monthValior != $monthNow && $dateNow >= $dayForInvoice){
                    if (!haveRole('ceo,accMaster', $userId)) {
                        $res = 'no_one';
                    }
                }
            }
        }

        // Ако възстановяваме известие и оригиналът му е оттеглен, не можем да го възстановим
        if ($action == 'restore' && isset($rec)) {
            if (isset($rec->type) && $rec->type != 'invoice') {
                if ($mvc->fetch("#containerId = {$rec->originId} AND #state = 'rejected'")) {
                    $res = 'no_one';
                }
            }
        }

        // Към ф-ра не можем да правим корекция, трябва да направим КИ или ДИ
        if ($action == 'correction' && isset($rec)) {
            $res = 'no_one';
        }

        // Може да се генерира фактура само в нишка с начало сделка, или от друга фактура
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            $state = $origin->rec()->state;
            if ($state != 'active') {
                $res = 'no_one';
            } else {
                if (!($origin->getInstance() instanceof deals_DealMaster || $origin->getInstance() instanceof deals_InvoiceMaster || $origin->getInstance() instanceof findeals_AdvanceReports || $origin->getInstance() instanceof sales_Proformas)) {
                    $res = 'no_one';
                }
            }
        }

        if ($action == 'add' && isset($rec->sourceContainerId)) {
            $Source = doc_Containers::getDocument($rec->sourceContainerId);
            if (!$Source->haveInterface('deals_InvoiceSourceIntf')) {
                $res = 'no_one';
            } else {
                $sourceState = $Source->fetchField('state');
                if ($Source->isInstanceOf('deals_InvoiceMaster')) {
                    $boolRes = $sourceState != 'active';
                } else {
                    $boolRes = $sourceState != 'active' && $sourceState != 'draft' && $sourceState != 'pending';
                }

                if ($boolRes) {
                    $res = 'no_one';
                }
            }
        }

        // Не може да се контира КИ и ДИ, ако оригиналната фактура е оттеглена
        if ($action == 'conto' && isset($rec)) {
            if ($res != 'no_one') {
                if ($rec->type == 'dc_note') {
                    $origin = doc_Containers::getDocument($rec->originId);
                    if ($origin->fetchField('state') == 'rejected') {
                        $res = 'no_one';
                    }
                }
            }
        }
    }


    /**
     * Намира ориджина на фактурата (ако има)
     */
    public static function getOrigin($rec)
    {
        $origin = null;
        $rec = static::fetchRec($rec);

        if ($rec->originId) {
            return doc_Containers::getDocument($rec->originId);
        }
        if ($rec->threadId) {
            return doc_Threads::getFirstDocument($rec->threadId);
        }

        return $origin;
    }


    /**
     * Кой е източника на фактурата
     */
    public static function getSourceOrigin($rec)
    {
        $rec = static::fetchRec($rec);
        if ($rec->sourceContainerId) {
            return doc_Containers::getDocument($rec->sourceContainerId);
        }

        return static::getOrigin($rec);
    }


    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed               $id     - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @param string $strategy - стратегия за намиране
     *
     * @return array $details - масив с артикули готови за запис
     *               o productId      - ид на артикул
     *               o packagingId    - ид на опаковка/основна мярка
     *               o quantity       - количество опаковка
     *               o quantityInPack - количество в опаковката
     *               o discount       - отстъпка
     *               o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc, $strategy)
    {
        $details = array();
        $rec = static::fetchRec($id);

        // Ако начисляваме аванс или има въведена нова стойност не се копират детайлите
        if ($rec->dpOperation == 'accrued') {
            return $details;
        }

        $Detail = cls::get($this->mainDetail);
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = '{$rec->id}'");
        $query->orderBy('id', 'ASC');
        $isProforma = ($this instanceof sales_Proformas);

        while ($dRec = $query->fetch()) {
            if(!empty($dRec->discount) && (!$isProforma)){
                $dRec->price = $dRec->price * (1 - $dRec->discount);
                $dRec->amount = $dRec->price * $dRec->quantity;
                $dRec->packPrice = $dRec->price * $dRec->quantityInPack;
                unset($dRec->discount);
            }

            if(!($this instanceof sales_Proformas)){
                $dRec->clonedFromDetailId = $dRec->id;
            }

            unset($dRec->id);
            unset($dRec->{$Detail->masterKey});
            unset($dRec->createdOn);
            unset($dRec->createdBy);
            $details[] = $dRec;
        }

        return $details;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!countR($data->rows)) {
            return;
        }
        $data->listTableMvc->FNC('valueNoVat', 'int');
        
        if (Mode::is('printing')) {
            unset($data->pager);
        }
    }
    
    
    /**
     * Оттегляне на документ
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }
    
    
    /**
     * Възстановяване на оттеглен документ
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param int      $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }
    
    
    /**
     * Преди експортиране като CSV
     */
    public static function on_BeforeExportCsv($mvc, &$recs)
    {
        if (!$recs) return ;
        
        $fields = $mvc->selectFields();
        $fields['-list'] = true;

        foreach ($recs as &$rec) {
            $rate = !empty($rec->displayRate) ? $rec->displayRate : $rec->rate;
            $rec->number = $mvc->getVerbal($rec, 'number');
            $rec->dealValue = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
            $rec->dealValue = !empty($rate) ? $rec->dealValue / $rate : $rec->dealValue;
            $rec->vatAmount = !empty($rate) ? $rec->vatAmount / $rate : $rec->vatAmount;
            $rec->dealValueWithoutDiscount = $rec->dealValue - $rec->vatAmount;
        }
    }
    
    
    /**
     * След подготвяне на заявката за експорт
     */
    public static function on_AfterPrepareExportQuery($mvc, &$query)
    {
        // Искаме освен фактурите показващи се в лист изгледа да излизат и тези,
        // които са били активни, но сега са оттеглени
        $query->where("#state != 'draft' OR (#state = 'rejected' AND #brState = 'active')");
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    protected static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if ($part == 'number') {
            if (!empty($rec->number)) {
                $number = core_Type::getByName('varchar')->toVerbal($rec->number);
                $number = str_pad($number, 10, '0', STR_PAD_LEFT);
                
                $num = $number;
            }
        }
    }


    /**
     * Може ли документа да се добавя като свързан документ към оридижина си
     */
    public static function canAddDocumentToOriginAsLink_($rec)
    {
        return $rec->type == 'dc_note';
    }


    /**
     * Какво да е основанието за неначисляване на ДДС
     *
     * @param stdClass $rec  - запис на фактурата
     * @return string|null
     */
    protected function getNoVatReason($rec)
    {
        if(!$this->isOwnCompanyVatRegistered($rec)) {

            return acc_Setup::get('VAT_REASON_MY_COMPANY_NO_VAT');
        }

        $bgCountryId = drdata_Countries::getIdByName('Bulgaria');
        if($rec->contragentCountryId != $bgCountryId){
            $reason = drdata_Countries::isEu($rec->contragentCountryId) ? 'VAT_REASON_IN_EU' : 'VAT_REASON_OUTSIDE_EU';

            return acc_Setup::get($reason);
        }

        return null;
    }


    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);

        if($mvc->cacheAdditionalConditions){
            if (empty($rec->additionalConditions)) {
                if(!empty($rec->accountId)) {
                    $ownBankAccountId = bank_OwnAccounts::fetchField($rec->accountId, 'bankAccountId');
                    $lang = $rec->tplLang ?? doc_TplManager::fetchField($rec->template, 'lang');
                    $condition = bank_Accounts::getDocumentConditionFor($ownBankAccountId, 'sales_Sales', $lang);
                    $rec->additionalConditions = array($condition);
                    $saveFields[] = 'additionalConditions';
                }
            }
        }

        // Кеширане на ид-то на съставителя
        if(empty($rec->issuerId)){
            $issuerId = null;
            $mvc->pushTemplateLg($rec->template);
            $rec->username = transliterate(deals_Helper::getIssuer($rec->createdBy, $rec->activatedBy, $issuerId));
            core_Lg::pop();
            $rec->issuerId = $issuerId;
            $saveFields[] = 'username';
            $saveFields[] = 'issuerId';
        }

        if(!in_array($rec->vatRate, array('yes', 'separate'))) {
            if (empty($rec->vatReason)) {
                $vatReason = $mvc->getNoVatReason($rec);
                if(!empty($vatReason)){
                    $rec->vatReason = $vatReason;
                    $saveFields[] = 'vatReason';
                }
            }
        }

        if(countR($saveFields)){
            $mvc->save_($rec, $saveFields);
        }

        // Ако има посочен параметър за информация към фактурата от артикула
        $Detail = cls::get($mvc->mainDetail);
        $saveRecs = array();
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
        $dQuery->show('productId,notes,discount,autoDiscount');
        while($dRec = $dQuery->fetch()){
            $save = false;

            // Ако има посочен параметър за информация за фактура - ще се записва в забележките
            if(isset($Detail->productInvoiceInfoParamName)){
                $invoiceInfo = cat_Products::getParams($dRec->productId, $Detail->productInvoiceInfoParamName);
                if(!empty($invoiceInfo)){
                    if (strpos($dRec->notes, "{$invoiceInfo}") === false) {
                        $dRec->notes = $invoiceInfo . ((!empty($dRec->notes) ? "\n" : '') . $dRec->notes);
                        $save = true;
                    }
                }
            }

            // Ако има ръчна отстъпка или авт. остъпка - ще се записва осреднената и ще се запомни ръчната
            if(!empty($dRec->discount) || !empty($dRec->autoDiscount)){
                $dRec->inputDiscount = $dRec->discount;
                if(isset($dRec->autoDiscount)){
                    if(isset($dRec->discount)){
                        $dRec->discount = round((1 - (1 - $dRec->discount) * (1 - $dRec->autoDiscount)), 6);
                    } else {
                        $dRec->discount = $dRec->autoDiscount;
                    }
                }
                $save = true;
            }

            if($save){
                $saveRecs[] = $dRec;
            }
        }

        if(countR($saveRecs)){
            $Detail->saveArray($saveRecs, 'id,notes,discount,inputDiscount');
        }

        // Има ли полета, чиито стойности да се преизчислят при активиране
        $cacheFields = $Detail->getFieldsToCalcOnActivation($rec);

        if(countR($cacheFields)){
            $saveDetails = array();
            $updateFields = implode(',', $cacheFields);

            // Извличат се детайлите
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            while($dRec = $dQuery->fetch()){
                $params = cat_Products::getParams($dRec->productId);
                if($Detail->calcFieldsOnActivation($dRec, $rec, $params)){
                    $saveDetails[] = $dRec;
                }
            }

            if(countR($saveDetails)){
                $Detail->saveArray($saveDetails, "id,{$updateFields}");
            }
        }
    }


    /**
     * Кои полета да се ъпдейтнат във визитката след промяна
     */
    public function getContragentCoverFieldsToUpdate($rec)
    {
        $Cover = doc_Folders::getCover($rec->folderId);
        Mode::push('htmlEntity', 'none');
        $name = $Cover->getVerbal('name');
        Mode::pop('htmlEntity');

        if($name != $rec->contragentName) return array();

        return arr::make(static::$updateContragentdataField, true);
    }


    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    }


    /**
     * След взимане на полетата за експорт в csv
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        $fieldset->setFieldType('dealValueWithoutDiscount', 'double');
    }


    /**
     * Как се казва политиката
     *
     * @param stdClass $rec - запис
     * @return array
     *            [rate]       - валутен курс
     *            [valior]     - вальор
     *            [currencyId] - код на валута
     *            [chargeVat]  - режим на начисляване на ДДС
     *            [amount]     - сума в основна валута без ддс и отстъпка
     */
    public function getTotalDiscountSourceData($rec)
    {
        $rec = $this->fetchRec($rec);

        $amount = core_Math::roundNumber($rec->dealValue - $rec->discountAmount);
        $res = (object)array('rate' => $rec->rate,
            'valior'     => $rec->valior,
            'currencyId' => $rec->currencyId,
            'chargeVat'  => 'separate',
            'amount'     => $amount,
        );

        return $res;
    }


    /**
     * Как се казва политиката
     *
     * @param stdClass $rec - запис
     * @return bool
     */
    public function canHaveTotalDiscount($rec)
    {
        $rec = $this->fetchRec($rec);
        if($rec->type){
            if($rec->type != 'invoice' || $rec->dpOperation == 'accrued') return false;
        }

        $Detail = cls::get($this->mainDetail);
        $detailCount = $Detail->count("#{$Detail->masterKey} = {$rec->id}");

        return !empty($detailCount);
    }


    /**
     * След обновяване на мастъра
     *
     * @param mixed $id - ид/запис на мастъра
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id, $save = true)
    {
        // Ако е зададено в мода да не се рекалкулират отстъпките
        $rec = $mvc->fetchRec($id);
        if(isset($rec->type) && $rec->type != 'invoice') return;
        if(!$mvc->hasPlugin('price_plg_TotalDiscount')) return;

        // Преизчисляване ако има автоматични отстъпки
        if($mvc->recalcAutoTotalDiscount($rec)){
            $mvc->updateMaster_($rec);
        }
    }


    /**
     * След подготовка на заявката за извличане на стойността от последна фактура
     * @see cond_plg_DefaultValues
     *
     * @param core_Mvc $mvc
     * @param core_Query $query
     * @param int $folderId
     * @param string $name
     * @param int|null $fromUser
     * @return void
     */
    protected static function on_AfterGetQueryFromLastDocumentDefault($mvc, &$query, $folderId, $name, $fromUser = null)
    {
        // Определени полета да се взимат от последната ф-ра САМО ако е за същия контрагент
        if(!in_array($name, arr::make('contragentCountryId,contragentVatNo,contragentEori,uicNo,contragentPCode,contragentPlace,contragentAddress'))) return;
        $Cover = doc_Folders::getCover($folderId);
        $query->where("#displayContragentId IS NULL OR (#displayContragentClassId = '{$Cover->className}' AND #displayContragentId = {$Cover->that})");
    }


    /**
     * Да се изисква ли основание за неначисляване на ДДС, ако няма
     *
     * @param stdClass $rec
     * @param array $productArr
     * @return false|string
     */
    public function doRequireVatReasonWhenTryingToPost($rec, $productArr)
    {
        // Ако има зададено основание - няма да се прави нищо
        $rec = $this->fetchRec($rec);
        if(!empty($rec->vatReason)) return false;

        // Ако е без или освободено от ДДС
        if(!in_array($rec->vatRate, array('yes', 'separate'))){
            $calcedVatReason = $this->getNoVatReason($rec);
            if(!empty($calcedVatReason)) return false;

            $bgId = drdata_Countries::getIdByName('Bulgaria');
            if($rec->contragentCountryId == $bgId){

                return 'При неначисляване на ДДС на контрагент от "България", трябва да е посочено основание|*!';
            }
        } else {

            if(isset($rec->changeAmount) && $rec->type == 'dc_note' && empty($rec->vatAmount)){

                return 'При известие с нулева ставка, трябва да е посочено основание за неначисляване на ДДС|*!';
            }

            // Ако има аванс и той е с нулева ставка
            if(!empty($rec->dpAmount) && isset($rec->dpVatGroupId)){
                $vatGroupPercent = acc_VatGroups::fetchField($rec->dpVatGroupId, 'vat');
                if(empty($vatGroupPercent)){

                    return 'При аванс с нулева ставка, трябва да е посочено основание за неначисляване на ДДС|*!';
                }
            }

            // Ако има артикули и поне един от тях е с нулева ставка
            if(countR($productArr)){
                $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
                $productsWithZeroVat = cat_products_VatGroups::getByVatPercent(0, $rec->date, $productArr, $vatExceptionId);
                if(countR($productsWithZeroVat)){

                    return 'При участие на артикули с нулева ставка, трябва да е посочено основание за неначисляване на ДДС|*!';
                }
            }
        }

        // Няма да се изисква, ако се стигне до тук
        return false;
    }
}
