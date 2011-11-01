<?php

/**
 * Клас 'core_Master' - Мениджър за единичните данни на бизнес обекти
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Master extends core_Manager
{
    
    
    /**
     * Мениджърите на детаилите записи към обекта
     */
    var $details;
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    var $singleTitle;
    
    
    /**
     * Изпълнява се след конструирането на мениджъра
     */
    function on_AfterDescription($mvc)
    {
        // Списъка с детаилите става на масив
        $this->details = arr::make($this->details, TRUE);
        
        // Зарежда mvc класовете
        $this->load($this->details);
    }
    
    
    /**
     * Връща единичния изглед на обекта
     */
    function act_Single()
    {
        // Имаме ли въобще права за единичен изглед?
        $this->requireRightFor('single');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има id
        expect($id = Request::get('id'));
        
        // Трябва да има $rec за това $id
        expect($data->rec = $this->fetch($id));
        
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('single', $data->rec);

        // Подготвяме данните за единичния изглед
        $this->prepareSingle($data);

        // Рендираме изгледа
        $tpl = $this->renderSingle($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        // Записваме, че потребителя е разглеждал този списък
        $this->log('Single: ' . ($data->log?$data->log:tr($data->title)), $id);
        
        return $tpl;
    }


    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    function prepareSingle_($data)
    {        
        // Подготвяме полетата за показване
        $this->prepareSingleFields($data);
        
        // Подготвяме вербалните стойности на записа
        $data->row = $this->recToVerbal($data->rec, arr::combine($data->singleFields, '-single'));
        
        // Подготвяме титлата
        $this->prepareSingleTitle($data);
        
        // Подготвяме тулбара
        $this->prepareSingleToolbar($data);
        
        // Подготвяме детаилите
        if(count($this->details)) {
            foreach($this->details as $var => $class) {
                if($var == $class) {
                    $method = 'prepareDetail';
                } else {
                    $method = 'prepare' . $var;
                }
                $detailData = $data->{$var} = new stdClass();
                $detailData->masterId = $data->rec->id;
                $detailData->masterData = $data;
                $this->{$var}->$method($detailData);
            }
        }

        return $data;
    }

    
    /**
     * Подготвя списъка с полетата, които ще се показват в единичния изглед
     */
    function prepareSingleFields_($data)
    {
        if( isset( $this->singleFields ) ) {
            
            // Ако са зададени $this->listFields използваме ги тях за колони
            $data->singleFields = arr::make($this->listFields, TRUE);
        } else {
            
            // Използваме за колони, всички полета, които не са означени с column = 'none'
            $fields = $this->selectFields("#single != 'none'");
            
            if (count($fields)) {
                foreach ($fields as $name => $fld) {
                    $data->singleFields[$name] = $fld->caption;
                }
            }
        }
        
        if (count($data->singleFields)) {
            
            // Ако титлата съвпада с името на полето, вадим името от caption
            foreach ($data->singleFields as $field => $caption) {
                if (($field == $caption) && $this->fields[$field]->caption) {
                    $data->singleFields[$field] = $this->fields[$field]->caption;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     */
    function prepareSingleTitle_($data)
    {
        $title = $this->getRecTitle($data->rec);
        
        $data->title = $this->singleTitle . "|* <b style='color:green;'>{$title}</b>";
        
        return $data;
    }
    
    
    /**
     * Подготвя тулбара за единичния изглед
     */
    function prepareSingleToolbar_($data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        $data->toolbar->class = 'SingleToolbar';

        if (isset($data->rec->id) && $this->haveRightFor('edit', $data->rec)) {
            $data->toolbar->addBtn('Редакция', array(
                $this,
                'edit',
                $data->rec->id,
                'ret_url' => TRUE
            ),
            'id=btnEdit,class=btn-edit');
        }
        
        if (isset($data->rec->id) && $this->haveRightFor('delete', $data->rec)) {
            $data->toolbar->addBtn('Изтриване', array(
                $this,
                'delete',
                $data->rec->id,
                'ret_url' => toUrl(array($this), 'local') 
            ),
            'id=btnDelete,class=btn-delete,warning=Наистина ли желаете да изтриете документа?,order=31');
        }
       
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderSingle_($data)
    {
        // Рендираме общия лейаут
        $tpl = $this->renderSingleLayout($data);
        
        // Поставяме данните от реда
        $tpl->placeObject($data->row);

        foreach($data->singleFields as $name => $caption) {
            $tpl->replace(tr($caption), 'CAPTION_' . $name);
        }

        // Поставя титлата
        $tpl->replace($this->renderSingleTitle($data), 'SingleTitle');
        
        // Поставяме toolbar-а
        $tpl->replace($this->renderSingleToolbar($data), 'SingleToolbar');
        
        // Поставяме детаилите
        if(count($this->details)) {
            foreach($this->details as $var => $class) {

                if($var == $class) {
                    $method = 'renderDetail';
                } else {
                    $method = 'render' . $var;
                }

                if($tpl->isPlaceholderExists($var)) {
                    $tpl->replace($this->{$var}->$method($data->{$var}), $var);
                } else {
                    $tpl->append($this->{$var}->$method($data->{$var}), 'DETAILS');
                }
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_($data)
    {
        if(isset($this->singleLayoutFile)) {
            $layoutText = file_get_contents(getFullPath($this->singleLayoutFile));
        } elseif( isset($this->singleLayoutTpl) ) {
            $layoutText = $this->singleLayoutTpl;
        } else {
            if( count($data->singleFields) ) {
                foreach($data->singleFields as $field => $caption) {
                    $fieldsHtml .= "<tr><td>[#CAPTION_{$field}#]</td><td>[#{$field}#]</td></tr>";
                }
            }
            
            $class = $this->cssClass ? $this->cssClass : $this->className;

            $layoutText = "[#SingleToolbar#]<div class='{$class}'><h2>[#SingleTitle#]</h2>" .
                          "<table class='listTable'>{$fieldsHtml}</table>" .
                          "<!--ET_BEGIN DETAILS-->[#DETAILS#]<!--ET_END DETAILS--></div>";
        }
        
        if(is_string($layoutText)) {
            $layoutText = tr("|*" . $layoutText);
        }

        return new ET($layoutText);
    }
    
    
    /**
     * Рендира титлата на обекта в single view
     */
    function renderSingleTitle_($data)
    {
        return new ET(tr($data->title));
    }
    
    
    /**
     * Рендира тулбара на единичния изглед
     */
    function renderSingleToolbar_($data)
    {
        if(cls::isSubclass($data->toolbar, 'core_Toolbar')) {
            
            return $data->toolbar->renderHtml();
        }
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_($action, $rec = NULL, $userId = NULL)
    {
        if($action == 'single') {
            $action{0} = strtoupper($action{0});
            $action = 'can' . $action;
            
            if(!($requiredRoles = $this->{$action})) {
                $requiredRoles = $this->getRequiredRoles('read', $rec, $userId);
            }
        } else {
            $requiredRoles = parent::getRequiredRoles_($action, $rec, $userId);
        }
        
        return $requiredRoles;
    }
}