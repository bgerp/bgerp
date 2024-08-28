<?php


/**
 * Блок за Изглед на нишки в папка на партньор
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
 * @title     Изглед на нишки в папка на партньор
 */
class colab_drivers_ThreadTabBlock extends core_BaseClass
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
        return 3;
    }


    /**
     * Подготвя данните
     * @return array
     */
    public function getBlockTabUrl()
    {
        $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
        $folderId = $folderId ?? Mode::get('lastFolderId');
        if ($folderId && colab_Threads::haveRightFor('list', (object) array('folderId' => $folderId))) {

            return array('colab_Threads', 'list', 'folderId' => $folderId);
        }

        return array();
    }


    /**
     * Връща заглавието за таба на съответния блок
     * @return string
     */
    public function getBlockTabName()
    {
        return 'Теми';
    }


    /**
     * Може ли да се избира блока
     * @return bool
     */
    public function displayTab()
    {
        if(core_Packs::isInstalled('colab')) return true;

        return false;
    }
}