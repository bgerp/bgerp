<?php

/**
 * Мениджър на отчети на Платежни документи в състояние "заявка"
 *
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Платежни документи в състояние 'заявка'
 */

class deals_reports_ReportPaymentDocuments extends frame2_driver_TableData
{
    /**
     *  cash_Pko
     *  cash Rko
     *  bank_SpendingDocuments
     *  bank_IncomeDocuments
     *  sales_Sales и purchase_Purchases ако в полето им contoActions има стойност `pay`
     **/

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,bank,cash';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = '';


    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = '';


    /**
     * По-кое поле да се групират листовите данни
     */
   // protected $groupByField = '';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'accountId,casesId,documentType,horizon';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Банкова сметка,placeholder=Всички,after=title');
        $fieldset->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,placeholder=Всички,after=accountId');
        $fieldset->FLD('documentType', 'keylist(mvc=core_Classes,select=name)', 'caption=Документи,placeholder=Всички,after=caseId');
        $fieldset->FLD('horizon', 'time', 'caption=Хоризонт,after=documentType');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        
        

        $accounts = self::getContableAccounts($form->rec);

        $form->setOptions('accountId', array('' => '') + $accounts);

        $documents = array('cash_Pko','cash_Rko','bank_SpendingDocuments','bank_IncomeDocuments');

        $docOptions = array();

        foreach ($documents as $className){

            $classId = $className::getClassId();
            
            $docOptions[$classId] = core_Classes::getTitleById($classId, FALSE);
            
        }

        $form->setSuggestions('documentType',  $docOptions);

    }

    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {


    	if ($form->isSubmitted()) {
    		
    		
    	}
    	
    }



        /**
     * Връща банковите сметки, които може да контира потребителя
     * @return array $res
     */
    public static function getContableAccounts($rec)
    {
        $res = array();

        $cu = (!empty($rec->createdBy)) ? $rec->createdBy : core_Users::getCurrent();

        $sQuery = bank_OwnAccounts::getQuery();

        $sQuery->where("#state != 'rejected'");

        while($sRec = $sQuery->fetch()){
        	
            if(bgerp_plg_FLB::canUse('bank_OwnAccounts', $sRec, $cu,select)){

                $res[$sRec->id] = bank_OwnAccounts::getTitleById($sRec->id, FdocumentALSE);
            }

        }

        return $res;
    }

    /**
     * Връща касите, които може да контира потребителя
     * @return array $res
     */
    public static function getContableCases($rec)
    {
        $res = array();

        $cu = (!empty($rec->createdBy)) ? $rec->createdBy : core_Users::getCurrent();

        $sQuery = cash_Cases::getQuery();

        $sQuery->where("#state != 'rejected'");

        while($sRec = $sQuery->fetch()){

            if(bgerp_plg_FLB::canUse('cash_Cases', $sRec, $cu,select)){

                $res[$sRec->id] = cash_Cases::getTitleById($sRec->id, FALSE);
            }
        }

        return $res;
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {

        $recs = array();
        $bankRecs = array();
        $caseRecs = array();
        $docClasses = array();

        $accountsId = isset($rec->accountId) ? array($rec->accountId => $rec->accountId) : array_keys(self::getContableAccounts($rec));
        
        $casesId = isset($rec->caseId) ? array($rec->caseId => $rec->caseId) : array_keys(self::getContableCases($rec));

        $documentFld = ($rec->documentType) ? 'documentType' : 'document';
        
        $docClasses = keylist::toArray($rec->documentType);
     
        $both = (!isset($rec->accountId) && !isset($rec->caseId) ) || (isset($rec->accountId) && isset($rec->caseId) );



        /*
         * Банкови платежни документи
         */
        if ($both || isset($rec->accountId)){

        foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {

            if (empty($docClasses) || in_array($pDoc::getClassId(),$docClasses)) {
            	
                $cQuery = $pDoc::getQuery();

                $cQuery->where("#ownAccount IS NULL");

                $cQuery->whereArr('ownAccount', $accountsId, TRUE, TRUE);


                $cQuery->where("#state = 'pending'");

                $cQuery->orderBy('termDate', 'ASC');

                while ($cRec = $cQuery->fetch()) {

                    $payDate = ($cRec->termDate) ? $cRec->termDate : $cRec->valior;

                    if (!empty($rec->horizon)) {

                        $horizon = dt::addSecs($rec->horizon, dt::today(), FALSE);

                        if ($payDate && ($payDate > $horizon)) {

                            unset($payDate);

                            continue;
                        }

                    }
                    
                    $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);
 
                    if (core_Users::getCurrent() != $cRec->credatedBy) {
                    	
						$Document = doc_Containers::getDocument($cRec->containerId);
												
                        if (!$Document->haveRightFor('conto')) continue;
                    }

                    $bankRecs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
											                        'amountDeal' => $cRec->amountDeal,
											                        'className' => $className,
											                        'payDate' => $payDate,
											                        'termDate' => $cRec->termDate,
											                        'valior' => $cRec->valior,
											                        'currencyId' => $cRec->currencyId,
											                        'documentId' => $cRec->id,
											                        'folderId' => $cRec->folderId,
											                        'createdOn' => $cRec->createdOn,
											                        'createdBy' => $cRec->createdBy,
											                        'ownAccount' => $cRec->ownAccount,
											                        'peroCase' => $cRec->peroCase,
											                    );

                }
            }

        }

     }

        /*
         * Касови платежни документи
         */
        if ($both || isset($rec->caseId)) {
            foreach (array('cash_Rko', 'cash_Pko') as $pDoc) {

                if (empty($docClasses) || in_array($pDoc::getClassId(),$docClasses)) {

                    $cQuery = $pDoc::getQuery();

                    
                    //$where = ""
                    
                    $cQuery->in("peroCase", $casesId);
                    $cQuery->orWhere("#peroCase IS NULL");
                   // $cQuery->whereArr('peroCase', $casesId, TRUE, TRUE);
                    
                  

                    $cQuery->where("#state = 'pending'");
 
                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {

                        $payDate = ($cRec->termDate) ? $cRec->termDate : $cRec->valior;

                        if (!empty($rec->horizon)) {

                            $horizon = dt::addSecs($rec->horizon, dt::today(), FALSE);

                            if ($payDate && ($payDate > $horizon)) {

                                unset($payDate);

                                continue;
                            }

                        }

                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->credatedBy) {

                            $Document = doc_Containers::getDocument($cRec->containerId);
												
                       		 if (!$Document->haveRightFor('conto')) continue;
                        }
                        $caseRecs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
                            'amountDeal' => $cRec->amountDeal,
                            'className' => $className,
                            'payDate' => $payDate,
                            'termDate' => $cRec->termDate,
                            'valior' => $cRec->valior,
                            'currencyId' => $cRec->currencyId,
                            'documentId' => $cRec->id,
                            'folderId' => $cRec->folderId,
                            'createdOn' => $cRec->createdOn,
                            'createdBy' => $cRec->createdBy,
                            'ownAccount' => $cRec->ownAccount,
                            'peroCase' => $cRec->peroCase,
                        );

                    }
                }

            }

        }
        $recs=$bankRecs+$caseRecs;

        usort($recs, array($this, 'orderByPayDate'));

        return $recs;

    }

    function orderByPayDate($a, $b)
    {

        return $a->payDate > $b->payDate;
    }

    protected function getTableFieldSet($rec, $export = FALSE)
    {

        $fld = cls::get('core_FieldSet');

        if($export === FALSE){

            $fld->FLD('documentId', 'varchar', 'caption=Документ');
            $fld->FLD('amountDeal', 'double(decimals=2)', 'caption=Сума,smartCenter');
            $fld->FLD('payDate', 'varchar', 'caption=Срок-> за плащане');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
           // $fld->FLD('createdBy', 'double(smartRound,decimals=2)', 'caption=Създател,smartCenter');
            $fld->FLD('created', 'varchar', 'caption=Създаване,smartCenter');

        } else {

            $fld->FLD('documentId', 'varchar', 'caption=Документ');
            $fld->FLD('amountDeal', 'varchar', 'caption=Сума');
            $fld->FLD('payDate', 'varchar', 'caption=Срок за плащане');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута');
            $fld->FLD('createdBy', 'varchar', 'caption=Създаване');

        }

        return $fld;

    }

    protected function detailRecToVerbal($rec, &$dRec)
    {

        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');

        $row = new stdClass();



        if (isset($dRec->documentId)) {
            $clsName = $dRec->className;
            $row->documentId = $clsName::getLink($dRec->documentId, 0);
            $row->documentId = $clsName::getLinkToSingle($dRec->documentId);
        }

        if(isset($dRec->createdBy)) {

            $row->createdBy = crm_Profiles::createLink($dRec->createdBy);
            $row->createdOn = $Date->toVerbal($dRec->createdOn);
        }



        $hint =($dRec->ownAccount)?bank_OwnAccounts::getTitleById($dRec->ownAccount) :cash_Cases::getTitleById($dRec->peroCase) ;
        $hint = $hint?$hint:'не посочена';

        if (isset($dRec->amountDeal)) {

            $row->amountDeal =core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountDeal);

            $row->amountDeal = ht::createHint($row->amountDeal, "$hint", 'notice');
        }
            $row->payDate =($dRec->payDate)? $Date->toVerbal($dRec->payDate):'не посочен';

        if(isset($dRec->currencyId)) {
            $row->currencyId = currency_Currencies::getCodeById($dRec->currencyId);
        }

        $row->created = $row->createdOn.' от '.$row->createdBy;

        return $row;
    }

}