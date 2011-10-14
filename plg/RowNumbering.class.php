<?php

/**
 * Клас 'plg_RowNumbering' - Добавя поле 'rowNumb' в $row
 * 
 * чрез това поле се номерират последователно всички редове, след извличането им за лист view
 *
 * Полето 'seqNumb' трябва да се добави в `var $listFields' за да се покаже.
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 3
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_RowNumbering extends core_Plugin
{
    /**
     *  Извиква се след подготовката на $data->recs и $data->rows за табличния изглед
     */
    function on_AfterPrepareListRecs($mvc, $res, $data)
    {
        if($cnt = count($data->recs)) {
            
            if($data->reverseOrder) {
                if($data->pager) {
                    $number = $data->pager->itemsCount - $data->pager->rangeStart;
                } else {
                    $number = count($data->rows);
                }
                
                $increment = -1;
            } else {
                if($data->pager) {
                    $number = $this->rangeStart + 1;
                } else {
                    $number = 1;
                }

                $increment = 1;
            }

            foreach($data->rows as $id => $row) {
                $data->rows[$id]->RowNumb = $number;
                $zebra = 0;
                if($mvc->zebraRows !== FALSE) {
                    $row->ROW_ATTR['class']  .=  ' zebra' . ($zebra % 2);
                }
                $zebra++;
                $number += $increment;
            }
        }
        
        if(!$data->listFields['RowNumb']) {
            $data->listFields = arr::combine( array('RowNumb' => '№') , $data->listFields);
        }
    }
    
 }