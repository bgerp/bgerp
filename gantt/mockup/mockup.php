<?php
$endTime = '2015-12-31 23:59:59';
$startTime =  '2013-01-01 0:00:00';
$endTime = dt::mysql2timestamp($endTime);
$startTime = dt::mysql2timestamp($startTime);
 
 
$testArr=array (
		'0' => array (
				'taskId' => "Tsk113",
				'rowId' => 0,
				'duration' => 31536000,
				'startTime' => dt::mysql2timestamp("2013-02-01 0:00:00"),
				'color' => "#c00",
				'hint' => '"2013-02-01 0:00:00" (godina)',
				'url' => 'http://www.google.com/'
		),
		'1' => array (
				'taskid' => "Tsk133",
				'rowId' => 2,
				'duration' => 15768000,
				'startTime' => dt::mysql2timestamp("2013-01-10 0:00:00"),
				'color' => "#090",
				'hint' => '"2013-01-10 0:00:00" (polovin godina)',
				'url' => 'http://www.google.com/'
		),
		'2' => array (
				'taskid' => "Tsk53",
				'rowId' => 1,
				'duration' =>  7884000,
				'startTime' => dt::mysql2timestamp("2013-10-27 0:00:00"),
				'color' => "#00c",
				'hint' => '"2013-10-27 0:00:00" (chetvyrt  godina)',
				'url' => 'http://www.google.com/'
		),
		'3' => array (
				'taskid' => "Tsk11",
				'rowId' => 3,
				'duration' =>  3*31536000,
				'startTime' => dt::mysql2timestamp("2013-01-01 0:00:00"),
				'color' => "lime",
				'hint' => '"2012-01-01 0:00:00" (2godini)',
				'url' => 'http://www.google.com/'
		),
		'4' => array (
				'taskid' => "Tsk10",
				'rowId' => 1,
				'duration' =>  15768000,
				'startTime' => dt::mysql2timestamp("2014-10-27 0:00:00"),
				'color' => "magenta",
				'hint' => '"2014-10-27 0:00:00" (random)',
				'url' => 'http://www.google.com/'
		),
		'5' => array (
				'taskid' => "Tsk19",
				'rowId' => 4,
				'duration' =>  2678400,
				'startTime' => dt::mysql2timestamp("2013-04-01 0:00:00"),
				'color' => "pink",
				'hint' => '"2013-04-01 0:00:00" (mesec)',
				'url' => 'http://www.google.com/'
		)
);


?>