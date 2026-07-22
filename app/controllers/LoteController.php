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

        $id = (int) ($_GET['id'] ?? 0);
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

    public function excluirItem(): void
    {
        exigirLogin();

        $itemId = (int) ($_GET['id'] ?? 0);

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