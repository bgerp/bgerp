<?php

 /**
 * Интерфейс за класове, даващи допълнителна информация за фирми
 *
 * @category   bgERP 2.0
 * @package    crm
 * @title:     Допълване на информацията за фирмите
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class crm_CompanyExpanderIntf
{
    
    /**
     * Подготовка на данните, които ще се показват
     */
    function prepareCompanyExpandData($data, $rec)
    {
        return $this->class->prepareCompanyExpandData($data, $rec);
    }
    

    /**
     * Рендиране на вече подготвените данни
     */
    function renderCompanyExpandData($data)
    {
        return $this->class->renderCompanyExpandData($data);
    }

}