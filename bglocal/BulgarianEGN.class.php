<?php


/**
 * Клас за парсиране на български ЕГН
 *
 * Този клас е взет от rosen [at] nazdrave.net
 * http://blog.nazdrave.net/?page_id=333
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @internal  да се поиска разрешението от автора за използването в GPL проект
 */
class bglocal_BulgarianEGN
{
    /**
     * @var string
     */
    public $egn;
    
    
    /**
     * @var int
     */
    public $birth_day;
    
    
    /**
     * @var int
     */
    public $birth_month;
    
    
    /**
     * @var int
     */
    public $birth_year;
    
    
    /**
     * @var string
     */
    public $region;
    
    
    /**
     * @var bool
     */
    public $is_male;
    
    
    /**
     * @var bool
     */
    public $is_female;
    
    
    /**
     * Taken from http://georgi.unixsol.org/programs/egn.php/view/
     *
     * @var array
     */
    private static $regions = array(
        'Благоевград' => 43, /* от 000 до 043 */
        'Бургас' => 93, /* от 044 до 093 */
        'Варна' => 139, /* от 094 до 139 */
        'Велико Търново' => 169, /* от 140 до 169 */
        'Видин' => 183, /* от 170 до 183 */
        'Враца' => 217, /* от 184 до 217 */
        'Габрово' => 233, /* от 218 до 233 */
        'Кърджали' => 281, /* от 234 до 281 */
        'Кюстендил' => 301, /* от 282 до 301 */
        'Ловеч' => 319, /* от 302 до 319 */
        'Монтана' => 341, /* от 320 до 341 */
        'Пазарджик' => 377, /* от 342 до 377 */
        'Перник' => 395, /* от 378 до 395 */
        'Плевен' => 435, /* от 396 до 435 */
        'Пловдив' => 501, /* от 436 до 501 */
        'Разград' => 527, /* от 502 до 527 */
        'Русе' => 555, /* от 528 до 555 */
        'Силистра' => 575, /* от 556 до 575 */
        'Сливен' => 601, /* от 576 до 601 */
        'Смолян' => 623, /* от 602 до 623 */
        'София - град' => 721, /* от 624 до 721 */
        'София - окръг' => 751, /* от 722 до 751 */
        'Стара Загора' => 789, /* от 752 до 789 */
        'Добрич' => 821, /* от 790 до 821 */
        'Търговище' => 843, /* от 822 до 843 */
        'Хасково' => 871, /* от 844 до 871 */
        'Шумен' => 903, /* от 872 до 903 */
        'Ямбол' => 925, /* от 904 до 925 */
        'Друг/Неизвестен' => 999, /* от 926 до 999 */
    );
    
    
    /**
     * @var array
     */
    private static $parity_weights = array(2, 4, 8, 5, 10, 9, 7, 3, 6);
    
    
    /**
     * @param string $egn_string
     *
     * @throws bglocal_exception_EGN
     */
    public function __construct($egn_string)
    {
        // must be 10-digit number:
        if (!preg_match('/^[0-9]{10}$/', $egn_string)) {
            throw new bglocal_exception_EGN('Полето трябва да съдържа 10 цифри.');
        }
        
        // parity digit must be correct:
        if (!self::isValid($egn_string)) {
            throw new bglocal_exception_EGN('Не е валидно ЕГН.');
        }
        
        $this->egn = $egn_string;
        
        $year = (int) substr($egn_string, 0, 2);
        $month = (int) substr($egn_string, 2, 2);
        $day = (int) substr($egn_string, 4, 2);
        
        
        /**
         * Month:
         * 1-12 means year 19xx,
         * 21-32 means year 18xx,
         * 41-52 means year 20xx
         */
        switch (true) {
            case $month >= 1 and $month <= 12:
            $year += 1900;
            break;
            
            case $month >= 21 and $month <= 32:
            $year += 1800;
            $month -= 20;
            break;
            
            case $month >= 41 and $month <= 52:
            $year += 2000;
            $month -= 40;
            break;
            
            default:
            throw new bglocal_exception_EGN('Месеца не е валиден');
        }
        
        // must be valid date (i.e. not 30/Feb)
        if (!checkdate($month, $day, $year)) {
            throw new bglocal_exception_EGN('В избрания месец няма толкова дни.');
        }
        
        $this->birth_year = $year;
        $this->birth_month = $month;
        $this->birth_day = $day;
        
        // digit 9 (which translates to index 8) is even for males, odd for females
        // Gender equality rulez, but is_male is assigned first! ;)
        $this->is_female = !($this->is_male = $egn_string{8} % 2 == 0);
        
        // detect birth region
        $this->region = self::getRegion($egn_string);
    }
    
    
    /**
     * Performs the parity check - we expect a 10-digit number!
     *
     * @param string $egn_string
     *
     * @return bool
     */
    public static function isValid($egn_string)
    {
        return self::getParityDigit($egn_string) == $egn_string{9};
    }
    
    
    /**
     * Computes the parity digit based on the first 9 digits
     *
     * @param string $egn_string
     *
     * @return int
     */
    public static function getParityDigit($egn_string)
    {
        $sum = 0;
        
        foreach (self::$parity_weights as $k => $weight) {
            $sum += $egn_string{$k} * $weight;
        }
        
        return ($sum % 11) % 10;
    }
    
    
    /**
     * Creates a new BulgarianEGN object
     * Usage example: echo BulgarianEGN::factory('0000000000')->region;
     *
     * @param string $egn_string
     *
     * @return BulgarianEGN
     */
    public static function factory($egn_string)
    {
        return new self($egn_string);
    }
    
    
    /**
     * Gives the name of the region where the person was most probably born
     * This is only relatively dependable, since it relies on common
     * practices instead of clear rules and regulations
     *
     * @param string $egn_string
     *
     * @return string
     */
    private static function getRegion($egn_string)
    {
        // extract the number on position 7 through 9
        $num = intval(substr($egn_string, 6, 3));
        
        foreach (self::$regions as $region => $boundary) {
            if ($num <= $boundary) {
                
                return $region;
            }
        }
        
        return '';
    }
}
