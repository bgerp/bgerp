<?php


/**
 * Плъгин даващ възможност да се печатат етикети от обект
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_plg_Print extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canPrintlabel, 'label, admin, ceo');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list']) && $mvc->hasPlugin('plg_RowTools2')) {
            $btnParams = self::getLabelBtnParams($mvc, $rec);
            if (!empty($btnParams['url'])) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $btnParams['attr'] = arr::make($btnParams['attr']);
                $btnParams['attr']['style'] = 'position: relative; top: -2px;';
                $row->_rowTools->addLink('Етикети', $btnParams['url'], $btnParams['attr'], 'alwaysShow');
            }
        }
    }
    
    
    /**
     * Параметрите на бутона за етикетиране
     *
     * @param core_mvc $mvc
     * @param stdClass $rec
     *
     * @return array $res -
     *               ['url'] - урл, ако има права
     *               ['attr] - атрибути
     */
    private static function getLabelBtnParams($mvc, $rec)
    {
        $res = array('url' => null, 'attr' => '');
        
        if ($mvc->haveRightFor('printlabel', $rec)) {
            $templates = label_Templates::getTemplatesByDocument($mvc, $rec->id, true);
            $error = (!count($templates)) ? ",error=Няма наличен шаблон за етикети от \"{$mvc->title}\"" : '';
            
            if (label_Prints::haveRightFor('add', (object) array('classId' => $mvc->getClassId(), 'objectId' => $rec))) {
                core_Request::setProtected(array('classId, objectId'));
                $res['url'] = array('label_Prints', 'add', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => true);
                $res['url'] = toUrl($res['url']);
                core_Request::removeProtected('classId,objectId');
                $res['attr'] = "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от|* |{$mvc->title}|* №{$rec->id}{$error}";
            }
        }
        
        return $res;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $btnParams = self::getLabelBtnParams($mvc, $data->rec);
        if (!empty($btnParams['url'])) {
            $data->toolbar->addBtn('Етикети', $btnParams['url'], null, $btnParams['attr']);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'printlabel' && isset($rec)) {
            if (in_array($rec->state, array('rejected', 'draft', 'template'))) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Заглавие от източника на етикета
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param mixed    $id
     *
     * @return void
     */
    public static function on_AfterGetLabelSourceLink($mvc, &$res, $id)
    {
        if (cls::existsMethod($mvc, 'getFormTitleLink')) {
            $res = $mvc->getFormTitleLink($id);
        } elseif ($mvc instanceof core_Detail) {
            $rec = $mvc->fetchRec($id);
            $res = $mvc->Master->getFormTitleLink($rec->{$mvc->masterKey});
        }
    }
}
