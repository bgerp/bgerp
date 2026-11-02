<?php


/**
 * Клас 'drdata_VatType' -
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_VatType extends type_Varchar
{
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 18;
    
    
    /**
     * Описание на различните видове статуси за VAT номера
     */
    public $statuses = array(
        'not_vat' => array('Липсва двубуквен префикс', 'red'),
        'bulstat' => array('Валиден ЕИК, но липсва BG в началото', 'red'),
        'syntax' => array('Невалиден/неподдържан формат на VAT номер (неправилен брой цифри и др.)', 'red'),
        'invalid' => array('Невалиден VAT номер', 'red'),
        'unknown' => array('VAT номер с неизвестна валидност', 'quiet'),
        'valid' => array('Валиден VAT номер', ''),
        '' => array('Грешка при определяне на валидността', 'red')
    );
    
    
    /**
     * Бележки за модела в LLM експорта по статус; валидният номер е без бележка
     */
    public $llmNotes = array(
        'not_vat' => 'VAT number not confirmed: no EU prefix',
        'bulstat' => 'Malformed VAT number: UIC without the BG prefix',
        'syntax' => 'Malformed VAT number',
        'invalid' => 'Invalid VAT number',
        'unknown' => 'VAT number not confirmed',
        'valid' => '',
        '' => 'VAT number not confirmed'
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
        $this->params['size'] = $this->params['size'] ?? $this->dbFieldLen;
        $this->params[0] = $this->params[0] ?? $this->dbFieldLen;
    }
    
    
    /**
     * Проверка за валидност на VAT номер
     *
     * Ако проверката е неуспешна - връща само предупреждение
     */
    public function isValid($value)
    {
        if (!$value) {
            
            return;
        }
        
        $Vats = cls::get('drdata_Vats');
        
        $res = array();
        
        $res['value'] = $value = strtoupper(trim($value));
        
        list($status, ) = $Vats->check($value, true);
        
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
    public function toVerbal_($value)
    {
        if (!$value) {
            
            return;
        }
        
        $value = parent::escape($value);
        
        // Цветът и хинтът се губят в LLM експорта, затова статусът се изписва като бележка
        if (Mode::is('renderForLlm')) {
            list($status, ) = $this->getStatus($value);
            $note = $this->llmNotes[$status] ?? $this->llmNotes[''];
            
            return $note === '' ? $value : $value . ' ' . doc_plg_LlmExportable::systemNote($note);
        }
        
        if (Mode::is('text', 'plain') || Mode::is('pdf') || Mode::is('printing')) {
            
            return $value;
        }
        
        list($status, $info) = $this->getStatus($value);
        
        $status = $this->statuses[$status];
        
        if (!$status) {
            $status = $this->statuses[''];
        }
        $attr = array();
        $attr['title'] = $status[0];
        
        if (trim((string) $info)) {
            $attr['title'] .= "|*\n" . $info;
        }
        $attr['class'] = $status[1];
        
        return ht::createElement('span', $attr, $value);
    }
    
    
    /**
     * Статус и информация за номера, кеширани за заявката
     */
    protected function getStatus($value)
    {
        if (!array_key_exists($value, static::$cache)) {
            static::$cache[$value] = cls::get('drdata_Vats')->check($value);
        }
        
        return static::$cache[$value];
    }
}
