<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - MT Par</title>
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="login-page">

<div class="login-card">
    <img src="public/img/logo.png" alt="MT Par" class="login-mark">
    <p class="login-tagline">Parcerias para fazer história</p>

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
        <button type="submit" class="btn-login">
            <i class="ti ti-login" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
            Entrar
        </button>
    </form>

    <p class="login-footer-note">
        Esqueceu sua senha? Procure o administrador do sistema.
    </p>
</div>

</body>
</html>
