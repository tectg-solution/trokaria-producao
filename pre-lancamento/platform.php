<?php
declare(strict_types=1);
$prelaunchConfig=require __DIR__.'/config.php';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!in_array($_POST['action']??'', ['prelaunch_interest','prelaunch_retry'],true)){http_response_code(400);exit('Solicitação inválida.');}
if($prelaunchConfig['site']==='medmor')require_once dirname(__DIR__).'/lib/bootstrap.php';else require_once dirname(__DIR__).'/app/bootstrap.php';
require_once __DIR__.'/core.php';
function pl_config():array{return $GLOBALS['prelaunchConfig'];}
function pl_db():PDO{return pl_config()['site']==='medmor'?mm_pdo():db();}
function pl_csrf():string{return pl_config()['site']==='medmor'?mm_csrf():csrf();}
function pl_check_csrf():void{$a=$_POST['csrf']??'';if(!is_string($a)||!hash_equals(pl_csrf(),$a))throw new InvalidArgumentException('Sua sessão expirou. Recarregue a página e tente novamente.');}
function pl_send(string $to,string $subject,string $body):bool{return pl_config()['site']==='medmor'?mm_mail($to,$subject,nl2br(pl_h($body))):sendMail($to,$subject,$body);}
function pl_require_admin():void{
 if(pl_config()['site']!=='medmor'){$user=currentUser();if(!$user){header('Location: /admin',true,303);exit;}if(($user['role']??'')!=='ADMIN'){http_response_code(403);exit('Acesso exclusivo da administração.');}return;}$s=$_SESSION['mm_admin']??null;
 if(is_array($s)&&!empty($s['id'])&&is_string($s['email']??null))foreach(['admins','administradores_sistema'] as $table){try{$r=pl_query(pl_db(),"SELECT status FROM $table WHERE id=? AND email=?",[(int)$s['id'],$s['email']])->fetchColumn();if(strtoupper((string)$r)==='ATIVO')return;}catch(PDOException){}}
 header('Location: /admin-medmor/',true,303);exit;
}
