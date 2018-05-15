<?php

/**
 * Мениджър на отчети за Неактивирани контиращи документи
 *
 * @category  bgerp
 * @package   acc
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Неактивирани контиращи документи 
 */
class acc_reports_UnactiveContableDocs extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'documentType';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date(smartTime)', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('selectedOff', 'set(FALSE=)', 'caption=Изключи избраните,after=documentType');
        
        $fieldset->FLD('documentType', 'keylist(mvc=core_Classes,select=title,allowEmpty)', 
            'caption=Документи,placeholder=Всички,after=to');
        
        $fieldset->FLD('states', 'keylist(mvc=doc_Containers,allowEmpty)', 
            'caption=Състояние,placeholder=Всички,after=selectedOff,single=none');
        $fieldset->FLD('dealerId', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 
            'caption=Търговец,after=states,single=none');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $contoClasses = core_Classes::getOptionsByInterface('acc_TransactionSourceIntf');
        
        $contoClasses = array_keys($contoClasses);
        
        $temp = array();
        $states = array();
        foreach ($contoClasses as $k => $v) {
            
            $temp[$v] = core_Classes::getTitleById($v);
        }
        
        $contoClasses = $temp;
        
        $form->setSuggestions('documentType', $contoClasses);
        
        $states = cls::get(sales_Sales)->getFieldType('state')->options;
        
        $form->setSuggestions('states', $states);
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
        $counter = array();
        
        $contoClasses = core_Classes::getOptionsByInterface('acc_TransactionSourceIntf');
        
        $contoClasses = array_keys($contoClasses);
        
        $query = doc_Containers::getQuery();
        
        if ($rec->states) {
            
            $states = type_Keylist::toArray($rec->states);
            
            $query->in('state', $states);
        }
        
        $query->in('docClass', $contoClasses);
        
        if ($rec->documentType) {
            
            if (! $rec->selectedOff) {
                $selectedOff = FALSE;
            } else {
                $selectedOff = TRUE;
            }
            
            $checkedClasses = type_Keylist::toArray($rec->documentType);
            
            $query->in('docClass', $checkedClasses, $selectedOff);
        }
        
        if ($rec->dealerId) {
            
            $dealers = keylist::toArray($rec->dealerId);
            
            $query->in('createdBy', $dealers);
            
            if (count($dealers) > 1) {
                
                $query->orderBy('createdBy', 'ASC');
            }
        }
        
        while ($document = $query->fetch()) {
            
            $Document = doc_Containers::getDocument($document->id);
            
            $className = $Document->className;
            $contDoc = $className::fetch($Document->that);
            
            $documentType = $className . '|' . $contDoc->state;
            
           
            
            $handle = $className::getHandle($Document->that);
            
            if ($contDoc->valior < $rec->from || $contDoc->valior > $rec->to)
                continue;
            
             $counterKey = $className . $contDoc->state;
            
            $counter[$counterKey] ++;
            
            if (! array_key_exists($Document->that, $recs)) {
                
                $recs[$Document->that] = (object) array(
                    
                    'documentType' => $documentType,
                    'counter' => $counter,
                    'documentFolder' => $document->folderId,
                    'containerId' => $document->id,
                    'documentId' => $Document->that,
                    'valior' => $contDoc->valior,
                    'dealerId' => $document->createdBy,
                    'handle' => $handle,
                    'states' => $contDoc->state
                );
            }
            
            $documentsArr[] = $contDoc;
        }
        
        if (count($recs)) {
            
            arr::natOrder($recs, 'documentType');
        }
     
        return $recs;
    }

    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === FALSE) {
            
            $fld->FLD('documentType', 'varchar', 'caption=Вид документ');
            
            $fld->FLD('valior', 'date', 'caption=Дата,smartCenter');
            // $fld->FLD('states', 'varchar', 'caption=Състояние');
            $fld->FLD('handle', 'varchar', 'caption=Документ,smartCenter');
            $fld->FLD('documentFolder', 'varchar', 'caption=Папка,smartCenter');
            
            if (count(type_Keylist::toArray($rec->dealerId)) > 1) {
                
                $fld->FLD('dealerId', 'varchar', 'caption=Търговец,smartCenter');
            }
        }
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        $Document = doc_Containers::getDocument($dRec->containerId);
        
        list ($className, $other) = explode('|', $dRec->documentType);
        
        foreach ($dRec->counter as $k =>$v){
            
            if ($k == $className.$other){
                
                $thisCounter = $v;
                
               // bp($k,$className.$other,$v);
            }
            
        }
        
//         bp($className,$other,$thisCounter,$dRec);
        
        // $typeOfDocument = $className::getTitleById($dRec->documentId);
        $typeOfDocument = $Document->title . '  »  ' . cls::get($className)->getFieldType('state')->toVerbal($other)." $thisCounter ".'бр.';
        
        $handle = $className::getHandle($dRec->documentId);
        
        $state = $dRec->states;
        
        $singleUrl = $Document->getUrlWithAccess($Document->getInstance(), $Document->that);
        
        $row->documentType .= "<span class= 'large' >" . "$typeOfDocument" . $Date->toVerbal($typeOfDate) . "</span>";
        
        $row->valior = $Date->toVerbal($dRec->valior);
        
        $row->states = "<span class= normal >" . cls::get($className)->getFieldType('state')->toVerbal($dRec->states) .
             "</span>";
        
        $row->handle = "<span class= 'state-{$state} document-handler' >" .
             ht::createLink("#{$handle}.</span>", $singleUrl, FALSE, "ef_icon={$Document->singleIcon}") . "</span>";
        
        $row->documentFolder = doc_Folders::getHyperlink($dRec->documentFolder);
        
        $row->dealerId = crm_Profiles::createLink($dRec->dealerId);
        
        return $row;
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
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN states-->|Състояние|*: [#states#]<!--ET_END states--></div></small>
                                <small><div><!--ET_BEGIN dealerId-->|Търговец|*: [#dealerId#]<!--ET_END dealerId--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->from)) {
            $fieldTpl->append((dt::mysql2verbal($data->rec->from, $mask = "d.m.Y")), 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append((dt::mysql2verbal($data->rec->to, $mask = "d.m.Y")), 'to');
        }
        
        if (isset($data->rec->states)) {
            
            foreach (type_Keylist::toArray($data->rec->states) as $state) {
                
                $statesVerb .= (cls::get(sales_Sales)->getFieldType('state')->toVerbal($state)) . ', ';
            }
            $fieldTpl->append(trim($statesVerb, ', '), 'states');
        }
        
        if (isset($data->rec->dealerId)) {
            
            foreach (type_Keylist::toArray($data->rec->dealerId) as $dealer) {
                
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }
            
            $fieldTpl->append(trim($dealersVerb, ',  '), 'dealerId');
        } else {
            $fieldTpl->append('Всички', 'dealerId');
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
    {}
}
