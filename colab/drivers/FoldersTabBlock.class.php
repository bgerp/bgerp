<?php


/**
 * Блок за папки в изгледа на партньор
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
 * @title     Папки в изгледа на партньор
 */
class colab_drivers_FoldersTabBlock extends core_BaseClass
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
        return 2;
    }


    /**
     * Подготвя данните
     * @return array
     */
    public function getBlockTabUrl()
    {
        return array('colab_Folders');
    }


    /**
     * Връща заглавието за таба на съответния блок
     * @return string
     */
    public function getBlockTabName()
    {
        return 'Папки';
    }


    /**
     * Може ли да се избира блока
     * @return bool
     */
    public function displayTab()
    {
        $res = false;
        if(core_Packs::isInstalled('colab')){
            $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');

            if (colab_Folders::getSharedFoldersCount() > 1) {
                $res = true;
            } else {
                $query = colab_Folders::getQuery();
                $folderId = $query->fetch()->id;
            }

            if ($folderId) {
                Mode::setPermanent('lastFolderId', $folderId);
            }
        }

        return $res;
    }
}