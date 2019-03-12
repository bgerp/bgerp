<?php


/**
 *
 *
 * @category  vendors
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_UserTranslates extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Потребителски преводи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, plg_RowTools2, plg_Created, plg_Modified, plg_PrevAndNext';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, translate';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, translate';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, translate';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, translate';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, translate';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'admin, translate';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, object=Обект, lang';
    
    
    /**
     * Префикс за името на полето, за стойността
     */
    private $fPrefix = '_';
    
    
    /**
     * Префикс за името на преведеното поле
     */
    private $tFPrefix = '_t_';
    
    
    public function description()
    {
        $this->FLD('classId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull,caption=Клас,silent');
        $this->FLD('recId', 'int', 'input=hidden,notNull,caption=Ид,silent');
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,silent');
        $this->FLD('data', 'blob(serialize, compress)', 'input=none');
    }
    
    
    /**
     * Връща преведения стринг за съответния език
     *
     * @param int         $classId
     * @param int         $recId
     * @param string      $lg
     * @param string      $fldName
     * @param null|string $checkValStr
     *
     * @return NULL|string
     */
    public static function getUserTranslatedStr($classId, $recId, $lg, $fldName, $checkValStr = null)
    {
        $rec = self::fetch(array("#classId = '[#1#]' AND #recId = '[#2#]' AND #lang = '[#3#]'", $classId, $recId, $lg));
        
        $tr = null;
        
        if ($rec) {
            $valArr = $rec->data[$fldName];
            
            if ($valArr && $valArr['tr']) {
                if (isset($checkValStr)) {
                    $checkValSrc = crc32($checkValStr);
                    if ($checkValSrc == $valArr['crc']) {
                        $tr = $valArr['tr'];
                    }
                } else {
                    $tr = $valArr['tr'];
                }
            }
        }
        
        return $tr;
    }
    
    
    /**
     * Помощна функция, която да връща всички полета, които могат да се превеждат от потребителя
     *
     * @param int      $clsId
     * @param string   $oField
     * @param null|int $recId
     *
     * @return array
     */
    public static function getUserTranslateFields($clsId, $oField = '*', $recId = null)
    {
        static $hashArr = array();
        
        if (!$clsId) {
            
            return array();
        }
        
        $hashStr = $clsId . '|' . $oField . '|' . $recId;
        
        if (isset($hashArr[$hashStr])) {
            
            return $hashArr[$hashStr];
        }
        
        $clsInst = cls::get($clsId);
        
        $tFields = $clsInst->selectFields('#translate');
        
        foreach ($tFields as $k => $f) {
            if (!($f->type instanceof type_Varchar)) {
                unset($tFields[$k]);
            }
        }
        
        if ($oField != '*') {
            foreach ($tFields as $k => $f) {
                $f->translate = trim($f->translate);
                $f->translate = strtolower($f->translate);
                if (!$f->translate) {
                    continue;
                }
                $tArr = explode('|', $f->translate);
                
                if (array_search('user', $tArr) === false) {
                    unset($tFields[$k]);
                }
            }
        }
        
        if ($recId && $tFields) {
            $fRec = $clsInst->fetch($recId);
            
            foreach ($tFields as $k => $f) {
                if (!trim($fRec->{$k})) {
                    unset($tFields[$k]);
                }
            }
        }
        
        $hashArr[$hashStr] = $tFields;
        
        return $hashArr[$hashStr];
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // Добавяме в ключовите думи на модела
        if ($rec->recId && $rec->classId && cls::load($rec->classId, true)) {
            $clsInst = cls::get($rec->classId);
            
            $plugins = $clsInst->getPlugins();
            
            if (isset($plugins['plg_Search'])) {
                $iRec = $clsInst->fetch($rec->recId);
                if ($iRec) {
                    $clsInst->save($iRec, 'searchKeywords');
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_UserTranslates $mvc
     * @param string              $requiredRoles
     * @param string              $action
     * @param stdClass            $rec
     * @param int                 $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add') && $rec->classId && $rec->recId && !$rec->id && ($requiredRoles != 'no_one')) {
            $tFields = $mvc->getUserTranslateFields($rec->classId, 'user', $rec->recId);
            if (empty($tFields)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        Request::setProtected(array('classId', 'recId'));
        
        $form = &$data->form;
        $rec = &$form->rec;
        
        $form->FNC('Selected', 'text', 'input=hidden, silent');
        
        $form->input(null, true);
        
        if (!$rec->id) {
            $clsId = Request::get('classId', 'int');
            $recId = Request::get('recId', 'int');
            
            $actName = 'add';
        } else {
            $clsId = $rec->classId;
            $recId = $rec->recId;
            $actName = 'edit';
        }
        
        expect($clsId && $recId);
        
        $clsInst = cls::get($clsId);
        
        expect($oRec = $clsInst->fetch($recId));
        
        expect($mvc->haveRightFor($actName, (object) array('classId' => $clsId, 'recId' => $recId)));
        
        if (!$rec->id) {
            cls::get('core_Lg');
            $form->setOptions('lang', arr::make(EF_LANGUAGES, true));
            $form->setDefault('lang', core_Lg::getCurrent());
        } else {
            $form->setReadOnly('lang');
        }
        
        $tFields = $mvc->getUserTranslateFields($clsId, 'user');
        
        $inpFields = 'lang';
        
        // Добавяме полетата за оригиналния текст и за превода
        foreach ($tFields as $name => $tField) {
            if (!$oRec->{$name}) {
                continue;
            }
            
            $fldName = $mvc->fPrefix . $name;
            $tFldName = $mvc->tFPrefix . $name;
            
            $fName = $tField->caption;
            
            $fName = str_replace('->', '|*: |', $fName);
            
            $form->FNC($fldName, $tField->type, array('input', 'caption' => $fName . '->Текст'));
            $form->setDefault($fldName, $oRec->{$name});
            $form->setReadOnly($fldName);
            
            $inpFields .= ',' . $fldName;
            
            $form->FNC($tFldName, 'varchar', array('input', 'caption' => $fName . '->Превод'));
            
            $form->fields['lang']->removeAndRefreshForm .= $tFldName . '|';
        }
        
        $form->input($inpFields);
        
        if ($rec->lang) {
            if (!$rec->id) {
                $cRec = $mvc->fetch(array("#classId = '[#1#]' AND #recId = '[#2#]' AND #lang = '[#3#]'", $clsId, $rec->recId, $rec->lang));
            } else {
                $cRec = $rec;
            }
        }
        
        if ($cRec && $cRec->data) {
            foreach ($cRec->data as $fName => $valArr) {
                $fldName = $mvc->fPrefix . $fName;
                if ($valArr['crc'] == crc32($rec->{$fldName})) {
                    $tFldName = $mvc->tFPrefix . $fName;
                    $form->setDefault($tFldName, $valArr['tr']);
                }
            }
        }
        
        // Коректни бутони за plg_PrevAndNext
        if ($sel = Request::get('Selected')) {
            $selArr = arr::make($sel);
            
            $selId = array_search($recId, $selArr);
            
            if ($selId !== false) {
                $data->buttons->prevId = $selArr[$selId - 1];
                $data->buttons->nextId = $selArr[$selId + 1];
            }
            
            $data->prevAndNextIndicator = ++$selId . '/' . count($selArr);
        }
    }
    
    
    /**
     * Преди подготовката на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, &$data)
    {
        // Махаме id-то добавено от plg_PrevAndNext
        if (Request::get('Selected')) {
            Request::push(array('id' => 0));
        }
    }
    
    
    /**
     * За коректна работа на plg_PrevAndNext
     *
     * @param stdClass $data
     * @param int|null $id
     */
    public function prepareRetUrl($data, $id = null)
    {
        $data = parent::prepareRetUrl_($data, $id);
        
        if ($sel = Request::get('Selected')) {
            $Cmd = Request::get('Cmd');
            
            $recId = Request::get('recId');
            $classId = Request::get('classId');
            
            $selArr = arr::make($sel);
            $selId = array_search($recId, $selArr);
            
            if ($selId !== false) {
                $prevId = $selArr[$selId - 1];
                $nextId = $selArr[$selId + 1];
            }
            
            $rUrl = array($this, 'add', 'PrevAndNext' => 'on', 'ret_url' => getRetUrl(), 'classId' => $classId, 'recId' => $prevId, 'Selected' => $sel, 'lang' => $data->form->rec->lang);
            
            if (isset($Cmd['save_n_prev']) && $prevId) {
                $data->retUrl = $rUrl;
            } elseif (isset($Cmd['save_n_next']) && $nextId) {
                $rUrl['recId'] = $nextId;
                $data->retUrl = $rUrl;
            } elseif ($Cmd['save']) {
                $clsInst = cls::get($classId);
                if (($clsInst instanceof core_Master) && ($clsInst->haveRightFor('single', $recId))) {
                    $data->retUrl = array($clsInst, 'single', $recId);
                } elseif (($clsInst instanceof core_Manager) && ($clsInst->haveRightFor('list', $recId))) {
                    $data->retUrl = array($clsInst, 'list');
                } else {
                    $data->retUrl = getRetUrl();
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_UserTranslates $mvc
     * @param stdClass            $row Това ще се покаже
     * @param stdClass            $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->classId) {
            if (cls::load($rec->classId, true)) {
                $cls = cls::get($rec->classId);
                $row->object = $cls->getLinkForObject($rec->recId);
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_UserTranslates $mvc    Мениджър, в който възниква събитието
     * @param int                 $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass            $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array        $fields Имена на полетата, които трябва да бъдат записани
     * @param string              $mode   Режим на записа: replace, ignore
     */
    public static function on_BeforeSave($mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->id === 0) {
            $rec->id = null;
        }
        $recArr = (array) $rec;
        if (!$rec->data) {
            $rec->data = array();
        }
        
        foreach ($recArr as $fName => $fVal) {
            if (stripos($fName, $mvc->tFPrefix) === 0) {
                $fName = substr($fName, strlen($mvc->tFPrefix));
                $rec->data[$fName] = array('tr' => $fVal, 'crc' => crc32($recArr[$mvc->fPrefix . $fName]));
            }
        }
        
        $cRecId = $mvc->fetchField(array("#classId = '[#1#]' AND #recId = '[#2#]' AND #lang = '[#3#]'", $rec->classId, $rec->recId, $rec->lang));
        
        if ($cRecId) {
            $rec->id = $cRecId;
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
}
