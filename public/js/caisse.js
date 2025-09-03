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
    const escapeHtml = s => s ? String(s)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", "&#039;") : '';

    const getTableButton = tableId => tablesSection.querySelector(`.btn-table[data-id="${tableId}"]`);

    const updateTableVisualState = tableId => {
        const btn = getTableButton(tableId);
        if (!btn) return;
        const badge = btn.querySelector('.table-badge');
        const totalQty = Object.values(window.CAISSE.ticket[tableId] || {})
            .reduce((sum, item) => sum + Number(item.quantite || 0), 0);
        if (totalQty > 0) {
            btn.classList.replace('btn-outline-primary', 'btn-warning');
            if (badge) { badge.textContent = totalQty; badge.classList.remove('d-none'); }
        } else {
            btn.classList.replace('btn-warning', 'btn-outline-primary');
            if (badge) { badge.textContent = '0'; badge.classList.add('d-none'); }
        }
    };

    const updateAllTableStates = () => tablesSection.querySelectorAll('.btn-table')
        .forEach(b => updateTableVisualState(b.dataset.id));

    // --- LocalStorage helpers ---
    const persistTicket = tableId => {
        if (!tableId) return;
        const data = { timestamp: Date.now(), ticket: window.CAISSE.ticket[tableId] || {} };
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
                    if (!data.timestamp || (now - data.timestamp) > maxAgeMs) localStorage.removeItem(key);
                } catch (e) { localStorage.removeItem(key); }
            }
        });
        console.log('Drafts locaux anciens nettoyés.');
    };
    clearOldDrafts();

    // --- Debounce ---
    const debounce = (fn, delay) => {
        let timer;
        return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
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
            const totalDiv = document.createElement('div'); totalDiv.className = 'd-flex justify-content-between fw-bold';
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
            case 'inc': if (ticketForTable[prodId]) ticketForTable[prodId].quantite++; break;
            case 'dec':
                if (ticketForTable[prodId]) {
                    const q = ticketForTable[prodId].quantite - 1;
                    if (q <= 0) delete ticketForTable[prodId];
                    else ticketForTable[prodId].quantite = q;
                }
                break;
            case 'rm': delete ticketForTable[prodId]; break;
        }
        renderTicket();
    };

    // --- Restore tickets ---
    tablesSection.querySelectorAll('.btn-table').forEach(btn => restoreTicket(btn.dataset.id));
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

    // --- Category click ---
    categoriesSection.addEventListener('click', e => {
        const btn = e.target.closest('.btn-cat'); if (!btn) return;
        document.querySelectorAll('[id^="cat-"]').forEach(d => d.classList.add('d-none'));
        const catDiv = document.getElementById('cat-' + btn.dataset.id);
        if (catDiv) catDiv.classList.remove('d-none');
    });

    // --- Products click ---
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

    // --- Save & Print ---
    saveBtn.addEventListener('click', () => {
        const tableId = window.CAISSE.tableId;
        if (!tableId) return alert('Aucune table sélectionnée.');
        const ticketForTable = Object.values(window.CAISSE.ticket[tableId] || {});
        if (!ticketForTable.length) return alert('Aucun produit sélectionné.');

        const payload = { tableId, produits: ticketForTable.map(i => ({ id: i.id, quantite: i.quantite })) };
        fetch('/caisse/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return alert('❌ Erreur serveur : ' + data.error);
                const commandeId = data.id;
                alert('✅ Commande enregistrée (ID ' + commandeId + ')');

                const tableName = tableNameDiv.textContent.trim() || String(tableId);
                const resto = window.RESTAURANT || { nom: "Restaurant", adresse: "", telephone: "", logo: "" };

                // --- Print ticket ---
                const printTicket = () => {
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'absolute';
                    iframe.style.left = '-9999px';
                    document.body.appendChild(iframe);
                    const doc = iframe.contentDocument || iframe.contentWindow.document;

                    // --- Style ---
                    const style = document.createElement('style');
                    style.textContent = `
                        @page{size:58mm auto;margin:0;}
                        body{font-family:"Courier New",monospace;width:58mm;margin:0;padding:6px;}
                        .center{text-align:center;}.bold{font-weight:bold;}.small{font-size:10px;}.hr{border-top:1px dashed #000;margin:4px 0;}
                        .row{display:grid;grid-template-columns:1fr 10mm 18mm;column-gap:2mm;}.qty{text-align:center;}.price{text-align:right;}
                        .tot{display:grid;grid-template-columns:1fr 28mm;margin-top:4px;}.tot .val{text-align:right;font-weight:bold;}
                        .qr{text-align:center;margin-top:6px;}
                        .logo{max-width:50mm;margin-bottom:4px;}
                    `;
                    doc.head.appendChild(style);

                    const wrap = doc.createElement('div'); doc.body.appendChild(wrap);

                    // --- Logo ---
                    let pendingLoads = 0;
                    const doPrint = () => {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        setTimeout(() => document.body.removeChild(iframe), 1000);
                    };

                    if (resto.logo) {
                        pendingLoads++;
                        const divLogo = document.createElement('div'); divLogo.className = 'center';
                        const img = document.createElement('img');
                        img.src = resto.logo;
                        img.className = 'logo';
                        img.onload = () => {
                            pendingLoads--;
                            if (pendingLoads === 0) doPrint();
                        };
                        img.onerror = () => {
                            console.warn('Erreur de chargement du logo:', resto.logo);
                            pendingLoads--;
                            if (pendingLoads === 0) doPrint();
                        };
                        divLogo.appendChild(img);
                        wrap.appendChild(divLogo);
                    }

                    // --- Restaurant info ---
                    const infoDiv = document.createElement('div');
                    infoDiv.innerHTML = `
                        <div class="center bold">${escapeHtml(resto.nom)}</div>
                        ${resto.adresse ? `<div class="center small">${escapeHtml(resto.adresse)}</div>` : ''}
                        ${resto.telephone ? `<div class="center small">${escapeHtml(resto.telephone)}</div>` : ''}
                        <div class="center small">${escapeHtml(tableName)}</div>
                        <div class="center small">Commande #${commandeId}</div>
                        <div class="center small">${new Date().toLocaleString('fr-FR')}</div>
                        <div class="hr"></div>
                    `;
                    wrap.appendChild(infoDiv);

                    // --- Ticket items ---
                    let total = 0;
                    const wrapText = (txt, n) => {
                        const out = []; let s = String(txt || '');
                        while (s.length > n) { out.push(s.slice(0, n)); s = s.slice(n); }
                        if (s) out.push(s); return out;
                    };
                    ticketForTable.forEach(item => {
                        const qte = Number(item.quantite) || 0;
                        const pu = Number(item.prixttc) || 0;
                        const lineTotal = qte * pu;
                        total += lineTotal;
                        wrapText(item.nom, 22).forEach((line, idx) => {
                            const row = doc.createElement('div'); row.className = 'row';
                            row.innerHTML = `<div>${escapeHtml(line)}</div><div class="qty">${idx === 0 ? qte : ''}</div><div class="price">${idx === 0 ? '€' + lineTotal.toFixed(2) : ''}</div>`;
                            wrap.appendChild(row);
                        });
                    });

                    wrap.innerHTML += `
                        <div class="hr"></div>
                        <div class="tot"><div>Total</div><div class="val">€${total.toFixed(2)}</div></div>
                        <div class="hr"></div>
                        <div class="center small">Merci de votre visite !</div>
                        <div class="qr"><canvas id="qr-code" width="100" height="100"></canvas></div>
                    `;

                    if (window.QRious) new QRious({ element: doc.getElementById('qr-code'), value: `CMD:${commandeId}`, size: 100 });

                    // Si pas de chargements en attente, imprimer immédiatement
                    if (pendingLoads === 0) doPrint();
                };
                printTicket();

                // --- Reset local ---
                window.CAISSE.ticket[tableId] = {};
                localStorage.removeItem(`ticket_${tableId}`);
                updateTableVisualState(tableId);
                renderTicket();
            })
            .catch(err => {
                console.error('Erreur fetch', err);
                alert('⚠ Impossible d’enregistrer sur le serveur, brouillon conservé en local.');
            });
    });

    // --- Debug panel ---
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
                        const div = document.createElement('div'); div.style.marginBottom = '5px';
                        div.innerHTML = `<strong>${key.replace('ticket_', 'Table ')}</strong> : ${totalItems} produit(s)`;
                        container.appendChild(div);
                    } catch (e) { localStorage.removeItem(key); }
                }
            });
        };
        setInterval(updatePanel, 1000);
    }
});