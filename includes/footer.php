<?php

declare(strict_types=1);
?>
        </main>
    </div>
</div>

<div class="modal" id="modal" hidden>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
        <h2 id="modal-titulo">Confirmar ação</h2>
        <p id="modal-mensagem"></p>
        <div class="modal-actions">
            <button class="btn btn-ghost" type="button" data-modal-close>Cancelar</button>
            <button class="btn btn-primary" type="button" id="modal-confirmar">Confirmar</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
