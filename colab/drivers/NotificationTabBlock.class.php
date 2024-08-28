<?php


/**
 * Блок за Изглед на известията на партньорите
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
 * @title     Изглед на известията на партньорите
 */
class colab_drivers_NotificationTabBlock extends core_BaseClass
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
        return 8;
    }


    /**
     * Подготвя данните
     * @return array
     */
    public function getBlockTabUrl()
    {
        return array('colab_Notifications', 'Show');
    }


    /**
     * Връща заглавието за таба на съответния блок
     * @return string
     */
    public function getBlockTabName()
    {
        $tabName = tr("Новини||News");
        $openNotifications = bgerp_Notifications::getOpenCnt();

        // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
        if ($openNotifications > 0) {
            $attr['class'] = 'haveNtf';
        } else {
            $attr['class'] = 'noNtf';
        }

        $countTpl = ht::createElement("span", $attr, $openNotifications);
        $countHtml = $countTpl->getContent();
        $tabName .= " |*<span id = 'nCntLink'>{$countHtml}</span>";

        return $tabName;
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