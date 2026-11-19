# Trokar.ia — plataforma PHP / MySQL

Este pacote substitui o protótipo por contas, anúncios e conversas reais. Foi preparado para o domínio `https://trokar.ia.br`, em uma instalação separada do Clube do Rei e do Medmor. Não importe bancos desses outros sites.

## Instalação na Hostinger

1. No domínio **trokar.ia.br**, ative o SSL e selecione PHP 8.2 ou superior. As extensões `pdo_mysql`, `mbstring`, `fileinfo`, `gd` e `curl` devem estar habilitadas.
2. Crie um banco MySQL **novo e exclusivo** para o Trokar.ia. Guarde o nome completo do banco, usuário, senha e host apresentados pela hospedagem.
3. Faça uma cópia dos arquivos que já estão no domínio. Extraia o ZIP diretamente em `public_html`: `index.php`, `install.php`, `.htaccess`, `app` e `assets` devem estar na raiz. Inclua os arquivos ocultos `.htaccess`. Se a versão anterior tiver um `index.html`, substitua-o pelo redirecionamento deste pacote.
4. Abra `https://trokar.ia.br/install.php`. Defina uma chave aleatória e longa em `TROKAR_INSTALL_TOKEN` no ambiente do PHP e use essa chave no formulário. Preencha os dados do banco, o responsável pela plataforma e a conta de administrador. Não existe senha padrão. Use uma senha de 12 a 72 caracteres.
5. O instalador cria as tabelas automaticamente, grava a configuração privada e desativa novas instalações. Não é necessário importar SQL. Ele recusa um banco que já contém a tabela `users`; uma tentativa parcialmente concluída deve ser refeita com um banco vazio exclusivo.
6. Entre na conta que acabou de criar. Em **Minha conta → Administração → Configurações**, configure o e-mail e a IA.

O PHP precisa poder gravar `app/config.php` e `app/storage/photos`. Não use permissões públicas de gravação como solução permanente. A pasta `app` deve continuar protegida pelo seu `.htaccess`.

## E-mails e confirmação de cadastro

Configure SMTP usando o servidor, usuário e senha do seu serviço de e-mail. O remetente deve ser um endereço autorizado por esse serviço. Use porta 587 com STARTTLS ou 465 com SSL; o sistema escolhe a segurança pela porta. Senhas e chaves salvas não aparecem no formulário nem nos registros administrativos.

Salve e use **Enviar e-mail de teste**. Confirme que o teste chega, inclusive na pasta de spam. Depois teste cadastro, confirmação e recuperação de senha com uma conta comum. A confirmação do e-mail é obrigatória para anunciar e enviar propostas/mensagens. Sem entrega de e-mails, a conta é criada, mas essas ações ficam aguardando confirmação. O suporte pode confirmar o e-mail após conferir a identidade do titular ou gerar um link temporário de recuperação pelo admin.

## Inteligência artificial e revisão humana

No admin, informe uma chave da OpenAI, habilite a integração e salve. O modelo de interpretação padrão é `gpt-4.1-mini`; o nome é configurável. A moderação usa `omni-moderation-latest`. A chave é usada somente no servidor.

Há duas análises dos anúncios: moderação de texto/fotos e interpretação contextual do objeto e dos interesses para os matches. Acessórios para pets são permitidos; animais vivos não fazem parte das trocas. Mensagens e propostas também passam por moderação de texto.

**A publicação automática vem desligada.** Anúncios ficam em revisão até decisão da equipe. Se ativada, só uma análise favorável pode publicar automaticamente. Conteúdo sinalizado ou ambíguo vai para revisão humana. Sem chave, com serviço indisponível ou com erro na análise, o conteúdo continua aguardando revisão. Mensagens pendentes ficam visíveis ao remetente e à moderação; não aparecem para o destinatário antes da liberação.

Configure uma tarefa cron para executar `app/cron.php` a cada cinco minutos. Use o PHP CLI da hospedagem e o caminho absoluto do arquivo no domínio. Exemplo de caminho — substitua pelo caminho real mostrado no painel:

```text
php /home/SEU_USUARIO/domains/trokar.ia.br/public_html/app/cron.php
```

O script é exclusivo da linha de comando; não abre uma URL pública. Cada execução processa até dois anúncios, com até três tentativas por análise, e limpa tokens antigos e fotos órfãs. Também há **Processar próxima análise** no admin para processar uma análise manualmente. Conteúdo que precisa de revisão humana continua na fila administrativa.

O uso é gratuito para os usuários. A hospedagem, e-mail e eventual consumo da API são custos de operação do responsável pelo site. Nenhuma credencial desses serviços está incluída no ZIP.

Documentação da integração:

- https://developers.openai.com/api/docs/guides/moderation
- https://developers.openai.com/api/docs/guides/structured-outputs

## O que está incluído

- Página inicial preta e verde, logo arredondada em SVG, layout adaptável a computador e celular.
- Cadastro, login, confirmação por e-mail, recuperação de senha, perfil, exportação dos próprios dados e encerramento de conta.
- Anúncios com até quatro fotos, categoria, condição, cidade/estado e interesses de troca. Fotos JPG, PNG ou WebP, até 5 MB cada, são reprocessadas para JPEG e têm metadados removidos.
- Busca, categorias, filtros por cidades cadastradas nos anúncios publicados e favoritos.
- Matches recíprocos e aviso de novos matches na área da conta. Há comparação por termos/sinônimos quando a IA não está configurada.
- Propostas com ou sem anúncio próprio, conversas privadas, aceite/recusa e conclusão somente após confirmação dos dois participantes. Um item vinculado não pode ter duas propostas aceitas simultaneamente.
- Edição, pausa, exclusão e indicação de item trocado. Alterações de anúncio retornam à moderação.
- Admin para anúncios, mensagens, usuários, denúncias, contestações, configuração da IA/e-mail e registro das ações administrativas.
- Página de doações com texto e contato configuráveis, regras e informações de privacidade com os contatos definidos na instalação.

Os anúncios da versão ilustrativa anterior não são importados nem apresentados como anúncios reais. A vitrine começa vazia e passa a mostrar o que for cadastrado e aprovado. O menu e os botões agora abrem páginas reais; o site não usa o armazenamento local do navegador como banco de dados.

## Antes de divulgar o endereço

Faça uma troca de teste com duas contas comuns: cadastre, confirme e-mails, publique fotos, aprove no admin, encontre os anúncios, envie uma proposta, converse e confirme a troca nas duas contas. Teste também a recuperação de senha e o acesso pelo celular. Confira que `/app/config.php` e `/app/storage/` não podem ser acessados pelo navegador.

Preencha os contatos do responsável e do Container Solidário. Defina os procedimentos da equipe para suporte, denúncias e retenção de registros. A página de privacidade descreve o funcionamento implementado; revise-a para refletir a operação que você efetivamente adotar.

`robots.txt` bloqueia a indexação durante a instalação e os testes. Após validar o site, remova a linha `Disallow: /` para permitir indexação das páginas públicas. Páginas privadas continuam exigindo autenticação.

## Atualizações e backup

Guarde backups do banco, de `app/config.php` e de `app/storage/photos`. Nas próximas atualizações, preserve esses dois caminhos: eles contêm a configuração e as fotos reais. O ZIP de instalação não contém `app/config.php`, contas de teste, bancos SQLite, fotos de teste nem chaves de API.

Não restaure um backup de outro projeto neste banco. Não execute o instalador novamente sobre uma instalação em uso. O pacote atual é uma instalação inicial; mudanças futuras no esquema devem vir acompanhadas de migração própria.

## Validação desta entrega

Foram executados testes locais de fluxos HTTP com PHP e SQLite para cadastro, confirmação, upload, permissões, favoritos, propostas, conversas, moderação humana, recuperação, suspensão, exportação e encerramento. Testes com respostas simuladas verificaram a integração de IA, análise de fotos, revisão humana, falhas, limites de tentativas, proteção de decisões humanas e matches recíprocos. PHP, JavaScript e CSS foram verificados sintaticamente.

O banco MySQL da Hostinger, a entrega real de e-mails, chamadas reais à OpenAI e a aparência no navegador da hospedagem dependem da sua configuração e precisam ser validados após a instalação. Os testes simulados não são uma confirmação desses serviços externos.
