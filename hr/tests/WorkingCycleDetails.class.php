<?php 


/**
 * Unit test за hr_WorkingCycles
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_tests_WorkingCycleDetails extends unit_Class
{
    /**
     * Връща сечението на два периода задаени с начало и продълцителност
     * За периодите се очаква, че са задаени в часове:минути формат
     */
    public static function test_getSection($wc)
    {
        ut::expectEqual($wc->getSection(0, 8 * 60 * 60, 1 * 60 * 60, 8 * 60 * 60), 7 * 60 * 60);
        ut::expectEqual($wc->getSection((1 * 60 + 45) * 60, 8 * 60 * 60, (1 * 60 + 30) * 60, 8 * 60 * 60), (7 * 60 + 45) * 60);
        ut::expectEqual($wc->getSection(0, (18 * 60 + 20) * 60, 1 * 60 * 60, (18 * 60 + 20) * 60), (17 * 60 + 20) * 60);
        ut::expectEqual($wc->getSection(0, (18 * 60 + 20) * 60, 18 * 60 * 60, (18 * 60 + 20) * 60), 20 * 60);
        ut::expectEqual($wc->getSection(0, (18 * 60 + 20) * 60, 19 * 60 * 60, 18 * 60 * 60), 0);
    }

    
    
    /**
     * Преобразува часове:минути в минути
     */
    public static function hoursToMunutes($time)
    {
    }


    /**
     * Преобразува минути в часове:минути
     */
    public static function minutesToHours($time)
    {
    }
}
