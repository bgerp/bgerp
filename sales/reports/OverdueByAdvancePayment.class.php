<?php



/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Просрочия по аванси
 */
class sales_reports_OverdueByAdvancePayment extends frame2_driver_TableData
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,sales';


    /**
     * Дилърите
     *
     * @var array
     */
    private static $dealers = array();


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title');

        $fieldset->FLD('tolerance', 'int', 'caption=Толеранс[дни],after=dealers');

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

        // Всички активни потебители
        $uQuery = core_Users::getQuery();
        $uQuery->where("#state = 'active'");
        $uQuery->orderBy("#names", 'ASC');
        $uQuery->show('id');


        // Които са търговци
        $roles = core_Roles::getRolesAsKeylist('ceo,sales');
        $uQuery->likeKeylist('roles', $roles);
        $allDealers = arr::extractValuesFromArray($uQuery->fetchAll(), 'id');


     //   bp($allDealers);

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

       // bp($suggestions);

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
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();

        $dealers = keylist::toArray($rec->dealers);

        $docQuery = bank_IncomeDocuments::getQuery();

        $docQuery->where("#state = 'pending'");

        $docQuery->orderBy('termDate', 'DESC');

        $docQuery->orderBy('modifiedOn', 'DESC');

        while ($inDocs = $docQuery->fetch()){

            $id = $inDocs->id;

            $firstDocument = doc_Threads:: getFirstDocument($inDocs->threadId);

            $contragentId[] = array($id=> $firstDocument->fetch()->contragentId);

            $dealerId = $firstDocument->fetch()->dealerId;

            if(!$dealerId){
                $fRec = doc_Folders::fetch($firstDocument->fetch()->folderId);
                $dealerId = $fRec->inCharge;
            }

//            $dealerId = $firstDocument->instance->fetch($firstDocument->that);
//            $dealerId = $firstDocument->instance->haveRightFor('single', $firstDocument->that);

//bp($dealerId, $firstDocument, $firstDocument->haveRightFor('single'), $firstDocument->instance->requireRightFor('single', $firstDocument->that), $firstDocument->getHandle());

//bp($dealerId,$dealers);

            if (strtotime(date('Y/m/d')) > strtotime (date('Y-m-d', strtotime($inDocs->termDate. "+ $rec->tolerance days" )))){

                $condition = 'просрочен';

            }else{$condition = 'ok';}

            if(in_array($dealerId,$dealers)) {

                $recs[$id] = (object)array(
                    'documentId' => $inDocs->id,
                    'clsName' => 'bank_IncomeDocuments',
                    'dealer'=> $firstDocument->fetch()->dealerId,
                    'state' => $inDocs->state,
                    'amount' => $inDocs->amount,
                    'curency' => $inDocs->currencyId,
                    'termDate' => $inDocs->termDate,
                    'folder' => $firstDocument->fetch()->folderId,
                    'condition' => $condition,

                );

            }

        }

        return $recs;

    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec   - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === FALSE) {
            $fld->FLD('documentId', 'varchar', 'smartCenter,caption=Документ');
            $fld->FLD('folder', 'varchar', 'caption=Папка,smartCenter');
            $fld->FLD('amount', 'varchar', 'smartCenter,caption=Сума');
            $fld->FLD('termDate', 'varchar', 'caption=Краен срок,smartCenter');
            $fld->FLD('condition', 'varchar', 'caption=Състояние,smartCenter');
        } else {
            $fld->FLD('documentId', 'varchar', 'smartCenter,caption=Документ');
            $fld->FLD('folder', 'varchar', 'caption=Папка,smartCenter');
            $fld->FLD('amount', 'varchar', 'smartCenter,caption=Сума');
            $fld->FLD('termDate', 'varchar', 'caption=Краен срок,smartCenter');
            $fld->FLD('condition', 'varchar', 'caption=Състояние,smartCenter');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');

        $row = new stdClass();


        if (isset($dRec->documentId)) {
            $row->documentId = $dRec->clsName::getLinkToSingle($dRec->documentId);
        }

        if (isset($dRec->folder)) {
            $row->folder = doc_Folders::getShortHyperlink($dRec-> folder);
        }

        if (isset($dRec->termDate)) {
            $row->termDate = $Date->toVerbal($dRec->termDate);
        }

        if (isset($dRec->amount)) {
            $row->amount = $dRec->amount;
        }

        if (isset($dRec->condition)) {
            $row->condition = $dRec->condition;
        }

        return $row;
    }
    }

