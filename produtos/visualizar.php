<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();
$id = (int) ($_GET['id'] ?? 0);
$produto = buscar_produto($id);

if (!$produto) {
    flash('erro', 'Produto não encontrado.');
    redirect('produtos/index.php');
}

$imagens = imagens_do_produto($id);
$produtoId = $id;
$erros = [];
$rotuloArquivos = 'Adicionar fotos';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    if (envio_excedeu_limite()) {
        $erros['imagens'] = 'O envio excedeu o limite do servidor. Envie menos fotos ou reduza o tamanho.';
    } else {
        $arquivos = normalizar_uploads($_FILES['imagens'] ?? []);
        $errosUpload = validar_lista_uploads($arquivos, count($imagens));
        if ($errosUpload) {
            $erros['imagens'] = implode(' ', $errosUpload);
        } elseif ($arquivos === []) {
            $erros['imagens'] = 'Selecione ao menos uma imagem.';
        } else {
            $pdo = db();
            $caminhos = [];
            $pdo->beginTransaction();
            try {
                $caminhos = salvar_imagens($arquivos);
                registrar_imagens($pdo, $id, $caminhos);
                $pdo->commit();
                flash('sucesso', 'Fotos adicionadas com sucesso.');
                redirect('produtos/visualizar.php?id=' . $id);
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($caminhos as $caminho) {
                    apagar_arquivo_upload($caminho);
                }
                if (!$e instanceof PDOException) {
                    $erros['imagens'] = $e->getMessage();
                } else {
                    throw $e;
                }
            }
        }
    }
}

$pageTitle = (string) $produto['nome'];
$activeMenu = 'produtos';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1><?= e($produto['nome']) ?></h1>
        <p>Código <?= e($produto['codigo']) ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(base_url('produtos/editar.php?id=' . $id)) ?>"><?= icon('lapis') ?> Editar</a>
        <a class="btn btn-ghost" href="<?= e(base_url('produtos/index.php')) ?>">Voltar</a>
    </div>
</section>

<section class="detail-layout">
    <div class="card">
        <h2>Galeria</h2>
        <?php require dirname(__DIR__) . '/includes/galeria-produto.php'; ?>
        <form method="post" action="" enctype="multipart/form-data" style="margin-top: 18px;">
            <?= csrf_field() ?>
            <?php require dirname(__DIR__) . '/includes/dropzone.php'; ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Enviar fotos</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Informações</h2>
        <dl class="detail-list">
            <dt>Status</dt>
            <dd><?= badge_status((string) $produto['status']) ?></dd>
            <dt>Categoria</dt>
            <dd><?= e($produto['categoria_nome']) ?></dd>
            <dt>Preço</dt>
            <dd class="money"><?= e(dinheiro($produto['preco'])) ?></dd>
            <dt>Estoque</dt>
            <dd class="<?= e(classe_estoque((int) $produto['estoque'])) ?>"><?= (int) $produto['estoque'] ?></dd>
            <dt>Faixa etária</dt>
            <dd><?= e(rotulo_faixa((string) $produto['faixa_etaria'])) ?></dd>
            <dt>Marca</dt>
            <dd><?= e((string) ($produto['marca'] ?: '—')) ?></dd>
            <dt>Responsável</dt>
            <dd><?= e($produto['funcionario_nome']) ?></dd>
            <dt>Cadastro</dt>
            <dd><?= e(data_br($produto['criado_em'])) ?></dd>
            <dt>Atualização</dt>
            <dd><?= e(data_br($produto['atualizado_em'])) ?></dd>
        </dl>
        <h2 class="mt">Descrição</h2>
        <p class="pre"><?= e($produto['descricao']) ?></p>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
