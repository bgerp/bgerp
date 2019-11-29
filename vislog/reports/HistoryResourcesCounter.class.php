<?php


/**
 * Мениджър на отчети за анализ на ресурси
 *
 * @category  bgerp
 * @package   vislog
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Сайт » Брояч на ресурси
 */
class vislog_reports_HistoryResourcesCounter extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,repAllGlobal';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;
    
    
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
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none');
        $fieldset->FLD('text', 'varchar', 'caption=Текст,single=none,after=to');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
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
        
        $hQuery = vislog_History::getQuery();
        
        $hQuery->EXT('query', 'vislog_HistoryResources', 'externalName=query,externalKey=HistoryResourceId');
        
        if (!is_null($rec->from)) {
            $hQuery->where(array(
                "#createdOn >= '[#1#]'",
                $rec->from . ' 00:00:01'
            ));
        }
        
        if (!is_null($rec->to)) {
            $hQuery->where(array(
                "#createdOn <= '[#1#]'",
                $rec->to . ' 23:59:59'
            ));
        }
        $counter = array();
        
        $text = mb_strtolower(trim("{$rec->text}"));
        
        while ($hRec = $hQuery->fetch()) {
            $queryToLower = mb_strtolower(trim("{$hRec->query}", ' '));
            
            if (strpos($queryToLower, $text) !== false) {
                $id = $hRec->HistoryResourceId;
                
                // добавяме в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'HistoryResourceId' => $id,
                        'query' => $hRec->query,
                        'createdOn' => $hRec->createdOn,
                        'counter' => 1
                    );
                } else {
                    $obj = &$recs[$id];
                    ++$obj->counter;
                }
            }
            unset($id, $queryToLower);
        }
        
        if (is_array($recs)) {
            usort($recs, array(
                $this,
                'orderByCounter'
            ));
        }
        
        return $recs;
    }
    
    
    public function orderByCounter($a, $b)
    {
        return $a->counter < $b->counter;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('resource', 'varchar', 'caption=Ресурс');
        $fld->FLD('counter', 'varchar', 'caption=Брой,smartCenter');
        
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
        
        $row->resource = $dRec->query;
        
        $row->counter = $dRec->counter;
        
        return $row;
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
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN text-->|Текст за търсене|*: [#text#]<!--ET_END text--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
        }
        
        if (isset($data->rec->text)) {
            $fieldTpl->append('<b>' . ' ' . $data->rec->text . ' ' . '</b>', 'text');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
    {
    }
}
