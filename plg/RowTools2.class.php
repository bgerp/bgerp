<?php



/**
 * Клас 'plg_RowTools2' - Dropdown инструменти за изтриване и редактиране на ред
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_RowTools2 extends core_Plugin
{
    
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain')) return;
        
        //if(!isset($mvc->rowTools2Field)) return;
        
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $ddTools = &$row->_rowTools;

        // Линк към сингъла
        if(method_exists($mvc, 'act_Single')) {
            
            $singleUrl = $mvc->getSingleUrlArray($rec->id);

            $icon = $mvc->getIcon($rec->id);
            
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
        if(cls::existsMethod($mvc, 'getRetUrl')) {
            $retUrl = $mvc->getRetUrl($rec);
        } else {
            $retUrl = TRUE;
        }
        
        $singleTitle = $mvc->singleTitle;
        $singleTitle = tr($singleTitle);
        $singleTitle = mb_strtolower($singleTitle);
        
        if ($mvc->haveRightFor('edit', $rec)) {
            $editUrl = $mvc->getEditUrl($rec);
            $ddTools->addLink('Редактиране', $editUrl, 'ef_icon=img/16/edit-icon.png');
        }
        
         if ($mvc->haveRightFor('delete', $rec)) {
            $deleteUrl = array(
	            $mvc,
	            'delete',
	            'id' => $rec->id,
	            'ret_url' => $retUrl
        	);
            $ddTools->addLink('Изтриване', $deleteUrl, "ef_icon=img/16/delete.png,warning=Наистина ли желаете записът да бъде изтрит?,id=del{$rec->id},title=Изтриване на {$singleTitle}");

        } else {
        	$loadList = arr::make($mvc->loadList);
        	if(in_array('plg_Rejected', $loadList)){
        		if($rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec->id) && !($mvc instanceof core_Master)){
        			$rejectUrl = array(
			            $mvc,
			            'reject',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
                    
                    $ddTools->addLink('Оттегляне', $rejectUrl, "ef_icon=img/16/reject.png,warning=Наистина ли желаете записът да бъде оттеглен?,id=del{$rec->id},title=Оттегляне на {$singleTitle}");        			
        		} elseif($rec->state == 'rejected' && $mvc->haveRightFor('restore', $rec->id)){
        			$restoreUrl = array(
			            $mvc,
			            'restore',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
			        
                    $ddTools->addLink('Възстановяване', $restoreUrl, "ef_icon=img/16/restore.png,warning=Наистина ли желаете записът да бъде възстановен?,id=del{$rec->id},title=Възстановяване на {$singleTitle}");        			
        		}
        	}
        }
        
        if($mvc->hasPlugin('change_Plugin')){
        	if ($mvc->haveRightFor('changerec', $rec)) {
        		$changeLink = $mvc->getChangeLink($rec->id);
        	}
        }
    }
    
    
    /**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    public static function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
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
        if(cls::existsMethod($mvc, 'getRetUrl')) {
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
        if(cls::existsMethod($mvc, 'getDeleteUrl')) {
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
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        foreach($data->rows as &$row) {  
            if(isset($row->_rowTools)) {
                $row->_rowTools = $row->_rowTools->renderHtml();
                if($row->_rowTools) {
                    $mustShow = TRUE;
                }
            }
        }
        
        if($mustShow) {
            $data->listFields =  arr::combine(array('_rowTools' => '▼'), arr::make($data->listFields, TRUE));
        }
    }

}