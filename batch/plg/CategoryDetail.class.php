<?php


/**
 * Клас 'batch_plg_CategoryDetail' - за добавяне на детайл към категория
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_plg_CategoryDetail extends core_Plugin
{
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $exRec = batch_CategoryDefinitions::fetch("#categoryId = {$rec->id}");
        if(is_object($exRec)){
            $row->templateId = batch_Templates::getHyperlink($exRec->templateId, true);
            if(batch_CategoryDefinitions::haveRightFor('edit', $exRec)){
                $row->templateId .= ht::createLink('', array('batch_CategoryDefinitions', 'edit', $exRec->id, 'ret_url' => true), false, "ef_icon=img/16/edit.png,title=Промяна на избраната партидност в категорията");
            }
            if(batch_CategoryDefinitions::haveRightFor('delete', $exRec)){
                $row->templateId .= ht::createLink('', array('batch_CategoryDefinitions', 'delete', $exRec->id, 'ret_url' => true), "Наистина ли желаете да отвържете партидността от категорията|*!", "ef_icon=img/16/delete.png,title=Премахване на избраната партидност от категорията");
            }
        } else {
            $row->templateId = tr('Няма');
            if(batch_CategoryDefinitions::haveRightFor('add', (object) array('categoryId' => $rec->id))){
                $row->templateId .= ht::createLink('', array('batch_CategoryDefinitions', 'add', 'categoryId' => $rec->id, 'ret_url' => true), false, "ef_icon=img/16/add.png,title=Избор на партидност за категорията");
            }
        }
    }
}
