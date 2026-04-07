{{-- HR Module Modern Styles --}}
<style>
    :root {
        --hr-primary: #6366f1;
        --hr-success: #22c55e;
        --hr-warning: #f59e0b;
        --hr-danger: #ef4444;
        --hr-info: #0ea5e9;
        --hr-bg: #f8fafc;
        --hr-card: #ffffff;
        --hr-border: #cbd5e1;
        --hr-text: #1e293b;
        --hr-muted: #64748b;
    }

    .page-header {
        margin-bottom: 28px;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--hr-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title i {
        color: var(--hr-primary);
    }

    .page-subtitle {
        color: var(--hr-muted);
        font-size: 0.9rem;
        margin-top: 4px;
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: var(--hr-card);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--hr-border);
        transition: all 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 12px;
    }

    .stat-card.primary .stat-icon {
        background: #eef2ff;
        color: var(--hr-primary);
    }

    .stat-card.success .stat-icon {
        background: #dcfce7;
        color: var(--hr-success);
    }

    .stat-card.warning .stat-icon {
        background: #fef3c7;
        color: var(--hr-warning);
    }

    .stat-card.info .stat-icon {
        background: #e0f2fe;
        color: var(--hr-info);
    }

    .stat-card.danger .stat-icon {
        background: #fee2e2;
        color: var(--hr-danger);
    }

    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--hr-text);
    }

    .stat-card .stat-label {
        font-size: 0.8rem;
        color: var(--hr-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Main Card Container */
    .hr-card {
        background: var(--hr-card);
        border-radius: 16px;
        border: 1px solid var(--hr-border);
        overflow: hidden;
    }

    .hr-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--hr-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        max-width: 350px;
        flex: 1;
    }

    .search-box input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid var(--hr-border);
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: #f8fafc;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--hr-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        background: white;
    }

    .search-box i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--hr-muted);
    }

    .btn-create {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        color: white;
    }

    /* Cards Grid */
    .hr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 20px;
        padding: 24px;
    }

    /* Stats Row in Grid */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    /* HR Navbar Hover Effect */
    @media (min-width: 992px) {
        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
            margin-top: 0 !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        .nav-item.dropdown .dropdown-menu {
            display: block;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
    }

    .hr-item-card {
        background: var(--hr-card);
        border: 1px solid var(--hr-border);
        border-radius: 14px;
        padding: 20px;
        transition: all 0.2s;
    }

    .hr-item-card:hover {
        border-color: var(--hr-primary);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.12);
        transform: translateY(-2px);
    }

    .hr-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .hr-avatar {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: 700;
        color: white;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }

    .hr-item-info {
        flex: 1;
        margin-left: 14px;
    }

    .hr-item-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--hr-text);
        margin: 0;
    }

    .hr-item-subtitle {
        font-size: 0.85rem;
        color: var(--hr-muted);
        margin-top: 2px;
    }

    .hr-item-meta {
        font-size: 0.75rem;
        color: var(--hr-muted);
        margin-top: 4px;
    }

    .hr-actions {
        display: flex;
        gap: 6px;
    }

    .hr-actions .btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s;
    }

    .hr-actions .btn-view {
        background: #e0f2fe;
        color: var(--hr-info);
        border: none;
    }

    .hr-actions .btn-view:hover {
        background: var(--hr-info);
        color: white;
    }

    .hr-actions .btn-edit {
        background: #fef3c7;
        color: var(--hr-warning);
        border: none;
    }

    .hr-actions .btn-edit:hover {
        background: var(--hr-warning);
        color: white;
    }

    .hr-actions .btn-delete {
        background: #fee2e2;
        color: var(--hr-danger);
        border: none;
    }

    .hr-actions .btn-delete:hover {
        background: var(--hr-danger);
        color: white;
    }

    /* Tags */
    .hr-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    .hr-tag {
        font-size: 0.75rem;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 500;
    }

    .hr-tag.success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }

    .hr-tag.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .hr-tag.warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .hr-tag.info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: white;
    }

    .hr-tag.default {
        background: #f1f5f9;
        color: var(--hr-text);
        border: 1px solid var(--hr-border);
    }

    /* Modal Styling */
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        max-width: 100%;
        overflow-x: hidden;
    }

    .modal-header.gradient {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 24px 28px;
        border: none;
    }

    .modal-header.gradient .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-header.gradient .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-body {
        padding: 24px;
        background: #ffffff;
        overflow-x: hidden;
    }

    .form-group-modern {
        margin-bottom: 20px;
    }

    .form-group-modern .form-label {
        font-weight: 600;
        color: var(--hr-text);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .form-group-modern .form-label i {
        color: var(--hr-primary);
        font-size: 0.9rem;
    }

    .form-group-modern .form-control,
    .form-group-modern .form-select {
        border: 1px solid var(--hr-border) !important;
        border-radius: 8px !important;
        padding: 10px 14px !important;
        font-size: 0.95rem !important;
        transition: all 0.2s ease;
        background: #f8fafc !important;
        box-shadow: none !important;
        height: auto !important;
    }

    .form-group-modern .form-control:focus,
    .form-group-modern .form-select:focus {
        border-color: var(--hr-primary) !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
        background: #ffffff !important;
    }

    .modal-footer-modern {
        padding: 20px 28px;
        background: #f8fafc;
        border-top: 1px solid var(--hr-border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: var(--hr-text);
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
    }

    .btn-save {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        color: white;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--hr-muted);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        color: #cbd5e1;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }

        .hr-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .stats-row {
            grid-template-columns: 1fr;
        }

        .hr-header {
            flex-direction: column;
        }

        .search-box {
            max-width: 100%;
        }
    }

    /* Bootstrap 5 Utility Polyfills for Bootstrap 4 Template */
    .gap-1 {
        gap: 0.25rem !important;
    }

    .gap-2 {
        gap: 0.5rem !important;
    }

    .gap-3 {
        gap: 1rem !important;
    }

    .gap-4 {
        gap: 1.5rem !important;
    }

    .ms-1 {
        margin-left: 0.25rem !important;
    }

    .ms-2 {
        margin-left: 0.5rem !important;
    }

    .ms-3 {
        margin-left: 1rem !important;
    }

    .ms-4 {
        margin-left: 1.5rem !important;
    }

    .ms-auto {
        margin-left: auto !important;
    }

    .me-1 {
        margin-right: 0.25rem !important;
    }

    .me-2 {
        margin-right: 0.5rem !important;
    }

    .me-3 {
        margin-right: 1rem !important;
    }

    .me-4 {
        margin-right: 1.5rem !important;
    }

    .me-auto {
        margin-right: auto !important;
    }

    .ps-1 {
        padding-left: 0.25rem !important;
    }

    .ps-2 {
        padding-left: 0.5rem !important;
    }

    .ps-3 {
        padding-left: 1rem !important;
    }

    .pe-1 {
        padding-right: 0.25rem !important;
    }

    .pe-2 {
        padding-right: 0.5rem !important;
    }

    .pe-3 {
        padding-right: 1rem !important;
    }

    .btn-close {
        box-sizing: content-box;
        width: 1em;
        height: 1em;
        padding: .25em .25em;
        color: #000;
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        border: 0;
        border-radius: .25rem;
        opacity: .5;
        cursor: pointer;
    }

    .btn-close:hover {
        color: #000;
        text-decoration: none;
        opacity: .75;
    }

    .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Modern UI Resets (Overrides for global style.css theme breakages) */
    .card {
        border-radius: 12px !important;
        border: 1px solid var(--hr-border) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
    }

    .card-header {
        border-radius: 11px 11px 0 0 !important;
        padding: 16px 20px !important;
        border-bottom: 1px solid var(--hr-border) !important;
        font-weight: 600 !important;
    }

    .alert {
        border-radius: 8px !important;
        border: none !important;
        padding: 12px 16px !important;
    }

    .alert-info {
        background: #e0f2fe !important;
        color: #0369a1 !important;
        border-left: 4px solid #0284c7 !important;
    }

    .alert-success {
        background: #dcfce7 !important;
        color: #166534 !important;
        border-left: 4px solid #15803d !important;
    }

    .alert-danger {
        background: #fee2e2 !important;
        color: #991b1b !important;
        border-left: 4px solid #b91c1c !important;
    }

    .alert-warning {
        background: #fef3c7 !important;
        color: #92400e !important;
        border-left: 4px solid #b45309 !important;
    }

    .alert-light {
        background: #f8fafc !important;
        color: #334155 !important;
        border-left: 4px solid #cbd5e1 !important;
        border: 1px solid #e2e8f0 !important;
    }

    .bg-primary {
        background: #3b82f6 !important;
    }

    .bg-success {
        background: #10b981 !important;
    }

    .bg-danger {
        background: #ef4444 !important;
    }

    .bg-warning {
        background: #f59e0b !important;
        color: #fff !important;
    }

    .bg-info {
        background: #0ea5e9 !important;
    }

    .bg-secondary {
        background: #64748b !important;
    }

    .bg-dark {
        background: #1e293b !important;
    }
</style>
