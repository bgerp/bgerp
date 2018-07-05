<?php



/**
 * Клас 'drdata_AddrRec' запис за парсирането на една линия от текста
 *
 *
 * @category  drdata
 * @package   bglocal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class drdata_AddrRec
{

    /**
     * Масив с данните извлечени от линията
     */
    public $data = array();

    /**
     * Масив с данни, чието писъствие трябва да се избягва
     */
    public $avoid = array();


    /**
     * Логическо разстояние с предходната линия. По-голямото разстояние може да означава нов блок
     */
    public $distance;
    

    public function __construct($avoid)
    {
        $this->avoid = $avoid;
    }

    public function add($field, $value, $trust = 1)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->add($field, $val, $trust);
            }
        } else {
            if (isset($this->data[$value])) {
                list($exField, $exTrust) = $this->data[$value];
                if ($exTrust >= $trust) {
                    return;
                }
            }
            
            // Ако в стойността се съдържа стринг, който трябва да избягваме -
            // прекъсваме и определяме голяма дистанция
            foreach ($this->avoid as $strToAvoid) {
                if (mb_strripos($value, $strToAvoid) !== false) {
                    $this->distance = 20;

                    return;
                }
            }

            $this->data[$value] = array($field, $trust);
        }
    }
    

    /**
     * Връща масив от съхранените данни
     */
    public function getData()
    {
        $res = null;

        if (count($this->data)) {
            foreach ($this->data as $value => $fieldArr) {
                list($field, $trust) = $fieldArr;
                $res[] = array($field, $value, $trust);
            }
        }

        return $res;
    }
}
