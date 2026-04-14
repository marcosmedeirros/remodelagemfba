/**
 * History.js - Visualização do Histórico de Temporadas
 * Usa as classes CSS do novo design system (season-card, award-chip, etc.)
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('historyContainer');
    const league = container?.dataset?.league || 'ELITE';
    loadHistory(league);
});

async function loadHistory(league) {
    const container = document.getElementById('historyContainer');

    try {
        container.innerHTML = `
            <div style="text-align:center;padding:48px 16px;color:var(--text-3);">
                <div class="spinner-border" style="color:var(--red);"></div>
                <p style="margin-top:10px;font-size:13px;">Carregando histórico...</p>
            </div>
        `;

        const response = await fetch(`/api/history-points.php?action=get_history&league=${encodeURIComponent(league)}`);
        const data = await response.json();

        if (!data.success) throw new Error(data.error || 'Erro ao carregar histórico');

        const history = data.history[league] || [];

        if (history.length === 0) {
            container.innerHTML = `
                <div style="text-align:center;padding:48px 16px;color:var(--text-3);">
                    <i class="bi bi-clock-history" style="font-size:36px;display:block;margin-bottom:12px;"></i>
                    <div style="font-size:15px;font-weight:700;color:var(--text-2);">Nenhum histórico registrado</div>
                    <p style="font-size:13px;margin-top:6px;">O histórico da liga ${league.toUpperCase()} aparecerá aqui após ser registrado.</p>
                </div>
            `;
            return;
        }

        const awardsConfig = [
            { key: 'champion',    label: 'Campeão',    icon: 'bi-trophy-fill',       color: 'gold',   teamKey: 'champion_team_name' },
            { key: 'runner_up',   label: 'Vice',        icon: 'bi-award-fill',        color: 'silver', teamKey: 'runner_up_team_name' },
            { key: 'mvp',         label: 'MVP',         icon: 'bi-star-fill',         color: 'amber',  teamKey: 'mvp_team_name' },
            { key: 'dpoy',        label: 'DPOY',        icon: 'bi-shield-fill',       color: 'blue',   teamKey: 'dpoy_team_name' },
            { key: 'sixth_man',   label: '6º Homem',    icon: 'bi-person-fill-add',   color: 'purple', teamKey: 'sixth_man_team_name' },
            { key: 'roy',         label: 'ROY',         icon: 'bi-person-up',         color: 'red',    teamKey: 'roy_team_name' },
            { key: 'mip',         label: 'MIP',         icon: 'bi-graph-up-arrow',    color: 'green',  teamKey: 'mip_team_name' },
        ];

        // Map field names: API returns e.g. champion_name, mvp_player
        const valueKey = {
            champion:  'champion_name',
            runner_up: 'runner_up_name',
            mvp:       'mvp_player',
            dpoy:      'dpoy_player',
            sixth_man: 'sixth_man_player',
            roy:       'roy_player',
            mip:       'mip_player',
        };

        let html = '';

        history.forEach(season => {
            const yearBadge = season.year ? `<span class="season-year-badge">${season.year}</span>` : '';

            const awards = awardsConfig
                .filter(a => season[valueKey[a.key]])
                .map(a => {
                    const name = season[valueKey[a.key]];
                    const team = season[a.teamKey] || '';
                    return `
                    <div class="award-chip">
                        <div class="award-icon ${a.color}">
                            <i class="bi ${a.icon}" style="color:inherit;"></i>
                        </div>
                        <div>
                            <div class="award-label ${a.color}">${a.label}</div>
                            <div class="award-name">${name}</div>
                            ${team ? `<div class="award-team">${team}</div>` : ''}
                        </div>
                    </div>`;
                }).join('');

            const draftBtn = season.has_draft_history
                ? `<div class="season-foot">
                       <button class="btn-ghost-sm" onclick="viewDraftHistory(${season.season_id})">
                           <i class="bi bi-list-ol"></i> Ver Draft
                       </button>
                   </div>`
                : '';

            html += `
            <div class="season-card">
                <div class="season-head">
                    <div class="season-head-left">
                        <div class="season-icon"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i></div>
                        <div>
                            <div class="season-title">Sprint ${season.sprint_number} — Temporada ${season.season_number}</div>
                            <div class="season-sub">Liga ${league.toUpperCase()}</div>
                        </div>
                    </div>
                    ${yearBadge}
                </div>
                <div class="season-body">
                    ${awards ? `<div class="awards-grid">${awards}</div>` : '<div style="font-size:13px;color:var(--text-3);font-style:italic;">Nenhum prêmio registrado.</div>'}
                </div>
                ${draftBtn}
            </div>`;
        });

        container.innerHTML = html;

    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
        container.innerHTML = `
            <div style="padding:24px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);border-radius:12px;color:#f87171;font-size:13px;">
                <i class="bi bi-exclamation-triangle" style="margin-right:6px;"></i>
                Erro ao carregar histórico: ${error.message}
            </div>
        `;
    }
}

async function viewDraftHistory(seasonId) {
    try {
        const response = await fetch(`/api/draft.php?action=draft_history&season_id=${seasonId}`);
        const data = await response.json();

        if (!data.success) throw new Error(data.error || 'Erro ao carregar histórico do draft');

        const order = data.draft_order || [];

        if (order.length === 0) {
            alert('Nenhum registro de draft encontrado para esta temporada.');
            return;
        }

        const rows = order.map(p => `
            <tr>
                <td>
                    <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;
                        background:${p.round === 1 ? 'var(--red-soft)' : 'var(--panel-3)'};
                        color:${p.round === 1 ? 'var(--red)' : 'var(--text-2)'};">
                        R${p.round} #${p.pick_position}
                    </span>
                </td>
                <td style="font-weight:600;color:var(--text);">${p.player_name || '—'}</td>
                <td>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:var(--red-soft);color:var(--red);">
                        ${p.player_position || '—'}
                    </span>
                </td>
                <td style="color:var(--text-2);">${p.player_ovr || '—'}</td>
                <td style="color:var(--text-2);">
                    ${p.team_city} ${p.team_name}
                    ${p.traded_from_city ? `<span style="font-size:11px;color:var(--text-3);"> (via ${p.traded_from_city})</span>` : ''}
                </td>
            </tr>
        `).join('');

        const modalHtml = `
            <div class="modal fade" id="draftHistoryModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content" style="background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);">
                            <h5 class="modal-title" style="font-family:var(--font);font-weight:700;color:var(--text);">
                                <i class="bi bi-list-ol" style="color:var(--red);margin-right:8px;"></i>
                                Draft — Temporada ${data.season?.season_number || ''}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding:20px;">
                            <div style="overflow-x:auto;">
                                <table style="width:100%;border-collapse:collapse;font-family:var(--font);font-size:13px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border);">
                                            <th style="padding:8px 12px;color:var(--text-3);font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Pick</th>
                                            <th style="padding:8px 12px;color:var(--text-3);font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Jogador</th>
                                            <th style="padding:8px 12px;color:var(--text-3);font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Pos</th>
                                            <th style="padding:8px 12px;color:var(--text-3);font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">OVR</th>
                                            <th style="padding:8px 12px;color:var(--text-3);font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody style="color:var(--text);">
                                        ${rows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border);">
                            <button type="button" class="btn-ghost-sm" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existing = document.getElementById('draftHistoryModal');
        if (existing) existing.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('draftHistoryModal')).show();

    } catch (error) {
        console.error('Erro:', error);
        alert(error.message || 'Erro ao carregar histórico do draft');
    }
}
