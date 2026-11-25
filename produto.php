<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!banco_tem_tabelas()) {
    exibir_instalacao();
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.faixa_etaria, p.marca, p.codigo,
            c.nome AS categoria_nome
     FROM produtos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     WHERE p.id = :id AND p.status = 'ativo' AND c.status = 'ativo'
     LIMIT 1"
);
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    http_response_code(404);
    $pageTitle = 'Produto não encontrado';
    require __DIR__ . '/includes/loja-header.php';
    ?>
    <section class="card empty">
        <?= icon('caixa') ?>
        <h1>Produto não encontrado</h1>
        <p>Esse brinquedo não está disponível na loja.</p>
        <p><a class="btn btn-primary" href="<?= e(base_url('loja.php')) ?>">Voltar para a loja</a></p>
    </section>
    <?php
    require __DIR__ . '/includes/loja-footer.php';
    exit;
}

$imagens = imagens_do_produto($id);
$principal = $imagens[0]['caminho'] ?? null;
$pageTitle = (string) $produto['nome'];
require __DIR__ . '/includes/loja-header.php';
?>
<p class="loja-voltar"><a href="<?= e(base_url('loja.php')) ?>">← Voltar para os brinquedos</a></p>

<article class="loja-produto">
    <div class="loja-galeria" data-galeria>
        <?php if ($principal): ?>
            <img class="loja-foto-principal" data-galeria-principal src="<?= e(url_upload((string) $principal)) ?>" alt="<?= e('Foto de ' . $produto['nome']) ?>">
        <?php else: ?>
            <span class="vitrine-sem-foto loja-foto-principal" aria-hidden="true"><?= icon('imagem') ?></span>
        <?php endif; ?>
        <?php if (count($imagens) > 1): ?>
            <div class="loja-miniaturas">
                <?php foreach ($imagens as $indice => $imagem): ?>
                    <button class="loja-miniatura<?= $indice === 0 ? ' is-ativa' : '' ?>" type="button" data-galeria-item data-src="<?= e(url_upload((string) $imagem['caminho'])) ?>" aria-label="Ver foto <?= $indice + 1 ?>">
                        <img src="<?= e(url_upload((string) $imagem['caminho'])) ?>" alt="">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card loja-resumo">
        <span class="badge badge-principal"><?= e($produto['categoria_nome']) ?></span>
        <h1><?= e($produto['nome']) ?></h1>
        <p class="loja-preco"><?= e(dinheiro($produto['preco'])) ?></p>
        <?php if ((int) $produto['estoque'] > 0): ?>
            <p><span class="badge badge-sucesso">Disponível</span></p>
        <?php else: ?>
            <p><span class="badge badge-erro">Esgotado</span></p>
        <?php endif; ?>
        <dl class="detail-list">
            <dt>Faixa etária</dt>
            <dd><?= e(rotulo_faixa((string) $produto['faixa_etaria'])) ?></dd>
            <dt>Marca</dt>
            <dd><?= e((string) ($produto['marca'] ?: '—')) ?></dd>
            <dt>Código</dt>
            <dd><?= e((string) $produto['codigo']) ?></dd>
        </dl>
        <h2>Descrição</h2>
        <p class="pre"><?= e((string) $produto['descricao']) ?></p>
    </div>
</article>
<?php require __DIR__ . '/includes/loja-footer.php'; ?>
