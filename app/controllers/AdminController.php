<?php

require_once __DIR__ . '/../helpers/auth.php';

class AdminController
{
    private string $dbPath;
    private string $backupDir;

    public function __construct()
    {
        $this->dbPath    = __DIR__ . '/../../database/mtpar.sqlite';
        $this->backupDir = __DIR__ . '/../../backups';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);

            $htaccess = $this->backupDir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order allow,deny\nDeny from all\n");
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(): void
    {
        $this->requireAdmin();

        $backups      = $this->listarBackups();
        $dbSize       = file_exists($this->dbPath) ? $this->formatarTamanho(filesize($this->dbPath)) : '—';
        $ultimoBackup = count($backups) > 0 ? $backups[0]['data_relativa'] : null;
        $pastaBackup  = realpath($this->backupDir) ?: $this->backupDir;

        require_once __DIR__ . '/../models/Demanda.php';
        require_once __DIR__ . '/../models/Cotacao.php';
        require_once __DIR__ . '/../models/ProcessoVantajosidade.php';

        $totalLixeira = Demanda::contarExcluidas()
            + Cotacao::contarExcluidas()
            + ProcessoVantajosidade::contarExcluidos();

        require_once __DIR__ . '/../views/admin/index.php';
    }

    /* ------------------------------------------------------------------ */
    /*  LIXEIRA                                                             */
    /* ------------------------------------------------------------------ */
    public function lixeira(): void
    {
        $this->requireAdmin();

        require_once __DIR__ . '/../models/Demanda.php';
        require_once __DIR__ . '/../models/Cotacao.php';
        require_once __DIR__ . '/../models/ProcessoVantajosidade.php';

        $demandasExcluidas      = Demanda::buscarExcluidas();
        $cotacoesExcluidas      = Cotacao::buscarExcluidas();
        $vantajosidadesExcluidas = ProcessoVantajosidade::buscarExcluidos();

        require_once __DIR__ . '/../views/admin/lixeira.php';
    }

    /* ------------------------------------------------------------------ */
    /*  RESTAURAR                                                           */
    /* ------------------------------------------------------------------ */
    public function restaurarDemanda(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/Demanda.php';
        $demanda = Demanda::buscarExcluidaPorId($id);

        if ($demanda !== null) {
            $demanda->restaurar();
            $_SESSION['sucesso'] = "Demanda <strong>{$demanda->numeroProcesso}</strong> restaurada com sucesso.";
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    public function restaurarCotacao(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/Cotacao.php';
        $cotacao = Cotacao::buscarExcluidaPorId($id);

        if ($cotacao !== null) {
            $cotacao->restaurar();
            $_SESSION['sucesso'] = "Cotação <strong>{$cotacao->numeroProcesso}</strong> restaurada com sucesso.";
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    public function restaurarVantajosidade(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/ProcessoVantajosidade.php';
        $processo = ProcessoVantajosidade::buscarExcluidaPorId($id);

        if ($processo !== null) {
            $processo->restaurar();
            $_SESSION['sucesso'] = "Vantajosidade <strong>{$processo->numeroAta}</strong> restaurada com sucesso.";
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  EXCLUIR DEFINITIVAMENTE                                             */
    /* ------------------------------------------------------------------ */
    public function excluirDefinitivamenteDemanda(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/Demanda.php';
        $demanda = Demanda::buscarExcluidaPorId($id);

        if ($demanda !== null) {
            $demanda->excluirDefinitivamente();
            $_SESSION['sucesso'] = 'Demanda excluída permanentemente.';
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    public function excluirDefinitivamenteCotacao(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/Cotacao.php';
        $cotacao = Cotacao::buscarExcluidaPorId($id);

        if ($cotacao !== null) {
            $cotacao->excluirDefinitivamente();
            $_SESSION['sucesso'] = 'Cotação excluída permanentemente.';
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    public function excluirDefinitivamenteVantajosidade(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        require_once __DIR__ . '/../models/ProcessoVantajosidade.php';
        $processo = ProcessoVantajosidade::buscarExcluidaPorId($id);

        if ($processo !== null) {
            $processo->excluirDefinitivamente();
            $_SESSION['sucesso'] = 'Vantajosidade excluída permanentemente.';
        } else {
            $_SESSION['erro'] = 'Registro não encontrado na lixeira.';
        }

        header('Location: index.php?action=admin_lixeira');
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  BACKUP                                                              */
    /* ------------------------------------------------------------------ */
    public function criarBackup(): void
    {
        $this->requireAdmin();

        if (!file_exists($this->dbPath)) {
            $_SESSION['erro'] = 'Arquivo do banco de dados não encontrado.';
            header('Location: index.php?action=admin');
            exit;
        }

        $timestamp  = date('Y-m-d_H-i');
        $nomeBackup = "mtpar_backup_{$timestamp}.sqlite";
        $destino    = $this->backupDir . '/' . $nomeBackup;

        if (copy($this->dbPath, $destino)) {
            $_SESSION['sucesso'] = "Backup criado com sucesso: <strong>{$nomeBackup}</strong>";
        } else {
            $_SESSION['erro'] = 'Não foi possível criar o backup.';
        }

        header('Location: index.php?action=admin');
        exit;
    }

    public function excluirBackup(): void
    {
        $this->requireAdmin();

        $arquivo = basename($_POST['arquivo'] ?? '');

        if (!preg_match('/^mtpar_backup_[\d_\-]+\.sqlite$/', $arquivo)) {
            $_SESSION['erro'] = 'Nome de arquivo inválido.';
            header('Location: index.php?action=admin');
            exit;
        }

        $caminho = $this->backupDir . '/' . $arquivo;

        if (file_exists($caminho) && unlink($caminho)) {
            $_SESSION['sucesso'] = "Backup excluído: <strong>{$arquivo}</strong>";
        } else {
            $_SESSION['erro'] = 'Não foi possível excluir o backup.';
        }

        header('Location: index.php?action=admin');
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS PRIVADOS                                                    */
    /* ------------------------------------------------------------------ */
    private function listarBackups(): array
    {
        $backups = [];

        if (!is_dir($this->backupDir)) return $backups;

        $arquivos = glob($this->backupDir . '/mtpar_backup_*.sqlite');
        if (!$arquivos) return $backups;

        usort($arquivos, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach ($arquivos as $arquivo) {
            $nome  = basename($arquivo);
            $mtime = filemtime($arquivo);

            preg_match('/mtpar_backup_(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})\.sqlite/', $nome, $m);
            $dataFormatada = isset($m[5])
                ? "{$m[3]}/{$m[2]}/{$m[1]} às {$m[4]}:{$m[5]}"
                : date('d/m/Y \à\s H:i', $mtime);

            $backups[] = [
                'nome'           => $nome,
                'tamanho'        => $this->formatarTamanho(filesize($arquivo)),
                'data_formatada' => $dataFormatada,
                'data_relativa'  => $this->dataRelativa($mtime),
            ];
        }

        return $backups;
    }

    private function formatarTamanho(int $bytes): string
    {
        if ($bytes < 1024)    return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function dataRelativa(int $mtime): string
    {
        $hoje     = date('Y-m-d');
        $ontem    = date('Y-m-d', strtotime('-1 day'));
        $diaMtime = date('Y-m-d', $mtime);

        if ($diaMtime === $hoje)  return 'Hoje, '  . date('H:i', $mtime);
        if ($diaMtime === $ontem) return 'Ontem, ' . date('H:i', $mtime);
        return date('d/m/Y \à\s H:i', $mtime);
    }

    private function requireAdmin(): void
    {
        $servidor = usuarioLogado();

        if ($servidor === null || !$servidor->ehAdmin()) {
            header('Location: index.php?action=login');
            exit;
        }
    }
}