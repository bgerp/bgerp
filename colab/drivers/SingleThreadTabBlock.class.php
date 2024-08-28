<?php


/**
 * Блок за Изглед на нишка в таб на партньор
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
 * @title     Изглед на нишка в таб на партньор
 */
class colab_drivers_SingleThreadTabBlock extends core_BaseClass
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
        return 4;
    }


    /**
     * Подготвя данните
     * @return array
     */
    public function getBlockTabUrl()
    {
        $threadId = Request::get('threadId', 'int');
        $threadId = $threadId ?? Mode::get('lastThreadId');
        if ($threadId) {

            $threadRec = doc_Threads::fetch($threadId);
            if (colab_Threads::haveRightFor('single', $threadRec)) {
                return array('colab_Threads', 'single', 'threadId' => $threadId);
            }
        }


        return array();
    }


    /**
     * Връща заглавието за таба на съответния блок
     * @return string
     */
    public function getBlockTabName()
    {
        return 'Нишка';
    }


    /**
     * Може ли да се избира блока
     * @return bool
     */
    public function displayTab()
    {
        $res = false;
        if(core_Packs::isInstalled('colab')) {
            $res = true;
            $threadId = Request::get('threadId', 'int');
            if ($threadId) {
                Mode::setPermanent('lastThreadId', $threadId);
            }
        }

        return $res;
    }
}