<?php



/**
 * Клас 'colab_Wrapper'
 *
 * Опаковка на пакета colab: за достъп на външни потребители до определени
 * модели от системата
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        //if(colab_Folders::count()){
        	//$this->TAB('colab_Folders', 'Папки', 'contractor');
        //}
     
        $this->TAB(array('colab_Profiles', 'Single'), 'Профил', 'contractor');
    }
}