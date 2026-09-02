<?php

require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/formatacao.php';

class LoteController
{
    public function criar(): void
    {
        exigirLogin();

        $cotacaoId = (int) ($_POST['cotacao_id'] ?? 0);

        $cotacao = Cotacao::buscarPorId($cotacaoId);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        $lote = new Lote($cotacao->id, Lote::proximoNumeroLote($cotacao->id));
        $lote->salvar();

        header('Location: index.php?action=cotacao&id=' . $cotacao->id . '#lote-' . $lote->id);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $lote = Lote::buscarPorId($id);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        $cotacaoId = $lote->cotacaoId;
        $lote->excluir();

        header('Location: index.php?action=cotacao&id=' . $cotacaoId);
        exit;
    }

    public function adicionarItem(): void
    {
        exigirLogin();

        $loteId = (int) ($_POST['lote_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $unidade = trim($_POST['unidade'] ?? 'UN');
        $quantidade = converterMoedaBrParaFloat($_POST['quantidade'] ?? '1');

        $lote = Lote::buscarPorId($loteId);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        $item = new Item($lote->id, $lote->proximoNumeroItem(), $descricao, $unidade, $quantidade);
        $item->salvar();

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#item-' . $item->id);
        exit;
    }

    public function editarItem(): void
    {
        exigirLogin();

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $unidade = trim($_POST['unidade'] ?? 'UN');
        $quantidade = converterMoedaBrParaFloat($_POST['quantidade'] ?? '1');

        $item = Item::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $item->descricao = $descricao;
        $item->unidade = $unidade;
        $item->quantidade = $quantidade;
        $item->salvar();

        $lote = Lote::buscarPorId($item->loteId);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#item-' . $item->id);
        exit;
    }

    /**
     * Reorganiza um item pra outro lote da mesma cotacao (ou pra um lote
     * novo, criado na hora) sem perder os precos ja cadastrados. Restrito
     * a administrador porque muda a estrutura de um orcamento ja em uso.
     */
    public function moverItem(): void
    {
        exigirAdmin();

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = Item::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $loteAtual = Lote::buscarPorId($item->loteId);

        if ($loteAtual === null) {
            echo 'Lote não encontrado.';
            return;
        }

        if (($_POST['criar_novo_lote'] ?? '') === '1') {
            $loteDestino = new Lote($loteAtual->cotacaoId, Lote::proximoNumeroLote($loteAtual->cotacaoId));
            $loteDestino->salvar();
        } else {
            $loteDestinoId = (int) ($_POST['lote_destino_id'] ?? 0);
            $loteDestino = Lote::buscarPorId($loteDestinoId);

            if ($loteDestino === null || $loteDestino->cotacaoId !== $loteAtual->cotacaoId) {
                echo 'Lote de destino inválido.';
                return;
            }
        }

        $item->moverParaLote($loteDestino->id, $loteDestino->proximoNumeroItem());

        // Fecha o buraco deixado no lote de origem e garante que o destino
        // tambem fique sequencial (corrige numeracao torta acumulada de
        // reorganizacoes anteriores, nao so a deste movimento).
        $loteAtual->renumerarItens();
        if ($loteDestino->id !== $loteAtual->id) {
            $loteDestino->renumerarItens();
        }

        header('Location: index.php?action=cotacao&id=' . $loteAtual->cotacaoId . '#item-' . $item->id);
        exit;
    }

    /**
     * Renumera os itens de um lote sequencialmente (1, 2, 3...), sem mudar
     * a ordem. Corrige lotes que ja ficaram com numeracao torta por causa
     * de reorganizacoes feitas antes desta correcao existir. Restrito a
     * administrador pelo mesmo motivo de moverItem().
     */
    public function renumerarItens(): void
    {
        exigirAdmin();

        $loteId = (int) ($_POST['lote_id'] ?? 0);
        $lote = Lote::buscarPorId($loteId);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        $lote->renumerarItens();

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#lote-' . $lote->id);
        exit;
    }

    public function excluirItem(): void
    {
        exigirLogin();

        $itemId = (int) ($_POST['id'] ?? 0);

        $item = Item::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $lote = Lote::buscarPorId($item->loteId);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        $item->excluir();

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#lote-' . $lote->id);
        exit;
    }
}