<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('produtos/index.php');
}

verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$retorno = retorno_seguro('produtos/index.php');
$produto = buscar_produto($id);

if (!$produto) {
    flash('erro', 'Produto não encontrado.');
    redirect($retorno);
}

$novo = $produto['status'] === 'ativo' ? 'inativo' : 'ativo';
$stmt = db()->prepare('UPDATE produtos SET status = :status WHERE id = :id');
$stmt->execute(['status' => $novo, 'id' => $id]);
flash('sucesso', $novo === 'ativo' ? 'Produto ativado.' : 'Produto desativado.');
redirect($retorno);
