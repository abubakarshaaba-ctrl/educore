{{-- Shared base styles for the standalone parent portal pages.
     Included BEFORE each page's own <style> so page-specific component rules
     (nav variants, badges, tables) still cascade on top. Only the genuinely
     identical boilerplate lives here — divergent components stay per-page. --}}
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
    --indigo:#D79A21;--emerald:#059669;--amber:#D97706;--crimson:#DC2626;
    --midnight:#071E45;--slate:#475569;--border:#E2E8F0;--bg:#F4F6FA;
}
body{
    font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;
    background:var(--bg);color:var(--midnight);min-height:100vh;
}
</style>
