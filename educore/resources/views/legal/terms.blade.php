<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore Terms of Service — Rules governing use of our school management platform.">
<title>Terms of Service — EduCore</title>
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
    <h1>Terms of Service</h1>
    <p>Rules governing your use of the EduCore platform and services.</p>
</section>

<article class="content" role="main">

    <div class="meta">
        <p><strong>Effective Date:</strong> July 26, 2026 &nbsp;|&nbsp; <strong>Last Updated:</strong> July 26, 2026</p>
        <p><strong>Company:</strong> EduCore &nbsp;|&nbsp; <strong>Email:</strong> <a href="mailto:support@educore.app">support@educore.app</a></p>
    </div>

    <p>Welcome to EduCore. These Terms of Service ("Terms") govern your access to and use of the EduCore platform, including our website, mobile application, APIs, and related services (collectively, the "Service"). By accessing or using the Service, you agree to be bound by these Terms.</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By creating an account or using the Service, you confirm that:</p>
    <ul>
        <li>You are at least 18 years old or have legal capacity to enter into binding agreements.</li>
        <li>You have the authority to bind the school or organization you represent.</li>
        <li>You agree to comply with these Terms and all applicable laws.</li>
    </ul>
    <p>If you do not agree to these Terms, you must not access or use the Service.</p>

    <h2>2. Account Registration</h2>
    <h3>2.1 School Accounts</h3>
    <p>School administrators register their institution and are responsible for:</p>
    <ul>
        <li>Providing accurate and complete registration information.</li>
        <li>Maintaining the confidentiality of login credentials.</li>
        <li>All activities that occur under their account.</li>
        <li>Granting and revoking access for staff, students, and parents.</li>
    </ul>

    <h3>2.2 User Accounts</h3>
    <p>Individual users (staff, students, parents) receive accounts created by their school administrator. You are responsible for:</p>
    <ul>
        <li>Keeping your password secure and not sharing it.</li>
        <li>Notifying your school administrator of any unauthorized access.</li>
        <li>Using the Service only for its intended educational purpose.</li>
    </ul>

    <h2>3. Acceptable Use</h2>
    <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You must not:</p>
    <ul>
        <li>Use the Service to transmit harmful, abusive, or inappropriate content.</li>
        <li>Attempt to gain unauthorized access to any part of the Service.</li>
        <li>Interfere with or disrupt the Service or its infrastructure.</li>
        <li>Use the Service to collect personal data of users without proper authorization.</li>
        <li>Reverse engineer, decompile, or disassemble any part of the Service.</li>
        <li>Use automated tools (bots, scrapers) to access the Service without written permission.</li>
        <li>Resell, sublicense, or redistribute the Service without authorization.</li>
    </ul>

    <h2>4. Subscriptions and Payments</h2>
    <h3>4.1 Pricing</h3>
    <p>EduCore uses a per-student, per-term pricing model. Current pricing is available at <a href="{{ route('home') }}#pricing">educore.app/pricing</a>. We reserve the right to modify pricing with 30 days' notice.</p>

    <h3>4.2 Free Tier</h3>
    <p>Schools with up to 20 students may use EduCore free of charge. The free tier includes all features with no restrictions.</p>

    <h3>4.3 Billing</h3>
    <ul>
        <li>Subscriptions are billed per academic term (approximately 3 months).</li>
        <li>Payment is due at the start of each term.</li>
        <li>Payments are processed through Monnify, Paystack, or Flutterwave.</li>
        <li>All fees are non-refundable except as required by law.</li>
    </ul>

    <h3>4.4 Late Payment</h3>
    <p>If payment is not received within 14 days of the due date, we may suspend access to the Service. We will restore access within 24 hours of receiving full payment.</p>

    <h2>5. Data and Intellectual Property</h2>
    <h3>5.1 Your Data</h3>
    <p>You retain full ownership of all data you enter into the Service, including student records, grades, financial data, and communications. We do not claim ownership of your data.</p>

    <h3>5.2 Our Intellectual Property</h3>
    <p>The Service, including its software, design, branding, and documentation, is owned by EduCore and protected by intellectual property laws. You may not copy, modify, or distribute any part of the Service without written permission.</p>

    <h3>5.3 Data Portability</h3>
    <p>You may export your data at any time using the data export tools within the Service. Upon account termination, we will provide a final data export within 30 days of request.</p>

    <h2>6. Service Availability</h2>
    <p>We strive to maintain 99.9% uptime but cannot guarantee uninterrupted service. We may temporarily suspend the Service for:</p>
    <ul>
        <li>Scheduled maintenance (with advance notice when possible).</li>
        <li>Emergency repairs or security updates.</li>
        <li>Circumstances beyond our reasonable control (force majeure).</li>
    </ul>
    <p>We are not liable for any loss or damage caused by service interruptions.</p>

    <h2>7. Limitation of Liability</h2>
    <p>To the maximum extent permitted by law:</p>
    <ul>
        <li>EduCore provides the Service "as is" without warranties of any kind, express or implied.</li>
        <li>We are not liable for any indirect, incidental, special, consequential, or punitive damages.</li>
        <li>Our total liability shall not exceed the amount you paid for the Service in the 12 months preceding the claim.</li>
        <li>We are not responsible for data loss, unauthorized access, or errors in educational assessments.</li>
    </ul>

    <h2>8. Indemnification</h2>
    <p>You agree to indemnify and hold harmless EduCore, its officers, directors, employees, and agents from any claims, losses, damages, liabilities, costs, and expenses (including legal fees) arising from:</p>
    <ul>
        <li>Your use of the Service.</li>
        <li>Your violation of these Terms.</li>
        <li>Your violation of any applicable law or third-party rights.</li>
        <li>Data you enter into the Service, including accuracy and legality.</li>
    </ul>

    <h2>9. Termination</h2>
    <h3>9.1 By You</h3>
    <p>You may terminate your account at any time by:</p>
    <ul>
        <li>Contacting <a href="mailto:support@educore.app">support@educore.app</a></li>
        <li>Using the account deletion feature in Settings.</li>
    </ul>

    <h3>9.2 By Us</h3>
    <p>We may suspend or terminate your account if:</p>
    <ul>
        <li>You breach these Terms.</li>
        <li>Your payment is overdue by more than 30 days.</li>
        <li>We are required to do so by law.</li>
        <li>We reasonably believe your use poses a security risk.</li>
    </ul>

    <h3>9.3 Effect of Termination</h3>
    <p>Upon termination:</p>
    <ul>
        <li>Your access to the Service will cease immediately.</li>
        <li>We will retain your data for 30 days to allow for export.</li>
        <li>After 30 days, your data will be permanently deleted.</li>
        <li>Provisions that by their nature should survive termination will survive.</li>
    </ul>

    <h2>10. Dispute Resolution</h2>
    <p>Any disputes arising from these Terms shall be resolved as follows:</p>
    <ul>
        <li><strong>Informal Resolution:</strong> Contact us at <a href="mailto:support@educore.app">support@educore.app</a> to attempt informal resolution for 30 days.</li>
        <li><strong>Governing Law:</strong> These Terms are governed by the laws of the Federal Republic of Nigeria.</li>
        <li><strong>Jurisdiction:</strong> Any legal proceedings shall be brought in the courts of Lagos State, Nigeria.</li>
    </ul>

    <h2>11. Changes to These Terms</h2>
    <p>We may update these Terms from time to time. We will notify you of material changes by:</p>
    <ul>
        <li>Posting the updated Terms on this page with a new "Last Updated" date.</li>
        <li>Sending an email notification to school administrators.</li>
        <li>Displaying an in-app notification for significant changes.</li>
    </ul>
    <p>Continued use of the Service after changes constitutes acceptance of the updated Terms.</p>

    <h2>12. Contact Us</h2>
    <p>If you have any questions about these Terms, please contact us:</p>
    <table>
        <tr><th>Contact</th><th>Details</th></tr>
        <tr><td>Email</td><td><a href="mailto:support@educore.app">support@educore.app</a></td></tr>
        <tr><td>Privacy</td><td><a href="mailto:privacy@educore.app">privacy@educore.app</a></td></tr>
        <tr><td>Website</td><td><a href="https://educore.app">https://educore.app</a></td></tr>
        <tr><td>Address</td><td>Lagos, Nigeria</td></tr>
    </table>

</article>

<footer>
    <p>&copy; {{ date('Y') }} EduCore. All rights reserved. &nbsp;|&nbsp; <a href="{{ route('legal.privacy') }}">Privacy Policy</a> &nbsp;|&nbsp; <a href="{{ route('legal.terms') }}">Terms of Service</a></p>
</footer>

</body>
</html>
