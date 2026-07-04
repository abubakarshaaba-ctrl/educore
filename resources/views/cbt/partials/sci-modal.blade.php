{{-- ═══════════════════════════════════════════════════════════════
     SCIENCE EQUATION BUILDER MODAL
     Shared between cbt/questions.blade.php and cbt/question-edit.blade.php
     Libraries required: KaTeX + mhchem (loaded per-page in @push scripts)
     Tabs: Math | Chemistry | Physics | Biology
════════════════════════════════════════════════════════════════ --}}
<div class="sci-modal-bg" id="sciModal" role="dialog" aria-modal="true" aria-labelledby="sciModalTitle">
<div class="sci-modal">

    <div class="sci-modal-head">
        <h3 id="sciModalTitle">⚗ Science Equation Builder</h3>
        <button class="sci-modal-close" onclick="closeSciModal()" aria-label="Close">&times;</button>
    </div>

    <div class="sci-modal-body">

        {{-- Subject tabs --}}
        <div class="sci-tabs" role="tablist">
            <button class="sci-tab active" data-tab="math"  onclick="switchSciTab('math')"  role="tab">📐 Math</button>
            <button class="sci-tab"        data-tab="chem"  onclick="switchSciTab('chem')"  role="tab">⚗ Chemistry</button>
            <button class="sci-tab"        data-tab="phys"  onclick="switchSciTab('phys')"  role="tab">⚛ Physics</button>
            <button class="sci-tab"        data-tab="bio"   onclick="switchSciTab('bio')"   role="tab">🧬 Biology</button>
        </div>

        {{-- ── MATH TEMPLATES ──────────────────────────────────────── --}}
        <div class="sci-templates active" data-tab="math">
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\frac{a}{b}')">a/b</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\sqrt{x}')">√x</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\sqrt[n]{x}')">ⁿ√x</button>
            <button class="sci-tpl" onclick="insertSciTemplate('x^{n}')">xⁿ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('x_{n}')">xₙ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\int_{a}^{b} f(x)\\\\,dx')">∫ᵇₐ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\sum_{i=1}^{n} x_i')">Σ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\lim_{x \\\\to \\\\infty}')">lim</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\vec{v}')">v⃗ Vector</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\overline{AB}')">AB̄ Segment</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\angle ABC')">∠ABC</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\triangle ABC')">△ABC</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\pi r^2')">πr²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\frac{-b \\\\pm \\\\sqrt{b^2-4ac}}{2a}')">Quadratic formula</button>
            <button class="sci-tpl" onclick="insertSciTemplate('a^2 + b^2 = c^2')">Pythagoras</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\begin{pmatrix} a & b \\\\\\\\ c & d \\\\end{pmatrix}')">Matrix</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\log_{b}(x)')">logₙ(x)</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\sin(\\\\theta)')">sin θ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\cos(\\\\theta)')">cos θ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\tan(\\\\theta)')">tan θ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\binom{n}{r}')">ⁿCᵣ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('n!')">n!</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\infty')">∞</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\pm')">±</button>
        </div>

        {{-- ── CHEMISTRY TEMPLATES ─────────────────────────────────── --}}
        <div class="sci-templates" data-tab="chem">
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{H2O}')">H₂O</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{H2SO4}')">H₂SO₄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{HCl}')">HCl</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{NaOH}')">NaOH</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{CO2}')">CO₂</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{NH3}')">NH₃</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Ca(OH)2}')">Ca(OH)₂</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Na2SO4}')">Na₂SO₄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{A + B -> C + D}')">Reaction →</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{A <=> B}')">Equilibrium ⇌</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{2H2 + O2 -> 2H2O}')">H₂ combustion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{CH4 + 2O2 -> CO2 + 2H2O}')">CH₄ combustion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}')">Neutralisation</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Zn + H2SO4 -> ZnSO4 + H2^{}}')">Zn + H₂SO₄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{CaCO3 ->[\\\\Delta] CaO + CO2}')">Thermal decomp.</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Fe^{3+} + 3OH- -> Fe(OH)3 v}')">Precipitation</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Fe^{2+}}')">Fe²⁺ ion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{Cl-}')">Cl⁻ ion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{[Ag(NH3)2]+}')">Complex ion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{^{14}_{6}C}')">¹⁴₆C Isotope</button>
        </div>

        {{-- ── PHYSICS TEMPLATES ───────────────────────────────────── --}}
        <div class="sci-templates" data-tab="phys">
            <button class="sci-tpl" onclick="insertSciTemplate('F = ma')">F = ma</button>
            <button class="sci-tpl" onclick="insertSciTemplate('E = mc^2')">E = mc²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('v = u + at')">v = u + at</button>
            <button class="sci-tpl" onclick="insertSciTemplate('s = ut + \\\\frac{1}{2}at^2')">s = ut + ½at²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('v^2 = u^2 + 2as')">v² = u² + 2as</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P = \\\\frac{W}{t}')">P = W/t</button>
            <button class="sci-tpl" onclick="insertSciTemplate('V = IR')">V = IR (Ohm)</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P = IV')">P = IV</button>
            <button class="sci-tpl" onclick="insertSciTemplate('W = mg')">W = mg</button>
            <button class="sci-tpl" onclick="insertSciTemplate('KE = \\\\frac{1}{2}mv^2')">KE = ½mv²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('PE = mgh')">PE = mgh</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P = \\\\frac{F}{A}')">P = F/A</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\rho = \\\\frac{m}{V}')">ρ = m/V</button>
            <button class="sci-tpl" onclick="insertSciTemplate('f = \\\\frac{1}{T}')">f = 1/T</button>
            <button class="sci-tpl" onclick="insertSciTemplate('v = f\\\\lambda')">v = fλ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('Q = mc\\\\Delta T')">Q = mcΔT</button>
            <button class="sci-tpl" onclick="insertSciTemplate('F = \\\\frac{Gm_1m_2}{r^2}')">Gravitation</button>
            <button class="sci-tpl" onclick="insertSciTemplate('F = \\\\frac{q_1q_2}{4\\\\pi\\\\varepsilon_0 r^2}')">Coulomb's Law</button>
            <button class="sci-tpl" onclick="insertSciTemplate('n = \\\\frac{\\\\sin i}{\\\\sin r}')">Snell's Law</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\tau = Fr\\\\sin\\\\theta')">Torque τ</button>
        </div>

        {{-- ── BIOLOGY TEMPLATES ───────────────────────────────────── --}}
        <div class="sci-templates" data-tab="bio">
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{6CO2 + 6H2O ->[light] C6H12O6 + 6O2}')">Photosynthesis</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{C6H12O6 + 6O2 -> 6CO2 + 6H2O + ATP}')">Respiration</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\ce{C6H12O6 -> 2C2H5OH + 2CO2}')">Fermentation</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\text{DNA} \\\\xrightarrow{\\\\text{transcription}} \\\\text{mRNA} \\\\xrightarrow{\\\\text{translation}} \\\\text{Protein}')">Central Dogma</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\frac{\\\\text{No. of organisms}}{\\\\text{Area}} = D')">Population Density</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\text{Magnification} = \\\\frac{\\\\text{Image size}}{\\\\text{Actual size}}')">Magnification</button>
            <button class="sci-tpl" onclick="insertSciTemplate('Aa \\\\times Aa \\\\to \\\\frac{1}{4}AA:\\\\frac{1}{2}Aa:\\\\frac{1}{4}aa')">Mendelian ratio</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\text{BMI} = \\\\frac{\\\\text{mass (kg)}}{\\\\text{height}^2 \\\\text{(m}^2\\\\text{)}}')" >BMI formula</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\text{Cardiac output} = \\\\text{SV} \\\\times \\\\text{HR}')">Cardiac output</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\\\text{Hardy-Weinberg: } p^2 + 2pq + q^2 = 1')">Hardy-Weinberg</button>
        </div>

        {{-- LaTeX input area --}}
        <div style="margin-top:10px">
            <div class="sci-preview-label">LaTeX Code</div>
            <div class="sci-mode-row">
                <button class="sci-mode-btn active" data-mode="inline" onclick="setSciMode('inline')">Inline \( ... \)</button>
                <button class="sci-mode-btn" data-mode="block" onclick="setSciMode('block')">Block $$ ... $$</button>
            </div>
            <input type="text" class="sci-input" id="sciLatexInput"
                   placeholder="Type LaTeX here or click a template above, e.g. \frac{a}{b} or \ce{H2O}">
        </div>

        {{-- Live Preview --}}
        <div style="margin-top:12px">
            <div class="sci-preview-label">Live Preview</div>
            <div class="sci-preview" id="sciPreview">
                <span style="color:#94A3B8">Preview will appear as you type</span>
            </div>
        </div>

        <button class="sci-insert-btn" onclick="insertSciEquation()">✓ Insert into Question</button>

        <div style="margin-top:10px;font-size:11px;color:#94A3B8;line-height:1.6">
            <strong>Tips:</strong>
            Math inline: <code>\( E = mc^2 \)</code> ·
            Math block: <code>$$ \int_0^\infty e^{-x}dx $$</code> ·
            Chemistry: <code>\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}</code>
        </div>
    </div>
</div>
</div>
