<?php



/**
 * Клас 'plg_RowTools' - Инструменти за изтриване и редактиране на ред
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

            if(cls::haveInterface('doc_DocumentIntf', $mvc)) {
                $icon = $mvc->getIcon($rec->id);
            } else {
                $icon = $mvc->singleIcon;
            }
            
            if($singleField = $mvc->rowToolsSingleField) { 
                $attr1['class'] = 'linkWithIcon';
                $attr1['style'] = 'background-image:url(' . sbf($icon) . ');';
                $row->{$singleField} = str::limitLen(strip_tags($row->{$singleField}), 70);
                $row->{$singleField} = ht::createLink($row->{$singleField}, $singleUrl, NULL, $attr1);  
            } else {
                $singleImg = "<img src=" . sbf($mvc->singleIcon) . ">";
                $singleLink = ht::createLink($singleImg, $singleUrl);
            }
        }
        
        // URL за връщане след редакция/изтриване
        if(method_exists($mvc, 'getRetUrl')) {
            $retUrl = $mvc->getRetUrl($rec);
        } else {
            $retUrl = TRUE;
        }
        
        if ($mvc->haveRightFor('edit', $rec)) {
            $editUrl = $mvc->getEditUrl($rec);
            $editImg = "<img src=" . sbf('img/16/edit-icon.png') . ">";

            $editLink = ht::createLink($editImg, $editUrl, NULL, "id=edt{$rec->id}");
        }
        
         if ($mvc->haveRightFor('delete', $rec)) {
            $deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . ">";
            $deleteUrl = array(
	            $mvc,
	            'delete',
	            'id' => $rec->id,
	            'ret_url' => $retUrl
        	);
        	
        	$deleteLink = ht::createLink($deleteImg, $deleteUrl,
                tr('Наистина ли желаете записът да бъде изтрит?'), "id=del{$rec->id}");
        } else {
        	$loadList = arr::make($mvc->loadList);
        	if(in_array('plg_Rejected', $loadList)){
        		if($rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec->id)){
        			$deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . ">";
        			$deleteUrl = array(
			            $mvc,
			            'reject',
			            'id' => $rec->id,
			            'ret_url' => TRUE);
			         $deleteLink = ht::createLink($deleteImg, $deleteUrl,
                		tr('Наистина ли желаете записът да бъде оттеглен?'), "id=rej{$rec->id}");
        			
        		} elseif($rec->state == 'rejected' && $mvc->haveRightFor('restore', $rec->id)){
        			$deleteImg = "<img src=" . sbf('img/16/restore-icon.png') . ">";
        				
        			$deleteUrl = array(
			            $mvc,
			            'restore',
			            'id' => $rec->id,
			            'ret_url' => TRUE);
			            
			        $deleteLink = ht::createLink($deleteImg, $deleteUrl,
                		tr('Наистина ли желаете записът да бъде възстановен?'), "id=res{$rec->id},class=btn-restore");
        		}
        	}
        }
                
        if($singleLink || $editLink || $deleteLink) {
            // Вземаме съдържанието на полето, като шаблон
            $tpl = new ET("<div class='rowtools'><div class='l nw'>[#TOOLS#]</div><div class='r'>[#ROWTOOLS_CAPTION#]</div></div>");
            $tpl->append($row->{$field}, 'ROWTOOLS_CAPTION');
            $tpl->append($singleLink, 'TOOLS');
            $tpl->append($editLink, 'TOOLS');
            $tpl->append($deleteLink, 'TOOLS');
            $row->{$field} = $tpl;
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     * 
     * @param core_Mvc $mvc
     * @param array $editUrl
     * @param stdClass $rec
     */
    public static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
        // URL за връщане след редакция
        if(method_exists($mvc, 'getRetUrl')) {
            $retUrl = $mvc->getRetUrl($rec);
        } else {
            $retUrl = TRUE;
        }
        
        $editUrl = array(
            $mvc,
            'edit',
            'id' => $rec->id,
            'ret_url' => $retUrl
        );
    }
    
    
	/**
     * Реализация по подразбиране на метода getDeleteUrl()
     * 
     * @param core_Mvc $mvc
     * @param array $editUrl
     * @param stdClass $rec
     */
    public static function on_BeforeGetDeleteUrl($mvc, &$deleteUrl, $rec)
    {
        // URL за връщане след редакция
        if(method_exists($mvc, 'getDeleteUrl')) {
            $retUrl = $mvc->getDeleteUrl($rec);
        } else {
            $retUrl = TRUE;
        }
        
        $deleteUrl = array(
            $mvc,
            'delete',
            'id' => $rec->id,
            'ret_url' => $retUrl
        );
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