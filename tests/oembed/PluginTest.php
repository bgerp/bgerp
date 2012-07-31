<?php

class oembed_PluginTest extends framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        
        oembed_Cache::delete('1=1');
    }
    
    
    function testGetEmbedHtml()
    {
        $result = oembed_Plugin::getEmbedHtml('http://www.youtube.com/watch?v=NWYOlMWmJbE&feature=fvst');
        $this->assertTrue(strpos($result, '<iframe') !== FALSE, (string)$result);
    }
    
    function testEmbedFlickr()
    {
        $flickrUrl = 'http://www.flickr.com/photos/conorkeller/7643906142/';
        
        $result = oembed_Plugin::getEmbedHtml($flickrUrl);
        $this->assertTrue(strpos($result, '<img') !== FALSE, (string)$result);
        
        // Тестваме дали резултата е кеширан правилно
        $cacheRec = oembed_Cache::fetch("#url = '{$flickrUrl}'");
        
        $this->assertObjectHasAttribute('html', $cacheRec);
        $this->assertEquals($result, $cacheRec->html);
        $this->assertEquals('http://www.flickr.com/services/oembed/', $cacheRec->provider);
    }
    
    function testEmbedVbox7()
    {
        $result = oembed_Plugin::getEmbedHtml('http://vbox7.com/play:7981015ce8');
        $this->assertContains('<object', (string)$result, (string)$result, TRUE);
    }

    function testGetEmbedHtmlNonEmbedable()
    {
        $result = oembed_Plugin::getEmbedHtml('http://nonembedable.example.org');
        $this->assertFalse($result, $result);
    }
}