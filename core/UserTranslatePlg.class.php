<?php


/**
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_UserTranslatePlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        $mvc->doWithSelected = arr::make($mvc->doWithSelected) + array('userTranslate' => 'Превод');
        setIfNot($mvc->canUsertranslate, 'admin, translate');
    }
    
    
    /**
     * С избраните да редиректва
     *
     * @return false
     */
    public function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'usertranslate') {
            $mvc->requireRightFor('usertranslate');
            
            $Selected = Request::get('Selected');
            $selArr = arr::make($Selected);
            
            Request::setProtected(array('classId', 'recId'));
            $res = new Redirect(array('core_UserTranslates', 'add', 'Selected' => $Selected, 'classId' => $mvc->getClassId(), 'recId' => $selArr[0], 'ret_url' => getRetUrl()));
            
            return false;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'usertranslate' && $requiredRoles != 'no_one') {
            if (!core_UserTranslates::haveRightFor('add', (object) array('classId' => $mvc->getClassId(), 'recId' => $rec->id))) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (core_UserTranslates::haveRightFor('add', (object) array('classId' => $mvc->getClassId(), 'recId' => $data->rec->id))) {
            Request::setProtected(array('classId', 'recId'));
            $data->toolbar->addBtn('Превод', array('core_UserTranslates', 'add', 'classId' => $mvc->getClassId(), 'recId' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/font.png, title = Добавяне на превод, row=2');
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     * 
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     * @param null|string $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        if (!($mvc instanceof core_Master)) {
            if (core_UserTranslates::haveRightFor('add', (object) array('classId' => $mvc->getClassId(), 'recId' => $rec->id))) {
                Request::setProtected(array('classId', 'recId'));
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Превод', array('core_UserTranslates', 'add', 'classId' => $mvc->getClassId(), 'recId' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/font.png, title = Добавяне на превод');
            }
        }
    }
    
    
    /**
     * След като е готово вербалното представяне
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param stdClass $rec
     * @param string $part
     */
    public static function on_AfterGetVerbal($mvc, &$res, $rec, $part)
    {
        $uTranslateFields = core_UserTranslates::getUserTranslateFields($mvc->getClassId(), '*', $rec->id);
        
        if ($uTranslateFields[$part] && $rec->{$part}) {
            
            $trArr = explode('|', $uTranslateFields[$part]->translate);
            
            $val = $rec->{$part};
            
            $tr = null;
            
            foreach ($trArr as $tName) {
                
                if ($tName == 'tr') {
                    $tr = tr($val);
                    if ($tr != $val) {
                        break;
                    }
                } elseif ($tName == 'transliterate') {
                    $tr = core_Lg::transliterate($val);
                    if ($tr != $val) {
                        break;
                    }
                } elseif ($tName == 'field') {
                    $lg = ucfirst(core_Lg::getCurrent());
                    $lgPart = $part . $lg;
                    if (isset($rec->{$lgPart}) && ($rec->{$lgPart} != $rec->{$part})) {
                        $tr = $rec->{$lgPart};
                        break;
                    }
                } elseif ($tName == 'user') {
                    $uTranslate = core_UserTranslates::getUserTranslatedStr($mvc->getClassId(), $rec->id, core_Lg::getCurrent(), $part, $rec->{$part});
                    if (isset($uTranslate)) {
                        $tr = $uTranslate;
                        
                        break;
                    }
                } else {
                    expect(FALSE, $trArr);
                }
            }
            
            if (isset($tr)) {
                $res = $tr;
            }
        }
    }
}
