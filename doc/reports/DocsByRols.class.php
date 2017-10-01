
<?php

/**
 * Мениджър на отчети за създадени документи от служители
 * с избрана роля.
 *
 * @category  bgerp
 * @package   doc
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Документи » Създадени документи по роля
 */

class doc_reports_DocsByRols extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';

    public $listFields = 'person,document,value,roleId,from,to,documents';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('roleId', 'key(mvc=core_Roles,select=role,allowEmpty)', 'caption=Роля,after=title,mandatory');
        $fieldset->FLD('from', 'datetime', 'caption=Период->От,mandatory,after=role');
        $fieldset->FLD('to', 'datetime', 'caption=Период->До,mandatory');
        $fieldset->FLD('documents', 'keylist(mvc=core_Classes,select=name)', 'caption=Документи,after=to');
        $fieldset->FLD('order', 'enum(cnt=брой документи,letter=азбучен ред)', 'caption=Подреди по,after=documents,mandatory,column=none');

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

        $query = doc_Containers::getQuery();

        $query->where(array("#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'", $rec->from, $rec->to ));

        $query->where("#state != 'rejected'");

        if(isset($rec->documents)){

            $documentsForCheck = type_Keylist::toArray($rec->documents);

            $query->whereArr("docClass", $documentsForCheck, TRUE);

        }
        $recs = array();

        if(core_Users::getByRole($rec->roleId)) {

            $query->in('createdBy', core_Users::getByRole($rec->roleId));

            $documentsForCheck = $query->fetchAll();

            foreach ($documentsForCheck as $doc) {

                $recs[$doc->createdBy]['user'] = $doc->createdBy;

                $recs[$doc->createdBy]['classes'][$doc->docClass]++;

                $recs[$doc->createdBy]['cnt']++;

                $dDoc[$doc->createdBy][$doc->docClass][$doc->docId] = $doc->docId;

                foreach ($dDoc[$doc->createdBy] as $clsId => $objArr) {

                    $clsInst = cls::get($clsId);

                    if ($clsInst->details) {

                        $clsInst->details = arr::make($clsInst->details);

                        foreach ($clsInst->details as $detail) {

                            $detailEnd = strtolower(substr($detail, -7));

                            if($detailEnd == 'details') {

                                $dInst = cls::get($detail);

                                $masterKey = $dInst->masterKey;

                                if (!$masterKey) continue;

                                $dQuery = $dInst->getQuery();

                                $dQuery->in($masterKey, $objArr);

                                $cnt = $dQuery->count();

                                if (!$cnt) continue;

                                $dArr[$clsId][$detail] = $cnt;

                                $recs[$doc->createdBy]['details'][$clsId]= $cnt;

                            }

                        }

                    }

                }

            }

        } //проверка за наличие на роля//

        return $recs;

    }

    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec      - записа
     * @param boolean $export    - таблицата за експорт ли е
     * @return core_FieldSet     - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if($export === FALSE){
            //  $fld->FLD('num', 'varchar','caption=№');
            $fld->FLD('person', 'varchar', 'caption=Служител');
            $fld->FLD('document', 'varchar', 'caption=Тип документ');
            $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Брой');

        } else {
            //    $fld->FLD('num', 'varchar','caption=№');
            $fld->FLD('person', 'varchar', 'caption=Служител');
            $fld->FLD('document', 'varchar', 'caption=Тип документ');
            $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Брой');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        // bp($dRec);

        $cntx = 0;

        $cnty = 0;

        //  $Int = cls::get('type_Int');

        $row = new stdClass();

        $row->person = crm_Profiles::createLink($dRec['user']);

        $vClassArr = array();
        $vClsNameArr = array();
        foreach ($dRec['classes'] as $key => $value){


            if (!cls::load($key, TRUE)) {
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

        if($rec->order == 'cnt') {

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

            $row->document .='<tr>'.'<td style="border: none">'.$vClassArr[$docId]
                                    .' ('.$vClsNameArr[$docId].')'.'</td>'
                                    .'<td style="min-width: 7%;border: none">'.$cnt.'</td>';

            if($dRec['details'][$docId]){ $row->document .='<td style="min-width: 7%;border: none">'.':'.'</td>';}

            elseif(!$dRec['details'][$docId]){ $row->document .='<td style="min-width: 7%;border: none">'.' '.'</td>';};

            $row->document .='<td style="min-width: 7%;border: none">'.$dRec['details'][$docId].'</td>'.'</tr>';

            /**
             * Общ брой създадени документи от този потребител
             */
            $cntx += $cnt;

            /**
             * Видове създадени документи(брой)
             */
            $cnty++;

        }

        $row->document.='</table>';

        $row->value = $cntx . ' от ' . $cnty;

        return $row;

    }
}
