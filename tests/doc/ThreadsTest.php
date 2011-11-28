<?php
class ThreadsTest extends PHPUnit_Framework_TestCase
{
	function testGenerateHandle() {
		$handles = array(0=>NULL);
		$nAttempts = 100000;
		
		$seq = 0;
		
		$sampleRec = (object)array(
			'id' => '323243'
		);
		
		foreach (range(1, $nAttempts) as $id) {
			$hnd = doc_Threads::generateHandle($sampleRec);
			$handles[$hnd]++;
			$seq += ($prevHnd == $hnd);
			$prevHnd = $hnd;
		}
		
		// Не генерираме последователно един и същ манипулатор по-често от 1 / 1000.
		$this->assertLessThan($nAttempts * 0.001, $seq);
		
		// Удостоверяваме, че уникалните манипулатори са повече от 30%
		$this->assertGreaterThanOrEqual(intval(round($nAttempts * 0.3)), count($handles));
	}
}