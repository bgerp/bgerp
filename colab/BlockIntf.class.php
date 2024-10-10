<?php


/**
 * Интерфейс за табове на партньорите
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class colab_BlockIntf extends core_BaseClass
{
    /**
     * Подредба на таба
     *
     * @return int
     */
    public function getTabOrder()
    {
        return $this->class->getTabOrder();
    }


    /**
     * Подготвя данните
     *
     * @return array
     */
    public function getBlockTabUrl()
    {
        return $this->class->getBlockTabUrl();
    }


    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName()
    {
        return $this->class->getBlockTabName();
    }


    /**
     * Може ли да се избира блока
     * @return bool
     */
    public function displayTab()
    {
        return $this->class->displayTab();
    }
}
