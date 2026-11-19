<?php
declare(strict_types=1);
function publicContacts():array{
 $c=cfg()['operator'];return ['support'=>$c['contact']?:'suporte@trokar.ia.br','general'=>$c['general_contact']??'contato@trokar.ia.br','security'=>$c['security_contact']??'seguranca@trokar.ia.br'];
}
function sponsorContent():array{return array_replace(['title'=>'Quer ser patrocinador do Trokar.ia?','text'=>'Conecte sua empresa à nossa comunidade. Conheça as oportunidades de patrocínio e publicidade e ajude a manter as trocas gratuitas para todos.','button'=>'Quero divulgar minha empresa'],cfg()['sponsorship']??[]);}
function sponsorMail():string{return 'mailto:'.publicContacts()['general'].'?subject='.rawurlencode('Patrocínio e publicidade no Trokar.ia');}
function renderSponsorCallout():void{
 $s=sponsorContent();echo '<section class="sponsor-callout" aria-labelledby="sponsor-heading"><div class="sponsor-symbol" aria-hidden="true">'.icon('spark').'</div><div class="sponsor-copy"><p class="eyebrow">PARA EMPRESAS · PATROCÍNIO E PUBLICIDADE</p><h2 id="sponsor-heading">'.e($s['title']).'</h2><p>'.e($s['text']).'</p></div><div class="sponsor-actions"><a class="button primary" href="'.e(sponsorMail()).'">'.e($s['button']).' '.icon('arrow').'</a><a class="text-button" href="'.e(url('sponsors')).'">Conhecer oportunidades</a></div></section>';
}
function renderSponsors():void{
 $s=sponsorContent();echo '<section class="reading-panel sponsors-page"><p class="eyebrow">SUA MARCA NO TROKAR.IA</p><h1>'.e($s['title']).'</h1><p>'.e($s['text']).'</p><div class="sponsor-options"><article class="panel"><h2>Patrocine a comunidade</h2><p>Associe sua marca a uma iniciativa que incentiva novas conexões e dá um novo ciclo aos produtos.</p></article><article class="panel"><h2>Divulgue sua empresa</h2><p>Converse com a equipe sobre espaços de publicidade para apresentar seus produtos e serviços.</p></article></div><p>Formatos, disponibilidade e valores são combinados diretamente com a equipe. As trocas entre usuários continuam gratuitas.</p><a class="button primary" href="'.e(sponsorMail()).'">'.e($s['button']).'</a><p>Prefere escrever diretamente? <a href="mailto:'.e(publicContacts()['general']).'">'.e(publicContacts()['general']).'</a></p></section>';
}
function renderContacts():void{
 $c=publicContacts();echo '<section class="reading-panel"><p class="eyebrow">FALE COM O TROKAR.IA</p><h1>Como podemos ajudar?</h1>';
 foreach(['support'=>['Suporte','Dúvidas sobre sua conta, anúncios ou funcionamento do site.'],'general'=>['Contato geral e parcerias','Patrocínio, publicidade e outros assuntos com a equipe.'],'security'=>['Denúncias e segurança','Avise sobre suspeitas de golpes, contas ou conteúdo inadequado.']] as $key=>$info)echo '<article class="panel"><h2>'.e($info[0]).'</h2><p>'.e($info[1]).'</p><a class="contact-email" href="mailto:'.e($c[$key]).'">'.e($c[$key]).'</a></article>';
 echo '<p>Para denunciar um anúncio ou uma mensagem dentro do site, use o botão Denunciar no próprio conteúdo.</p></section>';
}
