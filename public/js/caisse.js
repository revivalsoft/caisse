// public/js/caisse.js
document.addEventListener('DOMContentLoaded', () => {
    // --- Init idempotent ---
    window.CAISSE = window.CAISSE || {};
    if (window.CAISSE._initialized) return;
    window.CAISSE._initialized = true;

    // --- Etat global ---
    window.CAISSE.ticket = window.CAISSE.ticket || {};
    window.CAISSE.tableId = null;

    // --- Elements ---
    const tablesSection = document.getElementById('tables-section');
    const categoriesSection = document.getElementById('categories-section');
    const ticketContainer = document.getElementById('ticket-container');
    const tableNameDiv = document.getElementById('table-name');
    const saveBtn = document.getElementById('save-ticket-btn');
    const ticketDiv = document.getElementById('ticket');
    const backBtn = document.getElementById('back-to-tables');

    if (!tablesSection || !categoriesSection || !ticketContainer || !tableNameDiv || !saveBtn || !ticketDiv || !backBtn) {
        console.error('caisse.js : éléments HTML manquants.');
        return;
    }

    // --- Helpers ---
    const escapeHtml = s => s ? String(s).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#039;") : '';
    const getTableButton = tableId => tablesSection.querySelector(`.btn-table[data-id="${tableId}"]`);

    const updateTableVisualState = tableId => {
        const btn = getTableButton(tableId);
        if (!btn) return;
        const badge = btn.querySelector('.table-badge');
        const totalQty = Object.values(window.CAISSE.ticket[tableId] || {}).reduce((sum, item) => sum + Number(item.quantite || 0), 0);

        if (totalQty > 0) {
            btn.classList.replace('btn-outline-primary', 'btn-warning');
            if (badge) { badge.textContent = totalQty; badge.classList.remove('d-none'); }
        } else {
            btn.classList.replace('btn-warning', 'btn-outline-primary');
            if (badge) { badge.textContent = '0'; badge.classList.add('d-none'); }
        }
    };

    const updateAllTableStates = () => tablesSection.querySelectorAll('.btn-table').forEach(b => updateTableVisualState(b.dataset.id));

    // --- LocalStorage helpers avec timestamp ---
    const persistTicket = tableId => {
        if (!tableId) return;
        const data = {
            timestamp: Date.now(),
            ticket: window.CAISSE.ticket[tableId] || {}
        };
        localStorage.setItem(`ticket_${tableId}`, JSON.stringify(data));
        updateTableVisualState(tableId);
    };

    const restoreTicket = tableId => {
        const dataStr = localStorage.getItem(`ticket_${tableId}`);
        if (dataStr) {
            try {
                const data = JSON.parse(dataStr);
                window.CAISSE.ticket[tableId] = data.ticket || {};
            } catch (e) { console.warn('restoreTicket parse error', e); }
        }
    };

    const clearOldDrafts = (maxAgeDays = 7) => {
        const now = Date.now();
        const maxAgeMs = maxAgeDays * 24 * 60 * 60 * 1000;
        Object.keys(localStorage).forEach(key => {
            if (key.startsWith('ticket_')) {
                try {
                    const data = JSON.parse(localStorage.getItem(key));
                    if (!data.timestamp || (now - data.timestamp) > maxAgeMs) {
                        localStorage.removeItem(key);
                    }
                } catch (e) {
                    localStorage.removeItem(key);
                }
            }
        });
        console.log('Drafts locaux anciens nettoyés.');
    };

    clearOldDrafts(7);

    // --- Debounce ---
    const debounce = (fn, delay) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    };

    const saveDraft = debounce(tableId => {
        const ticketForTable = Object.values(window.CAISSE.ticket[tableId] || {});
        if (!ticketForTable.length) return;
        console.log('Draft sauvegardé localement pour table', tableId);
    }, 500);

    // --- Render ticket ---
    const renderTicket = () => {
        const tableId = window.CAISSE.tableId;
        ticketDiv.innerHTML = '';
        if (!tableId) return ticketDiv.innerHTML = '<div class="text-muted">Aucune table sélectionnée.</div>';

        const ticketForTable = window.CAISSE.ticket[tableId] || {};
        if (!Object.keys(ticketForTable).length) {
            ticketDiv.innerHTML = '<div class="text-muted">Aucun produit dans le ticket.</div>';
        } else {
            let total = 0;
            Object.values(ticketForTable).forEach(item => {
                const line = document.createElement('div');
                line.className = 'd-flex justify-content-between align-items-center mb-2';
                line.innerHTML = `
                    <div class="me-2">
                        <div class="fw-semibold">${escapeHtml(item.nom)}</div>
                        <div class="text-muted small">€ ${Number(item.prixttc).toFixed(2)} / unité</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1 ticket-action" data-action="dec" data-id="${item.id}">−</button>
                        <span class="mx-1 fw-bold" data-qty-for="${item.id}">${item.quantite}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1 ticket-action" data-action="inc" data-id="${item.id}">+</button>
                        <button type="button" class="btn btn-sm btn-danger ms-2 ticket-action" data-action="rm" data-id="${item.id}" title="Supprimer">🗑</button>
                        <div class="ms-3 fw-semibold">€ ${(Number(item.prixttc) * Number(item.quantite)).toFixed(2)}</div>
                    </div>`;
                ticketDiv.appendChild(line);
                total += Number(item.prixttc) * Number(item.quantite);
            });
            const hr = document.createElement('hr'); ticketDiv.appendChild(hr);
            const totalDiv = document.createElement('div');
            totalDiv.className = 'd-flex justify-content-between fw-bold';
            totalDiv.innerHTML = `<div>Total</div><div>€ ${total.toFixed(2)}</div>`;
            ticketDiv.appendChild(totalDiv);
        }
        persistTicket(tableId);
        saveDraft(tableId);
    };

    // --- Modify ticket ---
    const modifyTicket = (tableId, prodId, action) => {
        window.CAISSE.ticket[tableId] = window.CAISSE.ticket[tableId] || {};
        const ticketForTable = window.CAISSE.ticket[tableId];

        switch (action) {
            case 'inc':
                if (ticketForTable[prodId]) ticketForTable[prodId].quantite++;
                break;
            case 'dec':
                if (ticketForTable[prodId]) {
                    const q = ticketForTable[prodId].quantite - 1;
                    if (q <= 0) delete ticketForTable[prodId];
                    else ticketForTable[prodId].quantite = q;
                }
                break;
            case 'rm':
                delete ticketForTable[prodId];
                break;
        }
        renderTicket();
    };

    // --- Restore all tickets on load ---
    tablesSection.querySelectorAll('.btn-table').forEach(btn => {
        restoreTicket(btn.dataset.id);
    });
    updateAllTableStates();

    // --- Table click ---
    tablesSection.addEventListener('click', e => {
        const btn = e.target.closest('.btn-table'); if (!btn) return;
        const tableId = btn.dataset.id;
        window.CAISSE.tableId = tableId;

        restoreTicket(tableId);

        tableNameDiv.textContent = btn.querySelector('.table-label')?.textContent?.trim() || `Table ${tableId}`;
        tablesSection.classList.add('d-none');
        categoriesSection.classList.remove('d-none');
        ticketContainer.classList.remove('d-none');
        document.querySelectorAll('[id^="cat-"]').forEach(d => d.classList.add('d-none'));
        renderTicket();
    });

    // --- Categories click ---
    categoriesSection.addEventListener('click', e => {
        const btn = e.target.closest('.btn-cat'); if (!btn) return;
        document.querySelectorAll('[id^="cat-"]').forEach(d => d.classList.add('d-none'));
        const catDiv = document.getElementById('cat-' + btn.dataset.id);
        if (catDiv) catDiv.classList.remove('d-none');
    });

    // --- Produits / actions ---
    document.addEventListener('click', e => {
        const prodBtn = e.target.closest('.btn-produit');
        if (prodBtn) {
            const tableId = window.CAISSE.tableId;
            if (!tableId) { alert('Sélectionne une table avant d’ajouter des produits'); return; }
            const id = prodBtn.dataset.id;
            window.CAISSE.ticket[tableId] = window.CAISSE.ticket[tableId] || {};
            if (!window.CAISSE.ticket[tableId][id]) {
                window.CAISSE.ticket[tableId][id] = {
                    id,
                    nom: prodBtn.dataset.nom,
                    prixttc: parseFloat(prodBtn.dataset.prixttc),
                    quantite: 1
                };
            } else window.CAISSE.ticket[tableId][id].quantite++;
            renderTicket();
        }

        const actionBtn = e.target.closest('.ticket-action');
        if (actionBtn) {
            const tableId = window.CAISSE.tableId; if (!tableId) return;
            modifyTicket(tableId, actionBtn.dataset.id, actionBtn.dataset.action);
        }
    });

    // --- Back to tables ---
    backBtn.addEventListener('click', () => {
        categoriesSection.classList.add('d-none');
        tablesSection.classList.remove('d-none');
        ticketContainer.classList.add('d-none');
        document.querySelectorAll('[id^="cat-"]').forEach(d => d.classList.add('d-none'));
        window.CAISSE.tableId = null;
        tableNameDiv.textContent = '';
    });

    // --- Save ticket final (local + serveur) ---
    saveBtn.addEventListener('click', () => {
        const tableId = window.CAISSE.tableId;
        if (!tableId) { alert('Aucune table sélectionnée.'); return; }

        const ticketForTable = Object.values(window.CAISSE.ticket[tableId] || {});
        if (!ticketForTable.length) { alert('Aucun produit sélectionné.'); return; }

        const payload = {
            tableId: tableId,
            produits: ticketForTable.map(item => ({
                id: item.id,
                quantite: item.quantite
            }))
        };

        fetch('/caisse/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Commande enregistrée sur le serveur (ID ' + data.id + ')');
                    window.CAISSE.ticket[tableId] = {};
                    localStorage.removeItem(`ticket_${tableId}`);
                    updateTableVisualState(tableId);
                    renderTicket();
                } else {
                    alert('❌ Erreur serveur : ' + data.error);
                }
            })
            .catch(err => {
                console.error('Erreur fetch', err);
                alert('⚠ Impossible d’enregistrer sur le serveur, brouillon conservé en local.');
            });
    });

    // --- Debug panel (optionnel) ---
    if (window.CAISSE_DEBUG) {
        const panel = document.createElement('div');
        panel.style = 'position:fixed;bottom:10px;right:10px;width:300px;max-height:400px;overflow-y:auto;background:rgba(255,255,255,0.95);border:1px solid #ccc;border-radius:5px;padding:10px;font-size:12px;z-index:9999';
        panel.innerHTML = '<strong>Tickets en cours (localStorage)</strong><div id="local-tickets-list"></div>';
        document.body.appendChild(panel);

        const updatePanel = () => {
            const container = document.getElementById('local-tickets-list');
            container.innerHTML = '';
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('ticket_')) {
                    try {
                        const data = JSON.parse(localStorage.getItem(key));
                        const ticket = data.ticket || {};
                        const totalItems = Object.values(ticket).reduce((sum, item) => sum + (item.quantite || 0), 0);
                        const div = document.createElement('div');
                        div.style.marginBottom = '5px';
                        div.innerHTML = `<strong>${key.replace('ticket_', 'Table ')}</strong> : ${totalItems} produit(s)`;
                        container.appendChild(div);
                    } catch (e) { localStorage.removeItem(key); }
                }
            });
        };
        setInterval(updatePanel, 1000);
    }
});
