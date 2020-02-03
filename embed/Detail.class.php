<?php


/**
 * Мениджър на ембеднати обекти, за които отговарят драйвери
 *
 *
 * @category  bgerp
 * @package   embed
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class embed_Detail extends core_Detail
{
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface;
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $driverClassField = 'driverClass';
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Проверки дали са попълнени задължителните полета
        expect($mvc->driverInterface, $mvc);
        expect(is_subclass_of($mvc->driverInterface, 'embed_DriverIntf'), $mvc->driverInterface);
        
        // Добавяме задължителните полета само ако не е дефинирано, че вече съществуват
        if (!isset($mvc->fields[$mvc->driverClassField])) {
            $caption = $mvc->driverClassCaption ? $mvc->driverClassCaption : 'Вид';
            $mvc->FLD($mvc->driverClassField, "class(interface={$mvc->driverInterface}, allowEmpty, select=title)", "caption={$caption},mandatory,silent,smartCenter,refreshForm,after=id");
        }
        
        if (!isset($mvc->fields['driverRec'])) {
            $mvc->FLD('driverRec', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,column=none,single=none');
        }
        
        // Кои полета да се помнят след изтриване
        $fieldsBeforeDelete = "id, {$mvc->driverClassField}, driverRec";
        $mvc->fetchFieldsBeforeDelete = $fieldsBeforeDelete;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public function prepareEditForm_($data)
    {
        $data = parent::prepareEditForm_($data);
        
        $form = &$data->form;
        $rec = &$form->rec;
        
        // Извличаме позволените за избор опции
        $interfaces = static::getAvailableDriverOptions($rec);
        
        // Ако има избран вече драйвер, но го няма в опциите добавя се
        if ($rec->{$this->driverClassField} && !array_key_exists($rec->{$this->driverClassField}, $interfaces)) {
            $name = core_Classes::fetchField($rec->{$this->driverClassField}, 'title');
            $interfaces[$rec->{$this->driverClassField}] = core_Classes::translateClassName($name);
        }
        
        // Ако няма достъпни драйвери редирект със съобщение
        if (!countR($interfaces)) {
            $intf = cls::get($this->driverInterface);
             $msg = '|Липсват опции за|* |' . ($intf->driversCommonName ? $intf->driversCommonName : $this->title);
            if (haveRole('admin')) {
                redirect(array('core_Packs'), false, $msg, 'error');
            } else {
                followRetUrl(null, $msg, 'error');
            }
        } else {
            $form->setOptions($this->driverClassField, $interfaces);
            
            // Ако е наличен само един драйвер избираме него
            if (countR($interfaces) == 1) {
                $form->setDefault($this->driverClassField, key($interfaces));
                $form->setReadOnly($this->driverClassField);
            }
        }
        
        // Ако има източник инстанцираме го
        if ($rec->{$this->driverClassField}) {
            
            // Ако има съществуващ запис - полето не може да се сменя
            if ($id = $rec->id) {
                $form->setReadOnly($this->driverClassField);
            }
            
            if ($driver = $this->getDriver($rec)) {
                $driver = cls::get($rec->{$this->driverClassField}, array('Embedder' => $this));
                $driver->addFields($form);
                $driver->invoke('AfterAddFields', array($this, &$form));
            }
            
            $form->input(null, 'silent');
        }
        
        return $data;
    }
    
    
    /**
     * Връща позволените за избор драйвери според класа и потребителя
     *
     * @param mixed $userId - ид на потребител
     *
     * @return array $interfaces - възможните за избор опции на класове
     */
    public static function getAvailableDriverOptions($rec = null, $userId = null)
    {
        // Ако не е подаден потребител това е текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Зареждаме опциите за интерфейса
        $me = cls::get(get_called_class());
        $interfaces = core_Classes::getOptionsByInterface($me->driverInterface, 'title');
        if (countR($interfaces)) {
            foreach ($interfaces as $id => $int) {
                if (!cls::load($id, true)) {
                    continue;
                }
                
                $driver = cls::get($id, array('Embedder' => $me));
                
                // Ако потребителя не може да го избира, махаме го от масива
                if (cls::existsMethod($driver, 'canSelectDriver') && !$driver->canSelectDriver($rec, $userId)) {
                    unset($interfaces[$id]);
                }
            }
        }
        
        return $interfaces;
    }
    
    
    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     */
    public static function on_AfterRead($mvc, $rec)
    {
        try {
            if (cls::load($rec->{$mvc->driverClassField}, true)) {
                $driverRec = $rec->driverRec;
                
                if (is_array($driverRec)) {
                    foreach ($driverRec as $field => $value) {
                        $rec->{$field} = $value;
                    }
                }
                
                $driver = cls::get($rec->{$mvc->driverClassField}, array('Embedder' => $mvc));
                
                return $driver->invoke('AfterRead', array(&$rec));
            }
        } catch (core_exception_Expect $e) {
        }
    }
    
    
    /**
     * Преди запис в модела, компактираме полетата
     */
    public function save_(&$rec, $fields = null, $mode = null)
    {
        $saveDriverRec = false;
        
        if ($driver = $this->getDriver($rec)) {
            $driverRec = array();
            $addFields = self::getDriverFields($driver);
            
            foreach ($addFields as $name => $caption) {
                $driverRec[$name] = $rec->{$name};
                $saveDriverRec = true;
            }
            
            $rec->driverRec = $driverRec;
        }
        
        if ($fields && (is_array($fields) || $fields != '*')) {
            $fields = arr::make($fields, true);
            foreach ($fields as $f => $dummy) {
                if ($addFields[$f] && !$this->getField($f, false)) {
                    unset($fields[$f]);
                }
            }
        }
        
        if ($saveDriverRec && is_array($fields)) {
            $fields['driverRec'] = 'driverRec';
        }
        
        return parent::save_($rec, $fields, $mode);
    }
    
    
    /**
     * Изпълнява се след подготовка на единичните полета
     */
    public function prepareSingleFields_($data)
    {
        parent::prepareSingleFields_($data);
        
        // Ако има драйвър, добавяме полетата от него към полетата за показване
        if ($driver = $this->getDriver($data->rec)) {
            $driverFields = self::getDriverFields($driver, true);
            if (is_array($driverFields)) {
                $data->singleFields += $driverFields;
            }
        }
    }
    
    
    /**
     * Добавяме полетата от драйвера, ако са указани
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        if ($fields === '*') {
            
            // Ако извличаме всички полета се подсигуряваме че към тях са и полетата на драйвера
            $data = (object) array('rec' => $rec);
            static::prepareSingleFields($data);
            $fields = $data->singleFields;
        }
        
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());
        
        if (is_array($fields)) {
            if ($driver = static::getDriver($rec)) {
                $fieldset = self::getDriverFields($driver, false, true);
                
                foreach ($fieldset->fields as $name => $field) {
                    if (!isset($row->{$name}) && $fields[$name] && isset($rec->{$name})) {
                        $row->{$name} = $field->type->toVerbal($rec->{$name});
                    }
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Връща полетата добавени от драйвера
     *
     * @param core_BaseClass $driver           - драйвер
     * @param bool           $onlySingleFields - дали да са само полетата за сингъл
     * @param bool           $returnAsFieldSet - дали да се върнат като фийлд сетове
     *
     * @return array $res - добавените полета от драйвера
     */
    public static function getDriverFields($driver, $onlySingleFields = false, $returnAsFieldSet = false)
    {
        $me = cls::get(get_called_class());
        $fieldset = cls::get('core_Fieldset');
        $driver->addFields($fieldset);
        $driver->invoke('AfterAddFields', array($me, &$fieldset));
        
        $res = array();
        if (is_array($fieldset->fields)) {
            foreach ($fieldset->fields as $name => $f) {
                if ($onlySingleFields === true && $f->single == 'none') {
                    unset($fieldset->fields[$name]);
                    continue;
                }
                
                $res[$name] = $f->caption;
            }
        }
        
        return $returnAsFieldSet ? $fieldset : $res;
    }
    
    
    /**
     * Предаване на събитията и в драйвера
     */
    public function invoke($event, $args = array())
    {
        $status = parent::invoke($event, $args);
        
        $driverClass = null;
        
        if ($status !== false) {
            switch (strtolower($event)) {
                case 'aftercreate':
                case 'afterupdate':
                case 'afterread':
                case 'afteractivation':
                    $driverClass = $args[0]->{$this->driverClassField};
                    break;
                
                case 'aftergetrequiredroles':
                    if (is_object($args[2])) {
                        $driverClass = $args[2]->{$this->driverClassField};
                    }
                    break;
                
                case 'afterprepareeditform':
                    $driverClass = $args[0]->form->rec->{$this->driverClassField};
                    break;
                case 'afterrendersinglelayout':
                case 'afterrendersingletitle':
                case 'afterrendersingletoolbar':
                case 'beforerendersinglelayout':
                case 'beforerendersingle':
                case 'afterrendersingle':
                case 'beforerendersingle':
                case 'afterrendersingle':
                case 'beforepreparesingle':
                case 'afterpreparesingle':
                case 'beforepreparesinglefields':
                case 'afterpreparesinglefields':
                case 'beforepreparesingletoolbar':
                case 'afterpreparesingletoolbar':
                case 'afterprepareselectform':
                    $driverClass = $args[1]->rec->{$this->driverClassField};
                    break;
                case 'afterinputeditform':
                    $driverClass = $args[0]->rec->{$this->driverClassField};
                    break;
                
                case 'aftergetsearchkeywords':
                case 'aftergethidearrforletterhead':
                case 'beforesaveclonerec':
                case 'beforesave':
                case 'aftercreate':
                case 'aftergetdetailstoclone':
                case 'aftergetfieldforletterhead':
                case 'aftergetfieldsnottoclone':
                case 'aftersave':
                case 'afterrectoverbal':
                case 'afterrestore':
                case 'afterreject':
                case 'aftergetdefaultdata':
                    
                    $driverClass = $args[1]->{$this->driverClassField};
                    break;
                case 'aftergetthreadstate':
                    if ($args[1]) {
                        $rec = $this->fetchRec($args[1]);
                        $driverClass = $rec->driverClass;
                    }
                    
                    break;
            }
            
            // Ако има избран драйвер
            if ($driverClass) {
                $dRec = (object) array($this->driverClassField => $driverClass);
                if ($driver = $this->getDriver($dRec)) {
                    
                    // Добавяме ембедъра към аргументите на ивента
                    array_unshift($args, $this);
                    
                    // Генерираме същото събитие в драйвера за да може да го прихване при нужда
                    $status2 = $driver->invoke($event, $args);
                    
                    if ($status2 === false) {
                        $status = false;
                    } elseif ($status == -1 && $status2 === true) {
                        $status = true;
                    }
                }
            }
        }
        
        return $status;
    }
    
    
    /**
     * Връща инстанция на драйвера на класа
     *
     * @param int $id
     *
     * @return mixed - инстанция на драйвера или FALSE ако не може се инстанцира / има проблем с инсрабцирането
     */
    public static function getDriver($id)
    {
        $rec = static::fetchRec($id);
        $self = cls::get(get_called_class());
        
        // Ако има драйвер и той може да се зареди, инстанцираме го
        if (isset($rec->{$self->driverClassField}) && cls::load($rec->{$self->driverClassField}, true)) {
            
            return cls::get($rec->{$self->driverClassField}, array('driverRec' => $rec));
        }
        
        return false;
    }
}
