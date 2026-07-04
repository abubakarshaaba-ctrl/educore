# Local Tenant Host Setup

Phase 11H is local-only. Do not use public DNS, tunnels, cloud hosting or remote repositories for this setup.

## Windows hosts file

Edit `C:\Windows\System32\drivers\etc\hosts` as Administrator and add only the tenant hosts you want to test:

```text
127.0.0.1 educore.test
127.0.0.1 bluerayy.educore.test
127.0.0.1 another-school.educore.test
```

Windows hosts files do not support wildcard entries, so each local tenant subdomain must be listed explicitly.

## Apache virtual host

In XAMPP Apache, configure a local virtual host that points to the Laravel `public` directory:

```apache
<VirtualHost *:80>
    ServerName educore.test
    ServerAlias *.educore.test
    DocumentRoot "C:/xampp/htdocs/educore/public"

    <Directory "C:/xampp/htdocs/educore/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Restart Apache after saving the virtual-host file.

## Local URLs

```text
http://educore.test
http://bluerayy.educore.test
http://bluerayy.educore.test/login
http://bluerayy.educore.test/forgot-password
http://bluerayy.educore.test/apply
```

## Custom domains

Custom domains are resolved only when `tenants.custom_domain` is set and `tenants.domain_verified` is true. For this local phase, verification is a Super Admin action inside EduCore after the host is configured locally.

Do not point real public DNS at this local XAMPP installation.
