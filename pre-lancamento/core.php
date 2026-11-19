<?php
declare(strict_types=1);
function pl_h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function pl_table(string $site,string $kind='leads'):string{if(!in_array($site,['medmor','trokar'],true)||!in_array($kind,['leads','limits'],true))throw new RuntimeException('Configuração inválida.');return 'prelaunch_'.$site.'_'.$kind;}
function pl_query(PDO $db,string $sql,array $params=[]):PDOStatement{$s=$db->prepare($sql);$s->execute($params);return $s;}
function pl_schema(PDO $db,string $site):void{
 $auto=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';$suffix=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci':'';$t=pl_table($site);$r=pl_table($site,'limits');
 $db->exec("CREATE TABLE IF NOT EXISTS $t(id $auto,email_hash CHAR(64) NOT NULL UNIQUE,payload TEXT NOT NULL,consented_at VARCHAR(30) NOT NULL,consent_version VARCHAR(30) NOT NULL,created_at VARCHAR(30) NOT NULL,notify_attempts INT NOT NULL DEFAULT 0,notify_claim_until VARCHAR(30) NULL,notified_at VARCHAR(30) NULL,notify_error VARCHAR(120) NULL)".$suffix);
 $db->exec("CREATE TABLE IF NOT EXISTS $r(actor_hash CHAR(64) PRIMARY KEY,bucket BIGINT NOT NULL,attempts INT NOT NULL)".$suffix);
}
function pl_key(string $site,string $root):string{
 pl_table($site);$root=realpath($root)?:throw new RuntimeException('Raiz indisponível.');$cursor=$root;$base=null;
 while(dirname($cursor)!==$cursor){if(basename($cursor)==='domains'){$base=dirname($cursor);break;}$cursor=dirname($cursor);}
 $base??=dirname($root);$dir=$base.'/private_data';if(is_link($dir))throw new RuntimeException('Pasta privada inválida.');if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Não foi possível preparar o armazenamento privado.');
 $real=realpath($dir);if(!$real||$real===$root||str_starts_with($real,$root.DIRECTORY_SEPARATOR))throw new RuntimeException('O armazenamento deve ficar fora da pasta pública.');$path=$dir.'/prelaunch-'.$site.'-20261103.key';if(is_link($path))throw new RuntimeException('Chave privada inválida.');
 if(!is_file($path)){$f=@fopen($path,'x+b');if($f){try{$new=random_bytes(32);if(fwrite($f,$new)!==32)throw new RuntimeException('Não foi possível gravar a chave.');fflush($f);@chmod($path,0600);}finally{fclose($f);}}}
 $key=@file_get_contents($path);if(!is_string($key)||strlen($key)!==32)throw new RuntimeException('Chave privada indisponível.');if(!function_exists('openssl_encrypt'))throw new RuntimeException('Criptografia indisponível.');return $key;
}
function pl_encrypt(array $data,string $key):string{$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'prelaunch-v1',16);if($cipher===false)throw new RuntimeException('Falha ao proteger o contato.');return base64_encode($iv.$tag.$cipher);}
function pl_decrypt(string $payload,string $key):array{$raw=base64_decode($payload,true);if($raw===false||strlen($raw)<29)throw new RuntimeException('Contato indisponível.');$plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'prelaunch-v1');if($plain===false)throw new RuntimeException('Não foi possível abrir o contato.');return json_decode($plain,true,16,JSON_THROW_ON_ERROR);}
function pl_validate(array $input,string $site):array{
 $get=static function(string $name)use($input):string{$v=$input[$name]??'';return is_string($v)?trim($v):'';};$name=$get('nome');$email=strtolower($get('email'));$phone=$get('telefone');$area=$site==='medmor'?$get('area'):'';
 if(strlen($name)<2||strlen($name)>180||preg_match('/[\x00-\x1f]/',$name))throw new InvalidArgumentException('Informe seu nome, com até 180 caracteres.');
 if(strlen($email)>190||!filter_var($email,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$email))throw new InvalidArgumentException('Informe um e-mail válido.');$digits=preg_replace('/\D/','',$phone);
 if(($site==='medmor'&&$digits==='')||($phone!==''&&(strlen($digits)<10||strlen($digits)>15)))throw new InvalidArgumentException('Informe um telefone com DDD válido.');if(strlen($phone)>40)throw new InvalidArgumentException('Confira o telefone informado.');
 if($site==='medmor'&&(strlen($area)<2||strlen($area)>120||preg_match('/[\x00-\x1f]/',$area)))throw new InvalidArgumentException('Informe sua área de atuação.');
 if(($input['consentimento']??'')!=='1')throw new InvalidArgumentException('Marque a opção para receber nosso contato e o aviso de lançamento.');return ['nome'=>$name,'email'=>$email,'telefone'=>$phone,'area'=>$area];
}
function pl_rate(PDO $db,string $site,string $actor,string $key,int $now):void{
 $table=pl_table($site,'limits');$hash=hash_hmac('sha256',$site.'|'.$actor,$key);$bucket=(int)floor($now/3600);$mysql=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
 if($mysql)$sql="INSERT INTO $table(actor_hash,bucket,attempts) VALUES(?,?,1) ON DUPLICATE KEY UPDATE attempts=IF(bucket=VALUES(bucket),attempts+1,1),bucket=VALUES(bucket)";
 else $sql="INSERT INTO $table(actor_hash,bucket,attempts) VALUES(?,?,1) ON CONFLICT(actor_hash) DO UPDATE SET attempts=CASE WHEN bucket=excluded.bucket THEN attempts+1 ELSE 1 END,bucket=excluded.bucket";
 pl_query($db,$sql,[$hash,$bucket]);$count=(int)pl_query($db,"SELECT attempts FROM $table WHERE actor_hash=?",[$hash])->fetchColumn();if($count>30)throw new RuntimeException('Muitas tentativas. Aguarde um pouco antes de tentar novamente.');
}
function pl_store(PDO $db,string $site,array $data,string $key,int $now):array{
 $table=pl_table($site);$hash=hash_hmac('sha256',strtolower($data['email']),$key);$at=gmdate('Y-m-d H:i:s',$now);$sql=($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE')." INTO $table(email_hash,payload,consented_at,consent_version,created_at) VALUES(?,?,?,?,?)";
 $q=pl_query($db,$sql,[$hash,pl_encrypt($data,$key),$at,'launch-contact-v1',$at]);$new=$q->rowCount()===1;$id=(int)pl_query($db,"SELECT id FROM $table WHERE email_hash=?",[$hash])->fetchColumn();if($id<1)throw new RuntimeException('Não foi possível confirmar seu interesse.');return ['id'=>$id,'new'=>$new];
}
function pl_notice(PDO $db,string $site,int $id,string $key,string $recipient,string $brand,callable $send,int $now):bool{
 if(!filter_var($recipient,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$recipient))throw new RuntimeException('Destino dos avisos inválido.');$t=pl_table($site);$at=gmdate('Y-m-d H:i:s',$now);$claim=gmdate('Y-m-d H:i:s',$now+120);
 $q=pl_query($db,"UPDATE $t SET notify_claim_until=?,notify_attempts=notify_attempts+1 WHERE id=? AND notified_at IS NULL AND (notify_claim_until IS NULL OR notify_claim_until<?)",[$claim,$id,$at]);if($q->rowCount()!==1)return false;
 try{$row=pl_query($db,"SELECT payload,created_at FROM $t WHERE id=?",[$id])->fetch(PDO::FETCH_ASSOC);if(!$row)throw new RuntimeException('Contato indisponível.');$d=pl_decrypt($row['payload'],$key);$body="Novo interesse no lançamento do $brand\n\nNome: ".$d['nome']."\nE-mail: ".$d['email']."\nTelefone: ".($d['telefone']?:'Não informado').($site==='medmor'?"\nÁrea de atuação: ".$d['area']:'')."\n\nA pessoa autorizou o contato da equipe e o aviso de lançamento.\nCadastro #$id em ".$row['created_at']." UTC.\nLista completa no painel administrativo > Interessados no lançamento.";$sent=$send($recipient,'Novo interessado no '.$brand,$body)===true;
  pl_query($db,"UPDATE $t SET notify_claim_until=NULL,notified_at=?,notify_error=? WHERE id=?",[$sent?$at:null,$sent?null:'Envio não aceito pelo serviço de e-mail.',$id]);return $sent;
 }catch(Throwable $e){pl_query($db,"UPDATE $t SET notify_claim_until=NULL,notify_error=? WHERE id=?",['Aviso pendente. Reenvie pelo painel.',$id]);error_log('Prelaunch notification '.get_class($e));return false;}
}
function pl_csv_cell(mixed $value):string{$s=(string)$value;return preg_match('/^[=+@\-\t\r]/',$s)?"'".$s:$s;}
