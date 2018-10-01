<?php


/**
 * Клас 'marketing_InquirySourceIntf' - източници на запитвания
 *
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title:    Интерфейс за източници на запитвания
 */
class marketing_InquirySourceIntf
{
    
    
    /**
     * Клас на обекта
     */
    public $class;
    
    
    /**
     * Какви са дефолтните данни за създаване на запитване
     *
     * @param mixed $id - ид или запис
     * @return array $res
     *          ['title']         - заглавие
     *          ['drvId']         - ид на драйвер
     *          ['lg']            - език
     *          ['protos']        - списък от прототипни артикули
     *          ['quantityCount'] - опционален брой количества
     *          ['moq']           - МКП
     *          ['measureId']     - основна мярка
     *
     */
    public function getInquiryData($id)
    {
        return $this->class->getInquiryData($id);
    }
}