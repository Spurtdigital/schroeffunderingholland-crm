<style>
    /* ------------------------------------------------------------------
       Admin-uitstraling: strak & vlak (à la Filament demo)
       - witte sidebar met scheidingslijn
       - kaarten zonder schaduw, dunne rand, kleine radius
       - actief menu-item met oranje accentlijn links
       ------------------------------------------------------------------ */

    :root {
        --sfh-radius: 0.75rem;
        --sfh-radius-sm: 0.5rem;
        --sfh-border: var(--gray-200);
    }

    .dark {
        --sfh-border: color-mix(in oklab, white 10%, transparent);
    }

    /* Achtergrond */
    .fi-body {
        background-color: var(--gray-100);
    }

    .dark .fi-body {
        background-color: var(--gray-950);
    }

    /* Sidebar ------------------------------------------------------------ */
    @media (min-width: 64rem) {
        .fi-sidebar {
            background-color: white;
            border-inline-end: 1px solid var(--sfh-border);
        }

        .dark .fi-sidebar {
            background-color: var(--gray-900);
        }
    }

    .fi-sidebar-header {
        box-shadow: none !important;
        border-bottom: 0;
    }

    .fi-sidebar-group-label {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--gray-500);
    }

    .fi-sidebar-group + .fi-sidebar-group,
    .fi-sidebar-nav-groups > li + li {
        border-top: 1px solid var(--sfh-border);
        padding-top: 1rem;
    }

    .fi-sidebar-item-btn {
        border-radius: var(--sfh-radius-sm);
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--gray-100);
        box-shadow: inset 2px 0 0 var(--primary-500);
    }

    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: color-mix(in oklab, white 5%, transparent);
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: var(--primary-600);
    }

    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label,
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: var(--primary-400);
    }

    /* Topbar ------------------------------------------------------------- */
    .fi-topbar > nav,
    .fi-topbar {
        box-shadow: none !important;
    }

    .fi-topbar {
        background-color: white;
        border-bottom: 1px solid var(--sfh-border);
    }

    .dark .fi-topbar {
        background-color: var(--gray-900);
    }

    /* Kaarten: secties, tabellen, widgets, modals --------------------------- */
    .fi-section:not(.fi-section-not-contained),
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-wi-chart .fi-section,
    .fi-fo-repeater-item,
    .fi-in-repeatable-item,
    .fi-tabs:not(.fi-contained),
    .fi-dropdown-panel,
    .fi-modal-window {
        border-radius: var(--sfh-radius) !important;
        box-shadow:
            0 0 0 1px var(--sfh-border),
            0 1px 2px 0 rgb(0 0 0 / 0.04) !important;
    }

    /* Tabel: kop-/toolbar-balk en rijen duidelijk van elkaar te onderscheiden */
    .fi-ta-header-toolbar,
    .fi-ta-header {
        background-color: white;
    }

    /* Bovenste element in de tabel-container volgt de afgeronde hoeken */
    .fi-ta-ctn > :first-child,
    .fi-ta-ctn > :first-child > :first-child,
    .fi-ta-header-ctn > :first-child,
    .fi-ta-ctn > :first-child .fi-ta-content-ctn:first-child,
    .fi-ta-ctn > :first-child .fi-ta-content-ctn:first-child .fi-ta-table > thead > tr:first-child {
        border-top-left-radius: var(--sfh-radius);
        border-top-right-radius: var(--sfh-radius);
    }

    .fi-ta-ctn > :first-child .fi-ta-table > thead > tr:first-child > :first-child {
        border-top-left-radius: var(--sfh-radius);
    }

    .fi-ta-ctn > :first-child .fi-ta-table > thead > tr:first-child > :last-child {
        border-top-right-radius: var(--sfh-radius);
    }

    .dark .fi-ta-header-toolbar,
    .dark .fi-ta-header {
        background-color: transparent;
    }

    .fi-ta-table > thead > tr {
        background-color: var(--gray-50);
    }

    .fi-ta-table > thead {
        border-bottom: 1px solid var(--sfh-border);
    }

    .fi-ta-table > tbody > tr {
        background-color: white;
    }

    .fi-ta-table > tbody > tr:nth-child(even) {
        background-color: color-mix(in oklab, var(--gray-50) 60%, white);
    }

    .fi-ta-table > tbody > tr:hover {
        background-color: color-mix(in oklab, var(--primary-50) 70%, white);
    }

    .dark .fi-ta-table > thead > tr {
        background-color: color-mix(in oklab, white 4%, transparent);
    }

    .dark .fi-ta-table > tbody > tr {
        background-color: transparent;
    }

    .dark .fi-ta-table > tbody > tr:nth-child(even) {
        background-color: color-mix(in oklab, white 2%, transparent);
    }

    .dark .fi-ta-table > tbody > tr:hover {
        background-color: color-mix(in oklab, var(--primary-500) 8%, transparent);
    }

    .fi-modal-window,
    .fi-dropdown-panel {
        box-shadow: 0 10px 30px -10px rgb(0 0 0 / 0.15) !important;
    }

    .fi-section-header {
        padding-block: 1rem;
    }

    .fi-section-header-heading {
        font-weight: 600;
    }

    /* Formulier-elementen & knoppen --------------------------------------- */
    .fi-input-wrp,
    .fi-btn,
    .fi-icon-btn,
    .fi-tabs-item,
    .fi-pagination-items,
    .fi-fo-file-upload .filepond--panel-root {
        border-radius: var(--sfh-radius-sm) !important;
    }

    /* Primary knoppen: oranje achtergrond, witte tekst (licht én donker) */
    .fi-btn.fi-color-primary:not(.fi-outlined):not(label) {
        --bg: var(--primary-500) !important;
        --hover-bg: var(--primary-600) !important;
        --dark-bg: var(--primary-500) !important;
        --dark-hover-bg: var(--primary-600) !important;
        --text: white !important;
        --hover-text: white !important;
        --dark-text: white !important;
        --dark-hover-text: white !important;
        font-weight: 600;
    }

    /* Invoervelden: duidelijke grijze rand, oranje focus met zachte gloed */
    .fi-input-wrp {
        background-color: white;
        box-shadow: 0 0 0 1px var(--gray-300) !important;
    }

    .fi-input-wrp:not(.fi-disabled):hover {
        box-shadow: 0 0 0 1px var(--gray-400) !important;
    }

    .fi-input-wrp:not(.fi-disabled):focus-within {
        box-shadow:
            0 0 0 1px var(--primary-500),
            0 0 0 4px color-mix(in oklab, var(--primary-500) 20%, transparent) !important;
    }

    .fi-input-wrp.fi-disabled {
        background-color: var(--gray-50);
        box-shadow: 0 0 0 1px var(--gray-200) !important;
    }

    .fi-input-wrp.fi-invalid,
    .fi-input-wrp.fi-invalid:hover {
        box-shadow: 0 0 0 1px var(--danger-600) !important;
    }

    .fi-input-wrp.fi-invalid:focus-within {
        box-shadow:
            0 0 0 1px var(--danger-600),
            0 0 0 4px color-mix(in oklab, var(--danger-600) 20%, transparent) !important;
    }

    .dark .fi-input-wrp {
        background-color: color-mix(in oklab, white 5%, transparent);
        box-shadow: 0 0 0 1px color-mix(in oklab, white 15%, transparent) !important;
    }

    .dark .fi-input-wrp:not(.fi-disabled):hover {
        box-shadow: 0 0 0 1px color-mix(in oklab, white 25%, transparent) !important;
    }

    .dark .fi-input-wrp:not(.fi-disabled):focus-within {
        box-shadow:
            0 0 0 1px var(--primary-400),
            0 0 0 4px color-mix(in oklab, var(--primary-400) 25%, transparent) !important;
    }

    .dark .fi-input-wrp.fi-disabled {
        background-color: transparent;
        box-shadow: 0 0 0 1px color-mix(in oklab, white 10%, transparent) !important;
    }

    /* Checkbox / radio / toggle in primary */
    .fi-checkbox-input:checked,
    .fi-radio-input:checked {
        background-color: var(--primary-500);
        border-color: var(--primary-500);
    }

    .fi-checkbox-input:focus-visible,
    .fi-radio-input:focus-visible {
        --tw-ring-color: color-mix(in oklab, var(--primary-500) 40%, transparent);
    }

    /* Badges: klein en hoekig ---------------------------------------------- */
    .fi-badge {
        font-weight: 500;
    }

    /* Tabel-koppen subtieler ------------------------------------------------ */
    .fi-ta-header-cell {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--gray-500);
    }
</style>
