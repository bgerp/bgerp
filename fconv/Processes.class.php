<?php


/**
 * Показва стартираните процеси
 *
 *
 * @category  vendors
 * @package   fconv
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fconv_Processes extends core_Manager
{
    
    
    /**
     * Заглавие на модула
     */
    var $title = "Стартирани процеси";
    
    
    /**
     * 
     */
    var $loadList = 'fconv_Wrapper, plg_Created';
    
    
    /**
     * Дължина на манипулатора за processId
     */
    const FCONV_HANDLER_LEN = 8;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD("processId", "varchar(" . static::FCONV_HANDLER_LEN . ")",
            array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        $this->FLD("script", "blob(70000)", 'caption=Скрипт');
        
        $this->FLD("timeOut", "int", array('notNull' => TRUE, 'caption' => 'Продължителност'));
        
        $this->FLD("callBack", "varchar(128)", array('caption' => 'Функция'));
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Добавя запис в модела с подадените данни, които после ще послужат за callBack
     * 
     * @param string $processId
     * @param string $script
     * @param number $time
     * @param string $timeoutCallback
     */
    public static function add($processId, $script, $time = 2, $timeoutCallback = '')
    {
        $rec = new stdClass();
        $rec->processId = $processId;
        $rec->script = $script;
        $rec->timeOut = $time;
        $rec->callBack = $timeoutCallback;
        
        self::save($rec);
    }
    
    
    /**
     * Извиква подадената callBack функция
     * 
     * @param string $pid
     * @param string $func
     * 
     * @return boolean
     */
    public static function runCallbackFunc($pid, $func)
    {
        $rec = self::fetch(array("#processId = '[#1#]'", $pid));
        
        if (!is_object($rec)) {
            exit(1);
        }
        
        $script = unserialize($rec->script);
        $funcArr = explode('::', $func);
        $object = cls::get($funcArr[0]);
        $method = $funcArr[1];
        $result = call_user_func_array(array($object, $method), array($script));
        
        if ($result) {
            if (core_Os::deleteDir($script->tempDir)) {
                fconv_Processes::delete(array("#processId = '[#1#]'", $pid));
        
                return TRUE;
            }
        }
    }
    
    
    /**
     * Връща уникален идентификатор на процеса
     * 
     * @return string - Уникален `processId`
     */
    public static function getProcessId()
    {
        // Шаблона
        $pattern = str_repeat('*', static::FCONV_HANDLER_LEN);
        
        // Опитваме се да генерираме уникално id
        do {
            $processId = str::getRand($pattern);
        } while (static::fetch(array("#processId = '[#1#]'", $processId)));
        
        return $processId;
    }
    
    
    /**
     * Екшън за http callback от ОС скрипт
     * Получава управлението от шел скрипта и взема резултата от зададената функция.
     * Ако е TRUE тогава изтрива записите от таблицата за текущото конвертиране и
     * съответната директория.
     */
    function act_CallBack()
    {
        Request::setProtected('pid, func');
        
        $pid = Request::get('pid');
        $func = Request::get('func');
        
        expect($pid && $func);
        
        return self::runCallbackFunc($pid, $func);
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param fconv_Processes $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        // Сортиране на записите по num
        $data->query->orderBy('createdOn', 'DESC');
    }
}