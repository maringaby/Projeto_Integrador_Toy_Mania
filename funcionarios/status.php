<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('funcionarios/index.php');
}

verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$retorno = retorno_seguro('funcionarios/index.php');
$funcionario = buscar_funcionario($id);

if (!$funcionario) {
    flash('erro', 'Funcionário não encontrado.');
    redirect($retorno);
}

if ($id === (int) $usuario['id']) {
    flash('erro', 'Você não pode desativar a sua própria conta.');
    redirect($retorno);
}

$novo = $funcionario['status'] === 'ativo' ? 'inativo' : 'ativo';
$stmt = db()->prepare('UPDATE funcionarios SET status = :status WHERE id = :id');
$stmt->execute(['status' => $novo, 'id' => $id]);

flash('sucesso', $novo === 'ativo' ? 'Funcionário ativado.' : 'Funcionário desativado.');
redirect($retorno);
