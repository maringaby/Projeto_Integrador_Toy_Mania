<?php

declare(strict_types=1);

/**
 * Ícones em SVG, no estilo de traço usado em painéis administrativos.
 * O nome é escolhido por uma lista fixa, então o retorno é seguro para o HTML.
 */
function icon(string $nome): string
{
    $icones = [
        'painel' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'usuarios' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'caixa' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
        'etiqueta' => '<path d="M12 2H2v10l9.3 9.3a2 2 0 0 0 2.8 0l6.2-6.2a2 2 0 0 0 0-2.8Z"/><circle cx="7" cy="7" r="1.2" fill="currentColor" stroke="none"/>',
        'sair' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'mais' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'busca' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'lapis' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'lixeira' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'olho' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'imagem' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>',
        'menu' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'upload' => '<path d="M12 16V5"/><path d="M7 9l5-5 5 5"/><path d="M5 19h14"/>',
        'estrela' => '<path d="M12 3l2.7 5.5L21 9.2l-4.5 4.3L17.5 20 12 17.2 6.5 20l1-6.5L3 9.2l6.3-.7Z"/>',
        'alerta' => '<path d="M12 3 2.8 19h18.4L12 3z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
    ];

    $corpo = $icones[$nome] ?? '';

    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $corpo . '</svg>';
}
