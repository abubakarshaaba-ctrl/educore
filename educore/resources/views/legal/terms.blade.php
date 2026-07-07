<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore Terms of Service">
<title>Terms of Service — EduCore</title>
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
    <h1>Terms of Service</h1>
    <p class="updated">Last updated: {{ now()->format('d F Y') }}</p>

    <div class="card">
        <p>These Terms of Service ("Terms") govern access to and use of the EduCore school management platform, including our website, web application, and mobile app (together, the "Service"), operated by EduCore Education Technology ("EduCore", "we", "us"). By registering a school, creating an account, or otherwise using the Service, you agree to these Terms on behalf of yourself and, where applicable, the school you represent ("Client School").</p>
        <p>If you do not agree to these Terms, do not use the Service.</p>

        <h2>1. The Service</h2>
        <p>EduCore is a multi-tenant school management platform providing academic management, admissions, attendance, assessments, fee and payroll management, communications, reporting, and role-based portals for school administrators, staff, students, parents, and agents.</p>
        <p>Features and subscription plans available to a Client School depend on the plan selected. We may add, change, or retire features from time to time as the Service evolves.</p>

        <h2>2. Accounts and eligibility</h2>
        <ul>
            <li>A Client School must provide accurate information when registering, and keep it up to date.</li>
            <li>Individual user accounts (admin, staff, student, parent, agent) are created or approved by the Client School's administrator. EduCore is not responsible for verifying the identity of individual Users beyond the credentials issued by the school.</li>
            <li>Each account is for use by the individual it was issued to. Sharing login credentials is discouraged and the account holder remains responsible for activity on their account.</li>
            <li>Accounts for students are managed under the supervision of the Client School and, where applicable, the student's parent or guardian.</li>
        </ul>

        <h2>3. Client School responsibilities</h2>
        <p>As the data controller for its students, staff, and parents, each Client School is responsible for:</p>
        <ul>
            <li>Obtaining any consent required from parents, guardians, staff, or students before entering their personal data into the Service</li>
            <li>The accuracy of academic, attendance, and financial records entered into the platform</li>
            <li>Managing which staff have administrative access, and revoking access when a staff member leaves</li>
            <li>Complying with applicable Nigerian data protection and education regulations in how it uses the Service</li>
        </ul>

        <h2>4. Subscription plans, billing, and payment</h2>
        <ul>
            <li>Paid plans are billed in advance on a recurring basis (monthly or as otherwise agreed) in Nigerian Naira (₦).</li>
            <li>New schools may be offered a free trial period. At the end of the trial, continued use of paid features requires an active subscription.</li>
            <li>Fee collection from parents/guardians via the Service is processed through Paystack and/or Monnify. EduCore is not a party to, and is not liable for, the underlying fee arrangement between a Client School and its students' parents/guardians — our role is limited to providing the payment collection and record-keeping tools.</li>
            <li>Subscription fees paid to EduCore are non-refundable except where required by law or expressly agreed in writing.</li>
            <li>We may suspend access to a Client School's account if subscription fees are not paid when due, after reasonable notice.</li>
        </ul>

        <h2>5. Acceptable use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the Service for any unlawful purpose, or in a way that infringes the rights of others</li>
            <li>Attempt to gain unauthorised access to another school's data, or to any account you are not authorised to use</li>
            <li>Upload malicious code, or attempt to disrupt, overload, or reverse-engineer the Service</li>
            <li>Use the Service to send spam, harassing messages, or unlawful content through the messaging, SMS, or notification features</li>
            <li>Circumvent attendance verification features (e.g. falsifying location or photo verification for clock-in)</li>
        </ul>
        <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>

        <h2>6. Data ownership</h2>
        <p>As between EduCore and a Client School, the school retains ownership of all academic, financial, and personal data it enters into the Service ("Client Data"). EduCore does not claim ownership of Client Data and will only process it to provide the Service, as described in our <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.</p>
        <p>Upon termination of a subscription, a Client School may request export of its Client Data within a reasonable period before deletion.</p>

        <h2>7. Intellectual property</h2>
        <p>The Service, including its software, design, branding, and underlying technology, is the property of EduCore Education Technology and is protected by applicable intellectual property laws. These Terms do not grant you any right to use EduCore's trademarks, logos, or branding without prior written consent.</p>

        <h2>8. Mobile application</h2>
        <p>The EduCore staff mobile app is provided for use by staff of Client Schools to access attendance clock-in, payslips, timetables, and messaging. Use of the mobile app is subject to these Terms, and to any additional permissions (e.g. location, camera) the app requests for features such as clock-in verification, which are used solely for that purpose.</p>

        <h2>9. Availability and support</h2>
        <p>We aim to keep the Service available and reliable, but we do not guarantee uninterrupted or error-free operation. Scheduled maintenance, third-party outages (e.g. payment gateway or SMS provider downtime), or circumstances beyond our reasonable control may affect availability from time to time.</p>

        <h2>10. Limitation of liability</h2>
        <p>To the fullest extent permitted by Nigerian law, EduCore's total liability arising out of or relating to the Service shall not exceed the subscription fees paid by the affected Client School in the three (3) months preceding the event giving rise to the claim. EduCore shall not be liable for indirect, incidental, or consequential damages, including loss of data, revenue, or goodwill, arising from use of the Service.</p>
        <p>Nothing in these Terms limits liability for fraud, wilful misconduct, or any liability that cannot be excluded under Nigerian law.</p>

        <h2>11. Warranty disclaimer</h2>
        <p>The Service is provided "as is" and "as available". Except as expressly stated in these Terms, EduCore makes no other warranties, express or implied, regarding the Service.</p>

        <h2>12. Termination</h2>
        <p>A Client School may cancel its subscription at any time by contacting us or through account settings, where available. EduCore may suspend or terminate access to the Service for a Client School or individual account that breaches these Terms, fails to pay subscription fees when due, or where required by law.</p>

        <h2>13. Changes to these Terms</h2>
        <p>We may update these Terms from time to time. Continued use of the Service after an update constitutes acceptance of the revised Terms. Material changes will be communicated to Client Schools through the platform or by email.</p>

        <h2>14. Governing law</h2>
        <p>These Terms are governed by the laws of the Federal Republic of Nigeria. Any dispute arising out of or relating to these Terms shall first be addressed through good-faith negotiation between the parties, and failing resolution, shall be subject to the jurisdiction of the courts of Nigeria.</p>
    </div>

    <div class="contact-box">
        <strong>Questions about these Terms?</strong>
        <p style="color:rgba(255,255,255,.75);margin-top:6px">Contact EduCore Education Technology:</p>
        <p style="margin-top:8px">Phone: 07065595768 &nbsp;·&nbsp; WhatsApp: +2347065595768</p>
        <p><a href="mailto:support@educoreng.online">support@educoreng.online</a></p>
    </div>
</div>
</body>
</html>
