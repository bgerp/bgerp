<?php

class oembed_PluginTest extends framework_TestCase
{
    function testGetEmbedHtml()
    {
        $result = oembed_Plugin::getEmbedHtml('http://www.youtube.com/watch?v=NWYOlMWmJbE&feature=fvst');
        $this->assertTrue(strpos($result, '<iframe') !== FALSE, (string)$result);
    }
    
    function testEmbedFlickr()
    {
        $result = oembed_Plugin::getEmbedHtml('http://www.flickr.com/photos/conorkeller/7643906142/');
        $this->assertTrue(strpos($result, '<img') !== FALSE, (string)$result);
    }

    function testGetEmbedHtmlNonEmbedable()
    {
        $result = oembed_Plugin::getEmbedHtml('http://nonembedable.example.org');
        $this->assertFalse($result, $result);
    }
}