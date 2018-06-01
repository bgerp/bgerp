<?php



/**
 * Клас 'drdata_VatType' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_VatType extends type_Varchar
{
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 18;

    
    /**
     * Описание на различните видове статуси за VAT номера
     */
    var $statuses = array (
                        'not_vat' => array('Липсва двубуквен префикс', 'red'),
                        'bulstat' => array('Валиден ЕИК, но липсва BG в началото', 'red'),
                        'syntax'  => array('Невалидна дължина на цифрите', 'red'),
                        'invalid' => array('Невалиден VAT номер', 'red'),
                        'unknown' => array('VAT номер с неизвестна валидност', 'quiet'),
                        'valid'   => array('Валиден VAT номер', ''),
                        ''        => array('Грешка при определяне на валидността', 'red')
                    );

    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Инициализиране на дължината
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['size'], $this->dbFieldLen);
        setIfNot($this->params[0], $this->dbFieldLen);
    }
    
    
    /**
     * Проверка за валидност на VAT номер
     * 
     * Ако проверката е неуспешна - връща само предупреждение
     */
    function isValid($value)
    {
        if(!$value) return NULL;
        
        $Vats = cls::get('drdata_Vats');
        
        $res = array();

        $res['value'] = $value = strtoupper(trim($value));
        
        list($status, ) = $Vats->check($value);

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
     * Преобразува във вербален изглед посочения VAT номер
     */
    function toVerbal_($value)
    {
        if(!$value) return NULL;
        
        $value = parent::escape($value);
         
        if(Mode::is('text', 'plain')) return $value;

        $Vats = cls::get('drdata_Vats');
        if(!array_key_exists($value, static::$cache)){
        	static::$cache[$value] = $Vats->check($value);
        }
        
        list($status, $info) = static::$cache[$value];

        $status = $this->statuses[$status];
        
        if(!$status) {
            $status = $this->statuses[''];
        }
        $attr = array();
        $attr['title'] = $status[0];
        
        if(trim($info)) {
            $attr['title'] .= "|*\n" . $info;  
        }
        $attr['class'] = $status[1];
 
        return ht::createElement('span', $attr, $value);
    }
}