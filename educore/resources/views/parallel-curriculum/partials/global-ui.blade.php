<style>
/* Parallel Curriculum — EduCore global UI compliance layer.
   Keeps module-specific structure while inheriting the platform's visual scale,
   brand tokens, touch targets and responsive behaviour. */
.pc-shell,
.pc-assign,
.pc-results,
.pc-result,
.pc-score,
.pc-breakdown,
.pcl,
.pco {
    width: 100%;
    max-width: 1280px;
    margin-inline: auto;
    min-width: 0;
    color: var(--midnight);
}

/* Navigation */
.pc-shell .pc-tabs,
.pc-assign .pc-nav,
.pc-results .pc-tabs,
.pc-score .pc-tabs,
.pcl .tabs,
.pco .tabs {
    gap: 8px;
    margin-bottom: 18px;
    padding-bottom: 2px;
    scrollbar-width: thin;
}
.pc-shell .pc-tab,
.pc-assign .pc-nav a,
.pc-results .pc-tab,
.pc-score .pc-tab,
.pcl .tab,
.pco .tab {
    min-height: 40px;
    padding: 9px 14px;
    border-radius: var(--radius);
    font-size: 13px;
    line-height: 1.2;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.pc-shell .pc-tab:focus-visible,
.pc-assign .pc-nav a:focus-visible,
.pc-results .pc-tab:focus-visible,
.pc-score .pc-tab:focus-visible,
.pcl .tab:focus-visible,
.pco .tab:focus-visible {
    outline: 3px solid rgba(215,154,33,.25);
    outline-offset: 2px;
}

/* Hero / context areas */
.pc-shell .pc-hero,
.pc-assign .pc-hero,
.pc-results .hero,
.pcl .hero,
.pco .hero {
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: var(--shadow);
}
.pc-shell .pc-hero h2,
.pc-assign .pc-hero h2,
.pc-results .hero h2,
.pcl .hero h2,
.pco .hero h2 {
    color: #fff;
    font-size: 20px;
    line-height: 1.25;
    font-weight: 800;
    letter-spacing: -.015em;
}
.pc-shell .pc-hero p,
.pc-assign .pc-hero p,
.pc-results .hero p,
.pcl .hero p,
.pco .hero p {
    font-size: 13px;
    line-height: 1.6;
}
.pc-assign .pc-hero .tag {
    font-size: 11.5px;
    line-height: 1.2;
    padding: 6px 10px;
}
.pc-score .context {
    padding: 16px 18px;
    border-radius: 12px;
    box-shadow: var(--shadow);
}
.pc-score .context h2 {
    font-size: 17px;
    line-height: 1.3;
}
.pc-score .context p {
    font-size: 13px;
    line-height: 1.5;
}

/* Cards / panels */
.pc-shell .pc-card,
.pc-assign .pc-panel,
.pc-results .panel,
.pcl .card,
.pco .panel,
.pc-result .card,
.pc-breakdown .card {
    border-radius: 12px;
    box-shadow: var(--shadow);
}
.pc-shell .pc-head,
.pc-assign .pc-head,
.pc-results .head,
.pcl .head,
.pco .head,
.pc-result .head,
.pc-breakdown .head {
    padding: 13px 16px;
    font-size: 13px;
    line-height: 1.35;
}
.pc-shell .pc-body,
.pc-assign .pc-body,
.pc-results .body,
.pcl .body,
.pco .body,
.pc-result .body,
.pc-breakdown .body {
    padding: 16px;
}

/* Forms */
.pc-shell .fl,
.pc-assign .fl,
.pc-results .fl,
.pcl .fl,
.pco .fl {
    font-size: 12px;
    line-height: 1.3;
    font-weight: 700;
}
.pc-shell .fc,
.pc-assign .fc,
.pc-results .fc,
.pcl .fc,
.pco .fc {
    min-height: 42px;
    padding: 9px 11px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.35;
    font-family: inherit;
}
.pc-shell .fc:focus,
.pc-assign .fc:focus,
.pc-results .fc:focus,
.pcl .fc:focus,
.pco .fc:focus,
.pc-score .score:focus {
    outline: 3px solid rgba(215,154,33,.16);
    outline-offset: 1px;
    border-color: var(--indigo);
}

/* Buttons — matches EduCore global .btn scale */
.pc-shell .btn,
.pc-assign .btn,
.pc-results .btn,
.pcl .btn,
.pco .btn,
.pc-result .btn,
.pc-score .btn,
.pc-breakdown .btn {
    min-height: 40px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.2;
    font-weight: 600;
    font-family: inherit;
    transition: background 150ms, border-color 150ms, color 150ms, box-shadow 150ms;
}
.pc-shell .btn-p:hover,
.pc-assign .btn-p:hover,
.pc-results .btn-p:hover,
.pcl .p:hover,
.pco .p:hover,
.pc-result .btn-p:hover,
.pc-score .btn-p:hover {
    background: var(--indigo-dark);
}
.pc-shell .btn:focus-visible,
.pc-assign .btn:focus-visible,
.pc-results .btn:focus-visible,
.pcl .btn:focus-visible,
.pco .btn:focus-visible,
.pc-result .btn:focus-visible,
.pc-score .btn:focus-visible,
.pc-breakdown .btn:focus-visible {
    outline: 3px solid rgba(215,154,33,.25);
    outline-offset: 2px;
}

/* Supporting text */
.pc-shell .hint,
.pc-assign .hint,
.pc-results .hint,
.pcl .hint,
.pco .hint,
.pc-score .hint {
    font-size: 12px;
    line-height: 1.5;
}
.pc-shell .alert-s,
.pc-shell .alert-e,
.pc-assign .alert-s,
.pc-assign .alert-e,
.pc-score .alert-s,
.pc-score .alert-e,
.pc-score .note,
.pc-results .note,
.pc-result .note {
    padding: 11px 13px;
    font-size: 12.5px;
    line-height: 1.5;
}

/* Badges / chips */
.pc-shell .badge,
.pc-assign .badge,
.pc-results .badge,
.pc-result .badge,
.pc-breakdown .badge {
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1.2;
}
.pc-assign .metric,
.pc-results .stat span {
    font-size: 11px;
}
.pc-results .stat {
    min-width: 96px;
    padding: 10px 12px;
}
.pc-results .stat strong {
    font-size: 18px;
}

/* Index workspace cards */
.pc-shell .workspace {
    padding: 15px;
    border-radius: 10px;
    transition: border-color 150ms, background 150ms, box-shadow 150ms;
}
.pc-shell .workspace:hover {
    box-shadow: var(--shadow);
}
.pc-shell .workspace strong {
    font-size: 13.5px;
    line-height: 1.4;
}
.pc-shell .workspace span,
.pc-shell .item-main strong {
    font-size: 12.5px;
    line-height: 1.45;
}
.pc-shell .item-main span,
.pc-shell .checkbox-row {
    font-size: 12px;
    line-height: 1.45;
}
.pc-shell .empty,
.pc-assign .empty,
.pc-results .empty {
    font-size: 13px;
    line-height: 1.5;
}

/* Compact structure editing */
.pc-edit-details{flex:0 0 auto;min-width:132px}
.pc-edit-details summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:7px 10px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--midnight);font-size:12px;font-weight:700;user-select:none}
.pc-edit-details summary::-webkit-details-marker{display:none}
.pc-edit-details[open] summary{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(215,154,33,.12)}
.pc-edit-panel{width:min(520px,72vw);margin-top:8px;padding:12px;border:1px solid var(--border);border-radius:10px;background:#F8FAFC;box-shadow:var(--shadow);position:relative;z-index:2}
.pc-edit-panel .form-row{margin-bottom:0}
.pc-structure-group{padding:10px 0;border-bottom:1px solid #EEF2F7}
.pc-structure-group:last-child{border-bottom:0}
.pc-structure-label{margin:8px 0 4px;font-size:11.5px;font-weight:800;color:var(--slate);text-transform:uppercase;letter-spacing:.035em}
.pc-structure-child{padding:8px 0 8px 12px;border-left:2px solid #EEF2F7}
.pc-structure-child-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap}
.pc-structure-child-title{min-width:0;flex:1}
.pc-structure-child-title strong{display:block;font-size:12.5px;color:var(--midnight);line-height:1.4}
.pc-structure-child-title span{display:block;margin-top:2px;font-size:11.5px;color:var(--slate-light);line-height:1.4}
@media(max-width:640px){
    .pc-edit-details{width:100%;min-width:0}
    .pc-edit-details summary{width:100%;min-height:42px}
    .pc-edit-panel{width:100%}
    .pc-structure-child{padding-left:8px}
}

/* Student assignment workspace */
.pc-assign .students th,
.pc-results .results-table th,
.pc-result .report th,
.pc-score .sheet th {
    padding: 10px 11px;
    font-size: 12px;
    line-height: 1.35;
    letter-spacing: .005em;
}
.pc-assign .students td,
.pc-results .results-table td,
.pc-result .report td,
.pc-score .sheet td {
    padding: 10px 11px;
    font-size: 13px;
    line-height: 1.4;
}
.pc-assign .students .name,
.pc-results .name,
.pc-result .report .subject,
.pc-score .student {
    font-size: 13px;
    line-height: 1.4;
}
.pc-assign .selection-tools button {
    font-size: 12px;
}
.pc-assign .selection-count {
    font-size: 12px;
}
.pc-assign .student-card {
    padding: 13px;
}
.pc-assign .student-card .name {
    font-size: 13.5px;
}
.pc-assign .student-card .meta {
    font-size: 12px;
    line-height: 1.5;
}

/* Score entry */
.pc-score .adm {
    font-size: 11.5px;
    line-height: 1.4;
}
.pc-score .score {
    min-height: 38px;
    width: 74px;
    padding: 7px;
    font-size: 13px;
}
.pc-score .lock-note {
    font-size: 11px;
    line-height: 1.35;
}

/* Result detail */
.pc-result .field {
    padding: 11px 12px;
}
.pc-result .field span {
    font-size: 11.5px;
    line-height: 1.3;
}
.pc-result .field strong {
    font-size: 13px;
    line-height: 1.45;
}
.pc-result .metric {
    padding: 12px;
}
.pc-result .metric strong {
    font-size: 18px;
}
.pc-result .metric span {
    font-size: 11.5px;
    line-height: 1.35;
}

/* Composite breakdown */
.pc-breakdown .hero h2 {
    font-size: 20px;
    line-height: 1.3;
}
.pc-breakdown .hero p,
.pc-breakdown .message {
    font-size: 12.5px;
    line-height: 1.55;
}
.pc-breakdown .stat span {
    font-size: 11px;
    line-height: 1.3;
}
.pc-breakdown .stat strong {
    font-size: 17px;
}
.pc-breakdown .row {
    padding: 11px 0;
    font-size: 13px;
    line-height: 1.45;
}
.pc-breakdown .row > div:first-child > div {
    font-size: 11.5px !important;
    line-height: 1.45;
}

/* Large-screen readability: prevent the module from looking miniature. */
@media (min-width: 1440px) {
    .pc-shell,
    .pc-assign,
    .pc-results,
    .pc-score {
        max-width: 1360px;
    }
    .pc-shell .pc-hero h2,
    .pc-assign .pc-hero h2,
    .pc-results .hero h2,
    .pcl .hero h2,
    .pco .hero h2 {
        font-size: 21px;
    }
    .pc-shell .fc,
    .pc-assign .fc,
    .pc-results .fc,
    .pc-shell .btn,
    .pc-assign .btn,
    .pc-results .btn,
    .pcl .btn,
    .pco .btn,
    .pc-result .btn,
    .pc-score .btn,
    .pc-breakdown .btn {
        font-size: 13.5px;
    }
    .pc-assign .students td,
    .pc-results .results-table td,
    .pc-result .report td,
    .pc-score .sheet td {
        font-size: 13.5px;
    }
}

/* Tablet and mobile */
@media (max-width: 768px) {
    .pc-shell .pc-hero,
    .pc-assign .pc-hero,
    .pc-results .hero,
    .pcl .hero,
    .pco .hero {
        padding: 16px;
    }
    .pc-shell .pc-hero h2,
    .pc-assign .pc-hero h2,
    .pc-results .hero h2 {
        font-size: 18px;
    }
    .pc-shell .pc-hero p,
    .pc-assign .pc-hero p,
    .pc-results .hero p,
    .pcl .hero p,
    .pco .hero p {
        font-size: 12.5px;
    }
    .pc-shell .pc-body,
    .pc-assign .pc-body,
    .pc-results .body,
    .pcl .body,
    .pco .body,
    .pc-result .body,
    .pc-breakdown .body {
        padding: 13px;
    }
    .pc-shell .pc-tab,
    .pc-assign .pc-nav a,
    .pc-results .pc-tab,
    .pc-score .pc-tab,
    .pcl .tab,
    .pco .tab,
    .pc-shell .btn,
    .pc-assign .btn,
    .pc-results .btn,
    .pc-result .btn,
    .pc-score .btn,
    .pc-breakdown .btn,
    .pc-shell .fc,
    .pc-assign .fc,
    .pc-results .fc,
    .pcl .fc,
    .pco .fc {
        min-height: 44px;
    }
    .pc-assign .student-check {
        width: 20px;
        height: 20px;
    }
    .pc-score .score {
        min-height: 42px;
        width: 76px;
    }
}

@media (max-width: 768px) {
    .pc-shell .pc-tabs,
    .pc-assign .pc-nav,
    .pc-results .pc-tabs,
    .pc-score .pc-tabs,
    .pcl .tabs,
    .pco .tabs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
        overflow: visible;
        gap: 8px;
        margin-inline: 0;
    }
    .pc-shell .pc-tab,
    .pc-assign .pc-nav a,
    .pc-results .pc-tab,
    .pc-score .pc-tab,
    .pcl .tab,
    .pco .tab {
        width: 100%;
        min-width: 0;
        white-space: normal;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .pc-shell .pc-tabs,
    .pc-assign .pc-nav,
    .pc-results .pc-tabs,
    .pc-score .pc-tabs,
    .pcl .tabs,
    .pco .tabs {
        margin-inline: 0;
    }
    .pc-result .toolbar,
    .pc-score .context {
        align-items: stretch;
    }
    .pc-result .toolbar > .btn,
    .pc-result .actions,
    .pc-result .actions .btn,
    .pc-score .context .btn {
        width: 100%;
    }
    .pc-results .actions form,
    .pc-results .actions .btn {
        width: 100%;
    }
}

/* Progressive disclosure and dense-workspace controls */
.pc-section-toolbar{
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
    margin:0 0 14px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#F8FAFC;
}
.pc-section-toolbar .pc-toolbar-copy{min-width:0;flex:1}
.pc-section-toolbar .pc-toolbar-copy strong{display:block;font-size:12.5px;color:var(--midnight)}
.pc-section-toolbar .pc-toolbar-copy span{display:block;margin-top:2px;font-size:11.5px;line-height:1.45;color:var(--slate)}
.pc-section-toolbar .pc-toolbar-actions{display:flex;gap:7px;flex-wrap:wrap}
.pc-collapse-toggle{
    flex:0 0 auto;min-height:34px;padding:6px 10px;border:1px solid var(--border);border-radius:8px;
    background:#fff;color:var(--midnight);font:700 11.5px/1.2 inherit;cursor:pointer;
}
.pc-collapse-toggle:hover{border-color:var(--indigo);background:#F8FAFF}
.pc-collapsible-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
[data-collapsible-item].is-collapsed{box-shadow:none}
[data-collapsible-item].is-collapsed > .pc-head,
[data-collapsible-item].is-collapsed > .head{border-bottom:0}
.pc-workspace-filter{
    display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;margin-bottom:12px;
}
.pc-workspace-filter input{width:100%;min-height:42px;border:1px solid var(--border);border-radius:8px;padding:9px 11px;font:500 13px inherit}
.pc-workspace-filter .pc-filter-count{font-size:11.5px;font-weight:700;color:var(--slate);white-space:nowrap}

@media(max-width:640px){
    .pc-section-toolbar{align-items:stretch}
    .pc-section-toolbar .pc-toolbar-copy{flex-basis:100%}
    .pc-section-toolbar .pc-toolbar-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}
    .pc-section-toolbar .pc-toolbar-actions .btn{width:100%}
    .pc-collapse-toggle{min-height:40px}
    .pc-collapsible-head{align-items:flex-start}
    .pc-workspace-filter{grid-template-columns:1fr}
    .pc-workspace-filter .pc-filter-count{white-space:normal}
}

/* Operations tables become readable cards on phones instead of forcing horizontal scroll. */
@media(max-width:640px){
    .pco .att-table-wrap{overflow:visible;border:0}
    .pco table.att{min-width:0;width:100%;display:block}
    .pco table.att thead{display:none}
    .pco table.att tbody{display:grid;gap:10px}
    .pco table.att tr{display:block;border:1px solid var(--border);border-radius:10px;background:#fff;overflow:hidden}
    .pco table.att td{display:grid;grid-template-columns:minmax(92px,38%) minmax(0,1fr);gap:8px;align-items:center;padding:9px 10px;border-bottom:1px solid #EEF2F7}
    .pco table.att td:last-child{border-bottom:0}
    .pco table.att td::before{font-size:10.5px;font-weight:800;color:var(--slate)}
    .pco table.staff-att-table td:nth-child(1)::before{content:'Staff'}
    .pco table.staff-att-table td:nth-child(2)::before{content:'Arrival'}
    .pco table.staff-att-table td:nth-child(3)::before{content:'Status'}
    .pco table.staff-att-table td:nth-child(4)::before{content:'Departure'}
    .pco table.staff-att-table td:nth-child(5)::before{content:'Departure status'}
    .pco table.learner-att-table td:nth-child(1)::before{content:'Learner'}
    .pco table.learner-att-table td:nth-child(2)::before{content:'Status'}
    .pco table.learner-att-table td:nth-child(3)::before{content:'Remark'}
    .pco table.att .fc{min-width:0;width:100%}
}

</style>