<?php



/**
 * Клас 'plg_RowZebra' - Алтернативно оцветяване на редовете в лист изглед
 *
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
class plg_RowZebra extends core_Plugin
{
    
    
    /**
     * Извиква се след подготовката на $data->recs и $data->rows за табличния изглед
     */
    public function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if ($cnt = count($data->recs)) {
            $zebra = 1;
            
            foreach ($data->rows as $id => $row) {
                if ($mvc->zebraRows !== false && $rec->state == '') {
                    $row->ROW_ATTR['class'] .= ' zebra' . ($zebra % 2);
                }
                $zebra++;
            }
        }
    }
}
