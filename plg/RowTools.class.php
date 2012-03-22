<?php



/**
 * Клас 'plg_RowTools' - Инструменти за изтриване и редактиране на ред
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_RowTools extends core_Plugin
{
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        if(!arr::haveSection($fields, '-list')) return;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        if(method_exists($mvc, 'act_Single') && $mvc->haveRightFor('single', $rec)) {
            
            $singleUrl = toUrl(array(
                    $mvc,
                    'single',
                    'id' => $rec->id,
                    'ret_url' => TRUE
                ));
            
            if($singleField = $mvc->rowToolsSingleField) {
                $attr1['class'] = 'linkWithIcon';
                $attr1['style'] = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
                $row->{$singleField} = str::limitLen(strip_tags($row->{$singleField}), 70);
                $row->{$singleField} = ht::createLink($row->{$singleField}, $singleUrl, NULL, $attr1);
            } else {
                $singleImg = "<img src=" . sbf($mvc->singleIcon) . ">";
                $singleLink = ht::createLink($singleImg, $singleUrl);
            }
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
            $tpl = new ET("<div class='rowtools'><div class='l nw'>[#TOOLS#]</div><div class='r'>[#ROWTOOLS_CAPTION#]</span></div>");
            $tpl->append($row->{$field}, 'ROWTOOLS_CAPTION');
            $tpl->append($singleLink, 'TOOLS');
            $tpl->append($editLink, 'TOOLS');
            $tpl->append($deleteLink, 'TOOLS');
            $row->{$field} = $tpl;
        }
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listFields = arr::make($data->listFields, TRUE);
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        if(count($data->rows)) {
            foreach($data->rows as $row) {
                
                if($row->{$field}) return;
            }
        }
        
        if(isset($data->listFields[$field])) {
            unset($data->listFields[$field]);
        }
    }
}