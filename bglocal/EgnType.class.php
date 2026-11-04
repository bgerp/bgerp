<?php


/**
 * Клас 'drdata_EgnType' -
 *
 * тип ЕГН
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_EgnType extends type_Varchar
{
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 10;
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 10;
    
    
    /**
     * Performs the parity check - we expect a 10-digit number!
     *
     * @param string $egn_string
     *
     * @return bool
     */
    public function isValid($value)
    {
        if (!$value) {
            
            return;
        }
        
        $value = trim($value);
        $err = null;
        
        try {
            $Egn = new bglocal_BulgarianEGN($value);
        } catch (bglocal_exception_EGN $e) {
            $err = $e->getMessage();
        }
        
        $res = array();
        $res['value'] = $value;
        
        if ($err) {
            $res['error'] = $err;
            $Lnc = new bglocal_BulgarianLNC();
            
            if ($Lnc->isLnc($value) === true) {
                unset($res['error']);
            } else {
                $res['error'] .= $Lnc->isLnc($value);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Представя ЕГН-то в разбираем за потребителя вид
     */
    public function toVerbal($value)
    {
        if (!$value) {
            
            return;
        }
        
        $err = null;
        try {
            $Egn = new bglocal_BulgarianEGN($value);
        } catch (bglocal_exception_EGN $e) {
            $err = $e->getMessage();
        }

        if ($err) {
            $color = 'green';
            $type = 'ЛНЧ';
        } else {
            $color = 'black';
            $type = 'ЕГН';
        }

        if(isset($this->params['onlyString'])){

            return $value;
        }

        return "<span style=\"color:{$color}\">" . tr($type) . " {$value}</span>";
    }


    /**
     * Дали ЕГН/ЛНЧ не е измислено (контролната цифра не хваща еднаквите цифри)
     *
     * @param string $value
     * @return bool
     */
    public static function isReal($value)
    {
        if (!preg_match('/^[0-9]{10}$/', $value)) return false;

        // При ЛНЧ всяко число от еднакви цифри минава контролната сума
        if (countR(array_unique(str_split($value))) < 3) return false;

        try {
            $Egn = new bglocal_BulgarianEGN($value);
        } catch (bglocal_exception_EGN $e) {
            $Lnc = new bglocal_BulgarianLNC();

            return ($Lnc->isLnc($value) === true);
        }

        // Рождената дата не може да е в бъдещето или преди повече от 110 години
        $birthDate = sprintf('%04d-%02d-%02d', $Egn->birth_year, $Egn->birth_month, $Egn->birth_day);
        $today = dt::today();
        if ($birthDate > $today || $birthDate < dt::addMonths(-110 * 12, $today, false)) return false;

        return true;
    }
}
