<?php



/**
 * Клас 'plg_RowNumbering' - Добавя поле 'rowNumb' в $row
 *
 * чрез това поле се номерират последователно всички редове, след извличането им за лист view
 * За да се изключи зебра оцветяването - ver $zebraRows = FALSE
 * Плъгинът брои номерира редовете, като се съобразява с пейджъра core_Pager (страньора)
 * Може да поддържа реверсивно номериране, ако $data->reverseOrdering = TRUE
 * Плъгина добавя поле RowNumb, ако то липсва в $data->listFields
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_RowNumbering extends core_Plugin
{
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'ASC');
    }
    
    
    /**
     * Извиква се след подготовката на $data->recs и $data->rows за табличния изглед
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if ($cnt = count($data->recs)) {
            if ($data->reverseOrder) {
                if ($data->pager) {
                    $number = $data->pager->itemsCount - $data->pager->rangeStart;
                } else {
                    $number = count($data->rows);
                }
                
                $increment = -1;
            } else {
                if ($data->pager) {
                    $number = $data->pager->rangeStart + 1;
                } else {
                    $number = 1;
                }
                
                $increment = 1;
            }
            
            $zebra = 1;
            
            foreach ($data->rows as $id => $row) {
                if ($data->rows[$id]->RowNumb instanceof core_Et) {
                    $data->rows[$id]->RowNumb->append($number, 'ROWTOOLS_CAPTION');
                } else {
                    $data->rows[$id]->RowNumb .= "<span class='detailNumbering'>${number}</span>";
                }

                $rec = $data->recs[$id];

                if ($mvc->zebraRows !== false && $rec->state == '') {
                    $row->ROW_ATTR['class'] .= ' zebra' . ($zebra % 2);
                }
                $zebra++;
                $number += $increment;
            }
        }
        
        if (!$data->listFields['RowNumb'] && $mvc instanceof core_Detail) {
            $data->listFields = arr::combine(array('RowNumb' => '№'), $data->listFields);
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, &$data)
    {
        $data->listTableMvc->FLD('RowNumb', 'int', 'tdClass=rowNumColumn');
    }
}
