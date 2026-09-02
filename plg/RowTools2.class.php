<?php


/**
 * Клас 'plg_RowTools2' - Dropdown инструменти действия с реда
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_RowTools2 extends core_Plugin
{
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        static $titleDD;
        if (!$titleDD) {
            $titleDD = tr('Отваряне');
        }
        
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf') || Mode::is('noToolbar')) {
            return;
        }
        
        // Игнорираме действието, ако не се подготвя листовия изглед
        if (!arr::haveSection($fields, '-list')) {
            return;
        }

        $recObj = is_object($rec) ? $rec : $mvc->fetch($rec);
        $recId = $recObj->id ?? (is_scalar($rec) ? $rec : null);
        if (!isset($recId) || !is_scalar($recId)) {
            return;
        }
        
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $ddTools = &$row->_rowTools;
        
        // Линк към сингъла
        if (method_exists($mvc, 'act_Single')) {
            $singleUrl = $mvc->getSingleUrlArray($recId);
            
            $singleIcon = $mvc->getIcon($recId);
            
            if ($singleField = ($mvc->rowToolsSingleField ?? null)) {
                $attr1['ef_icon'] = $singleIcon;
                $row->{$singleField} = str::limitLen(strip_tags($row->{$singleField} ?? ''), 70);
                $row->{$singleField} = ht::createLink($row->{$singleField}, $singleUrl, null, $attr1);
            } else {
                $singleImg = '<img src=' . sbf($mvc->singleIcon) . " width='16' height='16' title='{$titleDD}' alt=''>";
                $singleLink = ht::createLink($singleImg, $singleUrl);
            }
        }
        
        // URL за връщане след редакция/изтриване
        if (cls::existsMethod($mvc, 'getRetUrl')) {
            $retUrl = $mvc->getRetUrl($rec);
        } else {
            $retUrl = true;
        }
        
        $singleTitle = $mvc->singleTitle;
        $singleTitle = tr($singleTitle);
        $singleTitle = mb_strtolower($singleTitle ?? '');
        
        if (!empty($singleUrl)) {
            $ddTools->addLink('Разглеждане', $singleUrl, "ef_icon={$singleIcon},title=Разглеждане на|* {$singleTitle},id=single{$recId}");
        }
        
        $editUrl = $mvc->getEditUrl($rec);
        if (!empty($editUrl)) {
            $editUrl = $mvc->getEditUrl($rec);
            $ddTools->addLink('Редактиране', $editUrl, "ef_icon=img/16/edit-icon.png,title=Редактиране на|* {$singleTitle},id=edt{$recId}");
        }

        $deleteUrl = $mvc->getDeleteUrl($rec);
        if (!empty($deleteUrl)) {
            $ddTools->addLink('Изтриване', $deleteUrl, "ef_icon=img/16/delete.png,warning=Наистина ли желаете записът да бъде изтрит?,id=del{$recId},title=Изтриване на|* {$singleTitle}");
        } else {
            if (($mvc->fields['state']->type->options['rejected'] ?? null) && !($mvc instanceof core_Master)) {
                if (($recObj->state ?? null) != 'rejected' && $mvc->haveRightFor('reject', $recId)) {
                    $rejectUrl = array($mvc, 'reject', 'id' => $recId, 'ret_url' => $retUrl);
                    $ddTools->addLink('Оттегляне', $rejectUrl, "ef_icon=img/16/reject.png,warning=Наистина ли желаете записът да бъде оттеглен?,id=rej{$recId},title=Оттегляне на|* {$singleTitle}");
                } elseif (($recObj->state ?? null) == 'rejected' && $mvc->haveRightFor('restore', $recId)) {
                    $restoreUrl = array($mvc, 'restore', 'id' => $recId, 'ret_url' => $retUrl);
                    $ddTools->addLink('Възстановяване', $restoreUrl, "ef_icon=img/16/restore.png,warning=Наистина ли желаете записът да бъде възстановен?,id=res{$recId},title=Възстановяване на|* {$singleTitle}");
                }
            }
        }
        
        if ($mvc->hasPlugin('change_Plugin')) {
            if ($mvc->haveRightFor('changerec', $rec)) {
                $changeUrl = $mvc->getChangeUrl($recId);
                $ddTools->addLink('Промяна', $changeUrl, "ef_icon=img/16/edit.png,id=chn{$recId},title=Промяна на|* {$singleTitle}");
            }
        }

        if ($mvc->hasPlugin('plg_Clone')) {
            if ($mvc->haveRightFor('clonerec', $rec)) {
                $cloneUrl = $mvc->getCloneUrl($recId);
                $ddTools->addLink('Клониране', $cloneUrl, "ef_icon=img/16/clone.png,id=clone{$recId},title=Клониране на|* {$singleTitle}");
            }
        }
        
        if (false) {
            $ddTools->addFnLink('Избор', 'actionsWithSelected();', array('ef_icon' => 'img/16/checked.png', 'title' => 'Действия с избраните', 'id' => "check{$recId}", 'class' => 'checkbox-btn'));
        }
        
        $mvc->rowToolsColumn['_rowTools'] = 'rowtools-column';
    }
    
    
    /**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    public static function on_AfterGetIcon($mvc, &$res, $id = null)
    {
        if (!$res) {
            $res = $mvc->singleIcon;
            if (log_Browsers::isRetina()) {
                $icon2 = str_replace('/16/', '/32/', $res);
                
                if (getFullPath($icon2)) {
                    $res = $icon2;
                }
            }
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     *
     * @param core_Mvc $mvc
     * @param array    $editUrl
     * @param stdClass $rec
     */
    public static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
        if (!$mvc->haveRightFor('edit', $rec)) {
            return;
        }
        $recId = is_object($rec) ? ($rec->id ?? null) : $rec;
        $retUrl = (cls::existsMethod($mvc, 'getRetUrl')) ? $mvc->getRetUrl($rec) : true;
        
        $editUrl = array(
            $mvc,
            'edit',
            'id' => $recId,
            'ret_url' => $retUrl
        );
    }
    
    
    /**
     * Реализация по подразбиране на метода getDeleteUrl()
     *
     * @param core_Mvc $mvc
     * @param array    $editUrl
     * @param stdClass $rec
     */
    public static function on_BeforeGetDeleteUrl($mvc, &$deleteUrl, $rec)
    {
        if (!$mvc->haveRightFor('delete', $rec)) {
            return;
        }
        
        $recId = is_object($rec) ? ($rec->id ?? null) : $rec;
        $deleteUrl = array($mvc, 'delete', 'id' => $recId, 'ret_url' => true);
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listFields = arr::make($data->listFields ?? array(), true);
        unset($data->listFields['_rowTools']);
        
        if (empty($data->rows) || !is_array($data->rows) || Mode::is('hideToolbar') === true) {
            return ;
        }
        
        $mustShow = false;
        
        foreach ($data->rows as $id => &$row) {
            if (!isset($data->recs[$id])) {
                continue;
            }
            $rec = $data->recs[$id];
            
            // Ако има тулбар за реда
            if (isset($row->_rowTools)) {
                $tools = &$row->_rowTools;
                
                // Ако е оказано поле за линк към сингъла, и имаме само бутон за сингъл
                if (isset($mvc->rowToolsSingleField) && $tools->haveButton("single{$rec->id}") && $tools->count() == 1) {
                    
                    // Махаме го
                    $tools->removeBtn("single{$rec->id}");
                }

                $mvc->invoke('BeforeRenderListTableRowToolbar', array(&$tools, $data));
                $tools = $tools->renderHtml($mvc->rowToolsMinLinksToShow ?? null);
                
                if ($tools) {
                    $mustShow = true;
                }
            }
        }
        
        $img = ht::createElement('img', array('src' => sbf('img/16/tools.png', '')));
        
        if ($mustShow) {
            $data->listFields = arr::combine(array('_rowTools' => '|*' . $img->getContent()), arr::make($data->listFields, true));
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        jquery_Jquery::run($tpl, 'actionsWithSelected();');
    }
}
