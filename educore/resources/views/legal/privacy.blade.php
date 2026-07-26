<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore Privacy Policy — How we collect, use, and protect your data.">
<title>Privacy Policy — EduCore</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#071E45;--navy-dark:#040f25;--navy-mid:#0d2a5e;
  --gold:#D79A21;--gold-light:#F2C35B;--gold-pale:#FEF9EC;
  --white:#FFFFFF;--off:#F7F9FC;--slate:#475569;--muted:#94A3B8;--border:#E2E8F0;
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--font);color:var(--navy);background:var(--white);line-height:1.7;overflow-x:hidden}

/* NAV */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:0 5vw;height:68px;display:flex;align-items:center;justify-content:space-between;gap:24px;background:rgba(7,30,69,.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.07)}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
.nav-brand img{width:34px;height:34px;border-radius:8px}
.nav-brand-name{font-size:17px;font-weight:800;color:#fff;letter-spacing:-.02em}
.nav-brand-name span{color:var(--gold)}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-links a{color:rgba(255,255,255,.75);font-size:13px;font-weight:500;padding:7px 13px;border-radius:8px;text-decoration:none;transition:all 150ms}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
.nav-cta{display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;border:none;cursor:pointer;font-family:inherit;transition:all 180ms;white-space:nowrap}
.btn-gold{background:var(--gold);color:var(--navy)}.btn-gold:hover{background:var(--gold-light);transform:translateY(-1px)}
.btn-outline{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.3)}.btn-outline:hover{background:rgba(255,255,255,.08)}
.nav-toggle{display:none;align-items:center;justify-content:center;width:38px;height:38px;border:none;background:rgba(255,255,255,.1);border-radius:8px;cursor:pointer}
.nav-toggle svg{width:20px;height:20px;fill:white}
.nav-mobile{display:none;position:fixed;top:68px;left:0;right:0;background:var(--navy-dark);border-top:1px solid rgba(255,255,255,.08);padding:16px 5vw 24px;flex-direction:column;gap:4px;z-index:99}
.nav-mobile.open{display:flex}
.nav-mobile a{color:rgba(255,255,255,.8);font-size:14px;padding:11px 14px;border-radius:8px;text-decoration:none}
.nav-mobile a:hover{background:rgba(255,255,255,.07)}

/* CONTENT */
.hero-mini{background:linear-gradient(145deg,var(--navy-dark) 0%,#081b3d 40%,#0e2650 100%);padding:120px 5vw 60px;text-align:center}
.hero-mini h1{font-size:clamp(28px,4vw,48px);font-weight:900;color:#fff;line-height:1.15;letter-spacing:-.02em;margin-bottom:12px}
.hero-mini p{font-size:16px;color:rgba(255,255,255,.6);max-width:500px;margin:0 auto}
.content{max-width:800px;margin:0 auto;padding:60px 5vw 100px}
.content h2{font-size:22px;font-weight:800;color:var(--navy);margin:40px 0 16px;padding-bottom:8px;border-bottom:2px solid var(--gold)}
.content h3{font-size:17px;font-weight:700;color:var(--navy);margin:28px 0 10px}
.content p{font-size:15px;color:var(--slate);margin-bottom:16px}
.content ul{margin:0 0 16px 20px}
.content li{font-size:15px;color:var(--slate);margin-bottom:8px}
.content li strong{color:var(--navy)}
.content a{color:var(--gold);text-decoration:underline;text-decoration-color:rgba(215,154,33,.3)}
.content a:hover{text-decoration-color:var(--gold)}
.meta{background:var(--off);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:40px}
.meta p{margin:0;font-size:14px;color:var(--muted)}
.meta strong{color:var(--navy)}
table{width:100%;border-collapse:collapse;margin:16px 0 24px;font-size:14px}
th,td{text-align:left;padding:10px 14px;border-bottom:1px solid var(--border)}
th{background:var(--off);font-weight:700;color:var(--navy);font-size:13px;text-transform:uppercase;letter-spacing:.04em}
td{color:var(--slate)}

/* FOOTER */
footer{background:var(--navy-dark);color:rgba(255,255,255,.65);padding:40px 5vw 24px;text-align:center}
footer p{font-size:13px}
footer a{color:var(--gold);text-decoration:none}

@media(max-width:768px){
  .nav-links,.nav-cta{display:none}
  .nav-toggle{display:flex}
  .content{padding:40px 5vw 80px}
}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ route('home') }}" class="nav-brand">
        <img src="/brand/educore-icon.svg" alt="EduCore">
        <span class="nav-brand-name">Edu<span>Core</span></span>
    </a>
    <div class="nav-links">
        <a href="{{ route('home') }}#features">Features</a>
        <a href="{{ route('home') }}#pricing">Pricing</a>
        <a href="{{ route('legal.privacy') }}">Privacy</a>
        <a href="{{ route('legal.terms') }}">Terms</a>
    </div>
    <div class="nav-cta">
        <a href="{{ route('login') }}" class="btn btn-outline">Login</a>
        <a href="{{ route('home') }}#pricing" class="btn btn-gold">Get Started &rarr;</a>
    </div>
    <button class="nav-toggle" onclick="document.getElementById('nm').classList.toggle('open')" aria-label="Menu">
        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
</nav>
<div class="nav-mobile" id="nm">
    <a href="{{ route('home') }}#features" onclick="document.getElementById('nm').classList.remove('open')">Features</a>
    <a href="{{ route('home') }}#pricing" onclick="document.getElementById('nm').classList.remove('open')">Pricing</a>
    <a href="{{ route('legal.privacy') }}">Privacy</a>
    <a href="{{ route('legal.terms') }}">Terms</a>
    <a href="{{ route('login') }}" class="btn btn-gold" style="justify-content:center;margin-top:12px">Get Started</a>
</div>

<section class="hero-mini">
    <h1>Privacy Policy</h1>
    <p>How EduCore collects, uses, and protects your personal information.</p>
</section>

<article class="content">

    <div class="meta">
        <p><strong>Effective Date:</strong> July 26, 2026 &nbsp;|&nbsp; <strong>Last Updated:</strong> July 26, 2026</p>
        <p><strong>Company:</strong> EduCore &nbsp;|&nbsp; <strong>Email:</strong> <a href="mailto:privacy@educore.app">privacy@educore.app</a></p>
    </div>

    <p>EduCore ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our school management platform, including our mobile application, web portal, and related services (collectively, the "Service").</p>

    <p>By using the Service, you agree to the collection and use of information in accordance with this policy. If you do not agree, please discontinue use of the Service.</p>

    <h2>1. Information We Collect</h2>

    <h3>1.1 Information You Provide</h3>
    <p>We collect information that you voluntarily provide when using the Service:</p>
    <table>
        <tr><th>Data Type</th><th>Examples</th><th>Purpose</th></tr>
        <tr><td>Account Information</td><td>Name, email address, phone number, role (admin, teacher, student, parent)</td><td>Account creation, authentication, and communication</td></tr>
        <tr><td>School Information</td><td>School name, address, logo, branding</td><td>Platform configuration and customization</td></tr>
        <tr><td>Academic Data</td><td>Student records, grades, attendance, timetables, exam results</td><td>Core school management functionality</td></tr>
        <tr><td>Financial Data</td><td>Fee invoices, payment records, salary information</td><td>Fee collection and payroll management</td></tr>
        <tr><td>Communications</td><td>Messages, notifications, contact form submissions</td><td>In-app messaging and support</td></tr>
    </table>

    <h3>1.2 Information Collected Automatically</h3>
    <p>When you use the mobile application, we may automatically collect:</p>
    <table>
        <tr><th>Data Type</th><th>Examples</th><th>Purpose</th></tr>
        <tr><td>Device Information</td><td>Device model, operating system, unique device identifiers</td><td>App optimization and troubleshooting</td></tr>
        <tr><td>Location Data</td><td>GPS coordinates (only when clock-in geo-fencing is enabled by your school)</td><td>Staff attendance verification</td></tr>
        <tr><td>Camera Data</td><td>QR code scan data (not stored images)</td><td>Staff QR clock-in authentication</td></tr>
        <tr><td>Usage Analytics</td><td>App features used, session duration, crash reports</td><td>Service improvement and debugging</td></tr>
    </table>

    <h2>2. How We Use Your Information</h2>
    <p>We use the information we collect for the following purposes:</p>
    <ul>
        <li><strong>Provide the Service:</strong> Deliver school management features including student records, attendance, grading, fee collection, and communication tools.</li>
        <li><strong>Authentication & Security:</strong> Verify user identity, prevent unauthorized access, and protect against fraud.</li>
        <li><strong>Communication:</strong> Send service-related notifications, updates, and respond to support requests.</li>
        <li><strong>Improvement:</strong> Analyze usage patterns to improve app performance, fix bugs, and develop new features.</li>
        <li><strong>Legal Compliance:</strong> Comply with applicable laws, regulations, and legal processes.</li>
    </ul>

    <h2>3. Data Sharing and Disclosure</h2>
    <p>We do not sell your personal information. We may share your data only in the following circumstances:</p>
    <ul>
        <li><strong>Within Your School:</strong> Data is shared between users within the same school organization based on their role and permissions (e.g., teachers see their students, parents see their children).</li>
        <li><strong>Service Providers:</strong> We use third-party services for payment processing (Monnify, Paystack, Flutterwave), hosting (cPanel), and email delivery. These providers access data only to perform their services and are bound by confidentiality obligations.</li>
        <li><strong>Legal Requirements:</strong> We may disclose information if required by law, court order, or governmental authority.</li>
        <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, user data may be transferred with prior notice.</li>
    </ul>

    <h2>4. Data Security</h2>
    <p>We implement industry-standard security measures to protect your data:</p>
    <ul>
        <li><strong>Encryption:</strong> All data transmitted between your device and our servers is encrypted using TLS/SSL.</li>
        <li><strong>Access Controls:</strong> Role-based access ensures users only see data relevant to their role.</li>
        <li><strong>Infrastructure:</strong> Our servers are hosted on secure, SOC 2 compliant infrastructure with regular security audits.</li>
        <li><strong>Authentication:</strong> Secure password hashing and optional two-factor authentication.</li>
    </ul>
    <p>While we strive to protect your data, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.</p>

    <h2>5. Data Retention</h2>
    <p>We retain your personal information for as long as your account is active or as needed to provide the Service. Specifically:</p>
    <ul>
        <li><strong>Active Accounts:</strong> Data is retained while your school subscription is active.</li>
        <li><strong>Academic Records:</strong> Student academic data is retained for the duration configured by the school administrator.</li>
        <li><strong>Account Deletion:</strong> When you delete your account, we remove your personal data within 30 days, except where retention is required by law.</li>
        <li><strong>Backup Retention:</strong> Encrypted backups are retained for up to 90 days for disaster recovery purposes.</li>
    </ul>

    <h2>6. Children's Privacy (COPPA Compliance)</h2>
    <p>EduCore is used in educational settings that may include students under 13 years of age. We take children's privacy seriously:</p>
    <ul>
        <li><strong>School Consent:</strong> For students under 13, schools act as agents for parental consent under COPPA's school consent provision.</li>
        <li><strong>Data Minimization:</strong> We collect only the minimum data necessary for educational purposes.</li>
        <li><strong>No Marketing:</strong> We do not use children's data for advertising, marketing, or profiling.</li>
        <li><strong>Parental Rights:</strong> Parents may request access to, correction, or deletion of their child's data by contacting <a href="mailto:privacy@educore.app">privacy@educore.app</a>.</li>
    </ul>

    <h2>7. Nigerian Data Protection (NDPA Compliance)</h2>
    <p>EduCore complies with the Nigeria Data Protection Act 2023 (NDPA) and the General Application and Implementation Directive (GAID):</p>
    <ul>
        <li><strong>Lawful Basis:</strong> We process data based on contractual necessity (providing the Service), legitimate interest (service improvement), and consent where required.</li>
        <li><strong>Data Subject Rights:</strong> Nigerian users have the right to access, rectify, erase, restrict processing, and data portability.</li>
        <li><strong>Cross-Border Transfer:</strong> Data may be processed outside Nigeria with appropriate safeguards as required by the NDPA.</li>
        <li><strong>Data Protection Officer:</strong> Contact our DPO at <a href="mailto:privacy@educore.app">privacy@educore.app</a> for any data protection inquiries.</li>
    </ul>

    <h2>8. Your Rights</h2>
    <p>Depending on your jurisdiction, you may have the following rights:</p>
    <table>
        <tr><th>Right</th><th>Description</th></tr>
        <tr><td>Access</td><td>Request a copy of the personal data we hold about you.</td></tr>
        <tr><td>Correction</td><td>Request correction of inaccurate or incomplete data.</td></tr>
        <tr><td>Deletion</td><td>Request deletion of your personal data (subject to legal obligations).</td></tr>
        <tr><td>Portability</td><td>Request your data in a structured, machine-readable format.</td></tr>
        <tr><td>Objection</td><td>Object to processing of your data for certain purposes.</td></tr>
        <tr><td>Withdraw Consent</td><td>Withdraw consent at any time where processing is based on consent.</td></tr>
    </table>
    <p>To exercise any of these rights, contact us at <a href="mailto:privacy@educore.app">privacy@educore.app</a>. We will respond within 30 days.</p>

    <h2>9. Account Deletion</h2>
    <p>You may delete your account at any time:</p>
    <ul>
        <li><strong>In-App:</strong> Navigate to Settings &rarr; Account &rarr; Delete Account.</li>
        <li><strong>Email:</strong> Send a deletion request to <a href="mailto:privacy@educore.app">privacy@educore.app</a> from your registered email address.</li>
        <li><strong>Admin Request:</strong> School administrators can request deletion of all school data by contacting support.</li>
    </ul>
    <p>Account deletion is permanent and cannot be undone. Academic records required by law may be retained in anonymized form.</p>

    <h2>10. Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify you of any material changes by:</p>
    <ul>
        <li>Posting the updated policy on this page with a new "Last Updated" date.</li>
        <li>Sending an in-app notification for significant changes.</li>
    </ul>
    <p>We encourage you to review this policy periodically. Continued use of the Service after changes constitutes acceptance of the updated policy.</p>

    <h2>11. Contact Us</h2>
    <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
    <table>
        <tr><th>Contact</th><th>Details</th></tr>
        <tr><td>Email</td><td><a href="mailto:privacy@educore.app">privacy@educore.app</a></td></tr>
        <tr><td>Support</td><td><a href="mailto:support@educore.app">support@educore.app</a></td></tr>
        <tr><td>Website</td><td><a href="https://educore.app">https://educore.app</a></td></tr>
        <tr><td>Address</td><td>Lagos, Nigeria</td></tr>
    </table>

</article>

<footer>
    <p>&copy; {{ date('Y') }} EduCore. All rights reserved. &nbsp;|&nbsp; <a href="{{ route('legal.privacy') }}">Privacy Policy</a> &nbsp;|&nbsp; <a href="{{ route('legal.terms') }}">Terms of Service</a></p>
</footer>

</body>
</html>
