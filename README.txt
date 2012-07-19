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

