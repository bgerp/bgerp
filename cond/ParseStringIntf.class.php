<?php


/**
 * Интерфейс за парсатор на стрингови стойности на типове на параметрите
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
class cond_ParseStringIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;


    /**
     * Парсиране на подадения стринг
     *
     * @param stdClass $rec
     * @param string $value
     * @return string
     */
    public function parse($rec, $value)
    {
        return $this->class->parse($rec, $value);
    }


    /**
     * Дали парсирания текст в хтмл
     *
     * @param stdClass $rec
     * @return boolean
     */
    public function isParsedAsHtml($rec)
    {
        return $this->class->isParsedAsHtml($rec);
    }
}