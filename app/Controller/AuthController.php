<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/includes.php';

$db = new DB();

function login()
{
    global $db;

    $user = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_SPECIAL_CHARS);

    if (filter_var($user, FILTER_SANITIZE_SPECIAL_CHARS)) {

        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

        $sql = 'SELECT * FROM usuarios WHERE login_usuario=? LIMIT 1';

        $mysqli = $db->connect('migracao');
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $user);
        
        if ($stmt->execute()) {
            
            $userDB = $stmt->get_result()->fetch_assoc();
            
            if ($userDB && password_verify($password, $userDB['password_usuario'])) {
                $_SESSION['user'] = $userDB;
                $_SESSION['logged'] = true;
                unset($_SESSION['user']['password_usuario']); // Remove o campo da senha do array, para não ficar no $_SESSION
                return true;
            }
        }
    }
    return false;
}

function check_company()
{
    global $db;

    $data['company'] = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($data['company']) {

        $sql = 'SELECT * FROM clientes WHERE id_cliente=? LIMIT 1';

        $mysqli = $db->connect('migracao');
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $data['company']);

        if ($stmt->execute()) {
            $company = $stmt->get_result()->fetch_assoc();

            if ($company) {
                $_SESSION['company_selected'] = true;
                $_SESSION['company'] = ['id' => $company['id_cliente'], 'nome' => $company['nome_cliente']];
                // dd($company);
                return true;
            }
        }
    }
    return false;
}

function createCompany()
{
    global $db;

    $codigo_cliente = filter_input(INPUT_POST, 'codigo_cliente', FILTER_SANITIZE_NUMBER_INT);
    $nome_cliente = filter_input(INPUT_POST, 'nome_cliente', FILTER_SANITIZE_SPECIAL_CHARS);
    $concorrente = filter_input(INPUT_POST, 'concorrente', FILTER_SANITIZE_NUMBER_INT);

    if ($codigo_cliente && $nome_cliente && $concorrente) {
        $sql = 'INSERT INTO clientes (codigo_cliente, nome_cliente, id_concorrente) VALUES (?, ?, ?)';

        $mysqli = $db->connect('migracao');
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isi', $codigo_cliente, $nome_cliente, $concorrente);

        if ($stmt->execute()) {
            $id = $mysqli->insert_id;

            
            $_SESSION['company_selected'] = true;
            $_SESSION['company'] = ['id' => $id, 'nome' => $nome_cliente];
            return true;
        }
    }
    return false;
}

function trocarSenha()
{   
    return ['view' => 'auth/trocar_senha', 'data' => []];
}

function salvarSenha()
{
    global $db;

    $id = $_SESSION['user']['id_usuario'];
    $senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_SPECIAL_CHARS);
    $nova_senha = filter_input(INPUT_POST, 'nova_senha', FILTER_SANITIZE_SPECIAL_CHARS);
    $confirma_senha = filter_input(INPUT_POST, 'confirma_senha', FILTER_SANITIZE_SPECIAL_CHARS);

    $erro = '';
    if ($senha && $nova_senha && $confirma_senha) {
        
        $sql = "SELECT * FROM usuarios WHERE id_usuario=$id LIMIT 1";
        $usuario = metodo_get($sql, 'migracao');
        if ($usuario && password_verify($senha, $usuario->password_usuario)) {
            if ($nova_senha === $confirma_senha) {
                
                $sql = 'UPDATE usuarios SET password_usuario=? WHERE id_usuario=?';

                insert_update($sql, "si", [password_hash($senha, PASSWORD_DEFAULT), $id], 'migracao');
                return ['view' => 'auth/trocar_senha', 'data' => ['success' => 'Senha alterada com sucesso.']];
            } else {
                $erro = 'As novas senhas não conferem.';
            }
        } else {
            $erro = 'Senha atual incorreta.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
    return ['view' => 'auth/trocar_senha', 'data' => ['erro' => $erro]];
}

function logout()
{
    session_unset();
    session_destroy();
    //header('Location: /auth/login');
    return ['view' => 'auth/login', 'data' => []];
}

function getCompanys()
{
    global $db;

    $sql = 'SELECT * FROM clientes ORDER BY nome_cliente';

    return metodo_all($sql, 'migracao');
}

function getConcorrentes()
{
    global $db;

    $sql = 'SELECT * FROM concorrentes ORDER BY nome';

    return metodo_all($sql, 'migracao');
}

function isAuthenticated()
{
    return !isset($_SESSION['logged']) ? false : $_SESSION['logged'];
}

function companySelected()
{
    return !isset($_SESSION['company_selected']) ? false : $_SESSION['company_selected'];
}
