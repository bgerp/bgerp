<?php


/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Договори, чакащи за задание
 */
class sales_reports_PurBomsRep extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'cat,ceo,sales,purchase';
    
    
    /**
     * Дилърите
     *
     * @var array
     */
    private static $dealers = array();
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields = 'deliveryTime';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'containerId';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'containerId';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title,single=none');
        $fieldset->FLD('precision', 'percent(min=0,max=1)', 'caption=Авансово платено,unit=и нагоре,after=dealers,remember');
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
        
        // Всички активни потебители
        $uQuery = core_Users::getQuery();
        $uQuery->where("#state = 'active'");
        $uQuery->orderBy('#names', 'ASC');
        $uQuery->show('id');
        
        // Които са търговци
        $roles = core_Roles::getRolesAsKeylist('ceo,sales');
        $uQuery->likeKeylist('roles', $roles);
        $allDealers = arr::extractValuesFromArray($uQuery->fetchAll(), 'id');
        
        // Към тях се добавят и вече избраните търговци
        if (isset($form->rec->dealers)) {
            $dealers = keylist::toArray($form->rec->dealers);
            $allDealers = array_merge($allDealers, $dealers);
        }
        
        // Вербализират се
        $suggestions = array();
        foreach ($allDealers as $dealerId) {
            $suggestions[$dealerId] = core_Users::fetchField($dealerId, 'nick');
        }
        
        // Задават се като предложение
        $form->setSuggestions('dealers', $suggestions);
        
        // Ако текущия потребител е търговец добавя се като избран по дефолт
        if (haveRole('sales') && empty($form->rec->id)) {
            $form->setDefault('dealers', keylist::addKey('', core_Users::getCurrent()));
        }
        
        // Опит за намиране на точноста от последната създадена съща тасправка от потребителя
        $cu = core_Users::getCurrent();
        $lQuery = $Embedder->getQuery();
        $lQuery->where("#{$Embedder->driverClassField} = {$Driver->getClassId()} AND #createdBy = {$cu} AND #state = 'active'");
        $lQuery->orderBy('id', 'DESC');
        $lQuery->limit(1);
        $lastReportRec = $lQuery->fetch();
        
        // Дефолтната точност е от предишния отчет или глобален дефолт
        $defaultPrecision = (!empty($lastReportRec->precision)) ? $lastReportRec->precision : 0.95;
        $form->setDefault('precision', $defaultPrecision);
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
        $recs = array();
        $salArr = array();
        $Sales = cls::get('sales_Sales');
        $dealers = keylist::toArray($rec->dealers);
        $count = 1;
        
        // Всички чакащи и активни продажби на избраните дилъри
        $sQuery = sales_Sales::getQuery();
        $sQuery->where("#state = 'active'");
        
        if (countR($dealers)) {
            $sQuery->in('dealerId', $dealers);
        }
        
        // За всяка
        while ($sRec = $sQuery->fetch()) {
            
            // Взимане на договорените и експедираните артикули по продажбата (събрани по артикул)
            $dealerId = ($sRec->dealerId) ? $sRec->dealerId : (($sRec->activatedBy) ? $sRec->activatedBy : $sRec->createdBy);
            $dealInfo = $Sales->getAggregateDealInfo($sRec);
            
            $delTime = (!empty($sRec->deliveryTime)) ? $sRec->deliveryTime : (!empty($sRec->deliveryTermTime) ?  dt::addSecs($sRec->deliveryTermTime, $sRec->valior) : null);
            if (empty($delTime)) {
                $delTime = $Sales->getMaxDeliveryTime($sRec->id);
                $delTime = ($delTime) ? dt::addSecs($delTime, $sRec->valior) : $sRec->valior;
            }
            
            // Колко е очакваното авансово плащане
            $downPayment = $dealInfo->agreedDownpayment;
            
            // Колко е очакваното платено
            $downpayment = $dealInfo->downpayment;
            
            // колко е платено
            $downpaymentAmount = $dealInfo->amountPaid;
            
            if (empty($downpayment)) {
                $dPayment = $downpaymentAmount;
            } else {
                $dPayment = $downpayment;
            }
            
            // ако имаме зададено авансово плащане
            // дали имаме поне 95% авансово плащане
            if (isset($rec->precision)) {
                if ($dPayment < $downPayment * $rec->precision) {
                    continue;
                }
            } else {
                if ($dPayment < $downPayment * 0.95) {
                    continue;
                }
            }
            
            // Артикулите
            $agreedProducts = $dealInfo->get('products');
            
            // За всеки договорен артикул
            foreach ($agreedProducts as $pId => $pRec) {
                // ако е нестандартен
                $productRec = cat_Products::fetch($pId, 'canManifacture,isPublic,nameEn');
                
                if ($sRec->closedDocuments != null) {
                    $newKeylist = keylist::addKey($sRec->closedDocuments, $sRec->id);
                    $salesArr = keylist::toArray($newKeylist);
                    $salesSrt = implode(',', $salesArr);
                }
                
                // Ако артикула е нестандартен и няма задание по продажбата
                // артикула да е произведим
                if ($productRec->isPublic == 'no' && $productRec->canManifacture == 'yes') {
                    if (is_array($salesArr)) {
                        if (in_array($sRec->id, $salesArr)) {
                            $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId IN ({$salesSrt})");
                        } else {
                            $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId = {$sRec->id} ");
                        }
                    } else {
                        $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId = {$sRec->id}");
                    }
                    
                    $jobState = null;
                    $jobQuantity = null;
                    if (isset($jobId)) {
                        $jobState = planning_Jobs::fetchField("#id = {$jobId}", 'state');
                        $jobQuantity = planning_Jobs::fetchField("#id = {$jobId}", 'quantity');
                    }
                    
                    if (!$jobId || ($jobState == 'draft' || $jobState == 'rejected') || $jobQuantity < $pRec->quantity * 0.90) {
                        $index = $sRec->id . '|' . $pId;
                        $d = (object) array('num' => $count,
                            'containerId' => $sRec->containerId,
                            'pur' => $sRec->id,
                            'purDate' => $sRec->valior,
                            'deliveryTime' => $delTime,
                            'article' => $pId,
                            'dealerId' => $dealerId,
                            'quantity' => $pRec->quantity);
                        
                        if ($pId == $d->article) {
                            $recs[$index] = $d;
                        }
                        
                        $count++;
                    }
                }
            }
        }
        
        return $recs;
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
        
        $fld->FLD('pur', 'varchar', 'caption=Договор->№');
        $fld->FLD('purDate', 'date', 'caption=Договор->Дата');
        $fld->FLD('dealerId', 'key(mvc=core_Users,select=nick)', 'caption=Търговец,smartCenter');
        if ($export === true) {
            $fld->FLD('code', 'varchar', 'caption=Код');
        }
        $fld->FLD('article', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('quantity', 'double', 'caption=Количество');
        $fld->FLD('deliveryTime', 'datetime', 'caption=Доставка');
        
        return $fld;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->code = cat_Products::fetchField($dRec->article, 'code');
        $res->code = ($res->code) ? $res->code : "Art{$dRec->article}";
        $res->pur = '#' . sales_Sales::getHandle($dRec->pur);
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
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');
        $row = new stdClass();
        
        // Линк към дилъра
        if (!array_key_exists($dRec->dealerId, self::$dealers)) {
            self::$dealers[$dRec->dealerId] = crm_Profiles::createLink($dRec->dealerId);
        }
        
        if (isset($dRec->dealerId)) {
            $row->dealerId = self::$dealers[$dRec->dealerId];
        }
        
        if (isset($dRec->num)) {
            $row->num = $Int->toVerbal($dRec->num);
        }
        
        if (isset($dRec->deliveryTime)) {
            $row->deliveryTime = dt::mysql2verbal($dRec->deliveryTime);
        }
        
        if (isset($dRec->pur)) {
            $row->pur = sales_Sales::getLink($dRec->pur, 0);
        }
        
        if (isset($dRec->purDate)) {
            $row->purDate = $Date->toVerbal($dRec->purDate);
        }
        
        if (isset($dRec->article)) {
            $row->article = cat_Products::getShortHyperlink($dRec->article);
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }
        
        return $row;
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
        if (isset($rec->precision) && $rec->precision != 1) {
            $row->precision .= ' ' . tr('+');
        }
        
        $dealers = keylist::toArray($rec->dealers);
        foreach ($dealers as $userId => &$nick) {
            $nick = crm_Profiles::createLink($userId)->getContent();
        }
        
        $row->dealerId = implode(', ', $dealers);
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
							            <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
							        </div>
							    </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->dealers)) {
            $fieldTpl->append($data->row->dealerId, 'dealers');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
}
