<?php


/**
 * Клас 'plg_Current' - Прави текущ за сесията избран запис от модела
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_Current extends core_Plugin
{
    /**
     * Връща указаната част (по подразбиране - id-то) на текущия за сесията запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string   $part   поле от модела-домакин
     * @param bool     $bForce Дали да редирект към мениджъра ако не е избран текущ обект
     */
    public static function on_AfterGetCurrent($mvc, &$res, $part = 'id', $bForce = true)
    {
        if (!$res) {
            $modeKey = self::getModeKey($mvc->className);
            
            // Опитваме се да вземем от сесията текущия обект
            $res = Mode::get($modeKey)->{$part};
            
            // Ако в сесията го има обекта, връщаме го
            if ($res) {
                
                return;
            }
            
            $query = $mvc->getQuery();
            $query->where("#state != 'rejected' || #state != 'closed' || #state IS NULL");
            
            // Генериране на събитие, нотифициращо че предстои форсиране на обекта
            $mvc->invoke('BeforeSelectByForce', array(&$query));
            
            // Ако има точно един обект, който потребителя може да избере се избира автоматично
            if ($query->count() == 1) {
                $rec = $query->fetch();
                
                // Избиране на единствения обект (ако потребителя може да го избере)
                self::setCurrent($mvc, $res, $rec);
            }
            
            // Ако форсираме
            if ($bForce) {
                
                // Ако няма резултат, и името на класа е различно от класа на контролера (за да не стане безкрайно редиректване)
                if (empty($res) && ($mvc->className != Request::get('Ctr'))) {
                    
                    // Подканваме потребителя да избере обект от модела, като текущ
                    redirect(array($mvc, 'list', 'ret_url' => true), false, '|Моля, изберете текущ/а|* |' . $mvc->singleTitle);
                }
            }
        }
    }
    
    
    /**
     * Слага id-то на даден мениджър в сесия
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string   $action
     *
     * @return bool
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'setcurrent') {
            $id = Request::get('id', 'int');
            
            expect($rec = $mvc->fetch($id));
            
            $mvc->requireRightFor('select', $rec);
            
            $mvc->selectCurrent($rec);
            
            if (!Request::get('ret_url')) {
                $res = new Redirect(array($mvc));
            } else {
                $res = new Redirect(getRetUrl());
            }
            
            return false;
        }
    }
    
    
    /**
     * Моделна функция, която задава текущия запис за посочения клас (mvc)
     *
     * @param $mvc   инстанция на mvc класа
     * @param $rec   mixed id към запис, който трябва да стана текущ или самия запис
     */
    public static function on_AfterSelectCurrent($mvc, &$res, $rec)
    {
        $className = cls::getClassName($mvc);
        
        if (!is_object($rec)) {
            expect(is_numeric($rec), $rec);
            expect($rec = $mvc->fetch($rec));
        }
        
        // Кой е текущия обект
        $curId = $mvc->getCurrent('id', false);
        
        // Ако текущия обект е различен от избрания, избира се новия
        if ($curId != $rec->id) {
            self::setCurrent($mvc, $res, $rec);
        }
        
        if (!isset($res)) {
            $res = $rec->id;
        }
    }
    
    
    /**
     * Помощна функция, която записва в сесията текущия обект
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $rec
     *
     * @return void
     */
    private static function setCurrent($mvc, &$res, &$rec)
    {
        // Ако текущия потребител няма права - не правим избор
        if (!$mvc->haveRightFor('select', $rec)) {
            
            return;
        }
        
        $className = cls::getClassName($mvc);
        
        // Задаваме новия текущ запис
        $modeKey = self::getModeKey($className);
        Mode::setPermanent($modeKey, $rec);
        
        // Слагане на нотификация
        $objectName = $mvc->getTitleById($rec->id);
        $singleTitle = mb_strtolower($mvc->singleTitle);
        
        // Добавяме статус съобщението
        core_Statuses::newStatus("|Успешен избор на {$singleTitle}|* \"{$objectName}\"");
        
        // Извикваме събитие за да сигнализираме, че е сменен текущия елемент
        $mvc->invoke('afterChangeCurrent', array(&$res, $rec));
        
        $res = $rec->id;
    }
    
    
    /**
     * Връща ключа от сесията, под който се държат текущите записи
     */
    private static function getModeKey($className)
    {
        return 'currentPlg_' . $className;
    }
    
    
    /**
     * Добавя функционално поле 'currentPlg'
     *
     * @param $mvc
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        $data->listFields['currentPlg'] = 'Текущ';
        $mvc->FNC('currentPlg', 'varchar', 'caption=Терминал,tdClass=centerCol');
    }
    
    
    /**
     * Слага съдържание на полето 'currentPlg'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Проверяваме имали текущ обект
        $currentId = $mvc->getCurrent('id', false);
        
        if ($rec->id == $currentId) {
            
            // Ако записа е текущия обект, маркираме го като избран
            $row->currentPlg = ht::createElement('img', array('src' => sbf('img/16/accept.png', ''), 'width' => '16', 'height' => '16'));
            $row->ROW_ATTR['class'] .= ' state-active';
        } elseif ($mvc->haveRightFor('select', $rec)) {
            
            // Ако записа не е текущия обект, но може да бъде избран добавяме бутон за избор
            $row->currentPlg = ht::createBtn('Избор||Select', array($mvc, 'SetCurrent', $rec->id, 'ret_url' => getRetUrl()), null, null, 'ef_icon = img/16/hand-point.png, title=Избор за текущ');
            $row->ROW_ATTR['class'] .= ' state-closed';
            
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Избор||Select', array($mvc, 'SetCurrent', $rec->id, 'ret_url' => getRetUrl()), 'ef_icon = img/16/hand-point.png, title=Избор за текущ');
        } else {
            
            // Ако записа не е текущия обект и не може да бъде избран оставяме го така
            $row->ROW_ATTR['class'] .= ' state-closed';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'select' && isset($rec)) {
            if ($rec->state == 'rejected') {
                
                // Никой не може да се логва в оттеглен обект
                $res = 'no_one';
            }
        }
    }
}
