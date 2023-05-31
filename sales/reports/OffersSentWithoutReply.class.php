<?php


/**
 * Мениджър на отчети за изпратени оферти без отговор
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Изпратени оферти без отговор
 */
class sales_reports_OffersSentWithoutReply extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, admin, debug, sales';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('periodStart', 'time(suggestions=|1 ден|3 дена|1 седмица|1 месец)', 'caption=Период->Старт, after=title,mandatory,single=none,removeAndRefreshForm');
        $fieldset->FLD('periodEnd', 'time(suggestions=1 седмица|2 седмици|3 седмици|1 месец|3 месеца)', 'caption=Период->Край, after=periodStart,mandatory,single=none,removeAndRefreshForm');

        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци->Търговци,placeholder=Всички,single=none,mandatory,after=periodEnd');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditFpassiveStartorm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $suggestions = array();
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('periodPassive', '6 месеца');

        $form->setDefault('periodActive', '2 години');


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

        $recs = $foldersArr = $incomingMailsArr = $outgoingMailsArr = array();

        $periodStart = dt::addSecs(-$rec->periodStart, dt::today(), false);
        $periodEnd = dt::addSecs(-$rec->periodEnd, $periodStart, false);

        //Определяме папките $foldersArr чиито отговорници са избраните дилъри
        $fQuery = doc_Folders::getQuery();
        $fQuery->in('state', array('rejected', 'draft'), true);

        $fQuery->in('inCharge',type_UserList::toArray($rec->dealers));

        while ($fRec = $fQuery->fetch()){
            if(!in_array($fRec->inCharge,array_keys($foldersArr))){
                $foldersArr[$fRec->inCharge] = array($fRec->id);
                $foldersforChek[$fRec->id] = $fRec->id;
            }else{

                array_push($foldersArr[$fRec->inCharge],$fRec->id);
                $foldersforChek[$fRec->id] = $fRec->id;
            }

        }

       // $foldersforChek = arr::extractValuesFromArray($fQuery->fetchAll(),'id');

        //Определяме последния изходящ имейл в тези папки
        //Изходящи имейли през пасивния период
        $mOutQuery = email_Outgoings::getQuery();
        $mOutQuery->in('folderId',$foldersforChek);
        $mOutQuery->where(array(
            "#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'",
            $periodEnd . ' 00:00:00', $periodStart . ' 23:59:59'));
        $mOutQuery->where("#searchKeywords REGEXP ' [Q|q][0-9]+'");

        while ($emOutRec = $mOutQuery->fetch()) {

            if(!in_array($emOutRec->folderId,array_keys($outgoingMailsArr))){
                $outgoingMailsArr[$emOutRec->folderId] = $emOutRec;
            }else{
                if($emOutRec->createdOn > $outgoingMailsArr[$emOutRec->folderId]->createdOn){
                    $outgoingMailsArr[$emOutRec->folderId] = $emOutRec;
                }
            }

        }
//bp($outgoingMailsArr);
        //Намира се последния изходящ мейл във всяка папка за избраните дилъри

        foreach ($foldersArr as $dil => $fId){
            foreach ($outgoingMailsArr as $email){
                if(in_array($email->folderId,$fId)){

                    $lastOutEmails[$dil] = $email;

                }


            }

            bp($lastOutEmails,$dil,$fId,$outgoingMailsArr);



        }

        bp($outgoingMailsArr,$foldersArr);



//
//        while ($shRec = $shQuery->fetch()) {
//
//            $id = $shRec->folderId;
//
//            $firstDoc = doc_Threads::getFirstDocument($shRec->threadId);
//
//            if (!(cls::get($firstDoc) instanceof sales_Sales)) continue;
//
//            //филтър по дилър
//            if (!in_array(-1,keylist::toArray($rec->dealers))){
//                $docDealer = $firstDoc->fetch()->dealerId;
//                if(!in_array($docDealer,keylist::toArray($rec->dealers))) continue;
//            }
//
//            //филтър по група на контрагента на експедицията
//            if ($rec->crmGroup) {
//                $checkContragentsGroups = keylist::toArray($rec->crmGroup);
//                $contragentsGroups = keylist::toArray(doc_Folders::getContragentData($shRec->folderId)->groupList);
//
//                if (countR(array_intersect($checkContragentsGroups, $contragentsGroups)) == 0) continue;
//
//            }
//
//            //отделяме експедициите с вальор преди началото на пасивния период и записваме
//            // $shipmentActivContragents масив активни клиенти(които имат експедиции в активния период)
//            if ($shRec->valior < $passivePeriodStart) {
//                if (!array_key_exists($id, $shipmentActivContragents)) {
//                    $shipmentActivContragents[$id] = (object)array(
//                        'folderId' => $shRec->folderId,
//                        'amountDelivered' => $shRec->amountDelivered,
//                        'numberOfSales' => 1,
//                        'numberOfInMails' => '',
//                        'numberOfOutMails' => '',
//                    );
//                } else {
//                    $obj = &$shipmentActivContragents[$id];
//                    $obj->amountDelivered += $shRec->amountDelivered;
//                    $obj->numberOfSales++;
//                }
//            }
//
//            //отделяме експедициите с вальор след началото на пасивния период и записваме
//            // $shipmentPassActivContragents масив клиенти, които имат експедиции в пасивния период
//            if ($shRec->valior >= $passivePeriodStart && $shRec->amountDelivered > 0) {
//
//                $shipmentPassActivContragents[$shRec->folderId] = $shRec->folderId;
//
//            }
//        }
//
//        //Добавяне на експедициите от БЪРЗИ ПРОДАЖБИ
//        $salQuery = sales_Sales::getQuery();
//        $salQuery->in('state', array('rejected', 'draft'), true);
//        $salQuery->like('contoActions', 'ship');
//        $salQuery->where("#valior >= '$activePeriodStart'");
//
//        while ($salRec = $salQuery->fetch()) {
//
//            $id = $salRec->folderId;
//
//            //филтър по дилър
//            if (!in_array(-1,keylist::toArray($rec->dealers))){
//                if(!in_array($salRec->dealerId,keylist::toArray($rec->dealers))) continue;
//            }
//
//            //филтър по група на контрагента на бързата продажба
//            if ($rec->crmGroup) {
//
//                $checkContragentsGroups = keylist::toArray($rec->crmGroup);
//                $contragentsGroups = keylist::toArray(doc_Folders::getContragentData($salRec->folderId)->groupList);
//
//                if (countR(array_intersect($checkContragentsGroups, $contragentsGroups)) == 0) continue;
//
//            }
//
//            //отделяме бързите продажби с вальор преди началото на пасивния период и записваме
//            // $shipmentActivContragents масив активни клиенти(които имат бързи продажби в активния период)
//            if ($salRec->valior < $passivePeriodStart) {
//                if (!array_key_exists($id, $shipmentActivContragents)) {
//                    $shipmentActivContragents[$id] = (object)array(
//                        'folderId' => $salRec->folderId,
//                        'amountDelivered' => $salRec->amountDelivered,
//                        'numberOfSales' => 1,
//                        'numberOfInMails' => '',
//                        'numberOfOutMails' => '',
//                    );
//                } else {
//                    $obj = &$shipmentActivContragents[$id];
//                    $obj->amountDelivered += $salRec->amountDelivered;
//                    $obj->numberOfSales++;
//                }
//            }
//
//            //отделяме бързите продажби с вальор след началото на пасивния период и записваме в
//            // $shipmentPassActivContragents масив клиенти, които имат бързи продажби в пасивния период
//            if ($shRec->valior >= $passivePeriodStart && $salRec->amountDelivered > 0) {
//
//                $shipmentPassActivContragents[$salRec->folderId] = $salRec->folderId;
//
//            }
//        }
//
//
//        //Ako избрания праг за стойност на експедициите през активния период не е нула
//        //От  масива $shipmentActivContragents, изключваме онези с продажби под определения праг
//        if ($rec->minShipment != 0 && (countR($shipmentActivContragents) > 0)) {
//
//            foreach ($shipmentActivContragents as $val) {
//
//                if ($val->amountDelivered < $rec->minShipment) {
//                    unset($shipmentActivContragents[$val->folderId]);
//                }
//            }
//        }
//
//        //Определяне на контрагентите с нулеви предажби през пасивния период и
//        //влизащи в масива на активните клиенти
//        foreach ($shipmentActivContragents as $key => $val) {
//
//            if (!in_array($key, $shipmentPassActivContragents)) {
//
//                $recs[$key] = $val;
//            }
//        }
//        $incomingMailsCount = $outgoingMailsCount = array();
//
//        //Входящи имейли през пасивния период
//        $mInQuery = email_Incomings::getQuery();
//        $mInQuery->in('folderId',array_keys($recs));
//        $mInQuery->where("#createdOn >= '$passivePeriodStart'");
//
//        while ($emInRec = $mInQuery->fetch()) {
//            if(!in_array($emInRec->folderId,array_keys($incomingMailsCount))){
//                $incomingMailsCount[$emInRec->folderId] = 1;
//            }else{
//                $incomingMailsCount[$emInRec->folderId] ++;
//            }
//
//        }
//
//        //Изходящи имейли през пасивния период
//        $mOutQuery = email_Outgoings::getQuery();
//        $mOutQuery->in('folderId',array_keys($recs));
//        $mOutQuery->where("#createdOn >= '$passivePeriodStart'");
//        // $mOutQuery->where("#searchKeywords REGEXP ' [Q|q][0-9]+'");
//
//        while ($emOutRec = $mOutQuery->fetch()) {
//            if(!in_array($emOutRec->folderId,array_keys($outgoingMailsCount))){
//                $outgoingMailsCount[$emOutRec->folderId] = 1;
//            }else{
//                $outgoingMailsCount[$emOutRec->folderId] ++;
//            }
//
//        }
//
//        foreach ($recs as $key => $val){
//
//            if(in_array($key, array_keys($incomingMailsCount))){
//                $recs[$key]->numberOfInMails = $incomingMailsCount[$key];
//            }
//
//            if(in_array($key, array_keys($outgoingMailsCount))){
//                $recs[$key]->numberOfOutMails = $outgoingMailsCount[$key];
//            }
//
//        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');


        if ($export === false) {
            $fld->FLD('contragentId', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
            $fld->FLD('activSalesNumber', 'int', 'caption=Активен продажби->Брой');
            $fld->FLD('activSalesAmount', 'double(decimals=2)', 'caption=Активен продажби->Стойност');
            $fld->FLD('passivMailsIn', 'int', 'caption=Пасивен Писма->Входящи');
            $fld->FLD('passivMailsOut', 'int', 'caption=Пасивен Писма->Изходяши');

        } else {

            $fld->FLD('contragentId', 'varchar', 'caption=Контрагент');

        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        $row->contragentId = doc_Folders::getHyperlink($dRec->folderId);

        $row->activSalesNumber = $Int->toVerbal($dRec->numberOfSales);

        $row->activSalesAmount = $Double->toVerbal($dRec->amountDelivered);

        $row->passivMailsIn = $Int->toVerbal($dRec->numberOfInMails);

        $row->passivMailsOut = $Int->toVerbal($dRec->numberOfOutMails);


        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Time = cls::get('type_Time');

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN periodPassive--><div>|Пасивен период|*: [#periodPassive#]</div><!--ET_END periodPassive-->
                                        <!--ET_BEGIN periodActive--><div>|Активен период|*: [#periodActive#]</div><!--ET_END periodActive-->
                                        <!--ET_BEGIN minShipment--><div>|Мин. продажби|*: [#minShipment#]</div><!--ET_END minShipment-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        $passivePeriodStart = dt::addSecs(-$data->rec->periodPassive, dt::today(), false);
        $activePeriodStart = dt::addSecs(-$data->rec->periodActive, $passivePeriodStart, false);

        if (isset($data->rec->periodPassive)) {
            $fieldTpl->append('<b>' . $Time->toVerbal($data->rec->periodPassive).' ('.$passivePeriodStart.' - '.dt::today().')' . '</b>', 'periodPassive');
        }

        if (isset($data->rec->periodActive)) {
            $fieldTpl->append('<b>' . $Time->toVerbal($data->rec->periodActive).' ('.$activePeriodStart.' - '.$passivePeriodStart.')' . '</b>', 'periodActive');
        }
        if (isset($data->rec->minShipment)) {
            $fieldTpl->append('<b>' . ($data->rec->minShipment) . '</b>', 'minShipment');
        }

        if (isset($data->rec->crmGroup)) {
            $marker = 0;
            if (isset($data->rec->crmGroup)) {
                foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
                    $marker++;

                    $groupVerb .= (crm_Groups::getTitleById($group));

                    if ((countR((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
            }
        }else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'crmGroup');
        }

        if ((isset($data->rec->dealers)) && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }

            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }



        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {

        $res->contragentId = self::getContragent($dRec, false, $rec);


    }
}
