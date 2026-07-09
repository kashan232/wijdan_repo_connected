<style>
/* Period Closing & Archive — Professional ERP UI */
.pc-page {
    padding: 1.25rem 0 2.5rem;
    background: #f4f6f9;
    min-height: calc(100vh - 70px);
}

/* ── Page banner ── */
.pc-hero {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e8ecf1;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
    position: relative;
    overflow: hidden;
}
.pc-hero::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #0d6efd, #0a58ca);
    border-radius: 12px 0 0 12px;
}
.pc-hero::after { display: none; }
.pc-hero-content { position: relative; z-index: 1; }
.pc-hero h2 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.35rem;
    letter-spacing: -0.02em;
}
.pc-hero h2 i { color: #0d6efd; }
.pc-hero p {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 0;
    max-width: 680px;
}
.pc-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 0.3rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.6rem;
}
.pc-hero .btn-outline-secondary {
    border-color: #cbd5e1;
    color: #475569;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.45rem 1rem;
    border-radius: 8px;
}
.pc-hero .btn-outline-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

/* ── Cards ── */
.pc-card {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}
.pc-card-header {
    padding: 1rem 1.35rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid #eef2f6;
    background: #fafbfc;
}
.pc-card-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: #0f172a;
}
.pc-card-header .pc-icon-wrap {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.pc-card-header.settings .pc-icon-wrap { background: #dbeafe; color: #1d4ed8; }
.pc-card-header.close-action .pc-icon-wrap { background: #fee2e2; color: #dc2626; }
.pc-card-header.history .pc-icon-wrap { background: #ede9fe; color: #7c3aed; }
.pc-card-header.archive .pc-icon-wrap { background: #e0f2fe; color: #0369a1; }
.pc-card-body { padding: 1.35rem; }

/* ── Forms ── */
.pc-form-label {
    font-weight: 600;
    font-size: 0.84rem;
    color: #334155;
    margin-bottom: 0.4rem;
}
.pc-form-control {
    border-radius: 8px;
    border: 1px solid #d1d9e6;
    padding: 0.55rem 0.85rem;
    font-size: 0.9rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.pc-form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
}
.pc-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #475569;
    margin: 1.15rem 0 0.85rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.pc-section-title i { color: #0d6efd; font-size: 0.85rem; }

/* ── Buttons ── */
.pc-btn-primary {
    background: #0d6efd;
    border-color: #0d6efd;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.55rem 1.15rem;
}
.pc-btn-primary:hover {
    background: #0b5ed7;
    border-color: #0a58ca;
}
.pc-btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.6rem 1.25rem;
}
.pc-btn-danger:hover {
    background: #bb2d3b;
    border-color: #b02a37;
}

/* ── Info strip ── */
.pc-info-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.1rem 1.35rem;
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}
.pc-info-item label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    display: block;
    margin-bottom: 0.2rem;
}
.pc-info-item span {
    font-weight: 700;
    color: #0f172a;
    font-size: 1.05rem;
}

/* ── Count grid ── */
.pc-count-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0.65rem;
}
.pc-count-chip {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem 0.65rem;
    text-align: center;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.pc-count-chip:hover {
    border-color: #93c5fd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
}
.pc-count-chip .label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.pc-count-chip .value {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin-top: 0.15rem;
    line-height: 1.2;
}
.pc-count-chip.total {
    background: #0f172a;
    border-color: #0f172a;
}
.pc-count-chip.total .label { color: #94a3b8; }
.pc-count-chip.total .value { color: #fff; }

/* ── Alerts ── */
.pc-alert-soft {
    border-radius: 10px;
    padding: 0.9rem 1.1rem;
    font-size: 0.9rem;
}
.pc-alert-soft.warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
}
.pc-alert-soft.info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-left: 4px solid #0d6efd;
}

/* ── Tables ── */
.pc-table-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.pc-table { margin-bottom: 0; }
.pc-table thead th {
    background: #1e293b;
    color: #f1f5f9;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.85rem 0.9rem;
    border: none;
    white-space: nowrap;
    letter-spacing: 0.02em;
}
.pc-table tbody td {
    padding: 0.8rem 0.9rem;
    vertical-align: middle;
    color: #334155;
    font-size: 0.875rem;
    border-color: #f1f5f9;
}
.pc-table tbody tr { transition: background 0.12s; }
.pc-table tbody tr:hover { background: #f8fafc; }

/* ── Period list cards ── */
.pc-period-card {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    height: 100%;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.pc-period-card:hover {
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1);
    transform: translateY(-2px);
}
.pc-period-card-top {
    padding: 1.15rem 1.35rem;
    background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%);
    color: #fff;
}
.pc-period-card-top .period-name {
    font-weight: 700;
    font-size: 1.05rem;
    margin-bottom: 0.4rem;
}
.pc-period-card-top .period-dates {
    font-size: 0.84rem;
    opacity: 0.9;
}
.pc-period-card-body { padding: 1.15rem 1.35rem 1.35rem; }
.pc-period-meta {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-bottom: 1.1rem;
}
.pc-period-meta-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.86rem;
    color: #64748b;
    padding: 0.45rem 0.65rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}
.pc-period-meta-item i { width: 16px; color: #0d6efd; text-align: center; }
.pc-period-meta-item strong { color: #1e293b; }

/* ── Stat boxes ── */
.pc-stat-box {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    padding: 1.25rem 1.35rem;
    height: 100%;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    transition: box-shadow 0.2s;
}
.pc-stat-box:hover { box-shadow: 0 6px 22px rgba(15, 23, 42, 0.08); }
.pc-stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.pc-stat-icon.blue { background: #dbeafe; color: #1d4ed8; }
.pc-stat-icon.slate { background: #f1f5f9; color: #475569; }
.pc-stat-icon.green { background: #dcfce7; color: #15803d; }
.pc-stat-content { flex: 1; min-width: 0; }
.pc-stat-box .stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    margin-bottom: 0.3rem;
}
.pc-stat-box .stat-value {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.pc-stat-box .stat-sub {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 0.2rem;
}

/* ── Module tiles ── */
.pc-module-card {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    height: 100%;
    display: block;
    text-decoration: none !important;
    color: inherit !important;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    overflow: hidden;
}
.pc-module-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 8px 24px rgba(13, 110, 253, 0.12);
    transform: translateY(-2px);
}
.pc-module-card.disabled {
    opacity: 0.55;
    pointer-events: none;
}
.pc-module-inner {
    padding: 1.15rem 1.1rem;
    text-align: center;
}
.pc-module-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: #eff6ff;
    color: #0d6efd;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 0.75rem;
    border: 1px solid #dbeafe;
}
.pc-module-card:hover .pc-module-icon {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
}
.pc-module-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.5rem;
}
.pc-module-count {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 0.55rem;
}
.pc-module-link {
    font-size: 0.78rem;
    font-weight: 600;
    color: #0d6efd;
}
.pc-module-card:hover .pc-module-link { color: #0a58ca; }
.pc-module-footer {
    padding: 0.55rem 1rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    text-align: center;
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
}

/* Legacy module-top/body support */
.pc-module-top { display: none; }
.pc-module-body { display: none; }

/* ── Snapshot cards ── */
.pc-snapshot-card {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    padding: 1.15rem 1.25rem;
    height: 100%;
    border-left: 4px solid #0d6efd;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.pc-snapshot-card.snap-purchase { border-left-color: #059669; }
.pc-snapshot-card.snap-expense { border-left-color: #dc2626; }
.pc-snapshot-card .snap-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    margin-bottom: 0.35rem;
}
.pc-snapshot-card .snap-count {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.pc-snapshot-card .snap-total {
    font-size: 0.9rem;
    font-weight: 700;
    color: #059669;
}

/* ── Empty state ── */
.pc-empty-state {
    text-align: center;
    padding: 3.5rem 2rem;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8ecf1;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
}
.pc-empty-state i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}
.pc-empty-state h5 {
    color: #334155;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

/* ── Archive listing header ── */
.pc-archive-list-header {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    padding: 1.1rem 1.35rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.85rem;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    border-left: 4px solid #0d6efd;
}
.pc-archive-list-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: #0f172a;
}
.pc-archive-list-header small { color: #64748b; font-size: 0.85rem; }
.pc-badge-closed {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    padding: 0.3rem 0.7rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}
.pc-badge-viewer {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 0.35rem 0.8rem;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
}
</style>
