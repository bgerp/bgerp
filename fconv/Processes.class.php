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
    var $loadList = 'fconv_Wrapper';
    
    
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
        
        $this->FLD("start", "type_Datetime", 'notNull, caption=Време на стартиране');
        
        $this->FLD("script", "blob(70000)", 'caption=Скрипт');
        
        $this->FLD("timeOut", "int", array('notNull' => TRUE, 'caption' => 'Продължителност'));
        
        $this->FLD("callBack", "varchar(128)", array('caption' => 'Функция'));
    }
    
    
    /**
     * Екшън за http callback от ОС скрипт
     * Получава управлението от шел скрипта и взема резултата от зададената функция.
     * Ако е TRUE тогава изтрива записите от таблицата за текущото конвертиране и
     * съответната директория.
     */
    function act_CallBack()
    {
        $pid = Request::get('pid');
        $func = Request::get('func');
        $rec = self::fetch(array("#processId = '[#1#]'", $pid));
        
        if (!is_object($rec)) {
            exit (1);
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
    static function getProcessId()
    {
        // Шаблона
        $pattern = str_repeat('*', static::FCONV_HANDLER_LEN);
        
        // Опитваме се да генерираме уникално id
        do {
            $processId = str::getRand($pattern);
        } while (static::fetch(array("#processId = '[#1#]'", $processId)));
        
        return $processId;
    }
}