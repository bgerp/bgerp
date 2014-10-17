<?php

class type_KeyTest extends PHPUnit_Framework_TestCase
{
    /**
     * 
     * @var test_KeyManager
     */
    protected $KeyManager;
    

    /**
     * 
     * @var type_CustomKey
     */
    protected $CustomKeySelect;
    

    /**
     * 
     * @var type_CustomKey
     */
    protected $CustomKeyCombo;
    
    /**
     * Този метод се извиква преди всеки test*() метод на класа
     * 
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        /* @var $TestSetup test_Setup */
        $TestSetup = cls::get('test_Setup');
        $TestSetup->install();
        
        $this->KeyManager = cls::get('test_KeyManager');
        
        $this->CustomKeySelect = $this->KeyManager->getField('keyIdSelect')->type;
        $this->CustomKeyCombo  = $this->KeyManager->getField('keyIdCombo')->type;
        
        // SELECT: Задаваме параметъра 'maxSuggestions' да е ПОВЕЧЕ от броя записи в
        // модела test_Key. 
        $this->CustomKeySelect->params['maxSuggestions'] = 100;

        // COMBO: Задаваме параметъра 'maxSuggestions' да е ПО-МАЛКО от броя записи в
        // модела test_Key. 
        $this->CustomKeyCombo->params['maxSuggestions'] = 2;
    }

    
    /**
     * Тест за конвертиране от ключ към вербална стойност
     * 
     * Резултатът би трябвало да е едни и същ, независимо от вида на полето (SELECT, COMBO)
     */
    public function testToVerbal()
    {
        $actual = $this->CustomKeySelect->toVerbal('CODE2');
        $this->assertEquals('title 2', $actual);
        
        $actual = $this->CustomKeySelect->toVerbal('CODE4');
        $this->assertEquals('CODE2', $actual);
        
        $actual = $this->CustomKeyCombo->toVerbal('CODE2');
        $this->assertEquals('title 2', $actual);

        $actual = $this->CustomKeyCombo->toVerbal('CODE4');
        $this->assertEquals('CODE2', $actual);
    }

    
    /**
     * Тест за конвертиране от вербална стойност към ключ в случая на SELECT ключ.
     * 
     * В този случай се очаква вербалната стойност да е всъщност ключ-стойност
     */
    public function testFromVerbalSelect()
    {
        $actual = $this->CustomKeySelect->fromVerbal('CODE3');
        $this->assertEquals('CODE3', $actual);
        $this->assertEmpty($this->CustomKeySelect->error);

        $actual = $this->CustomKeySelect->fromVerbal('CODE4');
        $this->assertEquals('CODE4', $actual);
        $this->assertEmpty($this->CustomKeySelect->error);

        $actual = $this->CustomKeySelect->fromVerbal('title 3');
        $this->assertFalse($actual);
        $this->assertNotEmpty($this->CustomKeySelect->error);
    }


    /**
     * Тест за конвертиране от вербална стойност към ключ в случая на COMBO ключ.
     *
     */
    public function testFromVerbalCombo()
    {
        $actual = $this->CustomKeyCombo->fromVerbal('title 3');
        $this->assertEquals('CODE3', $actual);
        $this->assertEmpty($this->CustomKey->error);
        
        $actual = $this->CustomKeyCombo->fromVerbal('CODE2');
        $this->assertEquals('CODE4', $actual);
        $this->assertEmpty($this->CustomKey->error);
        
        $actual = $this->CustomKeyCombo->fromVerbal('CODE3');
        $this->assertFalse($actual);
        $this->assertNotEmpty($this->CustomKeyCombo->error);
    }
    
    
    /**
     * Тест дали fromVerbal връща грешки когато трябва
     */
    public function testFromVerbalErrors()
    {
        $actual = $this->CustomKeySelect->fromVerbal('__MISSING_CODE__');
        $this->assertFalse($actual);
        $this->assertNotEmpty($this->CustomKeySelect->error);
        
        $actual = $this->CustomKeyCombo->fromVerbal('__Missing verbal value__');
        $this->assertFalse($actual);
        $this->assertNotEmpty($this->CustomKeyCombo->error);
    }

    
    public function testRenderInput()
    {
        $actualHtml = (string)$this->CustomKeySelect->renderInput('keyinput');
        $actualHtml = str_replace(array("\n", "\t", "\r"), array('', '', ''), $actualHtml);

        $expectedHtml = <<<EOT
<select name="keyinput"><option value="CODE4">CODE2</option><option value="CODE1">title 1</option><option value="CODE2">title 2</option><option value="CODE3">title 3</option></select>
EOT;
        $this->assertEquals($expectedHtml, $actualHtml);
    }
}