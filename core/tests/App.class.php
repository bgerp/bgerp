<?php


/**
 * Execution limit regression tests; does not bootstrap the application or use a DB.
 */
class core_tests_App extends unit_Class
{
    public function test_ProcessUrlWithQueryString()
    {
        $oldGet = $_GET;
        $oldPost = $_POST;
        $oldServer = $_SERVER;
        set_error_handler(function ($errno, $message, $file, $line) {
            throw new ErrorException($message, 0, $errno, $file, $line);
        });

        try {
            $cases = array(
                array('/index.php', '/?isPwa=yes', '', null),
                array('/index.php', '/?foo=bar', '', null),
                array('/index.php', '/index?isPwa=yes', 'index', null),
                array('/index.php', '/bgerp_Portal/Show/?isPwa=yes', 'bgerp_Portal', 'Show'),
                array('/index.php', '/controller_/show/?isPwa=yes', 'controller_', 'show'),
                array('/nested/index.php', '/nested/?isPwa=yes', '', null),
            );
            foreach ($cases as $case) {
                list($script, $uri, $controller, $action) = $case;
                $_GET = array('virtual_url' => $uri, 'isPwa' => 'yes');
                $_POST = array();
                $_SERVER['SCRIPT_NAME'] = $script;
                $_SERVER['REQUEST_URI'] = $uri;

                $query = core_App::processUrl();

                ut::expectEqual($query['Ctr'] ?? null, $controller);
                ut::expectEqual($query['Act'] ?? null, $action);
                ut::expectEqual($_GET['isPwa'], 'yes');
            }
        } finally {
            restore_error_handler();
            $_GET = $oldGet;
            $_POST = $oldPost;
            $_SERVER = $oldServer;
        }
    }


    public function test_SetTimeLimit()
    {
        $limitProperty = new ReflectionProperty('core_App', 'runningTimeLimit');
        $limitProperty->setAccessible(true);
        $timeProperty = new ReflectionProperty('core_App', 'timeSetTimeLimit');
        $timeProperty->setAccessible(true);
        $originalLimit = (int) ini_get('max_execution_time');
        $originalTrackedLimit = $limitProperty->getValue();
        $originalTrackedTime = $timeProperty->getValue();
        $reset = function ($limit) use ($limitProperty, $timeProperty) {
            set_time_limit($limit);
            $limitProperty->setValue(null, null);
            $timeProperty->setValue(null, null);
        };
        try {
            foreach (array(300, 600, 900) as $limit) {
                $reset($limit);
                core_App::setTimeLimit(20);
                ut::expectEqual((int) ini_get('max_execution_time'), $limit);
                ut::expectEqual($limitProperty->getValue(), $limit);
            }

            $reset(0);
            core_App::setTimeLimit(600);
            ut::expectEqual((int) ini_get('max_execution_time'), 0);
            core_App::setTimeLimit(20, true);
            ut::expectEqual((int) ini_get('max_execution_time'), 20);

            $reset(600);
            core_App::setTimeLimit(30, true);
            ut::expectEqual((int) ini_get('max_execution_time'), 30);
            core_App::setTimeLimit(0, true, 0);
            ut::expectEqual((int) ini_get('max_execution_time'), 0);
            core_App::setTimeLimit(1200);
            ut::expectEqual((int) ini_get('max_execution_time'), 0);

            $reset(20);
            core_App::setTimeLimit(30.1);
            ut::expectEqual((int) ini_get('max_execution_time'), 31);
            core_App::setTimeLimit(0, false, 0);
            ut::expectEqual((int) ini_get('max_execution_time'), 0);

            $reset(5);
            core_App::setTimeLimit(1);
            ut::expectEqual((int) ini_get('max_execution_time'), 20);
            core_App::setTimeLimit(1, false, 40);
            ut::expectEqual((int) ini_get('max_execution_time'), 40);

            // Expired wall-clock bookkeeping must not shorten PHP's CPU-time budget.
            $reset(600);
            $limitProperty->setValue(null, 600);
            $timeProperty->setValue(null, time() - 601);
            core_App::setTimeLimit(20);
            ut::expectEqual((int) ini_get('max_execution_time'), 600);
            ut::expectEqual($timeProperty->getValue() >= time() - 1, true);

            // An external increase or decrease invalidates the stored bookkeeping.
            set_time_limit(900);
            core_App::setTimeLimit(20);
            ut::expectEqual((int) ini_get('max_execution_time'), 900);
            set_time_limit(30);
            core_App::setTimeLimit(60);
            ut::expectEqual((int) ini_get('max_execution_time'), 60);

            // Preserve renewal of an already granted limit after its deadline.
            $timeProperty->setValue(null, time() - 61);
            core_App::setTimeLimit(60);
            ut::expectEqual((int) ini_get('max_execution_time'), 60);
            ut::expectEqual($timeProperty->getValue() >= time() - 1, true);
        } finally {
            set_time_limit($originalLimit);
            $limitProperty->setValue(null, $originalTrackedLimit);
            $timeProperty->setValue(null, $originalTrackedTime);
        }
    }
}
