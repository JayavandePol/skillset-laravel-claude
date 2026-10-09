# Local development: Herd, Sail, Docker

| Option | OS | Notes |
|---|---|---|
| **Herd** | macOS, Windows (Linux in 2026 — check) | native PHP/Nginx/Node, `.test` domains, Herd Pro adds DB/Redis/Mailpit/Reverb services |
| **Sail** | any with Docker (Windows → WSL2) | `php artisan sail:install`, `./vendor/bin/sail up -d`; mirrors production services |
| Project Docker Compose | any | when you want dev ≈ prod images (see `laravel-deployment`) |

Windows notes:
- Herd for Windows is the simplest; Sail must run inside WSL2 with the project on the Linux filesystem (`\\wsl$\...`) for performance.
- Line endings: `.gitattributes` with `* text=auto eol=lf`; `.editorconfig` `end_of_line = lf`.
- PowerShell vs bash: `composer run dev` works in both; `./vendor/bin/pest` → `vendor\bin\pest` in PowerShell.

Standard local loop:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev            # serve + queue:listen + pail + vite
```

Mail: Mailpit (Herd Pro / Sail) at `http://localhost:8025`, `MAIL_MAILER=smtp MAIL_HOST=127.0.0.1 MAIL_PORT=1025`.
