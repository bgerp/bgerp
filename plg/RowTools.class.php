<?php

/**
 * Клас 'plg_RowTools' - Инструменти за изтриване и редактиране на ред
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_RowTools extends core_Plugin
{
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        
        if( method_exists($mvc, 'act_Single') && $mvc->haveRightFor('single', $rec)) {
            
            $singleImg = "<img src=" . sbf('img/16/view.png') . ">";
            
            $singleUrl = toUrl(array(
                $mvc,
                'single',
                'id' => $rec->id,
                'ret_url' => TRUE
            ));
            
            $singleLink = ht::createLink($singleImg, $singleUrl);
        }
        

        if ($mvc->haveRightFor('edit', $rec)) {
            
            $editImg = "<img src=" . sbf('img/16/edit-icon.png') . ">";
            
            $editUrl = array(
                $mvc,
                'edit',
                'id' => $rec->id,
                'ret_url' => TRUE
            );
            
            $editLink = ht::createLink($editImg, $editUrl);
        }


        if ($mvc->haveRightFor('delete', $rec)) {
            
            $deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . ">";
            
            $deleteUrl = array(
                $mvc,
                'delete',
                'id' => $rec->id,
                'ret_url' => TRUE
            );
            
            $deleteLink = ht::createLink($deleteImg, $deleteUrl,
            tr('Наистина ли желаете записът да бъде изтрит?'));
        }
   

        if($singleLink || $editLink || $deleteLink) {
            // Вземаме съдържанието на полето, като шаблон
            $row->{$field} = new ET($row->{$field});
            $tpl =& $row->{$field};
             
            $tpl->append("<div class='rowtools'>");
            $tpl->append($singleLink);
            $tpl->append($editLink);
            $tpl->append($deleteLink);
            
            $tpl->append("</div>");
        }
    }

    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    function on_AfterRenderListSummary($mvc, $res, $data)
    {
    	// Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        if(count($data->rows)) {
            foreach($data->rows as $row) {

                if($row->{$field}) return; 
            }
            
        }
        
        unset($data->listFields[$field]);
    }
}