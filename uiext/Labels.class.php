<?php


/**
 * Клас 'uiext_Labels'
 *
 * Мениджър за тагове на обекти
 *
 * @category  bgerp
 * @package   uiext
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class uiext_Labels extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Тагове';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Таг';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, cond_Wrapper, plg_State2, plg_SaveAndNew';
    
    
    /**
     * Кой има право да гледа списъка?
     */
    public $canList = 'uiext, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'uiext, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'uiext, admin, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'uiext, admin, ceo';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'docClassId,title,color,state';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('docClassId', 'class(select=title,allowEmpty)', 'caption=Клас, mandatory,remember');
        $this->FLD('title', 'varchar', 'caption=Заглавие, mandatory');
        $this->FLD('color', 'color_Type()', 'caption=Фон, mandatory,tdClass=rightCol');
        
        $this->setDbUnique('docClassId,title');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (isset($form->rec->title)) {
            $form->rec->title = str::mbUcfirst($form->rec->title);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            if (uiext_ObjectLabels::fetch("#labels LIKE '%|{$rec->id}|%'")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Връща наличните опции за избор
     *
     * @param int $classId
     *
     * @return array
     */
    private static function getLabelOptions($classId)
    {
        if (!self::$cache[$classId]) {
            $options = array();
            $opt = new stdClass();
            $opt->title = '';
            $opt->attr = array('style' => 'background-color:#fff; color:#000');
            $options[''] = $opt;
            
            $lQuery = self::getQuery();
            $lQuery->where("#docClassId = {$classId}");
            $lQuery->where('#state != "closed"');
            $lQuery->show('title,color');
            
            while ($lRec = $lQuery->fetch()) {
                $textColor = (phpcolor_Adapter::checkColor($lRec->color, 'dark')) ? '#fff' : '#000';
                $opt = new stdClass();
                $opt->title = $lRec->title;
                
                $opt->attr = array('style' => "background-color:{$lRec->color}; color:{$textColor}", 'data-color' => "{$lRec->color}", 'data-text' => "{$textColor}");
                $options[$lRec->id] = $opt;
            }
            
            self::$cache[$classId] = $options;
        }
        
        return self::$cache[$classId];
    }
    
    
    /**
     * Помощен метод за показване на таговете
     *
     * @param int           $class       - за кой клас
     * @param mixed         $masterClass - ид на контейнера
     * @param int           $masterId    - ид на контейнера
     * @param array         $recs        - масив със записите
     * @param array         $rows        - масив със вербалните записи
     * @param array         $listFields  - колонките
     * @param array         $hashFields  - кои полета ще служат за хеш
     * @param string        $colName     - Как ще се казва колонката за избора на тагове
     * @param core_ET       $tpl         - шаблон за рендиране
     * @param core_FieldSet &$fieldset   - шаблон за рендиране
     * @param void
     */
    public static function showLabels($class, $masterClass, $masterId, $recs, &$rows, &$listFields, $hashFields, $colName, &$tpl, core_FieldSet &$fieldset)
    {
        if (!is_array($rows)) {
            
            return;
        }
        if (Mode::isReadOnly() || Mode::is('blank')) {
            
            return;
        }
        $fieldset->FLD('_tagField', 'varchar', 'tdClass=tagColumn small-field');
        
        $listFields = arr::make($listFields, true);
        $listFields['_tagField'] = $colName;
        $classId = cls::get($class)->getClassId();
        
        // Генериране на таговете на документа
        foreach ($rows as $key => $row) {
            $rec = $recs[$key];
            $hash = self::getHash($rec, $hashFields);
            $row->_tagField = self::renderLabel($masterClass, $masterId, $classId, $hash);
        }
    }
    
    
    /**
     * Активира нужните файлове за таговете
     *
     * @param core_ET $tpl
     *
     * @return void
     */
    public static function enable(&$tpl)
    {
        // Зареждане на нужните файлове
        if (core_Packs::isInstalled('uiext')) {
            $tpl->push('uiext/js/Label.js', 'JS');
            jquery_Jquery::run($tpl, 'labelActions();');
        }
    }
    
    
    /**
     * Хеша на документа, който трябва да е уникален за записа
     *
     * @param stdClass $rec        - запис
     * @param mixed    $hashFields - полета за хеш
     *
     * @return string $hash     - хеш
     */
    public static function getHash($rec, $hashFields)
    {
        $hash = array();
        $hashFields = arr::make($hashFields, true);
        foreach ($hashFields as $name) {
            $hash[] = $rec->{$name};
        }
        
        $hash = md5(implode('|', $hash));
        
        return $hash;
    }
    
    
    /**
     * Рендиране на таговете на документа
     *
     * @param int    $containerId - ид на контейнера
     * @param int    $classId     - ид на класа, от който ще се избират таговете
     * @param string $hash        - хеш на реда
     *
     * @return string - инпута за избор на тагове
     */
    public static function renderLabel($masterClass, $masterId, $classId, $hash)
    {
        $masterClass = cls::get($masterClass);
        $labels = self::getLabelOptions($classId);
        if (count($labels) <= 1) {
            
            return;
        }
        
        // Връщане
        $value = null;
        $selRec = uiext_ObjectLabels::fetchByDoc($masterClass->getClassId(), $masterId, $hash);
        if ($selRec) {
            $value = keylist::toArray($selRec->labels);
            $value = key($value);
        }
        
        $input = '';
        if (uiext_ObjectLabels::haveRightFor('selectlabel', (object) array('classId' => $masterClass->getClassId(), 'objectId' => $masterId))) {
            $attr = array();
            $attr['class'] = 'transparentSelect selectLabel';
            $attr['data-url'] = toUrl(array('uiext_ObjectLabels', 'saveLabels', 'masterClassId' => $masterClass->getClassId(), 'objectId' => $masterId, 'hash' => $hash, 'classId' => $classId), 'local');
            $attr['title'] = 'Избор на таг';
            
            $input = ht::createSelect('selTag', $labels, $value, $attr);
            $input->removePlaces();
            $input = $input->getContent();
        } else {
            if (!empty($value)) {
                $input = cls::get('uiext_ObjectLabels')->getFieldType('labels')->toVerbal($value);
            }
        }
        
        if(!empty($input)){
            $k = "{$masterClass->getClassId()}|{$masterId}|{$classId}|{$hash}";
            $input = "<span id='charge{$k}'>{$input}</span>";
        }
        
        return $input;
    }
}
