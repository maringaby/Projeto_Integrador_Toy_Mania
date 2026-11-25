<?php

declare(strict_types=1);

/** @var array $form */
/** @var array $erros */
/** @var bool $senhaObrigatoria */
/** @var bool $bloqueiaInativo */
?>
<div class="form-grid">
    <div class="field">
        <label for="nome_completo">Nome completo <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'nome_completo'))) ?>" id="nome_completo" name="nome_completo" maxlength="150" required value="<?= e($form['nome_completo']) ?>">
        <?= erro_campo($erros, 'nome_completo') ?>
    </div>

    <div class="field">
        <label for="email">E-mail <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'email'))) ?>" id="email" name="email" type="email" maxlength="180" required autocomplete="email" value="<?= e($form['email']) ?>">
        <?= erro_campo($erros, 'email') ?>
    </div>

    <div class="field">
        <label for="telefone">Telefone</label>
        <input class="<?= e(trim(invalido($erros, 'telefone'))) ?>" id="telefone" name="telefone" maxlength="20" inputmode="tel" placeholder="(11) 98888-7777" value="<?= e($form['telefone']) ?>">
        <?= erro_campo($erros, 'telefone') ?>
    </div>

    <div class="field">
        <label for="cargo">Cargo <span class="req">*</span></label>
        <input class="<?= e(trim(invalido($erros, 'cargo'))) ?>" id="cargo" name="cargo" maxlength="80" required list="cargos" value="<?= e($form['cargo']) ?>">
        <datalist id="cargos">
            <option value="Administrador"></option>
            <option value="Gerente"></option>
            <option value="Vendedor"></option>
            <option value="Estoquista"></option>
            <option value="Atendente"></option>
        </datalist>
        <?= erro_campo($erros, 'cargo') ?>
    </div>

    <div class="field">
        <label for="senha"><?= $senhaObrigatoria ? 'Senha <span class="req">*</span>' : 'Nova senha' ?></label>
        <input class="<?= e(trim(invalido($erros, 'senha'))) ?>" id="senha" name="senha" type="password" minlength="<?= $senhaObrigatoria ? '6' : '0' ?>" autocomplete="new-password" <?= $senhaObrigatoria ? 'required' : '' ?>>
        <small class="help"><?= $senhaObrigatoria ? 'Mínimo de 6 caracteres.' : 'Deixe em branco para manter a senha atual.' ?></small>
        <?= erro_campo($erros, 'senha') ?>
    </div>

    <div class="field">
        <label for="senha_confirmacao">Confirmar senha <?= $senhaObrigatoria ? '<span class="req">*</span>' : '' ?></label>
        <input class="<?= e(trim(invalido($erros, 'senha_confirmacao'))) ?>" id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" <?= $senhaObrigatoria ? 'required' : '' ?>>
        <?= erro_campo($erros, 'senha_confirmacao') ?>
    </div>

    <div class="field">
        <label for="status">Status <span class="req">*</span></label>
        <select class="<?= e(trim(invalido($erros, 'status'))) ?>" id="status" name="status" <?= $bloqueiaInativo ? 'aria-describedby="status-ajuda"' : '' ?>>
            <option value="ativo" <?= ($form['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= ($form['status'] ?? '') === 'inativo' ? 'selected' : '' ?> <?= $bloqueiaInativo ? 'disabled' : '' ?>>Inativo</option>
        </select>
        <?php if ($bloqueiaInativo): ?>
            <small class="help" id="status-ajuda">Você não pode desativar a sua própria conta.</small>
        <?php endif; ?>
        <?= erro_campo($erros, 'status') ?>
    </div>
</div>
