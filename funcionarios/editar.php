<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$funcionario = buscar_funcionario($id);

if (!$funcionario) {
    flash('erro', 'Funcionário não encontrado.');
    redirect('funcionarios/index.php');
}

$form = [
    'nome_completo' => (string) $funcionario['nome_completo'],
    'email' => (string) $funcionario['email'],
    'telefone' => (string) ($funcionario['telefone'] ?? ''),
    'cargo' => (string) $funcionario['cargo'],
    'status' => (string) $funcionario['status'],
];
$erros = [];
$senhaObrigatoria = false;
$bloqueiaInativo = (int) $usuario['id'] === $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $form['nome_completo'] = trim((string) ($_POST['nome_completo'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['telefone'] = trim((string) ($_POST['telefone'] ?? ''));
    $form['cargo'] = trim((string) ($_POST['cargo'] ?? ''));
    $form['status'] = (string) ($_POST['status'] ?? 'ativo');

    $resultado = validar_funcionario($_POST, false, $id);
    $erros = $resultado['erros'];

    if ($bloqueiaInativo && ($resultado['dados']['status'] ?? '') === 'inativo') {
        $erros['status'] = 'Você não pode desativar a sua própria conta.';
    }

    if (!$erros) {
        $dados = $resultado['dados'];
        $params = [
            'nome' => $dados['nome_completo'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'],
            'cargo' => $dados['cargo'],
            'status' => $dados['status'],
            'id' => $id,
        ];

        if ($dados['senha']) {
            $sql = 'UPDATE funcionarios
                    SET nome_completo = :nome, email = :email, telefone = :telefone,
                        cargo = :cargo, status = :status, senha = :senha
                    WHERE id = :id';
            $params['senha'] = $dados['senha'];
        } else {
            $sql = 'UPDATE funcionarios
                    SET nome_completo = :nome, email = :email, telefone = :telefone,
                        cargo = :cargo, status = :status
                    WHERE id = :id';
        }

        try {
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            flash('sucesso', 'Funcionário atualizado com sucesso.');
            redirect('funcionarios/index.php');
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $erros['email'] = 'Este e-mail já está cadastrado.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Editar funcionário';
$activeMenu = 'funcionarios';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Editar funcionário</h1>
        <p>Cadastrado em <?= e(data_br($funcionario['criado_em'])) ?>.</p>
    </div>
</section>

<section class="card">
    <?php if ($erros): ?>
        <div class="alert alert-erro">Revise os campos destacados antes de salvar.</div>
    <?php endif; ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php require dirname(__DIR__) . '/includes/form-funcionario.php'; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Salvar alterações</button>
            <a class="btn btn-ghost" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
