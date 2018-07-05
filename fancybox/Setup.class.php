<?php


/**
 * Път до външния пакет
 */
defIfNot('FANCYBOX_VERSION', '2.1.5');


/**
 * Клас 'fancybox_Fancybox'
 *
 * Съдържа необходимите функции за използването на
 * Fancybox
 *
 *
 * @category  vendors
 * @package   fancybox
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 * @link      http://fancybox.net/
 */
class fancybox_Setup extends core_ProtoSetup
{
        
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за fancybox - разглеждане на картинки и галерии';

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
       'FANCYBOX_VERSION' => array('enum(1.3.4, 2.1.5)', 'mandatory, caption=Версията на програмата->Версия')

     );
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('fancybox');
        
        return 'fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('fancybox');
        
        return 'fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.css';
    }
}
