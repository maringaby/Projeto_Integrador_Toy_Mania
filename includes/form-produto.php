<?php

declare(strict_types=1);

/** @var array $form */
/** @var array $erros */
/** @var array $categorias */
?>
<div class="form-grid">
    <div class="field">
        <label for="nome">Nome do produto <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'nome'))) ?>" id="nome" name="nome" maxlength="180" required value="<?= e($form['nome']) ?>">
        <?= erro_campo($erros, 'nome') ?>
    </div>

    <div class="field">
        <label for="codigo">Código do produto <span class="req">*</span></label>
        <div class="inline-field">
            <input class="<?= e(trim(invalido($erros, 'codigo'))) ?>" id="codigo" name="codigo" maxlength="60" required value="<?= e($form['codigo']) ?>">
            <button class="btn btn-ghost btn-small" type="button" data-gerar-codigo="codigo">Gerar</button>
        </div>
        <?= erro_campo($erros, 'codigo') ?>
    </div>

    <div class="field field-full">
        <label for="descricao">Descrição <span class="req">*</span></label>
        <textarea class="<?= e(trim(invalido($erros, 'descricao'))) ?>" id="descricao" name="descricao" maxlength="5000" rows="5" required><?= e($form['descricao']) ?></textarea>
        <?= erro_campo($erros, 'descricao') ?>
    </div>

    <div class="field">
        <label for="categoria_id">Categoria <span class="req">*</span></label>
        <select class="<?= e(trim(invalido($erros, 'categoria_id'))) ?>" id="categoria_id" name="categoria_id" required>
            <option value="">Selecione</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>" <?= (string) $form['categoria_id'] === (string) $categoria['id'] ? 'selected' : '' ?>>
                    <?= e($categoria['nome']) ?><?= $categoria['status'] !== 'ativo' ? ' (inativa)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= erro_campo($erros, 'categoria_id') ?>
    </div>

    <div class="field">
        <label for="faixa_etaria">Faixa etária <span class="req">*</span></label>
        <select class="<?= e(trim(invalido($erros, 'faixa_etaria'))) ?>" id="faixa_etaria" name="faixa_etaria" required>
            <option value="">Selecione</option>
            <?php foreach (faixas_etarias() as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $form['faixa_etaria'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
        <?= erro_campo($erros, 'faixa_etaria') ?>
    </div>

    <div class="field">
        <label for="preco">Preço <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'preco'))) ?>" id="preco" name="preco" inputmode="decimal" placeholder="59,90" required value="<?= e($form['preco']) ?>">
        <?= erro_campo($erros, 'preco') ?>
    </div>

    <div class="field">
        <label for="estoque">Quantidade em estoque <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'estoque'))) ?>" id="estoque" name="estoque" type="number" min="0" max="999999" step="1" required value="<?= e((string) $form['estoque']) ?>">
        <?= erro_campo($erros, 'estoque') ?>
    </div>

    <div class="field">
        <label for="marca">Marca</label>
        <input class="<?= e(trim(invalido($erros, 'marca'))) ?>" id="marca" name="marca" maxlength="100" value="<?= e($form['marca']) ?>">
        <?= erro_campo($erros, 'marca') ?>
    </div>

    <div class="field">
        <label for="status">Status <span class="req">*</span></label>
        <select class="<?= e(trim(invalido($erros, 'status'))) ?>" id="status" name="status">
            <option value="ativo" <?= $form['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= $form['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
        <?= erro_campo($erros, 'status') ?>
    </div>

    <?php require __DIR__ . '/dropzone.php'; ?>
</div>
