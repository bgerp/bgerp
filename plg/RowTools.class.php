<?php



/**
 * Клас 'plg_RowTools' - Инструменти за изтриване и редактиране на ред
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_RowTools extends core_Plugin
{
    
    
    /**
     * Шаблон за съзване на rowTools
     */
    static $rowToolsTpl = "<div class='rowtools'><div class='l nw'>[#TOOLS#]</div><div class='r'>[#ROWTOOLS_CAPTION#]</div></div>";
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf')) return;
        
        if(!arr::haveSection($fields, '-list')) return;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        if(method_exists($mvc, 'act_Single')) {
            
            $singleUrl = $mvc->getSingleUrlArray($rec->id);

            $icon = $mvc->getIcon($rec->id);
            
            if($singleField = $mvc->rowToolsSingleField) {
                $attr1['class'] = 'linkWithIcon';
                $attr1['ef_icon'] = $icon;
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
        $iconSize = 16;
        if(log_Browsers::isRetina()) {
            $iconSize = 32;
        }
        
        if ($mvc->haveRightFor('edit', $rec)) {
            $editUrl = $mvc->getEditUrl($rec);
            $editImg = "<img src=" . sbf("img/{$iconSize}/edit-icon.png") . " width=16  height=16 alt=\"" . tr('Редакция') . "\">";

            $editLink = ht::createLink($editImg, $editUrl, NULL, "id=edt{$rec->id},title=" . tr("Редактиране на") . ' ' . $singleTitle);
        }
        
         if ($mvc->haveRightFor('delete', $rec)) {
            $deleteImg = "<img src=" . sbf("img/{$iconSize}/delete.png") . " width=16  height=16 alt=\"" . tr('Изтриване') . "\">";
            $deleteUrl = array(
	            $mvc,
	            'delete',
	            'id' => $rec->id,
	            'ret_url' => $retUrl
        	);
        	
        	$deleteLink = ht::createLink($deleteImg, $deleteUrl,
                tr('Наистина ли желаете записът да бъде изтрит?'), "id=del{$rec->id},title=" . tr("Изтриване на") . ' ' . $singleTitle);
        } else {
        	$loadList = arr::make($mvc->loadList);
        	if(in_array('plg_Rejected', $loadList)){
        		if($rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec->id) && !($mvc instanceof core_Master)){
        			$deleteImg = "<img src=" . sbf("img/{$iconSize}/reject.png") . " width=16  height=16 alt=\"" . tr('Оттегляне') . "\">";
        			$deleteUrl = array(
			            $mvc,
			            'reject',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
			         $deleteLink = ht::createLink($deleteImg, $deleteUrl,
                		tr('Наистина ли желаете записът да бъде оттеглен?'), "id=rej{$rec->id},title=" . tr("Оттегляне на") . ' ' . $singleTitle);
        			
        		} elseif($rec->state == 'rejected' && $mvc->haveRightFor('restore', $rec->id)){
        			$restoreImg = "<img src=" . sbf("img/{$iconSize}/restore.png") . " width=16  height=16 alt=\"" . tr('Възстановяване') . "\">";
        				
        			$restoreUrl = array(
			            $mvc,
			            'restore',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
			            
			        $restoreLink = ht::createLink($restoreImg, $restoreUrl,
                		tr('Наистина ли желаете записът да бъде възстановен?'), "id=res{$rec->id},title=" . tr("Възстановяване на") . ' ' . $singleTitle);
        		}
        	}
        }
        
        if($mvc->hasPlugin('change_Plugin')){
        	if ($mvc->haveRightFor('changerec', $rec)) {
        		$changeLink = $mvc->getChangeLink($rec->id);
        	}
        }
        
        $tpl = new ET(static::$rowToolsTpl);
        $tpl->append($row->{$field}, 'ROWTOOLS_CAPTION');
        
        if ($singleLink || $editLink || $deleteLink || $restoreLink || $changeLink) {
            // Вземаме съдържанието на полето, като шаблон
            $tpl->append($singleLink, 'TOOLS');
            $tpl->append($editLink, 'TOOLS');
            $tpl->append($deleteLink, 'TOOLS');
            $tpl->append($restoreLink, 'TOOLS');
            $tpl->append($changeLink, 'TOOLS');
        }
        $row->{$field} = $tpl;
        
        if (!isset($mvc->rowToolsColumn)) {
            $mvc->rowToolsColumn = array();
        }
        $mvc->rowToolsColumn[$field] = 'rowtools-column';
    }
    
    
    /**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    public static function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
            if(log_Browsers::isRetina()) {
                $icon2 = str_replace('/16/', '/32/', $res);

                if(getFullPath($icon2)) {
                    $res = $icon2;
                }
            }
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
        $data->listFields =  arr::make($data->listFields, TRUE);

         
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        if(count($data->rows)) {
            $rowToolsTpl = new ET(static::$rowToolsTpl);
            
            foreach($data->rows as $row) {
                
                // Ако в някой от полетата има промяна по шаблона
                if(isset($row->{$field})){
                	if ($rowToolsTpl->content != $row->{$field}->content) return;
                }
            }
        }
        
        if(isset($data->listFields[$field])) {
            unset($data->listFields[$field]);
        }
    }
}