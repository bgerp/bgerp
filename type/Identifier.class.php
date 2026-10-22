<?php


/**
 * Клас  'type_Identifier' - Тип за идентификатор
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Identifier extends type_Varchar
{
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        if (!is_array($value)) {
            $value = trim($value ?? '');
        }
        $value = parent::fromVerbal($value);

        if ($value === false) {
            return false;
        }
        
        if ($value === '' || $value === null) {
            
            return;
        }
        
        // Проверяваме дали е валиден
        $res = $this->isValid($value);
        
        // Ако има грешка, показваме нея
        if ($res['error'] ?? null) {
            
            // Сетваме грешката
            $this->error = $res['error'];
            
            return false;
        }
        
        return $value;
    }
    
    
    /**
     * Проверява дали е валиден
     */
    public function isValid($value)
    {
        $value = str_replace(' ', '_', $value ?? '');
        
        //Проверяваме за грешки
        $res = parent::isValid($value);
        
        //Ако има грешки връщаме резултата
        if ($res['error'] ?? null) {
            
            return $res;
        }
        
        $pattern = '/^[a-zA-Z_]{1}[a-zA-Z0-9_]*$/i';
        
        if ($this->params['utf8'] ?? null) {
            $pattern = "/^[\p{L}a-zA-Z_]{1}[\p{L}a-zA-Z0-9_]*$/iu";
        }

        if ($this->params['allowed'] ?? null) {
            $pattern = str_replace('_]', preg_quote($this->params['allowed']) . '_]', $pattern);
        }
        
        if ($value && !preg_match($pattern, $value)) {
            $res['error'] = 'Некоректен идентификатор|* ' . parent::escape($value);
        }
        
        $res['value'] = $value;
        
        return $res;
    }
}
