<?php
use Behat\Mink\Exception\Exception;

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
    protected $CustomKey;
    
    protected function setUp()
    {
        /* @var $TestSetup test_Setup */
        $TestSetup = cls::get('test_Setup');
        $TestSetup->install();
        
        $this->KeyManager = cls::get('test_KeyManager');
        $this->CustomKey  = $this->KeyManager->getField('keyId')->type;
    }

    
    public function testToVerbal()
    {
        $actual = $this->CustomKey->toVerbal('CODE2');
        $expected = 'title 2';
        
        $this->assertEquals($expected, $actual);
    }

    
    public function testFromVerbal()
    {
        $actual = $this->CustomKey->fromVerbal('title 3');
        $expected = 'CODE3';
        
        $this->assertEquals($expected, $actual);
    }
    
    public function testRenderInput()
    {
        $actualHtml = (string)$this->CustomKey->renderInput('keyinput');
        $actualHtml = str_replace(array("\n", "\t", "\r"), array('', '', ''), $actualHtml);

        $expectedHtml = <<<EOT
<select name="keyinput"><option value="CODE1">title 1</option><option value="CODE2">title 2</option><option value="CODE3">title 3</option></select>
EOT;
        $this->assertEquals($expectedHtml, $actualHtml);
    }
}