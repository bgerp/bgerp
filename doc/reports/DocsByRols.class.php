<?php


/**
 * Мениджър на отчети за създадени документи от служители с избрана роля.
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Документи » Създадени документи по роля
 */
class doc_reports_DocsByRols extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'roleId,from,to,documents,order';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('roleId', 'key(mvc=core_Roles,select=role,allowEmpty)', 'caption=Роля,after=title,mandatory');
        $fieldset->FLD('from', 'date', 'caption=Период->От,mandatory,after=documents');
        $fieldset->FLD('to', 'date', 'caption=Период->До,mandatory');
        $fieldset->FLD('documents', 'keylist(mvc=core_Classes,select=title)', 'caption=Документи,after=roleId');
        $fieldset->FLD('order', 'enum(cnt=брой документи,letter=азбучен ред)', 'caption=Подреди по,after=documents,mandatory,column=none');
    }
    
    
    /**
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->rec->from > $form->rec->to) {
                $form->setError('from, to', 'Началната дата не може да бъде по-голяма от крайната дата');
            }
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
        $query = doc_Containers::getQuery();
        $query->where(array("#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
        $query->where("#state != 'rejected'");
        
        if (isset($rec->documents)) {
            $documentsForCheck = type_Keylist::toArray($rec->documents);
            $query->whereArr('docClass', $documentsForCheck, true);
        }
        
        $recs = array();
        
        $uArr = core_Users::getByRole($rec->roleId);
        
        if ($uArr) {
            $query->in('createdBy', $uArr);
            
            $documentsForCheck = $query->fetchAll();
            
            $timeLimit = (int)(countR($documentsForCheck) / 100);
            $timeLimit = max($timeLimit, 240);
            core_App::setTimeLimit($timeLimit);
            
            $query->show('createdBy, docClass, docId');
            
            $dDoc = array();
            
            foreach ($documentsForCheck as $doc) {
                $recs[$doc->createdBy]['user'] = $doc->createdBy;
                
                $recs[$doc->createdBy]['classes'][$doc->docClass]++;
                
                $recs[$doc->createdBy]['cnt']++;
                
                $dDoc[$doc->createdBy][$doc->docClass][$doc->docId] = $doc->docId;
            }
            
            arr::sortObjects($recs, 'cnt', 'desc');

            foreach ($dDoc as $createdBy => $dObjArr) {
                foreach ($dObjArr as $clsId => $objArr) {
                    if (cls::load($clsId, true)) {
                        $clsInst = cls::get($clsId);
                    }
                    if ($clsInst->details) {
                        $clsInst->details = arr::make($clsInst->details);
                        
                        foreach ($clsInst->details as $detail) {
                            $detailEnd = strtolower(substr($detail, -7));
                            
                            if ($detailEnd == 'details') {
                                $dInst = cls::get($detail);
                                
                                $masterKey = $dInst->masterKey;
                                
                                if (!$masterKey) {
                                    continue;
                                }
                                
                                $dQuery = $dInst->getQuery();
                                
                                $dQuery->in($masterKey, $objArr);
                                
                                $cnt = $dQuery->count();
                                
                                if (!$cnt) {
                                    continue;
                                }
                                
                                $recs[$createdBy]['details'][$clsId] = $cnt;
                            }
                        }
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
        $fld->FLD('person', 'key(mvc=core_Users,select=nick)', 'caption=Служител,smartCenter');
        $fld->FLD('document', 'varchar', 'caption=Тип документ');
        $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Брой');
        
        return $fld;
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
        $cnty = $cntx = 0;
        
        $row = new stdClass();
        $row->person = crm_Profiles::createLink($dRec['user']);
        
        $vClassArr = array();
        $vClsNameArr = array();
        foreach ($dRec['classes'] as $key => $value) {
            if (!cls::load($key, true)) {
                $title = $key;
                $clsName = $key;
            } else {
                $inst = cls::get($key);
                $title = $inst->title;
                $clsName = $inst->className;
            }
            
            $vClassArr[$key] = $title;
            
            
            $vClsNameArr[$key] = $clsName;
        }
        
        if ($rec->order == 'cnt') {
            arsort($dRec['classes']);
        } elseif ($rec->order == 'letter') {
            asort($vClassArr);
            
            $nArr = array();
            
            foreach ($vClassArr as $key => $dummy) {
                $nArr[$key] = $dRec['classes'][$key];
            }
            
            $dRec['classes'] = $nArr;
        }
        
        //$row->value = $Int->toVerbal($dRec['cnt']);
        
        $row->document .= '<table style="width: 100%;">';
        
        foreach ($dRec['classes'] as $docId => $cnt) {
            $row->document .= '<tr>'.'<td style="border: none">'.$vClassArr[$docId]
                                    .' ('.$vClsNameArr[$docId].')'.'</td>'
                                    .'<td style="min-width: 7%;border: none">'.$cnt.'</td>';
            
            if ($dRec['details'][$docId]) {
                $row->document .= '<td style="min-width: 3%;border: none">'.':'.'</td>';
            } elseif (!$dRec['details'][$docId]) {
                $row->document .= '<td style="min-width: 3%;border: none">'.' '.'</td>';
            }
            
            $row->document .= '<td style="min-width: 7%;border: none">'.$dRec['details'][$docId].'</td>'.'</tr>';
            
            
            /**
             * Общ брой създадени документи от този потребител
             */
            $cntx += $cnt;
            
            
            /**
             * Видове създадени документи(брой)
             */
            $cnty++;
        }
        $row->document .= '</table>';
        
        $row->value = $cntx . ' от ' . $cnty;
        
        return $row;
    }
}
