<?php


/**
 * Блок за търсене на документи в изгледа на партньор
 *
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Търсене на документи в изгледа на партньор
 */
class colab_drivers_SearchTabBlock extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'colab_BlockIntf';


    /**
     * Подредба на таба
     * @return int
     */
    public function getTabOrder()
    {
        return 9;
    }


    /**
     * Подготвя данните
     * @return array
     */
    public function getBlockTabUrl()
    {
        return array('colab_Search');
    }


    /**
     * Връща заглавието за таба на съответния блок
     * @return string
     */
    public function getBlockTabName()
    {
        return 'Търсене';
    }


    /**
     * Може ли да се избира блока
     * @return bool
     */
    public function displayTab()
    {
        if(core_Packs::isInstalled('colab')){
            return colab_Folders::getSharedFoldersCount() > 0;
        }

        return false;
    }
}