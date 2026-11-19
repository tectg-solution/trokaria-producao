<?php
declare(strict_types=1);
function installationDatabaseError(PDOException $error,string $stage):string{
 $code=(int)($error->errorInfo[1]??0);
 $state=(string)($error->errorInfo[0]??$error->getCode());
 $state=preg_match('/^[A-Z0-9]{5}$/',$state)?$state:'HY000';
 $detail=match($code){
  1045=>'O MySQL recusou o usuário ou a senha. Copie o usuário completo do banco, incluindo o prefixo, e use a senha criada para esse usuário — não a senha do painel da Hostinger.',
  1049=>'O nome do banco não foi encontrado. Copie o nome completo exibido na hospedagem, incluindo o prefixo.',
  1044,1142,1143,1227=>'O usuário não tem as permissões necessárias nesse banco. Confira se o usuário informado está vinculado ao banco do Trokar.ia e pode criar tabelas e inserir registros.',
  2002,2003,2005=>'Não foi possível conectar ao servidor MySQL. Use o host indicado pela hospedagem e confira se o serviço está disponível.',
  2006,2013=>'A conexão com o MySQL foi interrompida. Confira a disponibilidade do banco na hospedagem antes de tentar novamente.',
  1064,1071,1005,1215,3780=>'O banco recusou a estrutura das tabelas. Informe ao suporte o código abaixo e a versão do MySQL/MariaDB mostrada na hospedagem.',
  1062=>'Já existe um registro com estes dados. Não exclua tabelas de uma instalação em uso; confira se este é um banco novo e exclusivo do Trokar.ia.',
  default=>'O MySQL recusou uma operação. Informe ao suporte o código abaixo; ele identifica a etapa da instalação sem mostrar sua senha.'
 };
 return $detail.' Código: INST-'.$stage.'-'.$code.' / '.$state.'. Se a falha ocorreu depois de criar tabelas, não apague dados: confira no painel se este banco contém somente esta instalação nova.';
}
