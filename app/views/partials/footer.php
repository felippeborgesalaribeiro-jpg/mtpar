</div>
<!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Garante a rolagem ate a ancora (#lote-X / #item-X): o script fica no fim
    // do <body>, entao o elemento ja existe no DOM neste ponto - nao precisa
    // esperar o evento "load" (que pode ja ter passado).
    if (window.location.hash) {
        var alvo = document.getElementById(window.location.hash.slice(1));
        if (alvo) {
            alvo.scrollIntoView({ block: 'center' });
        }
    }
</script>
</body>
</html>