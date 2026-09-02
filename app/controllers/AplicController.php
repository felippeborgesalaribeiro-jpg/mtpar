<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';

class AplicController
{
    public function painel(): void
    {
        exigirLogin();

        $licitacoes = Licitacao::buscarTodas();

        $totalPendentes = 0;
        $totalEnviados = 0;
        $totalNaoAplicavel = 0;
        foreach ($licitacoes as $licitacao) {
            match ($licitacao->statusAplic()) {
                StatusAplic::Pendente => $totalPendentes++,
                StatusAplic::Enviado => $totalEnviados++,
                StatusAplic::NaoAplicavel => $totalNaoAplicavel++,
            };
        }

        // Evita N+1 na listagem (uma consulta em vez de N chamadas
        // buscarServidorResponsavel por linha).
        $mapaServidores = Servidor::mapaPorIds(array_map(
            fn(Licitacao $l) => $l->servidorResponsavelId, $licitacoes
        ));

        require __DIR__ . '/../views/painel_aplic.php';
    }

    public function marcarEnviado(): void
    {
        exigirLogin();

        $id = (int) ($_POST['licitacao_id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao !== null && $licitacao->statusAplic() !== StatusAplic::NaoAplicavel) {
            $licitacao->marcarEnviadoAplic();
        }

        header('Location: index.php?action=aplic');
        exit;
    }

    public function desmarcarEnviado(): void
    {
        exigirLogin();

        $id = (int) ($_POST['licitacao_id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao !== null) {
            $licitacao->desmarcarEnviadoAplic();
        }

        header('Location: index.php?action=aplic');
        exit;
    }
}
