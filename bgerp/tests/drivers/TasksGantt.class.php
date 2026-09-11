<?php


/**
 * Регресионни проверки за относителните периоди на порталния Гант.
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_tests_drivers_TasksGantt extends unit_Class
{
    public function test_Periods()
    {
        $cases = array(
            array('thisMonth', '2024-02-29', '2024-02-01', '2024-02-29'),
            array('thisMonth', '2025-02-28', '2025-02-01', '2025-02-28'),
            array('thisMonth', '2026-09-30', '2026-09-01', '2026-09-30'),
            array('nextMonth', '2026-12-31', '2027-01-01', '2027-01-31'),
            array('nextMonth', '2024-01-31', '2024-02-01', '2024-02-29'),
            array('aroundMonth', '2024-03-31', '2024-02-29', '2024-04-30'),
            array('aroundMonth', '2025-03-31', '2025-02-28', '2025-04-30'),
            array('aroundMonth', '2026-01-15', '2025-12-15', '2026-02-15'),
            array('thisYear', '2026-09-11', '2026-01-01', '2026-12-31'),
            array('relative', '2026-01-01', '2025-12-02', '2026-01-31'),
        );
        foreach ($cases as $case) {
            ut::expectEqual(bgerp_drivers_TasksGantt::getPeriodRange((object) array('period' => $case[0]), $case[1]),
                array($case[2], $case[3]));
        }

        $rec = (object) array('period' => 'relative', 'daysBefore' => 0, 'daysAfter' => 0);
        ut::expectEqual(bgerp_drivers_TasksGantt::getPeriodRange($rec, '2026-09-11'), array('2026-09-11', '2026-09-11'));
        $rec = (object) array('period' => 'fixed', 'dateFrom' => '2025-02-01', 'dateTo' => '2025-03-01');
        ut::expectEqual(bgerp_drivers_TasksGantt::getPeriodRange($rec, '2026-09-11'), array('2025-02-01', '2025-03-01'));
    }


    public function test_ExplicitChartRange()
    {
        $data = (object) array('ganttFrom' => '2026-09-01', 'ganttTo' => '2026-09-30',
            'recs' => array((object) array('timeStart' => '2026-08-01 09:00:00', 'timeEnd' => '2026-12-01 18:00:00')));
        $range = cal_Tasks::calcTasksMinStartMaxEndTime($data);
        ut::expectEqual($range->minStartTaskTime ?? null, dt::mysql2timestamp('2026-09-01 00:00:00'));
        ut::expectEqual($range->maxEndTaskTime ?? null, dt::mysql2timestamp('2026-09-30 23:59:59'));

        unset($data->ganttFrom, $data->ganttTo);
        $range = cal_Tasks::calcTasksMinStartMaxEndTime($data);
        ut::expectEqual($range->minStartTaskTime ?? null, dt::mysql2timestamp('2026-08-01 09:00:00'));
        ut::expectEqual($range->maxEndTaskTime ?? null, dt::mysql2timestamp('2026-12-01 18:00:00'));
    }


    public function test_PortalDisplayRange()
    {
        $cases = array(
            array('Years', '2026-05-01 00:00:00', '2026-09-01 00:00:00', '2026-05-01', '2026-08-31', 4),
            array('Years', '2026-05-15 09:00:00', '2026-05-15 10:00:00', '2026-05-01', '2026-05-31', 1),
            array('Years', '2026-05-01 00:00:00', '2026-07-01 00:00:00', '2026-05-01', '2026-06-30', 2),
            array('Years', '2026-05-01 00:00:00', '2026-08-01 00:00:00', '2026-05-01', '2026-07-31', 3),
            array('Months', '2026-06-01 00:00:00', '2026-07-01 00:00:00', '2026-06-01', '2026-06-30', 30),
            array('Months', '2026-06-15 09:00:00', '2026-06-15 10:00:00', '2026-06-15', '2026-06-15', 1),
            array('Months', '2026-06-15 00:00:00', '2026-06-17 00:00:00', '2026-06-15', '2026-06-16', 2),
            array('Months', '2026-06-15 00:00:00', '2026-06-18 00:00:00', '2026-06-15', '2026-06-17', 3),
            array('Months', '2026-06-15 00:00:00', '2026-06-19 00:00:00', '2026-06-15', '2026-06-18', 4),
            array('WeekDay', '2026-06-15 09:00:00', '2026-06-15 10:00:00', '2026-06-15', '2026-06-15', 1),
            array('YearWeek', '2026-06-03 09:00:00', '2026-06-03 10:00:00', '2026-06-01', '2026-06-07', 1),
        );
        $timezone = date_default_timezone_get();
        foreach ($cases as $case) {
            $start = dt::mysql2timestamp($case[1]);
            $end = dt::mysql2timestamp($case[2]);
            $data = (object) array('ganttPortal' => true, 'ganttScale' => $case[0],
                'ganttFrom' => '2026-01-01', 'ganttTo' => '2026-12-31',
                'ganttTasks' => array(array('rowId' => array(0),
                    'timeline' => array(array('startTime' => $start, 'duration' => $end - $start)))));
            $chart = cal_Tasks::renderGanttTimeType($data);
            $params = $chart->otherParams ?? array();
            ut::expectEqual($params['startTime'] ?? null, dt::mysql2timestamp($case[3] . ' 00:00:00'));
            ut::expectEqual($params['endTime'] ?? null, dt::mysql2timestamp($case[4] . ' 23:59:59'));
            $columns = 0;
            foreach ((array) ($chart->headerInfo ?? array()) as $header) {
                $columns += countR($header['subHeader'] ?? array());
            }
            ut::expectEqual($columns, $case[5]);
            ut::expectEqual(date_default_timezone_get(), $timezone);
        }

        $data->ganttFrom = '2026-06-01';
        $data->ganttTo = '2026-07-01';
        $data->ganttTasks = array(array('rowId' => array(0), 'timeline' => array(
            array('startTime' => dt::mysql2timestamp('2026-05-01'), 'duration' => 100 * 86400))));
        $range = cal_Tasks::calcTasksMinStartMaxEndTime($data);
        ut::expectEqual($range->minStartTaskTime ?? null, dt::mysql2timestamp('2026-06-01 00:00:00'));
        ut::expectEqual($range->maxEndTaskTime ?? null, dt::mysql2timestamp('2026-07-01 23:59:59'));

        $data->ganttScale = 'Months';
        $data->ganttTasks = array(array('rowId' => array(0), 'timeline' => array(
            array('startTime' => dt::mysql2timestamp('2026-06-10 09:00:00'), 'duration' => 3600),
            array('startTime' => dt::mysql2timestamp('2026-06-15 09:00:00'), 'duration' => 3600))));
        $chart = cal_Tasks::renderGanttTimeType($data);
        $headers = $chart->headerInfo ?? array();
        $columns = $headers[0]['subHeader'] ?? array();
        ut::expectEqual(countR($columns), 6);
        foreach ($columns as $index => $column) {
            ut::expectEqual(trim(strip_tags($column)), sprintf('%02d.06', 10 + $index));
        }
    }
}
