<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('produtos/index.php');
}

verificar_csrf();

$acao = (string) ($_POST['acao'] ?? '');
$id = (int) ($_POST['id'] ?? 0);
$produtoId = (int) ($_POST['produto_id'] ?? 0);
$retorno = retorno_seguro('produtos/visualizar.php?id=' . $produtoId);

$stmt = db()->prepare(
    'SELECT id, produto_id, caminho, principal
     FROM produto_imagens
     WHERE id = :id AND produto_id = :produto
     LIMIT 1'
);
$stmt->execute(['id' => $id, 'produto' => $produtoId]);
$imagem = $stmt->fetch();

if (!$imagem) {
    flash('erro', 'Imagem não encontrada.');
    redirect($retorno);
}

$pdo = db();

if ($acao === 'principal') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE produto_imagens SET principal = 0 WHERE produto_id = :produto')->execute(['produto' => $produtoId]);
        $pdo->prepare('UPDATE produto_imagens SET principal = 1 WHERE id = :id AND produto_id = :produto')->execute([
            'id' => $id,
            'produto' => $produtoId,
        ]);
        $pdo->prepare('UPDATE produtos SET atualizado_em = NOW() WHERE id = :id')->execute(['id' => $produtoId]);
        $pdo->commit();
        flash('sucesso', 'Imagem principal atualizada.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Toy Mania] ' . $e->getMessage());
        flash('erro', 'Não foi possível definir a imagem principal.');
    }
    redirect($retorno);
}

if ($acao === 'excluir') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM produto_imagens WHERE id = :id AND produto_id = :produto')->execute([
            'id' => $id,
            'produto' => $produtoId,
        ]);
        if ((int) $imagem['principal'] === 1) {
            $proxima = $pdo->prepare('SELECT id FROM produto_imagens WHERE produto_id = :produto ORDER BY id ASC LIMIT 1');
            $proxima->execute(['produto' => $produtoId]);
            $novoId = (int) $proxima->fetchColumn();
            if ($novoId > 0) {
                $pdo->prepare('UPDATE produto_imagens SET principal = 1 WHERE id = :id')->execute(['id' => $novoId]);
            }
        }
        $pdo->prepare('UPDATE produtos SET atualizado_em = NOW() WHERE id = :id')->execute(['id' => $produtoId]);
        $pdo->commit();
        apagar_arquivo_upload((string) $imagem['caminho']);
        flash('sucesso', 'Imagem excluída.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Toy Mania] ' . $e->getMessage());
        flash('erro', 'Não foi possível excluir a imagem.');
    }
    redirect($retorno);
}

flash('erro', 'Ação inválida.');
redirect($retorno);
