<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();
$categorias = categorias_para_select(null);
$form = [
    'nome' => '',
    'descricao' => '',
    'categoria_id' => '',
    'preco' => '',
    'faixa_etaria' => '',
    'estoque' => '0',
    'marca' => '',
    'codigo' => '',
    'status' => 'ativo',
];
$erros = [];
$rotuloArquivos = 'Fotos do produto';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    foreach (['nome', 'descricao', 'categoria_id', 'preco', 'faixa_etaria', 'estoque', 'marca', 'codigo', 'status'] as $campo) {
        $form[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (envio_excedeu_limite()) {
        $erros['imagens'] = 'O envio excedeu o limite do servidor. Envie menos fotos ou reduza o tamanho.';
        $arquivos = [];
    } else {
        $arquivos = normalizar_uploads($_FILES['imagens'] ?? []);
    }

    $resultado = validar_produto($_POST, null, null);
    $erros = array_merge($resultado['erros'], $erros);
    $errosUpload = validar_lista_uploads($arquivos, 0);
    if ($errosUpload) {
        $erros['imagens'] = implode(' ', $errosUpload);
    }

    if (!$erros) {
        $dados = $resultado['dados'];
        $pdo = db();
        $caminhos = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO produtos
                    (nome, descricao, categoria_id, preco, faixa_etaria, estoque, marca, codigo, status, funcionario_id)
                 VALUES
                    (:nome, :descricao, :categoria_id, :preco, :faixa_etaria, :estoque, :marca, :codigo, :status, :funcionario_id)'
            );
            $stmt->execute([
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'],
                'categoria_id' => $dados['categoria_id'],
                'preco' => $dados['preco'],
                'faixa_etaria' => $dados['faixa_etaria'],
                'estoque' => $dados['estoque'],
                'marca' => $dados['marca'],
                'codigo' => $dados['codigo'],
                'status' => $dados['status'],
                'funcionario_id' => (int) $usuario['id'],
            ]);
            $produtoId = (int) $pdo->lastInsertId();
            $caminhos = salvar_imagens($arquivos);
            registrar_imagens($pdo, $produtoId, $caminhos);
            $pdo->commit();
            flash('sucesso', 'Produto cadastrado com sucesso.');
            redirect('produtos/visualizar.php?id=' . $produtoId);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($caminhos as $caminho) {
                apagar_arquivo_upload($caminho);
            }
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $erros['codigo'] = 'Este código já está em uso.';
            } else {
                throw $e;
            }
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($caminhos as $caminho) {
                apagar_arquivo_upload($caminho);
            }
            $erros['imagens'] = $e->getMessage();
        }
    }
}

$pageTitle = 'Novo produto';
$activeMenu = 'produtos';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Novo produto</h1>
        <p>Preencha os dados do brinquedo e envie uma ou mais fotos.</p>
    </div>
</section>

<?php if (!$categorias): ?>
    <section class="card">
        <div class="alert alert-aviso">Cadastre uma categoria ativa antes de incluir produtos.</div>
        <a class="btn btn-primary" href="<?= e(base_url('categorias/criar.php')) ?>">Nova categoria</a>
    </section>
<?php else: ?>
    <section class="card">
        <?php if ($erros): ?><div class="alert alert-erro">Revise os campos destacados antes de salvar.</div><?php endif; ?>
        <form method="post" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php require dirname(__DIR__) . '/includes/form-produto.php'; ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Cadastrar produto</button>
                <a class="btn btn-ghost" href="<?= e(base_url('produtos/index.php')) ?>">Cancelar</a>
            </div>
        </form>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
