<?php

use App\Core\Router;

/** @var Router $router */

$router->get('/', 'HomeController@index');

// Disciplinas
$router->get('/disciplinas', 'DisciplinaController@index');
$router->post('/disciplinas', 'DisciplinaController@store');
$router->get('/disciplinas/{id}', 'DisciplinaController@show');

// Modulos (dentro de uma disciplina)
$router->post('/disciplinas/{id}/modulos', 'ModuloController@store');
$router->get('/modulos/{id}', 'ModuloController@show');
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
$router->post('/creche/pacotes/{id}/excluir', 'PacoteController@excluir');
$router->post('/creche/atividades/gerar', 'CrecheController@gerar');
$router->get('/creche/atividades/{id}', 'CrecheController@show');
$router->post('/creche/atividades/{id}', 'CrecheController@update');
$router->post('/creche/atividades/{id}/aprovar', 'CrecheController@aprovar');
$router->post('/creche/atividades/{id}/excluir', 'CrecheController@excluir');

// Calendario
$router->get('/calendario', 'CalendarioController@index');
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

// Banco de questoes
$router->get('/disciplinas/{id}/questoes', 'QuestaoController@banco');
$router->post('/modulos/{id}/questoes/gerar', 'QuestaoController@gerar');
$router->get('/modulos/{id}/questoes/nova', 'QuestaoController@novaForm');
$router->post('/modulos/{id}/questoes', 'QuestaoController@store');
$router->get('/questoes/{id}', 'QuestaoController@show');
$router->post('/questoes/{id}', 'QuestaoController@update');
$router->post('/questoes/{id}/aprovar', 'QuestaoController@aprovar');
$router->post('/questoes/{id}/excluir', 'QuestaoController@excluir');
