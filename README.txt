v0.9.2
@implemented
- funcionalidade para deletar do servidor os arquivos associados ao exame que o usuário deleta;
- link na opção de menu "cadastrar exame" removido, pois não levaria a lugar nenhum;
- funcionalidade para verificar se matrícula existe antes de cadastrar novo paciente;
- validação dos campos em branco caso obrigatórios;
- pelo menos 1 dos 3 campos relacionados ao exame (obs_medico, obs_paciente, arquivos) tem que ser preenchido;
- todos os campos do formulário serão desabilitados após envio;
- matrícula agora aparece na selectbox;
- datas exibidas em formato brasileiro;
- menu reorganizado e adicionada a opção 'pacientes cadastrados';
- quando tenta-se enviar arquivo maior que 10mb, agora é gerada mensagem de erro;
- agora é possível enviar png's e bmp's também.
@toImplement
- reorganizar name, class e id dos campos nos formulários e nas mensagens de erro;
- colocar títulos nas páginas;
- TinyMCE para campos text;
- filtrar com strip_tags() as tags HTML permitidas nos campos text;
- funcionalidade 'pacientes cadastrados';
- mostrar thumbnail dos arquivos na edição de exame;
- melhorar javascript do envio de arquivos: se não houver arquivo selecionado, não pode ser possível adicionar mais um campo de envio.
@fixed
- ao gerar novas senhas, as novas senhas mostradas para o administrador não batiam com as novas gravadas no banco. As senhas mostradas eram as antigas. Consertado o erro de lógica.
- alguns símbolos que deveriam ser removidos eram ignorados na limpeza do nome do arquivo pela função wp_sanitize_filename()
. Na verdade o problema era com nome de arquivos contendo acentos. Consertado usando remove_accents().
- alfabeto latino não passava na validação dos campos (pesquisar regex apropriada). Corrigido usando \u para aceitar caracteres unicode e \w para funcionar no windows... provavelmente terá de ser adaptado novamente para o servidor de produção linux
- dialog box sem estilo. Consertado apontando para o CDN do google. Posteriormente no servidor de produção, será apontado para o .css do plugin, que será baseado nos estilos do site do cliente.
- estava sendo possível enviar formulário do exame sem selecionar um paciente
@toFix

v0.9.1

- implementada (mas não testada) funcionalidade de mostrar arquivos associados ao exame (se existirem) na tela de edição, junto com opção para deletá-los

- código melhor comentado

@knownbugs

- alfabeto latino não passa na validação dos campos (pesquisar regex apropriada)

- dialog box sem estilo (pesquisar como carregar estilo para script jquery)

- alguns símbolos que deveriam ser removidos são ignorados na limpeza do nome do arquivo pela função wp_sanitize_filename()

- ao gerar novas senhas, as novas senhas mostradas para o administrador não batem com as novas gravadas no banco (erro de lógica, provavelmente está mostrando as senhas antigas)

@todo

- deletar do servidor os arquivos associados ao exame que o usuário deleta



v0.9.0 ********** versão final de testes do plugin ***********

- interface de usuário agora funcional

- o plugin passa a depender de outros 2 plugins para melhorar a experiência do usuário: hana-flv-player(videos) e wp_video_lightbox(imagens).

- corrigido charset do eclipse... acentos devolvidos para as palavras

@knownbugs

- alfabeto latino não passa na validação dos campos (pesquisar regex apropriada)

- dialog box sem estilo (pesquisar como carregar estilo para script jquery)

- alguns símbolos que deveriam ser removidos são ignorados na limpeza do nome do arquivo pela função wp_sanitize_filename()

@todo

- deletar do servidor os arquivos associados ao exame que o usuário deleta

- mostrar arquivos associados ao exame (se existirem) na tela de edição do exame, junto com opção para deletá-los



v0.3.0

- mensagens de erro de input definidas como constantes para facilitar customização posterior

- todo o painel de controle do administrador está agora funcional

- parte do 'cadastrar exame' teve que ser reescrita para implementar a validação de todos os input's

- criada função getForm() para gerar markup do formulário e seus erros de input sem reescrever código
- botão submit dos formulários enviados em 'cadastrar exame' agora é desabilitado quando exame é cadastrado com sucesso, para evitar reenvio acidental pelo usuário

- validação dos input's estão agora funcionando propriamente

- iniciada a implementação da interface de usuário
@bugfixes

- corrigido um bug onde pacientes eram cadastrados mesmo quando o formulário apresentava erros

- corrigido um bug onde paciente pré-selecionado era cadastrado novamente ao cadastrar exame

- corrigidos diversos erros de lógica que surgiram após a implementação de validações
@knownbugs

- alfabeto latino não passa na validação dos campos (pesquisar regex apropriada)

- dialog box sem estilo (pesquisar como carregar estilo para script jquery)

@todo

- deletar do servidor os arquivos associados ao exame que o usuário deleta

- mostrar arquivos associados ao exame (se existirem) na tela de edição do exame, junto com opção para deletá-los

- finalizar interface do usuário com plugin de terceiros para exibição de vídeo



v.0.2.0

- query strings reorganizadas

- configuração de arquivos permitidos e tamanho máximo de upload por arquivo agora definidas por constantes

- 'cadastrar exame' está agora funcional

- 'alterar senhas' está agora funcional

- 'editar exame' está funcional até a parte de exibir os exames por usuário

- criada função 'getDropdownList()' para gerar markups para a lista dropdown e populá-la a partir do banco



v.0.1.5

- havia esquecido do README!



v.0.1.4

- Cadastrar exame

-- função de envio de arquivos

-- formulários do painel de admin

-- criada massa de testes

-- conexão ao banco para recuperar pacientes e mostrar na lista dropdown


@todo 

- Cadastrar exame

-- organizar construção de formulários

-- organizar query strings

