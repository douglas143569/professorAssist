<?php

use App\Core\Router;

/** @var Router $router */

// Autenticacao (unicas rotas abertas: todo o resto exige login)
$router->get('/login', 'AuthController@formulario');
$router->post('/login', 'AuthController@entrar');
$router->get('/cadastro', 'AuthController@formularioCadastro');
$router->post('/cadastro', 'AuthController@cadastrar');
$router->post('/logout', 'AuthController@sair');

$router->get('/', 'HomeController@index');

// Administracao de contas (so admin; a checagem esta no ContaController)
$router->get('/admin/contas', 'ContaController@index');
$router->post('/admin/contas', 'ContaController@criar');
$router->post('/admin/contas/{id}/ativo', 'ContaController@alternarAtivo');
$router->post('/admin/contas/{id}/papel', 'ContaController@alternarPapel');
$router->post('/admin/contas/{id}/senha', 'ContaController@trocarSenha');

// Escolas (topo) > Turmas > Materias (disciplinas) > Temas (modulos)
$router->get('/escolas', 'EscolaController@index');
$router->post('/escolas', 'EscolaController@store');
$router->get('/escolas/{id}', 'EscolaController@show');
$router->post('/escolas/{id}', 'EscolaController@update');
$router->post('/escolas/{id}/excluir', 'EscolaController@excluir');
$router->post('/escolas/{id}/turmas', 'TurmaController@store');

// Turmas (dentro de uma escola)
$router->get('/turmas', 'TurmaController@index');
$router->get('/turmas/{id}', 'TurmaController@show');
$router->post('/turmas/{id}/excluir', 'TurmaController@excluir');
$router->post('/turmas/{id}/materias', 'DisciplinaController@store');

// Materias (tabela disciplinas)
$router->get('/disciplinas', 'DisciplinaController@index'); // redireciona para /turmas
$router->get('/disciplinas/{id}', 'DisciplinaController@show');
$router->post('/disciplinas/{id}/excluir', 'DisciplinaController@excluir');

// Temas da aula (dentro de uma materia)
$router->post('/disciplinas/{id}/modulos', 'ModuloController@store');
$router->get('/modulos/{id}', 'ModuloController@show');
$router->get('/modulos/{id}/atividade', 'ModuloController@atividade');
$router->post('/modulos/{id}/conteudos/gerar', 'ModuloController@gerarConteudo');

// Conteudos
$router->get('/conteudos/{id}', 'ConteudoController@show');
$router->post('/conteudos/{id}', 'ConteudoController@update');
$router->post('/conteudos/{id}/aprovar', 'ConteudoController@aprovar');

// Creche (Educacao Infantil)
$router->get('/creche', 'CrecheController@index');
// Cronograma semanal (registrar antes das rotas /creche/atividades/{id})
$router->get('/creche/cronograma', 'CronogramaController@index');
$router->post('/creche/cronograma/criar', 'CronogramaController@criar');
$router->post('/creche/cronograma/limpar', 'CronogramaController@limpar');
$router->get('/creche/cronograma/{id}', 'CronogramaController@show');
$router->post('/creche/cronograma/{id}', 'CronogramaController@update');
$router->post('/creche/cronograma/{id}/excluir', 'CronogramaController@excluir');
// Pacotes de atividades
$router->get('/creche/pacotes', 'PacoteController@index');
$router->post('/creche/pacotes/criar', 'PacoteController@criar');
$router->get('/creche/pacote-itens/{id}', 'PacoteController@itemShow');
$router->post('/creche/pacote-itens/{id}', 'PacoteController@itemUpdate');
$router->post('/creche/pacote-itens/{id}/excluir', 'PacoteController@itemExcluir');
$router->get('/creche/pacotes/{id}', 'PacoteController@show');
$router->get('/creche/pacotes/{id}/imprimir', 'PacoteController@imprimir');
$router->post('/creche/pacotes/{id}/excluir', 'PacoteController@excluir');
$router->post('/creche/atividades/gerar', 'CrecheController@gerar');
$router->get('/creche/atividades/{id}', 'CrecheController@show');
$router->post('/creche/atividades/{id}', 'CrecheController@update');
$router->post('/creche/atividades/{id}/aprovar', 'CrecheController@aprovar');
$router->post('/creche/atividades/{id}/excluir', 'CrecheController@excluir');

// Calendario
$router->get('/calendario', 'CalendarioController@index');
$router->get('/calendario/eventos', 'CalendarioController@eventos');
$router->post('/calendario', 'CalendarioController@store');
$router->post('/eventos/{id}/concluir', 'CalendarioController@concluir');
$router->post('/eventos/{id}/excluir', 'CalendarioController@excluir');

// Atividades
$router->post('/modulos/{id}/atividades/gerar', 'AtividadeController@gerar');
$router->get('/atividades/{id}', 'AtividadeController@show');
$router->post('/atividades/{id}', 'AtividadeController@update');
$router->post('/atividades/{id}/aprovar', 'AtividadeController@aprovar');
$router->post('/atividades/{id}/excluir', 'AtividadeController@excluir');

// Planos de aula
$router->post('/modulos/{id}/planos/gerar', 'PlanoAulaController@gerar');
$router->get('/planos/{id}', 'PlanoAulaController@show');
$router->post('/planos/{id}', 'PlanoAulaController@update');
$router->post('/planos/{id}/aprovar', 'PlanoAulaController@aprovar');
$router->post('/planos/{id}/excluir', 'PlanoAulaController@excluir');

// Provas (RF-15 a RF-18)
$router->get('/provas', 'ProvaController@index');
$router->post('/provas', 'ProvaController@store');
$router->get('/provas/{id}', 'ProvaController@show');
$router->post('/provas/{id}', 'ProvaController@update');
$router->get('/provas/{id}/imprimir', 'ProvaController@imprimir');
$router->get('/provas/{id}/cartao', 'ProvaController@cartaoResposta');
$router->post('/provas/{id}/versoes', 'ProvaController@gerarVersoes');
$router->post('/provas/{id}/questoes', 'ProvaController@adicionarQuestao');
$router->post('/provas/{id}/questoes/{questaoId}/remover', 'ProvaController@removerQuestao');
$router->post('/provas/{id}/excluir', 'ProvaController@excluir');

// Banco de questoes
$router->get('/disciplinas/{id}/questoes', 'QuestaoController@banco');
$router->post('/modulos/{id}/questoes/gerar', 'QuestaoController@gerar');
$router->get('/modulos/{id}/questoes/nova', 'QuestaoController@novaForm');
$router->post('/modulos/{id}/questoes', 'QuestaoController@store');
$router->get('/questoes/{id}', 'QuestaoController@show');
$router->post('/questoes/{id}', 'QuestaoController@update');
$router->post('/questoes/{id}/aprovar', 'QuestaoController@aprovar');
$router->post('/questoes/{id}/excluir', 'QuestaoController@excluir');
