<?php


/**
 * Проверки на предупрежденията за срока на годност при текстов експорт.
 *
 * @category  bgerp
 * @package   batch
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_tests_definitions_ExpirationDate extends unit_Class
{
    /**
     * Само Аида получава бележка до датата, когато партидата е изтекла или изтича скоро.
     */
    public function test_DisplayExpiryDate($Driver)
    {
        $today = dt::today();
        $expired = dt::addDays(-1, $today, false);
        $future = dt::addDays(10, $today, false);
        $lifetime = 5 * 86400;
        $expected = array(
            $expired => $expired . ' [!bgERP: Batch expired]',
            $today => $today . ' [!bgERP: Batch expires soon]',
            $future => $future,
        );

        Mode::push('text', 'plain');
        try {
            ut::expectEqual($Driver->displayExpiryDate($expired, 'Y-m-d', $lifetime), $expired);
            Mode::push('renderForLlm', true);
            try {
                foreach ($expected as $date => $text) {
                    ut::expectEqual(strip_tags($Driver->displayExpiryDate($date, 'Y-m-d', $lifetime)), $text);
                }
            } finally {
                Mode::pop('renderForLlm');
            }
        } finally {
            Mode::pop('text');
        }
    }
}
