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
     * @param bool|int $bForce Дали да редирект към мениджъра ако не е избран текущ обект. Ако е int и няма избран мениджър, автоматично се избира
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
            
            $rec = null;
            $query = $mvc->getQuery();
            $query->where("#state != 'rejected' || #state != 'closed' || #state IS NULL");
            $query->limit(2);
            
            // Генериране на събитие, преди изпълнението на заявката
            $mvc->invoke('BeforeSelectByForce', array(&$query));
            
            // Ако има точно един обект, който потребителя може да избере се избира автоматично
            if ($query->count() == 1) {
                $rec = $query->fetch();
            }
            
            // Ако форсираме
            if ($bForce && !$rec) {
                if (is_numeric($bForce)) {
                    $rec = $mvc->fetch($bForce);
                }
                
                // Ако няма резултат, и името на класа е различно от класа на контролера (за да не стане безкрайно редиректване)
                if (empty($rec) && ($mvc->className != Request::get('Ctr'))) {
                    
                    // Подканваме потребителя да избере обект от модела, като текущ
                    redirect(array($mvc, 'SelectCurrent', 'ret_url' => true), false, '|Моля, изберете текущ/а|* |' . mb_strtolower(tr($mvc->singleTitle)));
                }
            }
            
            // Избиране на  обект, ако е намерен подходящ
            if ($rec) {
                $currRes = self::setCurrent($mvc, $res, $rec);
            }
            
            // Ако няма резултат, и името на класа е различно от класа на контролера (за да не стане безкрайно редиректване)
            if (($currRes === false) && ($bForce)) {
                
                // Подканваме потребителя да избере обект от модела, като текущ
                redirect(array($mvc, 'SelectCurrent', 'ret_url' => true), false, '|Нямате права за избор на|* |' . mb_strtolower(tr($mvc->singleTitle)));
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
        
        if ($action == 'selectcurrent') {
            $form = cls::get('core_Form');
            $form->FLD('choice', "key(mvc={$mvc->className},select=name)", "caption={$mvc->singleTitle},input");
            
            $opt = array();
            $cnt = 0;
            $query = $mvc->getQuery();
            while ($rec = $query->fetch()) {
                if ($mvc->haveRightFor('select', $rec)) {
                    $opt[$rec->id] = $mvc->getRecTitle($rec);
                }
                $cnt++;
            }
            
            $retUrl = getRetUrl();
            if (!$resUrl || !count($retUrl) || $retUrl['Ctr'] == $mvc->className) {
                $retUrl = array('Portal', 'Show');
            }
            
            if (!count($opt) && $cnt) {
                $form->setField('choice', 'input=none');
                $form->info = "<div style='padding:10px; background-color:yellow;'>" . tr('Липсват достъпни за избор') . ' ' . mb_strtolower(tr($mvc->title)) . '</div>';
            } else {
                $form->setOptions('choice', $opt);
                
                $key = self::getPermanentKey($mvc);
                
                $lastId = core_Permanent::get($key);
                
                if ($lastId && $opt[$lastId]) {
                    $form->setDefault('choice', $lastId);
                }
                
                $rec = $form->input();
                
                if (count($opt) == 1) {
                    $rec->choice = key($opt);
                }
                
                if ($rec->choice && ($form->isSubmitted() || count($opt) == 1)) {
                    if ($mvc->haveRightFor('select')) {
                        $rec = $mvc->fetch($rec->choice);
                        $mvc->selectCurrent($rec);
                        $res = new Redirect(getRetUrl());
                        
                        return false;
                    }
                }
                
                $form->toolbar->addSbBtn('Напред', 'choice', array('class' => 'fright'), 'ef_icon = img/16/move.png');
            }
            
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
            
            $form->title = 'Избор на текущ|* ' . mb_strtolower(tr($mvc->singleTitle));
            
            $res = $mvc->renderWrapping($form->renderHtml());
            
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
     * @return null|false
     */
    private static function setCurrent($mvc, &$res, &$rec)
    {
        // Ако текущия потребител няма права - не правим избор
        if (!$mvc->haveRightFor('select', $rec)) {
            
            return false;
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
        
        $key = self::getPermanentKey($mvc);
        
        core_Permanent::set($key, $rec->id, 10000);
        
        $res = $rec->id;
    }
    
    
    /**
     * Връща ключа за запис в перманентните настройки
     */
    private static function getPermanentKey($mvc)
    {
        $key = 'Select-' . cls::getClassName($mvc) . '-' . core_Users::getCurrent();
        
        return $key;
    }
    
    
    /**
     * Връща ключа от сесията, под който се държат текущите записи
     */
    private static function getModeKey($className)
    {
        return 'currentPlg_' . $className;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (!Mode::is('printing')) {
            $data->listFields['currentPlg'] = 'Текущ';
            $data->listTableMvc->FNC('currentPlg', 'varchar', 'tdClass=centerCol');
        }
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
