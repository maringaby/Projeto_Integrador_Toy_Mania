<?php

declare(strict_types=1);

/** @var array $imagens */
/** @var int $produtoId */

$retornoGaleria = $retornoGaleria ?? url_relativa_atual();
?>
<?php if (!$imagens): ?>
    <p class="muted">Nenhuma foto enviada ainda.</p>
<?php else: ?>
    <div class="gallery">
        <?php foreach ($imagens as $imagem): ?>
            <figure class="gallery-item">
                <img src="<?= e(url_upload((string) $imagem['caminho'])) ?>" alt="Foto do produto">
                <figcaption>
                    <?php if ((int) $imagem['principal'] === 1): ?>
                        <span class="badge badge-principal">Principal</span>
                    <?php endif; ?>
                    <div class="gallery-actions">
                        <?php if ((int) $imagem['principal'] !== 1): ?>
                            <form method="post" action="<?= e(base_url('produtos/imagem.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="principal">
                                <input type="hidden" name="id" value="<?= (int) $imagem['id'] ?>">
                                <input type="hidden" name="produto_id" value="<?= (int) $produtoId ?>">
                                <input type="hidden" name="retorno" value="<?= e($retornoGaleria) ?>">
                                <button class="btn btn-small btn-secondary" type="submit">Definir principal</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(base_url('produtos/imagem.php')) ?>" data-confirm="Deseja excluir esta foto?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= (int) $imagem['id'] ?>">
                            <input type="hidden" name="produto_id" value="<?= (int) $produtoId ?>">
                            <input type="hidden" name="retorno" value="<?= e($retornoGaleria) ?>">
                            <button class="btn btn-small btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
