<?php


/**
 * Клас  'type_Keylist' - Списък от ключове към редове от MVC модел
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Treelist extends type_Keylist
{

    /**
     * Рендира HTML инпут поле
     *
     * @param string     $name
     * @param string     $value
     * @param array|NULL $attr
     *
     * @see core_Type::renderInput_()
     *
     * @return core_ET
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {  
        $attrCB = array();
        ht::setUniqId($attr);
        $eId = $attr['id'];

        if (is_array($value)) {
            $value = static::fromArray($value);
        }
        
        // Ако няма списък с предложения - установяваме го
        if (!isset($this->suggestions)) {
            $this->prepareSuggestions();
        }
        
        if ($value === null) {
            $emptyValue = true;
        }
        
        if (!$value) {
            $values = array();
        } else {
            $values = explode($value{0}, trim($value, $value{0}));
        }
        
        $attrCB['type'] = 'checkbox';
        $attrCB['class'] .= ' checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $maxChars = $this->params['maxChars'];
        $col = self::getCol((array) $this->suggestions, $maxChars);
        
        $i = 0;
        $html = '';
        
        $suggCnt = count($this->suggestions);
        $html = '';
        expect($parentIdName = $this->params['parentId']);
        
        $openIds = keylist::toArray($value);

        if ($suggCnt) {

            $downArrow    = "<i class='trigger'>▼</i>";
            $rightArrow   = "<i class='trigger'>►</i>";

            // Подготовка на данните
            $keys = '';
            foreach($this->suggestions as $id => $title) {
                $keys .= ($keys ? ',' : '') . $id;
            }
            $mvc = &cls::get($this->params['mvc']);
            $query = $mvc->getQuery();
            while($rec = $query->fetch("#id IN ({$keys})")) {
                $data[$rec->id] = (object) array('title' => $this->suggestions[$rec->id], 'parentId' => $rec->{$parentIdName});
            }
            
            $items = array();

            self::addItems($items, $data, $parentId, $openIds); 

            foreach($items as $i => $item) {
                $id = $eId . '_' . $i;
                $n = "{$name}[$i]";
                if(is_scalar($item)) {
                    if($item == 'openGroup') {
                        if($toggle == $downArrow) {
                            $html .= "<ul id='ul_{$lastId}'>";
                        } else {
                            $html .= "<ul id='ul_{$lastId}' class='hidden hidden1'>";
                        }
                    }elseif($item == 'closeGroup') {
                        $html .= "</ul>";
                    }
                    continue;
                }
                $lastId = $id;
                if($item->hasGroup) {
                    if($item->isOpen) {
                        $toggle = $downArrow;
                    } else {
                        $toggle = $rightArrow;
                    }
                    $class = 'class="toggleCheck"';
                } else {
                    $class = '';
                    $toggle = "<i>&nbsp;</i>";
                }

                if($item->checked) {
                    $html .= "\n<li>{$toggle}<input type='checkbox' name='{$n}' checked id='{$id}'><label $class for='{$id}'>{$item->title}</label></li>";
                } else {
                    $html .= "\n<li>{$toggle}<input type='checkbox' name='{$n}' id='{$id}'><label $class for='{$id}'>{$item->title}</label></li>";
                }
            }
        }        

        $res = new ET("<div class='treelist' style='border:solid 1px #bbbbbb; background-color:white'><ul>" . $html . "</ul></div>");
        
        jquery_Jquery::run($res, "setTrigger();\n", true);

        return $res;
    }


    /**
     * Рекурсивна функция за подготвяне на дървото с групите
     */
    private static function addItems(&$items, $data, $parentId, $openIds)
    {
        $hasOpen = false;
        $haveItem = false;  
        foreach($data as $id => $item) {
            if($item->parentId == $parentId) {
                
                if($openIds[$id]) {
                    $item->checked = true;
                    $hasOpen = true;
                }

                $items[$id] = $item;
                $haveItem = true;

                $items['openGroup_' . $id] = 'openGroup';

                $cnt = count($items);
                if(self::addItems($items, $data, $id, $openIds)) {
                    $items[$id]->hasGroup = true;
                }

                if(count($items) > $cnt) {
                    $items['closeGroup_' . $id] = 'closeGroup';
                } else {
                    unset($items['openGroup_' . $id]);
                }
            }
        }

        if($hasOpen) { 
            while($parentId) {
                $items[$parentId]->isOpen = true;
                $parentId = $items[$parentId]->parentId;
            }
        }

        return $haveItem;
    }



    
    
 }
