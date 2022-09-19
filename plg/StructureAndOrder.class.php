<?php


/**
 * Клас 'plg_StructureAndOrder' - Поддръжка на потеребителско подреждане на модел
 *
 * Очаква се в модела да има следните полета:
 * о
 *
 * Очаква се в модела да има следните методи:
 * о ->getAllSaoItems($rec = NULL) - връща всички структурни елементи, сред които може да се подреди посочения запис
 * о ->getSaoTitle($rec) - връща заглавието на посочения запис
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2016 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_StructureAndOrder extends core_Plugin
{
    const PADDING = '&nbsp;&nbsp;» ';
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        $mvc->FNC('saoPosition', 'enum(next=След,prev=Преди,subLevel=Под ниво)', 'caption=Структура и подредба->Положение,input=none,column=none,order=100000,maxRadio=3,columns=3');
        $mvc->FNC('saoRelative', 'int', 'caption=Структура и подредба->Спрямо,input=none,column=none,order=100000,class=w100');
        $mvc->FLD('saoParentId', 'int', 'caption=Структура и подредба->Родител,input=none,column=none,order=100000');
        $mvc->FLD('saoOrder', 'double(smartRound)', 'caption=Структура и подредба->Подредба,input=none,column=none,order=100000');
        $mvc->FLD('saoLevel', 'int', 'caption=Структура и подредба->Ниво,input=none,column=none,order=100000');
        
        $mvc->listItemsPerPage = max($mvc->listItemsPerPage, 1000);
        setIfNot($mvc->saoOrderPrioriy, -100);
        setIfNot($mvc->autoOrderBySaoOrder, true);
    }
    
    
    /**
     * Извиква се след подготовка на формата за въвеждане
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        // По подразбиране задаваме позиция след
        $form->setDefault('saoPosition', 'next');
        
        if ($rec->id) {
            $id = $rec->id;
        } else {
            $id = self::getOrSetLastId($mvc->className);
        }
        
        $form->setDefault('saoRelative', $id);
        
        $options = self::getOptions($mvc, $rec);
        
        if (countR($options)) {
            $form->setField('saoPosition', 'input');
            $form->setField('saoRelative', 'input');
            
            // Задаваме елементите като опции
            $form->setOptions('saoRelative', array('' => '') + $options);
            
            $canHaveSublevel = false;
            foreach ($options as $id => $title) {
                $r = $mvc->fetch($id);
                if ($mvc->saoCanHaveSublevel($r, $rec)) {
                    $canHaveSublevel = true;
                }
            }
            
            if (!$canHaveSublevel) {
                $form->setOptions('saoPosition', array('next' => 'След','prev' => 'Преди'));
            }
        }
    }
    

    /**
     * Премахва от резултатите скритите от менютата за избор
     */
    public static function on_AfterMakeArray4Select($mvc, &$res, $fields = null, &$where = '', $index = 'id')
    {
        // Подменяме предложенията с подробните
        foreach($res as $key => &$title) {
            if(is_object($title)) {
                if(isset($title->title)) {
                    $title = &$title->title;
                }
            }

            if(is_scalar($title)) {
                $rec = $mvc->fetch($key);
                $title = self::padOpt($title, $rec->saoLevel);
            }
        }
    }

    
    /**
     * Подготвя опциите за saoPosition
     */
    private static function getOptions($mvc, $rec)
    {
        $res = array();
        $removeIds = array();
        
        if ($rId) {
            $removeIds[$rec->id] = $rec->id;
        }
        
        $items = $mvc->getSaoItems($rec);
        $items = self::orderItems($items);
        if (is_array($items)) {
            foreach ($items as $iRec) {
                if (countR($removeIds)) {
                    if ($removeIds[$iRec->saoParentId]) {
                        $removeIds[$iRec->id] = $iRec->id;
                        continue;
                    }
                }
                $res[$iRec->id] = $mvc->saoGetTitle($iRec);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се след въвеждане на параметрите в Input-формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $items = $mvc->getSaoItems($rec);
            
            // По подразбиране - добавяме следващ елемент
            setIfNot($rec->saoPosition, 'next');
            
            // Ако нямаме никакви елементи правим дефолти
            if (!countR($items) || (!$rec->saoRelative && $rec->saoPosition != 'subLevel')) {
                $rec->saoParentId = null;
                $rec->saoOrder = null;
                $rec->saoLevel = 1;
            } elseif ($rec->saoPosition == 'subLevel') {
                if (!$rec->saoRelative || !$items[$rec->saoRelative]) {
                    $form->setError('saoRelative', 'Не е посочен родителски елемент');
                    
                    return;
                }
                if (!$mvc->saoCanHaveSublevel($items[$rec->saoRelative], $rec)) {
                    $form->setError('saoRelative', 'Този елемент не може да има подниво');
                    
                    return;
                }
                
                $rec->saoParentId = $rec->saoRelative;
                $rec->saoOrder = null;
                $rec->saoLevel = $items[$rec->saoRelative]->level + 1;
            } elseif ($rec->saoPosition == 'prev') {
                $prevRec = $items[$rec->saoRelative];
                $rec->saoParentId = $prevRec->saoParentId;
                $rec->saoOrder = $prevRec->saoOrder - 0.5;
            } elseif ($rec->saoPosition == 'next') {
                $prevRec = $items[$rec->saoRelative];
                $rec->saoParentId = $prevRec->saoParentId;
                $rec->saoOrder = $prevRec->saoOrder + 0.5;
            }
        }
    }
    
    
    /**
     * Дефолтна имплементация на saoCanHaveSublevel
     */
    public function on_AfterSaoCanHaveSublevel($mvc, &$res, $rec)
    {
        if ($res !== null) {
            
            return;
        }

        if($mvc->canHaveSubLevel === false) {
            $res = false;
        } else {
            if ($rec->saoLevel > 3) {
                $res = false;
            } else {
                $res = true;
            }
        }
    }
    
    
    /**
     * Дефолтна имплементация на saoCanHaveSublevel
     */
    public function on_AfterSaoGetTitle($mvc, &$res, $rec, $title = null, $saoPadding = null)
    {
        if ($res !== null) {
            
            return;
        }
        
        if (!$title) {
            $title = $mvc->getTitleById($rec, false);
        }
        
        $res = self::padOpt($title, $rec->saoLevel, $saoPadding);
    }
    
    
    /**
     * Връща или записва в сесията id-то на последния добавен запис
     */
    private static function getOrSetLastId($className, $id = null)
    {
        $key = 'lastAddId_' . $className;
        
        if ($id) {
            Mode::setPermanent($key, $id);
        } else {
            $id = Mode::get($key);
        }
        
        return $id;
    }
    
    
    /**
     * Запомня в сесията последно създадения запис, за да може да
     * предложи следващия да е след него
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        self::getOrSetLastId($mvc->className, $rec->id);
    }
    
    
    /**
     * Преподрежда записите от същото ниво, в случай, че току-що записания обект има същия
     * $pLevel като някой друг. Всички с номера на $pLevel по-големи или равни на текущия се
     * подреждат след него с нарасващи номера
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        if ($fields === null || $fields === '*') {
            $items = $mvc->getSaoItems($rec);
            
            // Подредба
            $items1 = self::orderItems($items);
            
            // Преномериране
            $i = 1;
            foreach ($items1 as &$r) {
                if ($r->saoOrder != $i) {
                    $r->saoOrder = $i;
                    $r->_mustSave = true;
                }
                $i++;
            }
            
            // Записваме само променените елементи
            reset($items1);
            foreach ($items1 as $i1 => $r1) {
                if ($r1->_mustSave) {
                    expect($r1->id);
                    $i = $mvc->save_($r1, 'saoOrder,saoLevel,saoParentId');
                    $res[$i] = $r1;
                }
            }
        }
    }
    
    
    /**
     * Забранява изтриването, ако в елемента има деца
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' || $action == 'changestate') {
            if ($rec->id && $mvc->fetch("#saoParentId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    public function on_AfterGetQuery($mvc, $query)
    {
        if($mvc->autoOrderBySaoOrder){
            $query->orderBy('#saoOrder', 'ASC', $mvc->saoOrderPrioriy);
        }
    }
    
    
    /**
     * Прави подравняване с начални отстъпи в листовия изглед
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        if (is_array($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                if ($f = $mvc->saoTitleField) {
                    $row->{$f} = $mvc->saoGetTitle($rec, $row->{$f});
                }

                if ($lastRec && $rec->saoLevel == $lastRec->saoLevel && $mvc->haveRightFor('edit', $rec)) {
                    $row->_rowTools->addLink(
                        'Нагоре',
                        array($mvc, 'SaoMove', $rec->id, 'direction' => 'up', 'rId' => $lastRec->id, 'ret_url' => true),
                        "ef_icon=img/16/arrow_up.png,title=Преместване на елемента нагоре,id=saoup{$rec->id}"
                    );
                    $lastRow->_rowTools->addLink(
                        'Надолу',
                        array($mvc, 'SaoMove', $lastRec->id, 'direction' => 'down', 'rId' => $rec->id, 'ret_url' => true),
                        "ef_icon=img/16/arrow_down.png,title=Преместване на елемента нагоре,id=saodown{$rec->id}"
                    );
                }
                $lastRec = $rec;
                $lastRow = $row;
            }
        }
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == strtolower('saomove')) {
            if (!$mvc->haveRightFor('edit')) {
                
                return;
            }
            
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            
            expect($direction = Request::get('direction'));
            
            if ($direction == 'up') {
                $rec->saoOrder -= 1.5;
            } else {
                $rec->saoOrder += 1.5;
            }
            
            expect($rId = Request::get('rId', 'int'));
            expect($rRec = $mvc->fetch($rId));
            
            if ($rRec && abs($rec->saoOrder - $rRec->saoOrder) == 0.5) {
                $mvc->save($rec);
                followRetUrl();
            } else {
                followRetUrl(null, 'Неуспешно преместване', 'error');
            }
        }
    }
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    public static function on_BeforePrepareQuery4Select($mvc, &$res, $query)
    {
        $query->orderBy('#saoOrder');
    }
    
    
    /**
     * Помощна функция за падване на стринг
     */
    private static function padOpt($title, $level, $padding = null)
    {
        $title = str_repeat($padding ? $padding : html_entity_decode(self::PADDING), max(0, $level - 1)) . $title;
        
        return $title;
    }
    
    
    /**
     * Подрежда масива с елементи
     */
    private static function orderItems($items, $level = 1, $parentId = null, &$orderedItems = array())
    {
        $selArr = array();
        
        if ($items) {
            reset($items);
            foreach ($items as $rec) {
                if ($rec->saoParentId == $parentId) {
                    if ($rec->saoLevel != $level) {
                        $rec->saoLevel = $level;
                        $rec->_mustSave = true;
                    }
                    $selArr[$rec->id] = $rec;
                }
            }
        }
        
        if (!countR($selArr)) {
            
            return array();
        }
        
        // Подредба на подмножеството с еднакъв родител
        self::sortItems($selArr);
        
        // Добавяне в резултата, с рекурсивно извикване за потенциалните наследници
        foreach ($selArr as $rec) {
            $orderedItems[$rec->id] = $rec;
            self::orderItems($items, $level + 1, $rec->id, $orderedItems);
        }
        
        return $orderedItems;
    }


    /**
     * Сортира масив с елементи
     */
    public static function sortItems(&$items, $field = 'saoOrder')
    {
        uasort($items, function ($a, $b) use ($field){

            if ($a->{$field} == $b->{$field}) {

                return 0;
            }

            if (!$a->{$field} || $a->{$field} > $b->{$field}) {

                return 1;
            }

            if (!$b->{$field} || $a->{$field} < $b->{$field}) {

                return -1;
            }
        });
    }
}
