<?php
declare(strict_types=1);
require __DIR__.'/platform.php';header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');
$c=pl_config();$values=['nome'=>'','email'=>'','telefone'=>'','area'=>''];$error='';$success=(bool)($_SESSION['prelaunch_success_'.$c['site']]??false);unset($_SESSION['prelaunch_success_'.$c['site']]);
$ready=true;try{pl_schema(pl_db(),$c['site']);pl_key($c['site'],dirname(__DIR__));}catch(Throwable $e){$ready=false;http_response_code(503);error_log('Prelaunch readiness '.get_class($e));$error='O cadastro de interesse estará disponível em instantes. Por favor, tente novamente.';}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 foreach($values as $field=>$value)if(is_string($_POST[$field]??null))$values[$field]=substr($_POST[$field],0,200);
 try{pl_check_csrf();if(is_string($_POST['website']??null)&&$_POST['website']!==''){$saved=['new'=>false];}else{$data=pl_validate($_POST,$c['site']);$db=pl_db();pl_schema($db,$c['site']);$key=pl_key($c['site'],dirname(__DIR__));pl_rate($db,$c['site'],(string)($_SERVER['REMOTE_ADDR']??'unknown'),$key,time());$saved=pl_store($db,$c['site'],$data,$key,time());if($saved['new'])pl_notice($db,$c['site'],$saved['id'],$key,$c['notify_to'],$c['brand'],'pl_send',time());}
  $_SESSION['prelaunch_success_'.$c['site']]=true;header('Location: /?interesse=registrado#cadastro',true,303);exit;
 }catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}catch(Throwable $e){http_response_code(503);error_log('Prelaunch signup '.get_class($e));$error='Não foi possível registrar agora. Tente novamente em instantes.';}
}
require __DIR__.'/view.php';
