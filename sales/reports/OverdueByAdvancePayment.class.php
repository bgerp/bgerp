<?php


/**
 * Мениджър на отчети относно просрочия по аванси
 *
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Просрочия по аванси
 */
class sales_reports_OverdueByAdvancePayment extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,sales,manager';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;
    
    
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
    protected $newFieldsToCheck = 'condition';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Дали групиращото поле да е на отделен ред или не
     */
    protected $groupedFieldOnNewRow = true;
    
    
    /**
     * Дилърите
     *
     * @var array
     */
    private static $dealers = array();
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'dealers,tolerance';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Търговци,after=title');
        $fieldset->FLD('tolerance', 'int', 'caption=Толеранс,unit= дни,after=dealers');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        // Кой може да вижда други търговци освен себе си
        $canSeeOthers = core_Roles::getRolesAsKeylist('ceo,manager');
        
        if ($form->isSubmitted()) {
            if (((countR(explode('|', $form->rec->dealers))) - 2) > 1) {
                if (! (core_Users::haveRole($canSeeOthers, $userId = null))) {
                    $form->setError('dealers', 'Имате достъп само до Вашите документи');
                }
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
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
        $okRecs = array();
        $overRecs = array();
        
        $dealers = keylist::toArray($rec->dealers);
        
        $docQuery = bank_IncomeDocuments::getQuery();
        
        $docQuery->where("#state = 'pending'");
        
        $docQuery->orderBy('termDate', 'ASC');
        
        $docQuery->orderBy('modifiedOn', 'ASC');
        
        while ($inDocs = $docQuery->fetch()) {
            $id = $inDocs->id;
            
            $firstDocument = doc_Threads::getFirstDocument($inDocs->threadId);
            
            if ((substr($inDocs->operationSysId, - 7) != 'Advance')) {
                if (($firstDocument->fetch()->amountDelivered)) {
                    continue;
                }
            }
            
            $dealerId = $firstDocument->fetch()->dealerId;
            
            if (! $dealerId) {
                $fRec = doc_Folders::fetch($firstDocument->fetch()->folderId);
                $dealerId = $fRec->inCharge;
            }
            
            if (! $inDocs->termDate) {
                $termDate = dt::addDays(3, $firstDocument->fetch()->createdOn, $full = false);
            } else {
                $termDate = $inDocs->termDate;
            }
            
            $markDay = dt::addDays($rec->tolerance, $termDate, $full = false);
            
            if ((dt::today()) > ($markDay)) {
                $condition = 'просрочен';
            } else {
                $condition = 'ok';
            }
            
            if (in_array($dealerId, $dealers)) {
                if ($condition == 'просрочен') {
                    $overRecs[$id] = (object) array(
                        'documentId' => $inDocs->id,
                        'clsName' => 'bank_IncomeDocuments',
                        'dealer' => $dealerId,
                        'state' => $inDocs->state,
                        'amount' => $inDocs->amount,
                        'curency' => $inDocs->currencyId,
                        'termDate' => $termDate,
                        'folder' => $firstDocument->fetch()->folderId,
                        'condition' => $condition,
                        'cntDealers' => countR($dealers)
                    );
                } else {
                    $okRecs[$id] = (object) array(
                        'documentId' => $inDocs->id,
                        'clsName' => 'bank_IncomeDocuments',
                        'dealer' => $dealerId,
                        'state' => $inDocs->state,
                        'amount' => $inDocs->amount,
                        'curency' => $inDocs->currencyId,
                        'termDate' => $termDate,
                        'folder' => $firstDocument->fetch()->folderId,
                        'condition' => $condition,
                        'cntDealers' => countR($dealers)
                    );
                }
            }
        }
        usort($overRecs, array(
            $this,
            'orderByTermDate'
        ));
        
        usort($okRecs, array(
            $this,
            'orderByTermDate'
        ));
        
        $recs = $overRecs;
        foreach ($okRecs as $v) {
            array_push($recs, $v);
        }
        
        return $recs;
    }
    
    
    /**
     * Подреждане на масива по поле в обекта
     */
    public function orderByTermDate($a, $b)
    {
        return $a->termDate > $b->termDate;
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
        $cntDealers = countR(explode('|', trim($rec->dealers, '|')));
        
        $fld = cls::get('core_FieldSet');
        $fld->FLD('documentId', 'varchar', 'caption=Документ');
        $fld->FLD('condition', 'varchar', 'caption=Състояние,tdClass=centered');
        $fld->FLD('folder', 'key(mvc=doc_Folders,select=title)', 'caption=Папка');
        $fld->FLD('termDate', 'date', 'caption=Краен срок');
        $fld->FLD('amount', 'double(decimals=2)', 'caption=Сума,smartCenter');
        if ($export === false) {
            if ($cntDealers > 1) {
                $fld->FLD('dealer', 'key(mvc=core_Users,select=nick)', 'caption=Търговец,tdClass=centered');
            }
        } else {
            $fld->FLD('dealer', 'key(mvc=core_Users,select=nick)', 'caption=Търговец,tdClass=centered');
        }
        
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
        $res->documentId = '#' . cls::get($dRec->clsName)->getHandle($dRec->documentId);
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
        
        if ($dRec->condition == 'просрочен') {
            $conditionColor = 'red';
        } else {
            $conditionColor = 'green';
        }
        
        $row = new stdClass();
        
        if (isset($dRec->documentId)) {
            $row->documentId = cls::get($dRec->clsName)->getLink($dRec->documentId, 0);
        }
        
        if (isset($dRec->folder)) {
            $row->folder = doc_Folders::recToVerbal(doc_Folders::fetch($dRec->folder))->title;
        }
        
        if (isset($dRec->termDate)) {
            $row->termDate = $Date->toVerbal($dRec->termDate);
        }
        
        if (isset($dRec->amount)) {
            $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amount);
        }
        
        if (isset($dRec->condition)) {
            $row->condition = "<span style='color: ${conditionColor}'>{$dRec->condition}</span>";
        }
        
        if (isset($dRec->dealer)) {
            $row->dealer = crm_Profiles::createLink($dRec->dealer);
        }
        
        return $row;
    }
}
