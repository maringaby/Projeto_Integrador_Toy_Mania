<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('categorias/index.php');
}

verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$retorno = retorno_seguro('categorias/index.php');
$categoria = buscar_categoria($id);

if (!$categoria) {
    flash('erro', 'Categoria não encontrada.');
    redirect($retorno);
}

$novo = $categoria['status'] === 'ativo' ? 'inativo' : 'ativo';
$stmt = db()->prepare('UPDATE categorias SET status = :status WHERE id = :id');
$stmt->execute(['status' => $novo, 'id' => $id]);
flash('sucesso', $novo === 'ativo' ? 'Categoria ativada.' : 'Categoria desativada.');
redirect($retorno);
