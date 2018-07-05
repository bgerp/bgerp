<?php



/**
 * Клас 'bglocal_IsBgName' -
 *
 * проверява дали дадено име е българско
 *
 * @category  bgerp
 * @package   bglocal
 * @author    Gabriela Petrova
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_IsBgName extends core_Manager
{
    
    /**
     * Статично зареждане на
     * @var string
     */
    public static $bgNames = '';
    
    /**
     * Тестов екшън
     */
    public function act_Test()
    {
        $name = 'ahah';
        self:: foundName($name);
    }
    
    
    /**
     * По зададен стринг, проверява в списък с имена
     * дали стринга не е българско име
     */
    public function foundName($name)
    {
        if (static::$bgNames == '') {
            static::$bgNames = file_get_contents('/var/www/ef_root/vendors/bglocal/data/bgLadiesNamesLatin.txt') . file_get_contents('/var/www/ef_root/vendors/bglocal/data/bgSurNamesLatin.txt');
        }
        
        $isName = strpos(static::$bgNames, "\n${name}\n");
        
        if ($isName === false) {
            $res = $name . ' не е българско име';
        } else {
            $res = $name . ' е българско име';
        }
        
        return $res;
    }
}
