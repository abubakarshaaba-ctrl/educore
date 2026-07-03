<style>
.grid{display:grid;gap:16px}.grid-2{grid-template-columns:2fr 1fr}.grid-3{grid-template-columns:repeat(3,1fr)}
.card{background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:16px}
.card-h{padding:13px 16px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--midnight);background:#F8FAFC}
.card-b{padding:16px}.stat{padding:16px;background:white;border:1px solid var(--border);border-radius:10px}
.stat-v{font-size:22px;font-weight:800;color:var(--midnight)}.stat-l{font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.form-row{margin-bottom:12px}.form-row label{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.control{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font-family:inherit;font-size:13px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 13px;border:none;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;font-family:inherit}
.btn-primary{background:var(--indigo);color:white}.btn-light{background:white;color:var(--midnight);border:1px solid var(--border)}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.alert{padding:11px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}.alert-ok{background:#ECFDF5;border:1px solid #A7F3D0;color:var(--emerald)}.alert-bad{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
table{width:100%;border-collapse:collapse}th{font-size:10px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;text-align:left;background:#F8FAFC}th,td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:13px}tr:last-child td{border-bottom:none}
.badge{display:inline-flex;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700}.on{background:#ECFDF5;color:var(--emerald)}.off{background:#FEF2F2;color:var(--crimson)}.muted{color:var(--slate-light)}
@media(max-width:900px){.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
