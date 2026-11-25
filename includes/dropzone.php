<?php

declare(strict_types=1);

/** @var array $erros */
$rotuloArquivos = $rotuloArquivos ?? 'Fotos do produto';
?>
<div class="field field-full">
    <label for="imagens"><?= e($rotuloArquivos) ?></label>
    <div class="dropzone" data-dropzone>
        <div class="dropzone-copy">
            <?= icon('upload') ?>
            <div>
                <strong>Arraste as fotos aqui</strong> ou escolha os arquivos no computador.
                <small class="help">JPG, JPEG, PNG ou WEBP. Até 2 MB cada. No máximo <?= (int) PRODUTO_MAX_IMAGENS ?> fotos por produto.</small>
            </div>
        </div>
        <input id="imagens" name="imagens[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
        <div class="preview-grid" data-preview hidden></div>
    </div>
    <?= erro_campo($erros, 'imagens') ?>
</div>

