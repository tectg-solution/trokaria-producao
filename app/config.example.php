<?php
declare(strict_types=1);
return [
 'installed'=>false,'base_url'=>'https://trokar.ia.br','db'=>['driver'=>'mysql','host'=>'localhost','port'=>3306,'name'=>'','user'=>'','password'=>''],
 'install_token'=>getenv('TROKAR_INSTALL_TOKEN') ?: '','app_key'=>'',
 'ai'=>['enabled'=>false,'key'=>'','model'=>'gpt-4.1-mini','auto_publish'=>false],
 'mail'=>['transport'=>'mail','from'=>'nao-responda@trokar.ia.br','smtp_host'=>'','smtp_port'=>587,'smtp_user'=>'','smtp_password'=>'','smtp_security'=>'tls'],
 'donation'=>['text'=>'Entre em contato com a equipe para conhecer os pontos de coleta do Container Solidário.','contact'=>''],
 'operator'=>['name'=>'Trokar.ia','contact'=>'','privacy_contact'=>'']
];
