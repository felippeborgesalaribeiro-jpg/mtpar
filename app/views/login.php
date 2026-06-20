<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - MT Par</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="text-center mb-4">
                <i class="ti ti-building-bank" aria-hidden="true" style="font-size: 40px; color: #1F3864;"></i>
                <h4 class="mt-2 mb-0">MT Par</h4>
                <p class="text-muted small">Coordenadoria de Licitação</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger small py-2">
                            <i class="ti ti-alert-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                            Usuário ou senha inválidos.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="index.php?action=fazer_login">
                        <div class="mb-3">
                            <label class="form-label">Usuário</label>
                            <input type="text" name="usuario" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-login" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                            Entrar
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-muted small mt-3">
                Esqueceu sua senha? Procure o administrador do sistema.
            </p>
        </div>
    </div>
</div>

</body>
</html>