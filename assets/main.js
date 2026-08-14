'use strict';

function log(...args) {
  try { console.log('[netventory]', ...args); } catch (error) {}
}

async function getJSON(url) {
  const response = await fetch(url, { credentials: 'same-origin' });
  if (!response.ok) throw new Error(`HTTP ${response.status} for ${url}`);
  const text = await response.text();
  try { return JSON.parse(text); }
  catch {
    throw new Error(`Non-JSON from ${url}: ${text.slice(0, 120)}...`);
  }
}

async function pingIp(ip) {
  return getJSON(`api/ping.php?ip=${encodeURIComponent(ip)}`);
}

async function checkPort(ip, port) {
  return getJSON(`api/check_port.php?ip=${encodeURIComponent(ip)}&port=${port}`);
}

async function checkIp(ip, ports) {
  if (!Array.isArray(ports) || ports.length === 0) return { ip, results: [] };
  return getJSON(`api/bulk_check.php?ip=${encodeURIComponent(ip)}&ports=${ports.join(',')}`);
}

const ESCAPE_MAP = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
};

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ESCAPE_MAP[char]);
}

function parseHost(tile) {
  try { return JSON.parse(tile.dataset.host || '{}'); }
  catch { return {}; }
}

function emptyValue(text = 'Not set') {
  return `<span class="detail-value-empty">${escapeHtml(text)}</span>`;
}

function renderField(label, valueHtml) {
  return `
    <div class="detail-field">
      <div class="detail-label">${escapeHtml(label)}</div>
      <div class="detail-value">${valueHtml}</div>
    </div>
  `;
}

function normalizePorts(host) {
  return Array.isArray(host.ports) ? host.ports : [];
}

function portNumbers(host) {
  return normalizePorts(host)
    .map((port) => Number(port.port))
    .filter((port) => Number.isFinite(port) && port > 0);
}

const hostState = new Map();
const hostById = new Map();
const tileById = new Map();
let hostTiles = [];
let selectedHostId = null;
let detailBody = null;
let deviceGrid = null;
let overviewStatusMessage = null;
let draggedTile = null;
let suppressTileClick = false;
let overviewStatusTimer = null;

function ensureState(hostId) {
  if (!hostState.has(hostId)) {
    hostState.set(hostId, {
      checking: true,
      reachable: null,
      pingMs: null,
      pingError: '',
      portResults: [],
      portError: '',
    });
  }
  return hostState.get(hostId);
}

function findPortResult(state, port) {
  return (state.portResults || []).find((item) => Number(item.port) === Number(port));
}

function hasOpenPorts(state) {
  return (state.portResults || []).some((item) => item.ok);
}

function tileStatusClass(state) {
  if (state.checking) return 'is-checking';
  return state.reachable ? 'is-online' : 'is-offline';
}

function tileStatusTitle(state) {
  if (state.checking) return 'Checking status';
  if (state.reachable) {
    return state.pingMs != null ? `Online (${state.pingMs}ms)` : 'Online';
  }
  if (state.pingError) return state.pingError;
  return 'Offline';
}

function setTileIndicator(tile, state) {
  const indicator = tile.querySelector('[data-tile-status]');
  if (!indicator) return;
  indicator.className = `status-indicator ${tileStatusClass(state)}`;
  indicator.title = tileStatusTitle(state);
}

function renderTags(tags) {
  if (!Array.isArray(tags) || tags.length === 0) return emptyValue('No tags');
  return tags.map((tag) => `<span class="tag">#${escapeHtml(tag)}</span>`).join('');
}

function renderStatusSummary(state) {
  const parts = [];
  if (state.checking) {
    parts.push('<span class="badge badge-warn">Checking...</span>');
  } else if (state.reachable) {
    parts.push(`<span class="badge badge-success">Online${state.pingMs != null ? ` • ${escapeHtml(state.pingMs)}ms` : ''}</span>`);
  } else {
    parts.push(`<span class="badge badge-danger">Offline${state.pingMs != null ? ` • ${escapeHtml(state.pingMs)}ms` : ''}</span>`);
  }

  if (state.portResults.length) {
    const open = state.portResults.filter((result) => result.ok).length;
    const closed = state.portResults.length - open;
    parts.push(`<span class="badge ${open ? 'badge-success' : 'badge-danger'}">${open} open • ${closed} closed</span>`);
  }

  if (state.portError) {
    parts.push(`<span class="badge badge-warn">${escapeHtml(state.portError)}</span>`);
  }

  if (state.pingError && !state.reachable) {
    parts.push(`<span class="badge badge-warn">${escapeHtml(state.pingError)}</span>`);
  }

  return parts.join('');
}

function renderPorts(host, state) {
  const ports = normalizePorts(host);
  if (!ports.length) return emptyValue('No ports configured');

  return `
    <div class="port-list">
      ${ports.map((port) => {
        const result = findPortResult(state, port.port);
        const label = port.label ? ` • ${escapeHtml(port.label)}` : '';
        const resultBadge = result
          ? `<span class="badge ${result.ok ? 'badge-success' : 'badge-danger'}">${result.ok ? 'Open' : 'Closed'}${result.latency_ms != null ? ` • ${escapeHtml(result.latency_ms)}ms` : ''}</span>`
          : '';

        return `
          <div class="port-entry">
            <button
              type="button"
              class="btn btn-ghost text-xs"
              data-action="check-port"
              data-host-id="${escapeHtml(host.id)}"
              data-port="${escapeHtml(port.port)}"
            >
              ${escapeHtml(port.port)}${label}
            </button>
            ${resultBadge}
          </div>
        `;
      }).join('')}
    </div>
  `;
}

function renderNotes(notes) {
  if (!notes) return '<span class="detail-value-empty">No notes.</span>';
  return escapeHtml(notes);
}

function renderHostIcon(host, baseClass = 'detail-hero-icon') {
  const fallback = escapeHtml(host?.fallback_icon || '🧩');
  const iconUrl = String(host?.icon_url || '').trim();

  if (iconUrl) {
    return `
      <div class="${baseClass} has-image" aria-hidden="true">
        <img class="${baseClass}-image" src="${escapeHtml(iconUrl)}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.closest('div').classList.remove('has-image'); this.remove();">
        <span class="${baseClass}-fallback">${fallback}</span>
      </div>
    `;
  }

  return `
    <div class="${baseClass}" aria-hidden="true">
      <span class="${baseClass}-fallback">${fallback}</span>
    </div>
  `;
}

function syncHostTilesFromDom() {
  hostTiles = deviceGrid ? Array.from(deviceGrid.querySelectorAll('.device-tile')) : [];
}

function setOverviewStatus(message, { isError = false, autoReset = false } = {}) {
  if (!overviewStatusMessage) return;

  overviewStatusMessage.textContent = message;
  overviewStatusMessage.style.color = isError ? 'var(--danger-tx)' : 'var(--muted)';

  if (overviewStatusTimer) {
    window.clearTimeout(overviewStatusTimer);
    overviewStatusTimer = null;
  }

  if (autoReset) {
    overviewStatusTimer = window.setTimeout(() => {
      overviewStatusMessage.textContent = 'Drag and drop tiles to reorder them. Changes save automatically.';
      overviewStatusMessage.style.color = 'var(--muted)';
      overviewStatusTimer = null;
    }, 2400);
  }
}

async function saveTileOrder() {
  if (!deviceGrid) return;

  const csrf = deviceGrid.dataset.csrf || '';
  const orderedIds = hostTiles.map((tile) => tile.dataset.hostId).filter(Boolean);
  if (!csrf || !orderedIds.length) return;

  const body = new URLSearchParams();
  body.set('csrf', csrf);
  orderedIds.forEach((hostId) => body.append('ordered_ids[]', hostId));

  setOverviewStatus('Saving layout...');

  const response = await fetch('api/reorder_hosts.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
    },
    body: body.toString(),
  });

  if (!response.ok) throw new Error(`HTTP ${response.status}`);

  const payload = await response.json();
  if (!payload?.ok) throw new Error(payload?.error || 'Unable to save order');

  setOverviewStatus('Layout saved.', { autoReset: true });
}

function clearDragState() {
  hostTiles.forEach((tile) => tile.classList.remove('drag-over', 'is-dragging'));
  draggedTile = null;
}

function moveDraggedTile(targetTile, event) {
  if (!deviceGrid || !draggedTile || !targetTile || targetTile === draggedTile) return false;

  const rect = targetTile.getBoundingClientRect();
  const insertBefore = event.clientX < rect.left + (rect.width / 2);

  if (insertBefore) {
    if (draggedTile.nextElementSibling === targetTile) return false;
    deviceGrid.insertBefore(draggedTile, targetTile);
  } else {
    if (targetTile.nextElementSibling === draggedTile) return false;
    deviceGrid.insertBefore(draggedTile, targetTile.nextElementSibling);
  }

  syncHostTilesFromDom();
  return true;
}

function renderDetails(host, tile) {
  if (!detailBody || !host) return;

  const state = ensureState(host.id);
  const editUrl = tile?.dataset.editUrl || '#';
  const deleteUrl = tile?.dataset.deleteUrl || '#';

  detailBody.innerHTML = `
    <div class="details-shell">
      <div class="detail-header">
        <div class="detail-hero">
          ${renderHostIcon(host)}
          <div>
            <div class="detail-title">${escapeHtml(host.name || 'Unnamed host')}</div>
            <div class="detail-subtitle"><code>${escapeHtml(host.ip || '')}</code></div>
          </div>
        </div>

        <div class="detail-actions">
          <button type="button" class="btn btn-primary" data-action="check-ip" data-host-id="${escapeHtml(host.id)}">Check host</button>
          <a class="btn btn-ghost" href="${escapeHtml(editUrl)}">Edit</a>
          <a class="btn btn-ghost" style="color:#ffbac4" href="${escapeHtml(deleteUrl)}" onclick="return confirm('Delete this item?')">Delete</a>
        </div>
      </div>

      <div class="detail-grid">
        ${renderField('IP Address', host.ip ? `<code>${escapeHtml(host.ip)}</code>` : emptyValue())}
        ${renderField('MAC Address', host.mac ? `<code>${escapeHtml(host.mac)}</code>` : emptyValue())}
        ${renderField('Type', host.type ? escapeHtml(host.type) : emptyValue())}
        ${renderField('Operating System', host.os ? escapeHtml(host.os) : emptyValue())}
        ${renderField('Status', `<div class="status-summary">${renderStatusSummary(state)}</div>`)}
        ${renderField('Tags', renderTags(host.tags))}
      </div>

      <div class="detail-field">
        <div class="detail-label">Ports</div>
        <div class="detail-value">${renderPorts(host, state)}</div>
      </div>

      <div>
        <div class="detail-label">Notes</div>
        <div class="detail-note">${renderNotes(host.notes)}</div>
      </div>
    </div>
  `;
}

function renderSelectedHost() {
  if (!selectedHostId) return;
  const host = hostById.get(selectedHostId);
  const tile = tileById.get(selectedHostId);
  renderDetails(host, tile);
}

async function refreshTileReachability(tile) {
  const hostId = tile.dataset.hostId;
  const host = hostById.get(hostId);
  if (!host) return;

  const state = ensureState(hostId);
  state.checking = true;
  state.pingError = '';
  setTileIndicator(tile, state);
  if (selectedHostId === hostId) renderSelectedHost();

  try {
    const response = await pingIp(host.ip);
    state.pingMs = response.rtt_ms ?? null;
    state.pingError = response.ok ? '' : (response.error || 'Host unreachable');
    state.reachable = Boolean(response.ok) || hasOpenPorts(state);
  } catch (error) {
    console.error(error);
    state.pingMs = null;
    state.pingError = 'Ping failed';
    state.reachable = hasOpenPorts(state);
  } finally {
    state.checking = false;
    setTileIndicator(tile, state);
    if (selectedHostId === hostId) renderSelectedHost();
  }
}

async function checkHostDetails(hostId) {
  const host = hostById.get(hostId);
  const tile = tileById.get(hostId);
  if (!host || !tile) return;

  const state = ensureState(hostId);
  state.checking = true;
  state.pingError = '';
  state.portError = '';
  setTileIndicator(tile, state);
  if (selectedHostId === hostId) renderSelectedHost();

  const ports = portNumbers(host);
  const requests = [pingIp(host.ip)];
  if (ports.length) requests.push(checkIp(host.ip, ports));

  const [pingResult, portResult] = await Promise.allSettled(requests);

  if (pingResult?.status === 'fulfilled') {
    state.pingMs = pingResult.value.rtt_ms ?? null;
    state.pingError = pingResult.value.ok ? '' : (pingResult.value.error || 'Host unreachable');
    state.reachable = Boolean(pingResult.value.ok);
  } else {
    console.error(pingResult?.reason);
    state.pingMs = null;
    state.pingError = 'Ping failed';
    state.reachable = false;
  }

  if (ports.length) {
    if (portResult?.status === 'fulfilled') {
      state.portResults = Array.isArray(portResult.value.results) ? portResult.value.results : [];
    } else {
      console.error(portResult?.reason);
      state.portResults = [];
      state.portError = 'Bulk port check failed';
    }
  } else {
    state.portResults = [];
  }

  if (hasOpenPorts(state)) state.reachable = true;

  state.checking = false;
  setTileIndicator(tile, state);
  if (selectedHostId === hostId) renderSelectedHost();
}

async function checkSinglePort(hostId, port) {
  const host = hostById.get(hostId);
  const tile = tileById.get(hostId);
  if (!host || !tile) return;

  const state = ensureState(hostId);
  state.portError = '';
  state.checking = true;
  setTileIndicator(tile, state);
  if (selectedHostId === hostId) renderSelectedHost();

  try {
    const result = await checkPort(host.ip, port);
    const next = (state.portResults || []).filter((item) => Number(item.port) !== Number(port));
    next.push(result);
    next.sort((left, right) => Number(left.port) - Number(right.port));
    state.portResults = next;
    if (result.ok) state.reachable = true;
  } catch (error) {
    console.error(error);
    state.portError = `Port ${port} check failed`;
  } finally {
    state.checking = false;
    setTileIndicator(tile, state);
    if (selectedHostId === hostId) renderSelectedHost();
  }
}

function selectTile(tile, { runCheck = true } = {}) {
  const hostId = tile.dataset.hostId;
  const state = ensureState(hostId);
  selectedHostId = hostId;

  hostTiles.forEach((item) => item.classList.toggle('is-selected', item === tile));
  renderSelectedHost();

  if (runCheck && !state.checking) void checkHostDetails(hostId);
}

async function refreshVisibleStatuses() {
  await Promise.allSettled(hostTiles.map((tile) => checkHostDetails(tile.dataset.hostId)));
}

document.addEventListener('DOMContentLoaded', () => {
  log('main.js loaded');

  detailBody = document.getElementById('hostDetailsBody');
  deviceGrid = document.getElementById('deviceGrid');
  overviewStatusMessage = document.getElementById('overviewStatusMessage');
  syncHostTilesFromDom();

  hostTiles.forEach((tile) => {
    const host = parseHost(tile);
    if (!host.id) return;
    hostById.set(host.id, host);
    tileById.set(host.id, tile);
    setTileIndicator(tile, ensureState(host.id));
  });

  if (hostTiles.length) void refreshVisibleStatuses();
});

document.addEventListener('click', async (event) => {
  const tile = event.target.closest('.device-tile');
  if (tile) {
    if (suppressTileClick) {
      suppressTileClick = false;
      return;
    }
    selectTile(tile);
    return;
  }

  const button = event.target.closest('button');
  if (!button) return;

  if (button.matches('[data-action="check-port"]')) {
    const hostId = button.dataset.hostId;
    const port = Number(button.dataset.port);
    if (!hostId || !Number.isFinite(port)) return;

    button.disabled = true;
    try {
      await checkSinglePort(hostId, port);
    } finally {
      button.disabled = false;
    }
    return;
  }

  if (button.matches('[data-action="check-ip"]')) {
    const hostId = button.dataset.hostId;
    if (!hostId) return;

    button.disabled = true;
    try {
      await checkHostDetails(hostId);
    } finally {
      button.disabled = false;
    }
    return;
  }

  if (button.id === 'checkAllBtn') {
    button.disabled = true;
    try {
      await refreshVisibleStatuses();
    } finally {
      button.disabled = false;
    }
  }
});

document.addEventListener('dragstart', (event) => {
  const tile = event.target.closest('.device-tile');
  if (!tile || !deviceGrid) return;

  draggedTile = tile;
  suppressTileClick = false;
  tile.classList.add('is-dragging');

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', tile.dataset.hostId || '');
  }
});

document.addEventListener('dragover', (event) => {
  if (!draggedTile || !deviceGrid) return;

  const grid = event.target.closest('#deviceGrid');
  if (!grid) return;

  event.preventDefault();

  const targetTile = event.target.closest('.device-tile');
  hostTiles.forEach((tile) => tile.classList.toggle('drag-over', tile === targetTile && tile !== draggedTile));

  if (targetTile) {
    moveDraggedTile(targetTile, event);
    return;
  }

  if (grid === deviceGrid && draggedTile !== deviceGrid.lastElementChild) {
    deviceGrid.appendChild(draggedTile);
    syncHostTilesFromDom();
  }
});

document.addEventListener('drop', async (event) => {
  if (!draggedTile || !deviceGrid) return;

  const grid = event.target.closest('#deviceGrid');
  if (!grid) return;

  event.preventDefault();
  suppressTileClick = true;
  hostTiles.forEach((tile) => tile.classList.remove('drag-over'));

  try {
    await saveTileOrder();
  } catch (error) {
    console.error(error);
    setOverviewStatus('Layout could not be saved.', { isError: true, autoReset: true });
  } finally {
    clearDragState();
  }
});

document.addEventListener('dragend', () => {
  if (!draggedTile && !hostTiles.length) return;
  hostTiles.forEach((tile) => tile.classList.remove('drag-over', 'is-dragging'));
  draggedTile = null;
});
