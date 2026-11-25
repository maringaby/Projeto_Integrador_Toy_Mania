(function () {
    const sidebar = document.getElementById("sidebar");
    const backdrop = document.getElementById("sidebar-backdrop");
    const toggle = document.getElementById("menu-toggle");

    function fecharMenu() {
        if (!sidebar) return;
        sidebar.classList.remove("is-open");
        if (backdrop) {
            backdrop.classList.remove("is-open");
            backdrop.hidden = true;
        }
    }

    function abrirMenu() {
        if (!sidebar) return;
        sidebar.classList.add("is-open");
        if (backdrop) {
            backdrop.hidden = false;
            backdrop.classList.add("is-open");
        }
    }

    if (toggle) {
        toggle.addEventListener("click", function () {
            if (sidebar && sidebar.classList.contains("is-open")) fecharMenu();
            else abrirMenu();
        });
    }

    if (backdrop) backdrop.addEventListener("click", fecharMenu);

    const modal = document.getElementById("modal");
    const modalMensagem = document.getElementById("modal-mensagem");
    const modalConfirmar = document.getElementById("modal-confirmar");
    let modalCallback = null;

    function fecharModal() {
        if (!modal) return;
        modal.hidden = true;
        modalCallback = null;
    }

    function abrirModal(mensagem, callback) {
        if (!modal || !modalMensagem) {
            if (window.confirm(mensagem)) callback();
            return;
        }
        modalMensagem.textContent = mensagem;
        modal.hidden = false;
        modalCallback = callback;
        if (modalConfirmar) modalConfirmar.focus();
    }

    if (modalConfirmar) {
        modalConfirmar.addEventListener("click", function () {
            const callback = modalCallback;
            fecharModal();
            if (callback) callback();
        });
    }

    if (modal) {
        modal.querySelectorAll("[data-modal-close]").forEach(function (elemento) {
            elemento.addEventListener("click", fecharModal);
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            fecharModal();
            fecharMenu();
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.locking === "1") {
            event.preventDefault();
            return;
        }

        const mensagem = form.dataset.confirm || "";
        if (mensagem && form.dataset.confirmed !== "1") {
            event.preventDefault();
            abrirModal(mensagem, function () {
                form.dataset.confirmed = "1";
                if (typeof form.requestSubmit === "function") form.requestSubmit();
                else form.submit();
            });
            return;
        }

        form.dataset.locking = "1";
        const botao = form.querySelector('[type="submit"]');
        if (botao instanceof HTMLButtonElement) botao.disabled = true;
    });

    document.addEventListener("click", function (event) {
        const alvo = event.target;
        if (!(alvo instanceof Element)) return;
        const link = alvo.closest("[data-confirm][data-post]");
        if (!(link instanceof HTMLAnchorElement)) return;

        event.preventDefault();
        const mensagem = link.getAttribute("data-confirm") || "Confirmar esta ação?";
        abrirModal(mensagem, function () {
            const form = document.createElement("form");
            form.method = "post";
            form.action = link.getAttribute("href") || "";
            const token = document.querySelector('meta[name="csrf-token"]');
            const campos = {
                csrf_token: token ? token.getAttribute("content") || "" : "",
                id: link.getAttribute("data-id") || ""
            };
            Object.keys(campos).forEach(function (nome) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = nome;
                input.value = campos[nome];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        });
    });

    document.querySelectorAll("[data-dropzone]").forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector("[data-preview]");
        if (!(input instanceof HTMLInputElement) || !preview) return;

        function mostrar() {
            if (Array.isArray(input._urls)) {
                input._urls.forEach(function (url) { URL.revokeObjectURL(url); });
            }
            input._urls = [];
            preview.innerHTML = "";
            const arquivos = Array.from(input.files || []);
            if (!arquivos.length) {
                preview.hidden = true;
                return;
            }
            preview.hidden = false;
            arquivos.forEach(function (arquivo) {
                const item = document.createElement("figure");
                if (!arquivo.type || arquivo.type.indexOf("image/") !== 0) {
                    item.textContent = arquivo.name;
                    preview.appendChild(item);
                    return;
                }
                const img = document.createElement("img");
                const url = URL.createObjectURL(arquivo);
                input._urls.push(url);
                img.alt = arquivo.name;
                img.src = url;
                item.appendChild(img);
                const legenda = document.createElement("figcaption");
                legenda.textContent = arquivo.name;
                item.appendChild(legenda);
                preview.appendChild(item);
            });
        }

        input.addEventListener("change", mostrar);
        ["dragenter", "dragover"].forEach(function (evento) {
            zone.addEventListener(evento, function (event) {
                event.preventDefault();
                zone.classList.add("is-drag");
            });
        });
        ["dragleave", "drop"].forEach(function (evento) {
            zone.addEventListener(evento, function (event) {
                event.preventDefault();
                zone.classList.remove("is-drag");
            });
        });
        zone.addEventListener("drop", function (event) {
            const arquivos = event.dataTransfer && event.dataTransfer.files;
            if (!arquivos || !arquivos.length) return;
            input.files = arquivos;
            mostrar();
        });
    });

    document.querySelectorAll("[data-galeria]").forEach(function (galeria) {
        const principal = galeria.querySelector("[data-galeria-principal]");
        if (!(principal instanceof HTMLImageElement)) return;
        galeria.querySelectorAll("[data-galeria-item]").forEach(function (botao) {
            botao.addEventListener("click", function () {
                const src = botao.getAttribute("data-src") || "";
                if (src === "") return;
                principal.src = src;
                galeria.querySelectorAll("[data-galeria-item]").forEach(function (item) {
                    item.classList.remove("is-ativa");
                });
                botao.classList.add("is-ativa");
            });
        });
    });

    document.querySelectorAll("[data-gerar-codigo]").forEach(function (botao) {
        botao.addEventListener("click", function () {
            const alvo = document.getElementById(botao.getAttribute("data-gerar-codigo") || "");
            if (!(alvo instanceof HTMLInputElement)) return;
            const aleatorio = Math.random().toString(36).slice(2, 8).toUpperCase();
            alvo.value = "TM-" + aleatorio;
            alvo.focus();
        });
    });
})();
