<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Preco.php';
require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../helpers/auth.php';

class PrecoController
{
    public function adicionar(): void
    {
        exigirLogin();

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
        $parametro = trim($_POST['parametro'] ?? '');
        $fonte = trim($_POST['fonte'] ?? '');

        $item = Item::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $preco = new Preco($item->id, $valor, $parametro, $fonte);
        $preco->salvar();

        $lote = Lote::buscarPorId($item->loteId);

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#item-' . $item->id);
        exit;
    }

    public function editar(): void
    {
        exigirLogin();

        $precoId = (int) ($_POST['preco_id'] ?? 0);
        $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
        $parametro = trim($_POST['parametro'] ?? '');
        $fonte = trim($_POST['fonte'] ?? '');

        $preco = Preco::buscarPorId($precoId);

        if ($preco === null) {
            echo 'Preço não encontrado.';
            return;
        }

        $preco->valor = $valor;
        $preco->parametro = $parametro;
        $preco->fonte = $fonte;
        $preco->salvar();

        $item = Item::buscarPorId($preco->itemId);
        $lote = $item !== null ? Lote::buscarPorId($item->loteId) : null;

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#item-' . $item->id);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $precoId = (int) ($_GET['id'] ?? 0);

        $preco = Preco::buscarPorId($precoId);

        if ($preco === null) {
            echo 'Preço não encontrado.';
            return;
        }

        $item = Item::buscarPorId($preco->itemId);
        $lote = $item !== null ? Lote::buscarPorId($item->loteId) : null;

        if ($lote === null) {
            echo 'Lote não encontrado.';
            return;
        }

        $preco->excluir();

        header('Location: index.php?action=cotacao&id=' . $lote->cotacaoId . '#item-' . $item->id);
        exit;
    }
}