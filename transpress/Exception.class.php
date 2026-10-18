<?php


/**
 * Грешка, върната от API-то на Transpress
 *
 * @category  bgerp
 * @package   transpress
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class transpress_Exception extends core_exception_Expect
{
    /**
     * Грешките по полета: пътят на грешката (или число) => съобщение
     */
    public $errors = array();
}
