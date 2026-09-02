<?php

require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthController
{
    // Regras da proteção contra brute-force. Consideramos "janela" e "limite"
    // por identificador (usuário + IP): se houve MAX_TENTATIVAS falhas na
    // janela, novo login é recusado até a janela expirar.
    const JANELA_MINUTOS = 15;
    const MAX_TENTATIVAS = 5;

    public function mostrarLogin(): void
    {
        if (usuarioLogado() !== null) {
            header('Location: index.php?action=dashboard');
            exit;
        }

        $erro = $_GET['erro'] ?? null;

        require __DIR__ . '/../views/login.php';
    }

    public function login(): void
    {
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $identificador = $this->identificadorTentativa($usuario);

        if ($this->estaBloqueado($identificador)) {
            header('Location: index.php?action=login&erro=bloqueado');
            exit;
        }

        $servidor = Servidor::buscarPorUsuario($usuario);

        if ($servidor === null || !$servidor->verificarSenha($senha)) {
            $this->registrarTentativa($identificador);
            // Delay pequeno pra dificultar ataque de força bruta em paralelo.
            usleep(300000);
            header('Location: index.php?action=login&erro=1');
            exit;
        }

        // Login OK: zera contador daquele identificador e regenera o session id
        // (protege contra "session fixation" - o id anterior fica invalido).
        $this->limparTentativas($identificador);
        session_regenerate_id(true);

        efetuarLogin($servidor);

        if ($servidor->senhaProvisoria) {
            header('Location: index.php?action=perfil&trocar_senha=1');
            exit;
        }

        header('Location: index.php?action=dashboard');
        exit;
    }

    public function logout(): void
    {
        efetuarLogout();
        header('Location: index.php?action=login');
        exit;
    }

    private function identificadorTentativa(string $usuario): string
    {
        // Usuario + IP: uma pessoa da rede tentando adivinhar a senha de "felippe"
        // acumula tentativas separadamente de outra pessoa tentando o mesmo usuario.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '?';
        return mb_strtolower(trim($usuario)) . '|' . $ip;
    }

    private function estaBloqueado(string $identificador): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM tentativas_login
             WHERE identificador = :ident
               AND tentativa_em >= datetime('now', :janela)"
        );
        $stmt->execute([
            'ident'  => $identificador,
            'janela' => '-' . self::JANELA_MINUTOS . ' minutes',
        ]);
        return ((int) $stmt->fetchColumn()) >= self::MAX_TENTATIVAS;
    }

    private function registrarTentativa(string $identificador): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('INSERT INTO tentativas_login (identificador) VALUES (:ident)')
            ->execute(['ident' => $identificador]);
    }

    private function limparTentativas(string $identificador): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM tentativas_login WHERE identificador = :ident')
            ->execute(['ident' => $identificador]);
    }
}