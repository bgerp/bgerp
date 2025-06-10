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
    protected $newFieldsToCheck = 'folderId';



    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'dealer';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'periodStart,periodEnd,dealers';


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

        $form->setDefault('periodStart', '3 дена');

        $form->setDefault('periodEnd', '3 седмици');


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

            //$outgoingMailsArr последния изходящ имейл във всяка папка
            if(!in_array($emOutRec->folderId,array_keys($outgoingMailsArr))){
                $outgoingMailsArr[$emOutRec->folderId] = $emOutRec;
            }else{

                if($emOutRec->createdOn > $outgoingMailsArr[$emOutRec->folderId]->createdOn){
                    $outgoingMailsArr[$emOutRec->folderId] = $emOutRec;
                }
            }

        }
;
        //Разпределяне на папките и последния имейл в тях по дилъри
        //и филтрирам само тези които съдържат оферта
        $lastOutEmails = array();
        foreach ($foldersArr as $dil => $fId){
            foreach ($outgoingMailsArr as $email){

                if(in_array($email->folderId,$fId)){

                    if (!preg_match('/#Q\d+/', $email->body))continue;

                    $key = $dil.'|'.$email->folderId;
                    $lastOutEmails[$key] = $email;

                }

            }

        }

        //Определяме последния входящ имейл в папките на избраните дилъри
        $mInQuery = email_Incomings::getQuery();
        $mInQuery->in('folderId',$foldersforChek);
        $mInQuery->where(array(
            "#createdOn >= '[#1#]'", $periodEnd . ' 00:00:00'));

        while ($mInRec = $mInQuery->fetch()) {

            //$incomingMailsArr последния входящ имейл във всяка папка
            if(!in_array($mInRec->folderId,array_keys($incomingMailsArr))){
                $incomingMailsArr[$mInRec->folderId] = $mInRec;
            }else{

                if($mInRec->createdOn > $incomingMailsArr[$mInRec->folderId]->createdOn){
                    $incomingMailsArr[$mInRec->folderId] = $mInRec;
                }
            }

        }

        //От филтрираните изходящи имейли съдържащи оферта, отделяме тези,
        // които са с по голяма дата от последния входящ имей в същата папка

        foreach ($lastOutEmails as $outMailKey => $outMail){
            list($deal, $outFolder) = explode('|', $outMailKey);

            $shouldAdd = false;

            if (!array_key_exists($outFolder, $incomingMailsArr)) {
                // Няма входящ имейл — включваме офертата
                $shouldAdd = true;
            } elseif ($outMail->createdOn > $incomingMailsArr[$outFolder]->createdOn) {
                // Има входящ имейл, но офертата е по-късна — също я включваме
                $shouldAdd = true;
            }

            if ($shouldAdd) {
                $id = $deal . '|' . $outFolder;

                $recs[$id] = (object)[
                    'folderId' => $outFolder,
                    'outMail' => $outMail,
                    'dealer' => $deal,
                ];
            }
        }

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
            $fld->FLD('dealer', 'key(mvc=core_Users,select=names)', 'caption=Дилър');
            $fld->FLD('folderId', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
            $fld->FLD('outEmail', 'varchar', 'caption=Изх. имейл ->Оферта');
            $fld->FLD('outEmailDatate', 'varchar', 'caption=Изх. имейл ->Дата');

        } else {

            $fld->FLD('dealer', 'key(mvc=core_Users,select=names)', 'caption=Дилър');
            $fld->FLD('folderId', 'varchar', 'caption=Контрагент');
            $fld->FLD('outEmail', 'varchar', 'caption=Изх. имейл ->Оферта');
            $fld->FLD('outEmailDatate', 'date', 'caption=Изх. имейл ->Дата');

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

        $row->folderId = doc_Folders::getHyperlink($dRec->folderId);

        $row->dealer = crm_Profiles::createLink($dRec->dealer);

        $row->outEmail = email_Outgoings::getHyperlink($dRec->outMail);

        $row->outEmailDatate = $Date->toVerbal(($dRec->outMail)->createdOn);


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
        $Date = cls::get('type_Date');

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN periodEnd--><div>|Период от|*: [#periodEnd#]</div><!--ET_END periodEnd-->
                                        <!--ET_BEGIN periodStart--><div>|Период до|*: [#periodStart#]</div><!--ET_END periodStart-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        $periodStart = dt::addSecs(-$data->rec->periodStart, dt::today(), false);
        $periodEnd = dt::addSecs(-$data->rec->periodEnd, $periodStart, false);

        if (isset($data->rec->periodStart)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($periodStart). '</b>', 'periodStart');
        }

        if (isset($data->rec->periodEnd)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($periodEnd) . '</b>', 'periodEnd');
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

        $res->folderId = doc_Folders::fetch($dRec->folderId)->title;

        $res->outEmail = ($dRec->outMail)->subject;

        $res->outEmailDatate = ($dRec->outMail)->createdOn;

    }

}
