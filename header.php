<?php require_once __DIR__ . '/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <link rel="icon" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0b1220">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{
      --bg:#0b1220; --panel:#111a2b; --panel-2:#16243c; --line:#263a59;
      --muted:#8fa7c2; --text:#e6eef8; --accent:#4ea4ff; --accent-2:#2f8dff;
      --success-bg:#0f2c23; --success-br:#1f6f4a; --success-tx:#86f3c9;
      --danger-bg:#2d1519;  --danger-br:#7a2230;  --danger-tx:#ffbac4;
      --warn-bg:#2c240f;    --warn-br:#6b5420;    --warn-tx:#ffd88a;
      --chip:#213454; --chip-br:#36517b; --chip-tx:#cfe0f8;
    }
    body{background:var(--bg); color:var(--text);}
    header{background:linear-gradient(180deg,#0f1a2e 0%, #0c1526 100%); border-bottom:1px solid var(--line);}
    .card{ background:var(--panel); border:1px solid var(--line); border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,.3); }
    a{ color:inherit; }
    .muted{ color:var(--muted); }
    .brand-lockup{ display:flex; align-items:center; gap:.75rem; min-width:0; }
    .brand-lockup img{ width:2.25rem; height:2.25rem; border-radius:10px; }
    .brand-title{ display:flex; flex-direction:column; line-height:1.1; }
    .brand-title strong{ font-size:1rem; }
    .brand-title span{ color:var(--muted); font-size:.72rem; }
    .top-nav{ display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; justify-content:flex-end; }
    .top-nav a{ padding:.45rem .6rem; border-radius:10px; color:#dbe8fb; }
    .top-nav a:hover{ background:var(--panel-2); }
    .mobile-tabbar{ display:none; position:fixed; left:.75rem; right:.75rem; bottom:.75rem; z-index:20; grid-template-columns:repeat(5, minmax(0,1fr)); gap:.25rem; padding:.4rem; border:1px solid var(--line); border-radius:18px; background:rgba(11,18,32,.94); backdrop-filter:blur(14px); box-shadow:0 16px 40px rgba(0,0,0,.38); }
    .mobile-tabbar a{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.15rem; min-height:3.1rem; border-radius:14px; color:#dbe8fb; font-size:.68rem; }
    .mobile-tabbar a:hover{ background:var(--panel-2); }
    .mobile-tabbar .tab-ico{ font-size:1.05rem; line-height:1; }

    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.65rem .85rem; min-height:2.6rem; border-radius:12px; border:1px solid var(--line); }
    .btn-primary{ background:var(--accent); color:#041224; border-color:transparent; }
    .btn-primary:hover{ background:var(--accent-2); }
    .btn-ghost{ background:var(--panel-2); color:var(--text); }
    .btn-ghost:hover{ background:#1a2a44; }
    .btn:disabled{ opacity:.65; cursor:not-allowed; }

    input,textarea,select{ background:var(--panel-2); color:var(--text); border:1px solid var(--line); border-radius:12px; padding:.75rem .85rem; min-height:2.75rem; }
    input::placeholder, textarea::placeholder{ color:var(--muted); }

    .table-wrap{ overflow-x:hidden; } table{ width:100%; border-collapse:separate; border-spacing:0; table-layout:auto; }
    thead th{ position:sticky; top:0; z-index:1; background:#102039; color:#cfe0f8; font-weight:600; border-bottom:1px solid var(--line); white-space:nowrap; }
    tbody tr{ border-top:1px solid var(--line); } tbody tr:hover{ background:#11213a; }
    th,td{ padding:.85rem .9rem; vertical-align:middle; }
    td.actions, th.actions{ text-align:right; white-space:nowrap; }

    .tag,.pill,.badge{ display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; padding:.28rem .55rem; border-radius:10px; border:1px solid var(--chip-br); background:var(--chip); color:var(--chip-tx); margin:.15rem .25rem .15rem 0; }
    .badge-success{ background:var(--success-bg); color:var(--success-tx); border-color:var(--success-br); }
    .badge-danger { background:var(--danger-bg);  color:var(--danger-tx);  border-color:var(--danger-br); }
    .badge-warn   { background:var(--warn-bg);    color:var(--warn-tx);    border-color:var(--warn-br); }
    code{ background:var(--chip); color:var(--chip-tx); border:1px solid var(--chip-br); padding:.18rem .4rem; border-radius:8px; }

    .live-dot{ width:10px; height:10px; border-radius:999px; display:inline-block; margin-right:.5rem; border:1px solid #2a3d60; vertical-align:middle;}
    .live-ok { background:#11c770; border-color:#0f7f4b; }
    .live-bad{ background:#ff5c6f; border-color:#a8313b; }
    .live-unk{ background:#344a6e; }

    .device-grid{ display:grid; grid-template-columns:repeat(10, minmax(0, 1fr)); gap:.8rem; }
    .device-tile{
      position:relative;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:space-between;
      min-height:9.5rem;
      width:100%;
      padding:.9rem .5rem .7rem;
      border:1px solid var(--line);
      border-radius:18px;
      background:linear-gradient(180deg, #16243c 0%, #101a2e 100%);
      box-shadow:0 10px 24px rgba(0,0,0,.2);
      cursor:grab;
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .device-tile:hover,
    .device-tile:focus-visible{
      transform:translateY(-2px);
      border-color:#3d5f91;
      background:linear-gradient(180deg, #1a2a44 0%, #13203a 100%);
      box-shadow:0 14px 28px rgba(0,0,0,.28);
      outline:none;
    }
    .device-tile.is-selected{
      border-color:var(--accent);
      box-shadow:0 0 0 1px rgba(78,164,255,.35), 0 16px 32px rgba(0,0,0,.32);
    }
    .device-tile.is-dragging{
      opacity:.55;
      cursor:grabbing;
      transform:scale(.98);
    }
    .device-tile.drag-over{
      border-color:#7ab9ff;
      box-shadow:0 0 0 2px rgba(122,185,255,.28), 0 16px 32px rgba(0,0,0,.32);
    }
    .device-tile-name{
      min-height:2.5rem;
      width:100%;
      text-align:center;
      font-size:.82rem;
      line-height:1.15rem;
      font-weight:600;
      color:var(--text);
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
    .device-tile-ip{
      position:absolute;
      top:.55rem;
      left:50%;
      transform:translate(-50%, -8px);
      opacity:0;
      pointer-events:none;
      white-space:nowrap;
      z-index:2;
      font-size:.7rem;
      line-height:1;
      padding:.28rem .45rem;
      border-radius:999px;
      color:#dce8fb;
      background:rgba(4,18,36,.96);
      border:1px solid #36517b;
      box-shadow:0 6px 16px rgba(0,0,0,.24);
      transition:opacity .18s ease, transform .18s ease;
    }
    .device-tile:hover .device-tile-ip,
    .device-tile:focus-visible .device-tile-ip{
      opacity:1;
      transform:translate(-50%, 0);
    }
    .device-tile-icon{
      display:flex;
      align-items:center;
      justify-content:center;
      width:3.75rem;
      height:3.75rem;
      border-radius:18px;
      font-size:2rem;
      background:rgba(78,164,255,.12);
      border:1px solid rgba(78,164,255,.18);
      box-shadow:inset 0 1px 0 rgba(255,255,255,.03);
      overflow:hidden;
    }
    .device-tile-icon-fallback{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:100%;
      height:100%;
    }
    .device-tile-icon.has-image{
      background:rgba(10,16,28,.8);
      border-color:rgba(78,164,255,.24);
    }
    .device-tile-icon.has-image .device-tile-icon-fallback{
      display:none;
    }
    .device-tile-icon-image{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .status-indicator{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:1rem;
      color:var(--muted);
      font-size:.72rem;
    }
    .status-indicator-dot{
      width:11px;
      height:11px;
      border-radius:999px;
      border:1px solid #35507a;
      background:#344a6e;
      box-shadow:0 0 0 4px rgba(52,74,110,.12);
      transition:background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .status-indicator.is-online .status-indicator-dot{
      background:#11c770;
      border-color:#0f7f4b;
      box-shadow:0 0 0 4px rgba(17,199,112,.14);
    }
    .status-indicator.is-offline .status-indicator-dot{
      background:#ff5c6f;
      border-color:#a8313b;
      box-shadow:0 0 0 4px rgba(255,92,111,.12);
    }
    .status-indicator.is-checking .status-indicator-dot{
      background:#f2c86a;
      border-color:#8c6a20;
      box-shadow:0 0 0 4px rgba(242,200,106,.14);
    }

    .empty-panel{
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:13rem;
      text-align:center;
      color:var(--muted);
      border:1px dashed #36517b;
      border-radius:16px;
      background:rgba(22,36,60,.45);
      padding:1.5rem;
    }
    .auth-shell{ min-height:calc(100vh - 10rem); display:grid; place-items:start center; gap:.65rem; padding:4.5rem 0 1.5rem; }
    .auth-brand{ display:flex; align-items:center; gap:1rem; width:min(100%, 28rem); }
    .auth-brand h1{ font-size:2rem; font-weight:800; line-height:1; }
    .auth-brand p{ color:var(--muted); margin-top:.35rem; }
    .auth-logo{ width:4rem; height:4rem; border-radius:18px; box-shadow:0 14px 30px rgba(0,0,0,.28); }
    .auth-card{ width:min(100%, 28rem); padding:1.25rem; display:grid; gap:1rem; }
    label span{ display:block; color:var(--muted); font-size:.86rem; margin-bottom:.35rem; }
    .check-row{ display:flex; align-items:center; gap:.65rem; color:var(--muted); }
    .check-row input{ width:1.1rem; height:1.1rem; min-height:auto; }
    .check-row span{ margin:0; }
    .auth-submit{ width:100%; }
    .auth-links{ display:flex; align-items:center; justify-content:space-between; gap:1rem; color:var(--muted); font-size:.9rem; }
    .notice{ padding:.85rem 1rem; border-radius:14px; border:1px solid var(--line); }
    .notice-info{ background:rgba(78,164,255,.12); color:#d7eaff; border-color:rgba(78,164,255,.3); }
    .notice-danger{ background:var(--danger-bg); color:var(--danger-tx); border-color:var(--danger-br); }
    .page-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .page-head h1{ font-size:1.75rem; font-weight:800; line-height:1.1; }
    .page-head p{ color:var(--muted); margin-top:.25rem; }
    .settings-grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:1rem; }
    .settings-card{ padding:1.25rem; display:grid; gap:1rem; }
    .settings-card h2{ font-size:1.15rem; font-weight:700; }
    .stack{ display:grid; gap:1rem; }
    .danger-text{ color:var(--danger-tx); }
    .qr-panel{ display:grid; gap:.75rem; justify-items:start; }
    .qr-panel img{ width:13rem; max-width:100%; border-radius:16px; background:white; padding:.75rem; }
    .version-grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.85rem; }
    .version-grid div{ padding:.9rem; border:1px solid var(--line); border-radius:14px; background:var(--panel-2); }
    .version-grid span{ display:block; color:var(--muted); font-size:.76rem; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.3rem; }
    .version-grid strong{ word-break:break-word; }
    .details-shell{ display:grid; gap:1rem; }
    .detail-header{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:1rem;
      flex-wrap:wrap;
    }
    .detail-hero{ display:flex; align-items:center; gap:1rem; }
    .detail-hero-icon{
      display:flex;
      align-items:center;
      justify-content:center;
      width:4.25rem;
      height:4.25rem;
      border-radius:20px;
      font-size:2.2rem;
      background:rgba(78,164,255,.12);
      border:1px solid rgba(78,164,255,.18);
      overflow:hidden;
    }
    .detail-hero-icon-fallback{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:100%;
      height:100%;
    }
    .detail-hero-icon.has-image{
      background:rgba(10,16,28,.8);
    }
    .detail-hero-icon.has-image .detail-hero-icon-fallback{
      display:none;
    }
    .detail-hero-icon-image{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .detail-title{ font-size:1.3rem; font-weight:700; line-height:1.2; }
    .detail-subtitle{ color:var(--muted); margin-top:.2rem; }
    .detail-grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.85rem; }
    .detail-field{
      padding:.95rem 1rem;
      border-radius:16px;
      border:1px solid var(--line);
      background:var(--panel-2);
    }
    .detail-label{
      font-size:.72rem;
      letter-spacing:.08em;
      text-transform:uppercase;
      color:var(--muted);
      margin-bottom:.4rem;
    }
    .detail-value{ color:var(--text); word-break:break-word; }
    .detail-value-empty{ color:var(--muted); }
    .detail-actions{ display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .status-summary{
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:.5rem;
      text-align:center;
      width:100%;
    }
    .status-summary .badge{ margin:0; justify-content:center; }
    .port-list{ display:grid; grid-template-columns:repeat(auto-fit, minmax(9rem, 1fr)); gap:.75rem; }
    .port-entry{
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:flex-start;
      gap:.45rem;
      padding:.8rem;
      border:1px solid var(--line);
      border-radius:14px;
      background:rgba(11,18,32,.22);
      text-align:center;
    }
    .port-entry .btn{ width:100%; justify-content:center; }
    .port-entry .badge{ margin:0; justify-content:center; }
    .detail-note{
      border:1px solid var(--line);
      border-radius:16px;
      background:var(--panel-2);
      padding:1rem;
      color:var(--text);
      white-space:pre-wrap;
    }

    @media (max-width: 1280px){
      .device-grid{ grid-template-columns:repeat(8, minmax(0, 1fr)); }
    }
    @media (max-width: 1024px){
      .device-grid{ grid-template-columns:repeat(6, minmax(0, 1fr)); }
    }
    @media (max-width: 768px){
      .device-grid{ grid-template-columns:repeat(4, minmax(0, 1fr)); }
      .detail-grid{ grid-template-columns:1fr; }
      .top-nav{ display:none; }
      .mobile-tabbar{ display:grid; }
      main{ padding-bottom:6.25rem; }
      .settings-grid,.version-grid{ grid-template-columns:1fr; }
      .page-head{ align-items:flex-start; flex-direction:column; }
    }
    @media (max-width: 520px){
      .device-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
      .device-tile{ min-height:8.8rem; }
      .detail-hero{ align-items:flex-start; }
      .auth-shell{ place-items:start stretch; gap:.65rem; padding-top:1.25rem; }
      .auth-brand,.auth-card{ width:100%; }
      .detail-actions{ width:100%; }
      .detail-actions .btn{ flex:1 1 auto; }
    }
  </style>
</head>
<body class="min-h-screen">
  <header>
    <div class="mx-auto px-4 py-3 flex items-center justify-between" style="max-width:90rem">
      <a class="brand-lockup" href="index.php">
        <img src="assets/icons/netventory-192.png" alt="">
        <span class="brand-title"><strong><?= h($SITE_NAME) ?></strong><span>Network inventory</span></span>
      </a>
      <nav class="top-nav text-sm">
        <?php if (is_logged_in()): ?>
          <a href="index.php">Home</a>
          <a href="add.php">Add</a>
          <a href="options.php">Options</a>
          <a href="import.php">Import</a>
          <a href="export.php">Export</a>
          <a href="account.php">Account</a>
          <a href="version.php">Version</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.php">Login</a>
          <?php if ($REGISTRATION_OPEN): ?><a href="register.php">Register</a><?php endif; ?>
          <a href="version.php">Version</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <main class="mx-auto px-4 py-6" style="max-width:90rem">
