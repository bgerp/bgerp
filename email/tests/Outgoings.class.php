<?php


/**
 * Юнит тестове за email_Outgoings
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_tests_Outgoings extends unit_Class
{
    /**
     * Текст без кирилица в кирилски контекст се отговаря на английски, дори с ударения и символи
     */
    public function test_getLanguage()
    {
        core_Lg::push('bg');
        try {
            UT::expectEqual(email_Outgoings::getLanguage(null, null, null, "Buongiorno Guido,\necco le fustelle: 6,3 × 11 cm - inclinazione 10°.\nDimmi quale va bene e ti confermo la disponibilità."), 'en');
            UT::expectEqual(email_Outgoings::getLanguage(null, null, null, 'Hello, please see the attached file.'), 'en');
            UT::expectEqual(email_Outgoings::getLanguage(null, null, null, 'Здравейте, прилагам офертата - 6,3 × 11 см.'), 'bg');
            UT::expectEqual(email_Outgoings::getLanguage(null, null, null), 'bg');
        } finally {
            core_Lg::pop();
        }
    }
}
