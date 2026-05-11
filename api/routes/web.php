<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$router->get('/', function () use ($router) {
    return response()->json([
        'name' => 'API Lizzie',
        'version' => '1.0.0',
        'status' => 'running'
    ]);
});

// Rotas públicas - Auth (com CORS)
$router->group(['prefix' => 'api', 'middleware' => 'cors'], function () use ($router) {
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/refresh', 'AuthController@refresh');
    $router->get('/auth/me', 'AuthController@me');
    $router->put('/auth/profile', 'AuthController@updateProfile');

    // Busca global
    $router->get('/busca', 'BuscaController@index');

    // SSE - Tempo Real (sem JWT pois browsers não enviam headers em EventSource)
    $router->get('/alertas/stream', ['middleware' => 'cors', 'uses' => 'AlertaController@stream']);

    // Alertas
    $router->get('/alertas', 'AlertaController@index');
    $router->get('/alertas/nao-lidos', 'AlertaController@naoLidos');
    $router->put('/alertas/{id}/ler', 'AlertaController@ler');
});

// Rotas protegidas - Requerem JWT (com CORS)
$router->group(['prefix' => 'api', 'middleware' => ['cors', 'jwt']], function () use ($router) {

    // Dashboard
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/dashboard/estatisticas', 'DashboardController@estatisticas');

    // Usuários (Gerenciamento)
    $router->get('/usuarios', 'AuthController@index');
    $router->post('/usuarios', 'AuthController@store');
    $router->put('/usuarios/{id}', 'AuthController@update');
    $router->delete('/usuarios/{id}', 'AuthController@destroy');

    // Clientes
    $router->get('/clientes', 'ClienteController@index');
    $router->get('/clientes/{id}', 'ClienteController@show');
    $router->post('/clientes', 'ClienteController@store');
    $router->put('/clientes/{id}', 'ClienteController@update');
    $router->delete('/clientes/{id}', 'ClienteController@destroy');

    // Produtos
    $router->get('/produtos', 'ProdutoController@index');
    $router->get('/produtos/{id}', 'ProdutoController@show');
    $router->post('/produtos', 'ProdutoController@store');
    $router->put('/produtos/{id}', 'ProdutoController@update');
    $router->delete('/produtos/{id}', 'ProdutoController@destroy');

    // Pedidos
    $router->get('/pedidos', 'PedidoController@index');
    $router->get('/pedidos/{id}', 'PedidoController@show');
    $router->get('/pedidos/{id}/etiqueta', 'PedidoController@etiqueta');
    $router->get('/pedidos/{id}/calculo', 'PedidoController@calculo');
    $router->post('/pedidos', 'PedidoController@store');
    $router->put('/pedidos/{id}', 'PedidoController@update');
    $router->put('/pedidos/{id}/status', 'PedidoController@updateStatus');
    $router->delete('/pedidos/{id}', 'PedidoController@destroy');
    $router->get('/pedidos/{id}/itens', 'ItemPedidoController@index');
    $router->post('/pedidos/{id}/itens', 'ItemPedidoController@store');
    $router->put('/itens/{id}', 'ItemPedidoController@update');
    $router->delete('/itens/{id}', 'ItemPedidoController@destroy');

    // Vendedores
    $router->get('/vendedores', 'VendedorController@index');
    $router->get('/vendedores/{id}', 'VendedorController@show');
    $router->post('/vendedores', 'VendedorController@store');
    $router->put('/vendedores/{id}', 'VendedorController@update');
    $router->delete('/vendedores/{id}', 'VendedorController@destroy');

    // Estoque - rotas estáticas primeiro
    $router->get('/estoque/movimentacao', 'EstoqueController@movimentacao');
    $router->get('/estoque', 'EstoqueController@index');
    $router->get('/estoque/baixo', 'EstoqueController@estoqueBaixo');
    $router->get('/estoque/resumo', 'EstoqueController@resumoEstoque');
    $router->get('/estoque/{id}', 'EstoqueController@show');
    $router->post('/estoque/entrada', 'EstoqueController@registrarEntrada');
    $router->post('/estoque/saida', 'EstoqueController@registrarSaida');
    $router->post('/estoque/reservar', 'EstoqueController@reservar');
    $router->post('/estoque/liberar', 'EstoqueController@liberar');

    // Relatorios
    $router->get('/relatorios/vendas', 'DashboardController@relatorioVendas');
    $router->get('/relatorios/vendedores', 'DashboardController@relatorioVendedores');
    $router->get('/relatorios/produtos', 'DashboardController@relatorioProdutos');
    $router->get('/relatorios/estatisticas', 'DashboardController@estatisticas');
    $router->get('/relatorios/insights', 'DashboardController@insights');
    $router->get('/relatorios/clientes', 'DashboardController@relatorioClientes');
    $router->get('/relatorios/clientes/{id}', 'DashboardController@clienteDetalhes');

    // PDFs de Pedidos
    $router->post('/pdf/pedido/{id}', 'PdfController@gerarPedido');
    $router->post('/pdf/etiqueta/{id}', 'PdfController@gerarEtiqueta');
    $router->post('/pdf/carne/{id}', 'PdfController@gerarCarne');
    $router->post('/pdf/recibo/{id}', 'PdfController@gerarRecibo');

    // Documentos (novo contrato)
    $router->get('/documents/pedido/{id}/preview', 'DocumentController@previewPedido');
    $router->post('/documents/pedido/{id}/generate', 'DocumentController@generatePedido');
    $router->get('/documents/{documentId}/metadata', 'DocumentController@metadata');
    $router->get('/documents/{documentId}/content', 'DocumentController@content');
    $router->post('/documents/cleanup', 'DocumentController@cleanup');

    // PDFs de Relatórios
    $router->post('/pdf/relatorio/vendas', 'PdfController@relatorioVendas');
    $router->post('/pdf/relatorio/vendedores', 'PdfController@relatorioVendedores');
    $router->post('/pdf/relatorio/produtos', 'PdfController@relatorioProdutos');
});
