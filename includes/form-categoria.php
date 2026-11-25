<?php

declare(strict_types=1);

/** @var array $form */
/** @var array $erros */
?>
<div class="form-grid">
    <div class="field">
        <label for="nome">Nome da categoria <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'nome'))) ?>" id="nome" name="nome" maxlength="100" required value="<?= e($form['nome']) ?>">
        <?= erro_campo($erros, 'nome') ?>
    </div>

    <div class="field">
        <label for="status">Status <span class="req">*</span></label>
        <select class="<?= e(trim(invalido($erros, 'status'))) ?>" id="status" name="status">
            <option value="ativo" <?= ($form['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= ($form['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
        <?= erro_campo($erros, 'status') ?>
    </div>

    <div class="field field-full">
        <label for="descricao">Descrição</label>
        <textarea class="<?= e(trim(invalido($erros, 'descricao'))) ?>" id="descricao" name="descricao" maxlength="2000" rows="4"><?= e($form['descricao']) ?></textarea>
        <?= erro_campo($erros, 'descricao') ?>
    </div>
</div>
