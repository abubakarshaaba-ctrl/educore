<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore Privacy Policy">
<title>Privacy Policy — EduCore</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#071E45;--navy-dark:#040f25;
  --gold:#D79A21;--gold-light:#F2C35B;
  --off:#F7F9FC;--slate:#475569;--muted:#94A3B8;--border:#E2E8F0;
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--font);color:var(--navy);background:var(--off);line-height:1.7}
.nav{background:var(--navy-dark);padding:0 5vw;height:64px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{display:flex;align-items:center;gap:9px;text-decoration:none}
.nav-brand img{width:30px;height:30px;border-radius:7px}
.nav-brand span{font-size:16px;font-weight:800;color:#fff}
.nav-brand span b{color:var(--gold);font-weight:800}
.nav a.back{color:rgba(255,255,255,.7);font-size:13px;text-decoration:none;font-weight:600}
.nav a.back:hover{color:var(--gold)}
.wrap{max-width:820px;margin:0 auto;padding:56px 5vw 90px}
.wrap h1{font-size:clamp(28px,4vw,40px);font-weight:900;letter-spacing:-.02em;margin-bottom:8px}
.updated{color:var(--muted);font-size:13px;margin-bottom:40px}
.wrap h2{font-size:19px;font-weight:800;margin:36px 0 12px;color:var(--navy)}
.wrap h3{font-size:15px;font-weight:700;margin:20px 0 8px;color:var(--navy)}
.wrap p{font-size:14.5px;color:var(--slate);margin-bottom:12px}
.wrap ul{margin:0 0 12px 20px;color:var(--slate);font-size:14.5px}
.wrap li{margin-bottom:6px}
.wrap a{color:var(--navy);font-weight:700;text-decoration:underline}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:36px 40px;box-shadow:0 4px 24px rgba(7,30,69,.06)}
table.simple{width:100%;border-collapse:collapse;margin:12px 0 20px;font-size:13.5px}
table.simple th,table.simple td{text-align:left;padding:9px 12px;border-bottom:1px solid var(--border)}
table.simple th{background:var(--off);font-weight:700;color:var(--navy)}
.contact-box{background:var(--navy);color:#fff;border-radius:12px;padding:22px 26px;margin-top:32px}
.contact-box a{color:var(--gold-light);font-weight:700;text-decoration:none}
@media(max-width:640px){.card{padding:26px 20px}}
</style>
</head>
<body>
<nav class="nav">
    <a href="{{ route('home') }}" class="nav-brand">
        <img src="/brand/educore-icon.svg" alt="EduCore">
        <span>Edu<b>Core</b></span>
    </a>
    <a href="{{ route('home') }}" class="back">&larr; Back to home</a>
</nav>

<div class="wrap">
    <h1>Privacy Policy</h1>
    <p class="updated">Last updated: {{ now()->format('d F Y') }}</p>

    <div class="card">
        <p>This Privacy Policy explains how EduCore Education Technology ("EduCore", "we", "us") collects, uses, discloses, and protects information when a school ("Client School") and its staff, students, parents, and guardians ("Users") use the EduCore school management platform, including our website, web application, and mobile app (together, the "Service").</p>
        <p>EduCore is built for Nigerian K-12 schools and is designed with the Nigeria Data Protection Act 2023 (NDPA) and the Nigeria Data Protection Regulation (NDPR) in mind.</p>

        <h2>1. Who does what with your data</h2>
        <p>Each Client School is the <strong>Data Controller</strong> for the personal data of its own students, staff, parents, and guardians — the school decides what data to collect and why. EduCore acts as the <strong>Data Processor</strong>, providing the platform the school uses to store and manage that data, and processing it only on the school's instructions and for the purpose of delivering the Service.</p>
        <p>If you are a parent, student, or staff member with a question about your personal data, please contact your school's administration first, as they control your records. If your enquiry is about how EduCore itself operates the platform, contact us using the details at the end of this policy.</p>

        <h2>2. Information we collect</h2>
        <h3>2.1 Information Client Schools provide about students, staff, and parents</h3>
        <ul>
            <li>Identity details — full name, date of birth, gender, admission/staff ID, photograph</li>
            <li>Contact details — phone number, email address, home address</li>
            <li>Academic records — scores, attendance, report cards, transcripts, timetable, class/subject assignments</li>
            <li>Guardian/parent relationship details for students</li>
            <li>Employment records for staff — role, employment date, qualifications, salary and bank details for payroll</li>
            <li>Health records where a school chooses to record them (e.g. allergies, blood group)</li>
            <li>Biometric-adjacent verification data — a live photo captured at the point of clock-in/clock-out or a proxy attendance request, used only to verify the person present, and location coordinates captured at the same moment for geofence verification</li>
        </ul>

        <h3>2.2 Information collected automatically</h3>
        <ul>
            <li>Device information (device type, operating system) and app version, for the mobile app</li>
            <li>Push notification tokens, so we can deliver in-app and school notices</li>
            <li>Login activity, IP address, and basic usage logs, for security and audit purposes</li>
        </ul>

        <h3>2.3 Payment information</h3>
        <p>Fee payments are processed by our licensed payment partners, Paystack and Monnify. EduCore does not store full card numbers or bank authentication credentials — these are handled directly by the payment processor under their own security and compliance standards (PCI-DSS). We retain only the payment reference, amount, status, and timestamp needed to reconcile a school's fee records.</p>

        <h2>3. How we use information</h2>
        <table class="simple">
            <tr><th>Purpose</th><th>Example</th></tr>
            <tr><td>Deliver the Service</td><td>Displaying report cards, timetables, attendance, and payslips to the right account</td></tr>
            <tr><td>Communication</td><td>School announcements, in-app messages, SMS/email notices, password reset emails</td></tr>
            <tr><td>Attendance verification</td><td>Confirming a staff member is at the registered school location when clocking in</td></tr>
            <tr><td>Payments</td><td>Generating invoices, recording fee payments, running payroll</td></tr>
            <tr><td>Platform security</td><td>Detecting suspicious login activity, enforcing role-based access</td></tr>
            <tr><td>Support &amp; improvement</td><td>Diagnosing reported bugs, improving reliability and performance</td></tr>
        </table>
        <p>We do not sell personal data to third parties, and we do not use student or staff data for advertising.</p>

        <h2>4. Who we share information with</h2>
        <ul>
            <li><strong>Payment processors</strong> — Paystack and Monnify, to process fee and subscription payments</li>
            <li><strong>SMS and push notification providers</strong> — to deliver notices and alerts you or your school opted into</li>
            <li><strong>Hosting and infrastructure providers</strong> — who store data on our behalf under contractual confidentiality obligations</li>
            <li><strong>Law enforcement or regulators</strong> — only where required by Nigerian law, or to protect the rights, safety, or property of EduCore, a Client School, or its Users</li>
        </ul>
        <p>We do not share student or staff data with third parties for their own marketing purposes.</p>

        <h2>5. Data retention</h2>
        <p>A Client School's data is retained for as long as the school maintains an active subscription, plus a reasonable grace period after cancellation to allow the school to export its records. A school may request permanent deletion of its tenant data at any time, subject to any legal obligation we or the school may have to retain specific records (e.g. financial records) for a minimum period.</p>

        <h2>6. Data security</h2>
        <ul>
            <li>Passwords are stored using one-way cryptographic hashing — we never store plain-text passwords</li>
            <li>Each school's data is logically isolated from every other school on the platform (multi-tenant isolation)</li>
            <li>Sensitive configuration values (e.g. payment gateway secret keys) are encrypted at rest</li>
            <li>Access to production systems is restricted to authorised personnel only</li>
            <li>All traffic to the Service is encrypted in transit via HTTPS</li>
        </ul>
        <p>No system can guarantee absolute security. If we become aware of a data breach affecting personal data, we will notify affected Client Schools without undue delay, in line with NDPA/NDPR requirements.</p>

        <h2>7. Children's data</h2>
        <p>EduCore is used by schools to manage records belonging to minors. We rely on the Client School's relationship with, and consent obtained from, parents/guardians as the lawful basis for processing a student's data on the platform. Schools are responsible for ensuring they have the appropriate parental consent or other lawful basis in place before enrolling a student's data into EduCore.</p>

        <h2>8. Your rights</h2>
        <p>Subject to applicable law and the Client School's own policies, Users may have the right to:</p>
        <ul>
            <li>Request access to the personal data held about them</li>
            <li>Request correction of inaccurate personal data</li>
            <li>Request deletion of personal data, where legally permissible</li>
            <li>Request a copy of their data in a portable format</li>
            <li>Object to certain processing</li>
        </ul>
        <p>Requests relating to student, parent, or staff records should be directed to the relevant Client School in the first instance, since the school controls those records. Requests relating to EduCore's own account or platform-level data can be sent to us directly.</p>

        <h2>9. Cookies and similar technologies</h2>
        <p>The Service uses strictly necessary cookies (such as session and CSRF-protection cookies) to keep you securely signed in. We do not use third-party advertising or tracking cookies.</p>

        <h2>10. International data transfers</h2>
        <p>Our infrastructure providers may process data in data centres located outside Nigeria. Where this occurs, we require our providers to maintain security standards consistent with NDPA/NDPR requirements for cross-border data transfer.</p>

        <h2>11. Changes to this policy</h2>
        <p>We may update this Privacy Policy from time to time to reflect changes in the Service or applicable law. We will update the "Last updated" date above when we do. Material changes will be communicated to Client Schools through the platform or by email.</p>
    </div>

    <div class="contact-box">
        <strong>Questions about this policy?</strong>
        <p style="color:rgba(255,255,255,.75);margin-top:6px">Contact EduCore Education Technology:</p>
        <p style="margin-top:8px">Phone: 07065595768 &nbsp;·&nbsp; WhatsApp: +2347065595768</p>
        <p><a href="mailto:support@educoreng.online">support@educoreng.online</a></p>
    </div>
</div>
</body>
</html>
