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
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2016 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_StructureAndOrder extends core_Plugin
{
    
    const PADDING = '&nbsp;&nbsp;» ';

    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        $mvc->FNC('saoPosition', 'enum(next=След,prev=Преди,subLevel=Под ниво)', 'caption=Структура и подредба->Положение,input=none,column=none,order=100000,maxRadio=3,columns=3');
        $mvc->FNC('saoRelative', 'int', 'caption=Структура и подредба->Спрямо,input=none,column=none,order=100000,class=w100');

        $mvc->FLD('saoParentId', 'int', 'caption=Структура и подредба->Родител,input=none,column1=none,order=100000');
        $mvc->FLD('saoOrder',    'double', 'caption=Структура и подредба->Подредба,input=none,column1=none,order=100000');
        $mvc->FLD('saoLevel',    'int', 'caption=Структура и подредба->Ниво,input=none,column1=none,order=100000');
        
        $mvc->listItemsPerPage = max($mvc->listItemsPerPage, 1000);
        //expect($mvc->posTitleField);
    }


    /**
     * Извиква се след подготовка на формата за въвеждане
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec  = $form->rec;

        // По подразбиране задаваме позиция след
        $form->setDefault('saoPosition', 'next');
        
        if($rec->id) {
            $id = $rec->id;
        } else {
            $id = self::getOrSetLastId($mvc->className);
        }
        
        $form->setDefault('saoRelative', $id);

        $options = self::getOptiopns($mvc, $rec);

        if(count($options)) {
            $form->setField('saoPosition', 'input');
            $form->setField('saoRelative', 'input');
            
            // Задаваме елементите като опции
            $form->setOptions('saoRelative', array('' => '') + $options);
            
            $canHaveSublevel = FALSE;
            foreach($options as $r) {
                if($mvc->SaoCanHaveSublevel($rec)) {
                    $canHaveSublevel = TRUE;
                }
            }

            if(!$canHaveSublevel) {
                $form->setOptions('saoPosition', array('next' => 'След','prev' => 'Преди'));
            }
        }
    }
 

    /**
     * Подготвя опциите за saoPosition
     */
    private static function getOptiopns($mvc, $rec)
    {
        $res = array();
        $removeIds = array();
        
        if ($rId) {
            $removeIds[$rec->id] = $rec->id;
        }
        
        $items = $mvc->getSaoItems($rec);
        $items = self::orderItems($items);
        if(is_array($items)) {
            foreach($items as $iRec) {
                if(count($removeIds)) {
                    if($removeIds[$iRec->saoParentId]) {
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
        if($form->isSubmitted()) {
            $rec = $form->rec;
            
            $items = $mvc->getSaoItems($rec);
            
            // По подразбиране - добавяме следващ елемент
            setIfNot($rec->saoPosition, 'next');

            // Ако нямаме никакви елементи правим дефолти
            if(!count($items) || (!$rec->saoRelative && $rec->saoPosition != 'subLevel')) {
                $rec->saoParentId = NULL;
                $rec->saoOrder = NULL;
                $rec->saoLevel = 1;
            } elseif($rec->saoPosition == 'subLevel') {
                if(!$rec->saoRelative || !$items[$rec->saoRelative]) {
                    $form->setError('saoRelative', 'Не е посочен родителски елемент');
                    return;
                }
                if(!$mvc->saoCanHaveSublevel($items[$rec->saoRelative])) {
                    $form->setError('saoRelative', 'Този елемент не може да има подниво');
                    return;
                }

                $rec->saoParentId = $rec->saoRelative;
                $rec->saoOrder = NULL;
                $rec->saoLevel = $items[$rec->saoRelative]->level + 1;
            }  elseif($rec->saoPosition == 'prev') {
                $prevRec = $items[$rec->saoRelative];
                $rec->saoParentId = $prevRec->saoParentId;
                $rec->saoOrder = $prevRec->saoOrder - 0.5;
            } elseif($rec->saoPosition == 'next') {
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
        if($res !== NULL) return;

        if($rec->saoLevel > 3) {
            $res = FALSE;
        } else {
            $res = TRUE;
        }
    }


    /**
     * Дефолтна имплементация на saoCanHaveSublevel
     */
    public function on_AfterSaoGetTitle($mvc, &$res, $rec, $title = NULL)
    {
        if($res !== NULL) return;
        
        if(!$title) {
            $title = $mvc->getTitleById($rec, FALSE);
        }

        $res = self::padOpt($title, $rec->saoLevel);
    }


    /**
     * Връща или записва в сесията id-то на последния добавен запис
     */
    private static function getOrSetLastId($className, $id = NULL)
    {
        $key = 'lastAddId_' . $className;
        
        if($id) {
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
    public static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        if($fields === NULL || $fields === '*') {
            $items = $mvc->getSaoItems($rec);
          
            // Подредба
            $items1 = self::orderItems($items);

            // Преномериране
            $i = 1; 
            foreach($items1 as &$r) {
                if($r->saoOrder != $i) {
                    $r->saoOrder = $i;
                    $r->_mustSave = TRUE;
                }
                $i++;
            }
   
            // Записваме само променените елементи
            reset($items1);
            foreach($items1 as $i1 => $r1) {
                if($r1->_mustSave) {
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
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'delete' || $action == 'changestate') {
 
            if ($rec->id && $mvc->fetch("#saoParentId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }

    
    /**
     * Подреждане на записите в листови изглед
     */
    public static function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#saoOrder', 'ASC', TRUE);
    }


    /**
     * Прави подравняване с начални отстъпи в листовия изглед
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        if($f = $mvc->saoTitleField) {
            foreach($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                $row->{$f} = $mvc->saoGetTitle($rec, $row->{$f});
                $ddTools = $row->_rowTools;
                if($lastRec && $rec->saoLevel == $lastRec->saoLevel && $mvc->haveRightFor('edit', $rec)) {
                    $row->_rowTools->addLink('Нагоре', array($mvc, 'SaoMove', $rec->id, 'direction' => 'up', 'rId' => $lastRec->id, 'ret_url' => TRUE), 
                        "ef_icon=img/16/arrow_up.png,title=Преместване на елемента нагоре,id=saoup{$rec->id}");
                    $lastRow->_rowTools->addLink('Надолу', array($mvc, 'SaoMove', $lastRec->id, 'direction' => 'down', 'rId' => $rec->id, 'ret_url' => TRUE), 
                        "ef_icon=img/16/arrow_down.png,title=Преместване на елемента нагоре,id=saoup{$rec->id}");
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
     * @param core_ET $res
     * @param string $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if(strtolower($action) == strtolower('saomove')) {

            if (!$mvc->haveRightFor('edit')) return;

            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            
            expect($direction = Request::get('direction'));

            if($direction == 'up') {
                $rec->saoOrder -= 1.5;
            } else {
                $rec->saoOrder += 1.5;
            }

            expect($rId = Request::get('rId', 'int'));
            expect($rRec = $mvc->fetch($rId));
            
            if($rRec && abs($rec->saoOrder - $rRec->saoOrder) == 0.5) {
                $mvc->save($rec);
                followRetUrl();
            } else {
                followRetUrl(NULL, 'Неуспешно преместване', 'error');
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
    private static function padOpt($title, $level)
    {   
        $title = str_repeat(html_entity_decode(self::PADDING) , max(0, $level-1)) . $title; 

        return $title;
    }


    /**
     * Подрежда масива с елементи
     */
    private static function orderItems($items, $level = 1, $parentId = NULL, &$orderedItems = array())
    {
        $selArr = array();
        reset($items);
        foreach($items as $rec) {
            if($rec->saoParentId == $parentId) {
                if($rec->saoLevel != $level) {
                    $rec->saoLevel = $level;
                    $rec->_mustSave = TRUE;
                }
                $selArr[$rec->id] = $rec;
            }
        }

        if(!count($selArr)) return;

        // Подредба на подмножеството с еднакъв родител
        self::sortItems($selArr);
   
        // Добавяне в резултата, с рекурсивно извикване за потенциалните наследници
        foreach($selArr as $rec) {
            $orderedItems[$rec->id] = $rec;
            self::orderItems($items, $level + 1, $rec->id, $orderedItems);
        }

        return $orderedItems;
    }


    /**
     * Сортира масив с елементи
     */
    private static function sortItems(&$items)
    {
        uasort($items, function($a, $b) {
                            if($a->saoOrder == $b->saoOrder)  return 0;
                            
                            if(!$a->saoOrder || $a->saoOrder > $b->saoOrder)  return 1;
                            if(!$b->saoOrder || $a->saoOrder < $b->saoOrder)  return -1;

                            
                        });

    }

}