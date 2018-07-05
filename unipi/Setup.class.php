<?php


/**
 * Драйвери за Unipi Neuron
 *
 * @category  bgerp
 * @package   unipi
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://www.unipi.technology/
 */
class unipi_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
      
    /**
     * Описание на модула
     */
    public $info = 'Драйвери за Unipi Neuron';
    
            
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'unipi_Neuron',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
         
        return $html;
    }
}
