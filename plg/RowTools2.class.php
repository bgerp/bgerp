<?php



/**
 * Клас 'plg_RowTools2' - Dropdown инструменти действия с реда
 *
 *
 * @category  bgerp
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
        static $titleDD;
        if(!$titleDD) {
            $titleDD = tr('Отваряне');
        }

        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf')) return;
        
        // Игнорираме действието, ако не се подготвя листовия изглед
        if(!arr::haveSection($fields, '-list')) return;
        
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $ddTools = &$row->_rowTools;

        // Линк към сингъла
        if(method_exists($mvc, 'act_Single')) {
 
            $singleUrl = $mvc->getSingleUrlArray($rec->id);

            $singleIcon = $mvc->getIcon($rec->id);
            
            if($singleField = $mvc->rowToolsSingleField) {
                $attr1['ef_icon'] =$singleIcon;
                $row->{$singleField} = str::limitLen(strip_tags($row->{$singleField}), 70);
                $row->{$singleField} = ht::createLink($row->{$singleField}, $singleUrl, NULL, $attr1);  
            } else {
                $singleImg = "<img src=" . sbf($mvc->singleIcon) . " width='16' height='16' title='{$titleDD}' alt=''>";
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
        
        if (!empty($singleUrl)) {
            $ddTools->addLink('Разглеждане', $singleUrl, "ef_icon={$singleIcon},title=Разглеждане на|* {$singleTitle},id=single{$rec->id}");
        }

        if ($mvc->haveRightFor('edit', $rec)) {
            $editUrl = $mvc->getEditUrl($rec);
            $ddTools->addLink('Редактиране', $editUrl, "ef_icon=img/16/edit-icon.png,title=Редактиране на|* {$singleTitle},id=edt{$rec->id}");
        }
        
         if ($mvc->haveRightFor('delete', $rec)) {
            $deleteUrl = array(
	            $mvc,
	            'delete',
	            'id' => $rec->id,
	            'ret_url' => $retUrl
        	);


             $ddTools->addLink('Изтриване', $deleteUrl, "ef_icon=img/16/delete.png,warning=Наистина ли желаете записът да бъде изтрит?,id=del{$rec->id},title=Изтриване на|* {$singleTitle}");

        } else {
        	if($mvc->fields['state']->type->options['rejected']){
        		if($rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec->id)){  
        			$rejectUrl = array(
			            $mvc,
			            'reject',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
                    
        			if(!($mvc instanceof core_Master)){

                        $ddTools->addLink('Оттегляне', $rejectUrl, "ef_icon=img/16/reject.png,warning=Наистина ли желаете записът да бъде оттеглен?,id=rej{$rec->id},title=Оттегляне на|* {$singleTitle}");
        			}
        		} elseif($rec->state == 'rejected' && $mvc->haveRightFor('restore', $rec->id)){
        			$restoreUrl = array(
			            $mvc,
			            'restore',
			            'id' => $rec->id,
			            'ret_url' => $retUrl);
			        
        			if(!($mvc instanceof core_Master)){
        				$ddTools->addLink('Възстановяване', $restoreUrl, "ef_icon=img/16/restore.png,warning=Наистина ли желаете записът да бъде възстановен?,id=res{$rec->id},title=Възстановяване на|* {$singleTitle}");
        			}
        		}
        	}
        }
        
        if($mvc->hasPlugin('change_Plugin')){
        	if ($mvc->haveRightFor('changerec', $rec)) {
        		$changeUrl = $mvc->getChangeUrl($rec->id);
        		$ddTools->addLink('Промяна', $changeUrl, "ef_icon=img/16/edit.png,id=chn{$rec->id},title=Промяна на|* {$singleTitle}");

                }
        }

        if(FALSE) {
            $ddTools->addFnLink('Избор', 'actionsWithSelected();', array('ef_icon' => "img/16/checked.png", 'title' => "Действия с избраните", "id"=>"check{$rec->id}", "class" => 'checkbox-btn'));
        }

        $mvc->rowToolsColumn['_rowTools'] = 'rowtools-column';
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
        $data->listFields = arr::make($data->listFields, TRUE);

        unset($data->listFields['_rowTools']);

        if (!is_array($data->rows) || empty($data->rows)) return ;
        
        $mustShow = FALSE;
        
        foreach($data->rows as $id => &$row) {
			$rec = $data->recs[$id];
        	
			// Ако има тулбар за реда
    		if(isset($row->_rowTools)) {
    			$tools = &$row->_rowTools;
            	
    			// Ако е оказано поле за линк към сингъла, и имаме само бутон за сингъл
            	if(isset($mvc->rowToolsSingleField) && $tools->hasBtn("single{$rec->id}") && $tools->count() == 1){
            		
            		// Махаме го
            		$tools->removeBtn("single{$rec->id}");
            	}
            	
                $tools = $tools->renderHtml($mvc->rowToolsMinLinksToShow);
                if($tools) {
                    $mustShow = TRUE;
                }
            }
        }
        
        $img = ht::createElement('img', array('src'=> sbf('img/16/tools.png', "")));
        
        if($mustShow) {
            $data->listFields =  arr::combine(array('_rowTools' => '|*' . $img->getContent()), arr::make($data->listFields, TRUE));	
        }


    }


    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data) {
        jquery_Jquery::run($tpl, "actionsWithSelected();");
    }
}