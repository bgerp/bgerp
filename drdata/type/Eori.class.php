<?php


/**
 * Клас 'drdata_type_Eori' -
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_type_Eori extends type_Varchar
{
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 18;
    
    
    /**
     * Описание на различните видове статуси за EORI номера
     */
    public $statuses = array(
        'syntax' => array('Неправилен брой цифри', 'red'),
        'invalid' => array('Невалиден EORI номер', 'red'),
        'unknown' => array('EORI номер с неизвестна валидност', 'quiet'),
        'valid' => array('Валиден EORI номер', ''),
        '' => array('Грешка при определяне на валидността', 'red')
    );
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Инициализиране на дължината
     */
    public function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['size'], $this->dbFieldLen);
        setIfNot($this->params[0], $this->dbFieldLen);
    }
    
    
    /**
     * Проверка за валидност на EORI номер
     *
     * Ако проверката е неуспешна - връща само предупреждение
     */
    public function isValid($value)
    {
        if (!$value) {
            
            return;
        }
        
        $Eori = cls::get('drdata_Eori');
        
        $res = array();
        
        $res['value'] = $value = strtoupper(trim($value));
        
        list($status, ) = $Eori->check($value, true);
        
        $status = $this->statuses[$status];

        if ($status[1] == 'red') {
            $res['warning'] = $status[0];
            
            return $res;
        }
        
        $res = parent::isValid($value);
        $res['value'] = $value;
        
        return $res;
    }
    
    
    /**
     * Преобразува във вербален изглед посочения EORI номер
     */
    public function toVerbal_($value)
    {
        if (!$value) {
            
            return;
        }
        
        $value = parent::escape($value);

        if (Mode::is('text', 'plain') || Mode::is('pdf') || Mode::is('printing')) {
            
            return $value;
        }
        
        $Eori = cls::get('drdata_Eori');
        if (!array_key_exists($value, static::$cache)) {
            static::$cache[$value] = $Eori->check($value);
        }

        list($status, $info) = static::$cache[$value];

        $status = $this->statuses[$status];

        if (!$status) {
            $status = $this->statuses[''];
        }
        $attr = array();
        $attr['title'] = $status[0];
        
        if (trim($info)) {
            $attr['title'] .= "|*\n" . $info;
        }
        $attr['class'] = $status[1];
        
        return ht::createElement('span', $attr, $value);
    }
}
