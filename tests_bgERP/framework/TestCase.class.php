<?php
class framework_TestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
         
        /* @var $BgerpSetup bgerp_Setup */
        $BgerpSetup = cls::get('bgerp_Setup');
    }
    
    protected function assertArrayMatch($expected, $actual)
    {
        $expected = (array) $expected;
        $actual = (array) $actual;
        
        foreach ($actual as $key => $val) {
            if (!array_key_exists($key, $expected)) {
                unset($actual[$key]);
            }
        }
        
        return $this->assertEquals($expected, $actual);
    }
    
    
    /**
     * Зареждане тестови данни.
     *
     * Всички съществуващи данни на моделите биват заличени.
     *
     * @param array $fixtureData тестови данни. Структурата на масива е
     *
     * array(
     *     "име_MVC1" => array(
     *         array( ... 'запис на MVC1' ... ),
     *         array( ... 'запис на MVC1' ... ),
     *         ...
     *     ),
     *     "име_MVC2" => array(
     *         array( ... 'запис на MVC2' ... ),
     *         array( ... 'запис на MVC2' ... ),
     *         ...
     *     ),
     *     ...
     * )
     */
    protected function loadFixtureData($fixtureData)
    {
        $result = array();
        
        if (isset($data)) {
            expect(is_scalar($fixtureData));
            $fixtureData = array($fixtureData => $data);
        }
        
        $this->truncate(array_keys($fixtureData));
        
        foreach ($fixtureData as $mvcName => $data) {
            $mvc = cls::get($mvcName);
            
            foreach ($data as $r) {
                try {
                    $result[$mvcName][] = $mvc->save((object) $r);
                } catch (core_exception_Expect $ex) {
                    print_r($ex->args());
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Изчиства всички данни на модели
     *
     * @param array|string|core_Mvc $models
     */
    protected function truncate($models)
    {
        if (is_string($models)) {
            $models = arr::make($models);
        } elseif ($models instanceof core_Mvc) {
            $models = array($models);
        }
        
        expect(is_array($models));
        
        foreach ($models as $mvc) {
            $mvc = cls::get($mvc);
            $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
        }
    }
}
