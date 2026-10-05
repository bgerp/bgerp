<?php
// Isolated real HTTP responses from core_App; no database or application bootstrap.
if (PHP_SAPI !== 'cli') exit('CLI only');
$socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
if(!$socket)throw new RuntimeException($error);
$address=stream_socket_get_name($socket,false);fclose($socket);
$log=tempnam(sys_get_temp_dir(),'redirect_http_');
$server=proc_open(array(PHP_BINARY,'-d','display_errors=0','-S',$address,__DIR__.'/redirect.php'),array(0=>array('pipe','r'),1=>array('file',$log,'a'),2=>array('file',$log,'a')),$pipes);
$checks=0;
function httpCheck($ok,$msg){global $checks;if(!$ok)throw new RuntimeException($msg);$checks++;}
try{
    for($i=0;$i<100;$i++){
        $connection=@stream_socket_client('tcp://'.$address,$errno,$error,0.05);
        if($connection){fclose($connection);break;}usleep(20000);
    }
    foreach(array(''=>302,'?permanent=1'=>301,'?template=1'=>302,'?ajax=1'=>200,'?ajax=1&template=1'=>200) as $query=>$status){
        $context=stream_context_create(array('http'=>array('follow_location'=>0,'ignore_errors'=>true)));
        $body=file_get_contents('http://'.$address.'/'.$query,false,$context);
        httpCheck(strpos($http_response_header[0],(string)$status)!==false,'Response status '.$query.': '.implode(';',$http_response_header));
        $headers=implode("\n",$http_response_header);
        httpCheck(strpos($headers,'X-Test-Status: notice:7:http-hit')!==false,'Status message emitted for original recipient');
        if(strpos($query,'ajax')!==false){
            $json=json_decode($body,true);
            httpCheck(is_array($json) && count($json)===1 && $json[0]['func']==='redirect' && $json[0]['arg']['url']==='/destination?hit_id=http-hit','Clean AJAX redirect JSON: '.$body);
            httpCheck(stripos($headers,'Content-Type: application/json')!==false && stripos($headers,'Location:')===false,'AJAX uses JSON, without Location');
        }else{
            httpCheck(strpos($headers,'Location: /destination?hit_id=http-hit')!==false,'Location header preserved');
        }
    }
    echo "OK: {$checks} HTTP redirect checks.\n";
}finally{
    proc_terminate($server);fclose($pipes[0]);proc_close($server);unlink($log);
}
