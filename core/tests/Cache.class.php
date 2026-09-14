<?php


/**
 * Проверки на кеша в текущото PHP изпълнение, без достъп до база данни
 *
 * @category ef
 * @package core
 */
class core_tests_Cache extends unit_Class
{
    public static function test_RememberEmptyValuesAndArrayIsolation()
    {
        foreach (array(array(), null, false, array(7 => 'Customer')) as $value) {
            $calls = 0;
            $key = uniqid('', true);
            $load = function () use (&$calls, $value) {
                $calls++;
                return $value;
            };

            $first = core_Cache::remember(__METHOD__, $key, $load);
            if (is_array($first)) {
                $first[99] = 'POS customer';
            }
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), $value);
            ut::expectEqual($calls, 1);
        }
    }


    public static function test_RememberSeparatesContexts()
    {
        $calls = 0;
        $type = uniqid('', true);
        $load = function () use (&$calls) { return ++$calls; };
        $contexts = array(
            array(1, 'bg', 'all'), array(2, 'bg', 'all'),
            array(1, 'en', 'all'), array(1, 'bg', 'selected'),
        );
        foreach ($contexts as $index => $context) {
            ut::expectEqual(core_Cache::remember($type, $context, $load), $index + 1);
        }
        ut::expectEqual(core_Cache::remember($type, $contexts[0], $load), 1);
        ut::expectEqual(core_Cache::remember($type . '_other', $contexts[0], $load), 5);
    }


    public static function test_RememberInvalidatesOnlyChangedDependencies()
    {
        $key = uniqid('', true);
        $sales = self::makeMvc('sales_' . $key, 'core_tests_CacheSales');
        $folders = self::makeMvc('folders_' . $key, 'core_tests_CacheFolders');
        $sameTable = self::makeMvc($folders->dbTableName ?? '');
        $unrelated = self::makeMvc('other_' . $key);
        $anotherDb = self::makeMvc($folders->dbTableName ?? '');
        $anotherDb->db = (object) array('dbName' => 'another_database');
        $depends = array(get_class($sales), get_class($folders));
        $calls = 0;
        $load = function () use (&$calls) { return ++$calls; };

        // cls::get() разрешава зависимостите до singleton инстанциите на моделите.
        $oldSingletons = array();
        foreach (array($sales, $folders) as $mvc) {
            $class = get_class($mvc);
            $oldSingletons[$class] = cls::$singletons[$class] ?? null;
            cls::$singletons[$class] = $mvc;
        }
        try {
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, $depends), 1);
            $unrelated->dbTableUpdated_();
            $anotherDb->dbTableUpdated_();
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, $depends), 1);
            $sales->dbTableUpdated_();
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, $depends), 2);
            $sameTable->dbTableUpdated_();
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, $depends), 3);
            $sameTable->dbTableUpdated_();
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, $depends), 4);
            ut::expectEqual($folders->getDbTableUpdateCount(), 2);
        } finally {
            foreach ($oldSingletons as $class => $mvc) {
                if (isset($mvc)) {
                    cls::$singletons[$class] = $mvc;
                } else {
                    unset(cls::$singletons[$class]);
                }
            }
        }
    }


    public static function test_RememberRetriesAfterException()
    {
        $key = uniqid('', true);
        $calls = 0;
        $load = function () use (&$calls) {
            if (++$calls == 1) {
                throw new RuntimeException('Temporary failure');
            }
            return array(1 => 'Recovered');
        };
        $failed = false;
        try {
            core_Cache::remember(__METHOD__, $key, $load);
        } catch (RuntimeException $e) {
            $failed = true;
        }
        ut::expectEqual($failed, true);
        ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), array(1 => 'Recovered'));
        ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), array(1 => 'Recovered'));
        ut::expectEqual($calls, 2);
    }


    public static function test_RememberHonorsStopCaching()
    {
        $key = uniqid('', true);
        $calls = 0;
        $load = function () use (&$calls) { return ++$calls; };
        $previous = core_Cache::$stopCaching;
        try {
            core_Cache::$stopCaching = false;
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), 1);
            core_Cache::$stopCaching = true;
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), 2);
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), 3);
            core_Cache::$stopCaching = false;
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), 4);
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load), 4);
        } finally {
            core_Cache::$stopCaching = $previous;
        }
    }


    public static function test_RememberAcrossRequestsAndExpiry()
    {
        $store = new core_tests_CacheStore();
        $oldStore = cls::$singletons['core_Cache'] ?? null;
        $memory = new ReflectionProperty('core_Cache', 'requestCache');
        $memory->setAccessible(true);
        $oldMemory = $memory->getValue();
        cls::$singletons['core_Cache'] = $store;
        $calls = 0;
        $key = uniqid('', true);
        $load = function () use (&$calls) { return ++$calls; };

        try {
            $memory->setValue(null, array());
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, array(), 5), 1);
            ut::expectEqual($store->writes, 1);
            ut::expectEqual($store->keepMinutes, 5);
            $expires = reset($store->data)->value['expiresOn'];
            ut::expectEqual($expires > microtime(true) + 295 && $expires <= microtime(true) + 300, true);

            // Нов хит, но същият стандартен кеш: няма преизчисление или удължаване.
            $memory->setValue(null, array());
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, array(), 5), 1);
            ut::expectEqual($store->reads, 2);
            ut::expectEqual($store->writes, 1);
            ut::expectEqual(reset($store->data)->value['expiresOn'], $expires);
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, array(), 5), 1);
            ut::expectEqual($store->reads, 2);

            // Изтичането важи и за дълъг хит; backend-ът умишлено връща стария запис.
            $requestEntries = $memory->getValue();
            foreach ($requestEntries as &$entry) {
                $entry['expiresOn'] = microtime(true) - 1;
            }
            unset($entry);
            $memory->setValue(null, $requestEntries);
            foreach ($store->data as $entry) {
                $entry->value['expiresOn'] = microtime(true) - 1;
            }
            ut::expectEqual(core_Cache::remember(__METHOD__, $key, $load, array(), 5), 2);
            ut::expectEqual($store->writes, 2);

            foreach (array(array(), null, false) as $value) {
                $valueKey = uniqid('', true);
                $valueCalls = 0;
                $valueLoad = function () use ($value, &$valueCalls) { $valueCalls++; return $value; };
                ut::expectEqual(core_Cache::remember(__METHOD__, $valueKey, $valueLoad, array(), 5), $value);
                $memory->setValue(null, array());
                ut::expectEqual(core_Cache::remember(__METHOD__, $valueKey, $valueLoad, array(), 5), $value);
                ut::expectEqual($valueCalls, 1);
            }
        } finally {
            $memory->setValue(null, $oldMemory);
            if (isset($oldStore)) {
                cls::$singletons['core_Cache'] = $oldStore;
            } else {
                unset(cls::$singletons['core_Cache']);
            }
        }
    }


    private static function makeMvc($table, $class = 'core_Mvc')
    {
        $mvc = new $class();
        $mvc->db = (object) array('dbName' => 'cache_unit_test');
        $mvc->dbTableName = $table;

        return $mvc;
    }
}


/**
 * Тестов модел за продажби, без достъп до база данни
 */
class core_tests_CacheSales extends core_Mvc
{
}


/**
 * Тестов модел за папки, без достъп до база данни
 */
class core_tests_CacheFolders extends core_Mvc
{
}


/**
 * Изолира само съхранението; изпълнява реалните core_Cache::get/set/remember
 */
class core_tests_CacheStore extends core_Cache
{
    public $data = array();
    public $reads = 0;
    public $writes = 0;
    public $keepMinutes;

    public function getData($key, $keepMinutes = null)
    {
        $this->reads++;

        return isset($this->data[$key]) ? unserialize(serialize($this->data[$key])) : null;
    }

    public function setData($key, $data, $keepMinutes)
    {
        $this->writes++;
        $this->keepMinutes = $keepMinutes;
        $this->data[$key] = unserialize(serialize($data));
    }

    public function deleteData($key, $onlyInMemory = false)
    {
        unset($this->data[$key]);
    }
}
