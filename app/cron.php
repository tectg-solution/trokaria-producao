<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';
if(!cfg()['installed']){fwrite(STDERR,"Instalação incompleta.\n");exit(1);}
try{
 echo processJobs(2)." análise(s) processada(s).\n";
 sql('DELETE FROM limits_log WHERE created_at<?',[gmdate('Y-m-d H:i:s',time()-86400*2)]);
 foreach(['recovery_tokens','verification_tokens'] as $table)sql("DELETE FROM $table WHERE expires_at<?",[gmdate('Y-m-d H:i:s',time()-86400)]);
 $directory=__DIR__.'/storage/photos';
 foreach(glob($directory.'/*.jpg')?:[] as $file){
  $name=basename($file);
  if(!preg_match('/^[a-f0-9]{32}\.jpg$/',$name)||is_link($file)||filemtime($file)>time()-86400*7)continue;
  if(!sql('SELECT id FROM photos WHERE filename=?',[$name])->fetchColumn())unlink($file);
 }
}catch(Throwable $e){error_log('Trokar cron: '.$e->getMessage());fwrite(STDERR,"Falha. Consulte o registro de erros.\n");exit(1);}
