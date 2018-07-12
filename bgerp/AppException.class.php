<?php
class bgerp_AppException extends Exception
{
    public $options;
    
    public $message;
    
    
    /**
     * Конструктор
     */
    public function __construct($message, $options = array())
    {
        parent::__construct($message);
        
        $this->options = $options;
        $this->message = $message;
    }
    
    
    /**
     * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
     *
     * @param bool   $condition
     * @param string $message
     * @param array  $options
     *
     * @throws static
     */
    public static function expect($condition, $message, $options = array())
    {
        if (!(boolean) $condition) {
            throw new static($message, $options);
        }
    }
    
    
    /**
     * Конвертира към стринг
     */
    public function __toString()
    {
        if (!empty($this->options['redirect'])) {
            $redirect = $this->options['redirect'];
            unset($this->options['redirect']);
        }
        
        $message = $this->getMessage() . ': ' . json_encode($this->options);
        
        if (empty($redirect)) {
            core_Message::redirect($message, 'page_Error');
        } else {
            core_App::redirect($redirect, false, $message, 'error');
        }
    }
}
