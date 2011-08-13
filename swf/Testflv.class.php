<?php

/**
 * Тестов клас за swfObject 2
 */
class Testflv () {
	
	var $loadList = 'swfObject';
	
	function act_Test()
	{

		$this->swfObject->setSwfFile('test.swf');
		$this->swfObject->setAlternativeContent('<h2>Нямате флаш или ДжаваСкрипт</h2>');
		$this->swfObject->setWidth(300);
		$this->swfObject->setHeight(120);
		
		return $this->swfObject->getContent();
		
	}
}