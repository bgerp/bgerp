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
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'className';

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
        $fieldset->FLD('documentType', 'keylist(mvc=core_Classes,select=title,allowEmpty)', 
            'caption=Документи,placeholder=Всички,after=to');
        $fieldset->FLD('states', 'set(draft=Чернова,pending=Заявка)', 'caption=Състояние,after=documentType');
        $fieldset->FLD('dealerId', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Търговец,after=states');
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
        foreach ($contoClasses as $k => $v) {
            
            $temp[$v] = core_Classes::getTitleById($v);
        }
        
        $contoClasses = $temp;
        
        $form->setSuggestions('documentType', $contoClasses);
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
        
        $contoClasses = core_Classes::getOptionsByInterface('acc_TransactionSourceIntf');
        
        $contoClasses = array_keys($contoClasses);
        
        $query = doc_Containers::getQuery();
        
        $states = arr::make($rec->states);
        
        $query->in('state', $states);
        
        $query->in('docClass', $contoClasses);
        
        if ($rec->documentType) {
            
            $checkedClasses = type_Keylist::toArray($rec->documentType);
            
            $query->in('docClass', $checkedClasses);
        }
        
        while ($document = $query->fetch()) {
            
            $Document = doc_Containers::getDocument($document->id);
            
            $className = $Document->className;
            $ContDoc = $className::fetch($Document->that);
            
            
            
            if ($ContDoc->valior < $rec->from || $ContDoc->valior > $rec->to)
                continue;
          
            
            if (! array_key_exists($Document->that, $recs)) {
                
                $recs[$Document->that] = (object) array(
                    
                    'documentType' => $Document->className,
                    'containerId'=>$document->id,
                    'documentId'=> $Document->that,
                    'valior' => $ContDoc->valior,
                    'states' => $ContDoc->state
                );
            }
            
            $documentsArr[] = $ContDoc;
        }
        // bp($documentsArr);
       // bp($recs);
        return $recs;
    }

    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === FALSE) {
            
            $fld->FLD('documentType', 'varchar', 'caption=Вид документ');
            $fld->FLD('valior', 'date', 'caption=Дата,smartCenter');
            
            $fld->FLD('states', 'varchar', 'caption=Състояние,smartCenter');
        } else {}
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
        
        
        $className = $dRec->documentType;
        $typeOfDocument = $className::getTitleById($dRec->documentId);
        $Document = doc_Containers::getDocument($dRec->containerId);
        
        $handle = $className::getHandle($dRec->documentId);
        
        
        
        $state = $dRec->states;
        $singleUrl = $Document->getUrlWithAccess($Document->getInstance(), $Document->that);
        
        $row->documentType .= "<span class= 'small' >" . "$typeOfDocument" . $Date->toVerbal($typeOfDate) . "</span>" .
            '  »  ' . "<span class= 'state-{$saleState} document-handler' >" . ht::createLink("#{$handle}",
            $singleUrl, FALSE, "ef_icon={$Document->singleIcon}") . "</span>";
        
        
        
        
        $row->valior = $Date->toVerbal($dRec->valior);
        $row->states = ($dRec->states);
        
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
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                </fieldset><!--ET_END BLOCK-->"));
        
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
