# Toy Mania

Painel administrativo de uma loja de brinquedos e produtos infantis, desenvolvido em PHP e MySQL para o Projeto Integrador 2026.

O sistema permite que funcionários entrem com e-mail e senha e gerenciem a equipe, as categorias e o catálogo de brinquedos, incluindo o envio de várias fotos por produto.

## Requisitos

- PHP 8.0 ou superior no terminal, com as extensões `pdo_mysql`, `gd`, `fileinfo` e `mbstring`
- MySQL 8 ou MariaDB 10.4 ou superior, com o cliente `mysql` disponível no terminal
- Navegador atualizado

A extensão `exif` é opcional. Quando está ativa, fotos JPEG tiradas pelo celular são giradas para a orientação correta.

## Configurar o banco de dados

1. Deixe o MySQL em execução. No macOS com Homebrew, por exemplo: `brew services start mysql`.
2. Na pasta do projeto, importe o script:

```bash
mysql -u root -p < database/toymania.sql
```

O comando pede a senha do usuário informado. Troque `root` se a sua conta do MySQL tiver outro nome.

Esse arquivo cria o banco `toymania_db`, as tabelas `funcionarios`, `categorias`, `produtos` e `produto_imagens`, as chaves estrangeiras e sete categorias iniciais (Bonecas, Carrinhos, Jogos educativos e outras).

Ele não cria funcionário nem senha. O primeiro acesso é feito pela própria aplicação, para não deixar uma credencial padrão no sistema.

Para reinstalar do zero:

```bash
mysql -u root -p -e "DROP DATABASE IF EXISTS toymania_db;"
mysql -u root -p < database/toymania.sql
```

## Configurar a conexão

Abra `config/database.php` e confira estes valores:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'toymania_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

`DB_USER` e `DB_PASS` precisam ser os mesmos dados que você usa no cliente `mysql`. Se a conta tiver senha, coloque essa senha em `DB_PASS`. Deixe vazio só quando a conta realmente não tem senha.

## Executar

Na pasta do projeto, com o banco já importado:

```bash
php -d upload_max_filesize=8M -d post_max_size=24M -S localhost:8000 router.php
```

Os limites de upload entram na hora, para ser possível enviar várias fotos de uma vez. O `router.php` entrega o sistema e impede que um arquivo enviado para `uploads/` seja executado como PHP.

A pasta `uploads/produtos` precisa existir e ter permissão de escrita para o usuário que iniciou o PHP. Ela já vem criada no projeto.

## Acessar o sistema

A loja para o cliente fica em [http://localhost:8000](http://localhost:8000). Ela mostra somente os produtos ativos cadastrados no painel.

A área da equipe fica em [http://localhost:8000/login.php](http://localhost:8000/login.php). As páginas internas não abrem sem uma sessão válida.

## Primeiro cadastro de funcionário

1. Acesse [http://localhost:8000/login.php](http://localhost:8000/login.php).
2. Como ainda não existe funcionário, a aplicação abre `setup.php`.
3. Informe nome, e-mail e uma senha com pelo menos 6 caracteres.
4. O cargo desse primeiro acesso fica como **Administrador**.
5. Depois do cadastro, entre pela tela de login com o e-mail e a senha que você escolheu.

A tela de configuração inicial deixa de aceitar novos cadastros assim que o primeiro funcionário existe. Não há usuário `admin` nem senha pronta no banco.

Funcionários inativos não conseguem entrar. Ninguém pode desativar ou excluir a própria conta. Se um funcionário que cadastrou produtos for excluído, esses produtos passam para a pessoa que fez a exclusão.

## Organização

```text
assets/          CSS, JavaScript e a logo
config/          conexão e configurações gerais
includes/        sessão, validação, layout e uploads
uploads/produtos fotos enviadas
funcionarios/    cadastro da equipe
produtos/        catálogo e galeria
categorias/      categorias dos brinquedos
database/        script de criação do banco
login.php        entrada no sistema
setup.php        criação do primeiro administrador
index.php        dashboard
```

As senhas são gravadas com `password_hash()` e conferidas com `password_verify()`. As consultas usam PDO com prepared statements. Os formulários enviam um token de proteção contra CSRF, e o texto exibido nas páginas passa por escape para evitar XSS.

As fotos aceitas são JPG, JPEG, PNG e WEBP, com até 2 MB cada e no máximo 10 por produto. O arquivo é relido com a biblioteca GD antes de ser salvo, e a pasta de uploads não executa scripts.
