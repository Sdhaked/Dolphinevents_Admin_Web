# Dolphin Events CI/CD Setup

This project uses GitHub Actions for Laravel tests, Vite builds, and direct production deployment over SSH. Complete these steps once before the first deployment.

## 1. Information required from the hosting account

Collect these values from cPanel or MilesWeb support:

- SSH hostname or IP address.
- SSH port, normally `22`, but hosting providers sometimes use a custom port.
- cPanel SSH username.
- Full deploy path.
- Production domain URL.
- Confirmation that SSH access is enabled.
- PHP 8.2 or newer CLI binary path.
- `rsync` availability on the server.

Confirmed values for this server:

- `DEPLOY_USER`: `dgwowuwr`
- `DEPLOY_PATH`: `/home/dgwowuwr/dolphinevents.co.uk`
- `DEPLOY_PHP_BINARY`: `/usr/local/bin/php`
- `PRODUCTION_URL`: `https://dolphinevents.co.uk`
- Local deployment key fingerprint: `SHA256:JFCp/UmGBjq9dewzaDfqKprfYZO6OsmKPvGgCqLLGZo`

Still confirm before deployment:

- `DEPLOY_HOST`: use the same hostname/IP that accepts SSH for this cPanel account.
- `DEPLOY_PORT`: use the SSH port that works from PowerShell, normally `22`.

Open cPanel `Terminal` and run:

```bash
whoami
pwd
php -v
which php
```

If `php -v` is older than PHP 8.2, ask support for the PHP 8.2 CLI path. A common cPanel path is `/opt/cpanel/ea-php82/root/usr/bin/php`, but use the path confirmed on this server.

## 2. Create a dedicated deployment SSH key on Windows

Open PowerShell on the development computer and run:

```powershell
ssh-keygen -t ed25519 -C "github-actions-dolphinevents" -f "$env:USERPROFILE\.ssh\dolphinevents_cicd"
```

When asked for a passphrase, press Enter twice. GitHub Actions is non-interactive, so this dedicated key must not have a passphrase. Do not reuse a personal SSH key.

The command creates:

```text
C:\Users\YOUR_USER\.ssh\dolphinevents_cicd       private key
C:\Users\YOUR_USER\.ssh\dolphinevents_cicd.pub   public key
```

Never send the private key in chat, email, or commit it to Git.

## 3. Authorize the public key in cPanel

1. Open `cPanel > Security > SSH Access > Manage SSH Keys`.
2. Click `Import Key`.
3. Use the name `dolphinevents_cicd`.
4. In PowerShell, display the public key with:

```powershell
Get-Content "$env:USERPROFILE\.ssh\dolphinevents_cicd.pub"
```

5. Paste that one public-key line into cPanel's public key box. Leave the private key box empty.
6. Import it, then open `Manage` beside the public key and click `Authorize`.
7. Test the key from PowerShell:

```powershell
ssh -i "$env:USERPROFILE\.ssh\dolphinevents_cicd" -p SSH_PORT dgwowuwr@SSH_HOST
```

The command must log in without asking for the cPanel password.

## 4. Prepare the Laravel folder on the server

After connecting through SSH, run:

```bash
cd /home/dgwowuwr/dolphinevents.co.uk
mkdir -p .deploy/releases .deploy/backups
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs
chmod 700 .deploy
which rsync
```

The final layout will be:

```text
/home/dgwowuwr/dolphinevents.co.uk/
|-- .deploy/
|   |-- backups/
|   `-- releases/
|-- .env
|-- app/
|-- bootstrap/
|-- public/
|-- storage/
`-- vendor/
```

GitHub uploads each build to `.deploy/releases/RELEASE_ID`, backs up the current files into `.deploy/backups/RELEASE_ID`, then syncs the new code directly into `/home/dgwowuwr/dolphinevents.co.uk`.

## 5. Create the production environment file

Copy the project's `.env.example` content to this server file using cPanel File Manager or Terminal:

```text
/home/dgwowuwr/dolphinevents.co.uk/.env
```

Generate a new production application key on the local project computer:

```powershell
php artisan key:generate --show
```

Put the generated value in server `.env` as `APP_KEY`. At minimum, verify these settings:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dolphinevents.co.uk

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANEL_DATABASE
DB_USERNAME=CPANEL_DATABASE_USER
DB_PASSWORD=STRONG_DATABASE_PASSWORD
```

Also copy the real production mail, Stripe, session, queue, filesystem, OTP, and other project-specific values from the currently working production configuration. Never add production `.env` to GitHub.

## 6. Point the domain to Laravel public

The domain document root must be:

```text
/home/dgwowuwr/dolphinevents.co.uk/public
```

For an addon domain or subdomain, open `cPanel > Domains > Manage`, change the document root, and use the path relative to the cPanel home directory.

cPanel does not let account users change the primary domain document root. For a primary domain, ask MilesWeb support to point it to the path above. Do not expose the Laravel project root directly; only the `public` directory can be web-accessible.

## 7. Capture and verify the server host key

Run this from PowerShell with the real host and port:

```powershell
ssh-keyscan -H -p SSH_PORT SSH_HOST
```

Confirm the displayed fingerprint with MilesWeb support or the first trusted SSH connection. Save the complete output; it becomes the `DEPLOY_KNOWN_HOSTS` secret. This prevents GitHub Actions from connecting to an impersonated server.

## 8. Create the GitHub production environment

Open the GitHub repository and go to:

```text
Settings > Environments > New environment > production
```

Recommended: add a required reviewer so production deployment waits for approval after CI passes.

Create these environment secrets:

- `DEPLOY_HOST`: server hostname or IP address.
- `DEPLOY_USER`: `dgwowuwr`.
- `DEPLOY_PORT`: SSH port only, normally `22`.
- `DEPLOY_PATH`: `/home/dgwowuwr/dolphinevents.co.uk`.
- `DEPLOY_SSH_KEY`: complete content of `dolphinevents_cicd`, including the BEGIN and END lines.
- `DEPLOY_KNOWN_HOSTS`: complete verified `ssh-keyscan` output.

Display the private key only while entering the GitHub secret:

```powershell
Get-Content -Raw "$env:USERPROFILE\.ssh\dolphinevents_cicd"
```

Create these environment variables, not secrets:

- `PRODUCTION_URL`: `https://dolphinevents.co.uk`.
- `DEPLOY_PHP_BINARY`: `/usr/local/bin/php`.
- `KEEP_RELEASES`: `5`.

No GitHub personal access token and no GitHub repository deploy key are required. The dedicated key connects GitHub Actions to cPanel; its public half belongs in cPanel and its private half belongs in the GitHub `production` environment secret.

## 9. First deployment

Commit and push the CI/CD files to `main`. Open the GitHub repository `Actions` tab and select `CI and Production Deploy`.

The workflow will:

1. Validate Composer files.
2. Install PHP dependencies.
3. Build assets with Node 22.
4. Run the automated tests.
5. Compile Blade views.
6. Package the production application with `vendor` and `public/build`.
7. Upload the build to `.deploy/releases/RELEASE_ID`.
8. Back up the current app files to `.deploy/backups/RELEASE_ID`.
9. Sync the new app files directly into `/home/dgwowuwr/dolphinevents.co.uk`.
10. Run migrations and Laravel optimization.
11. Check `https://dolphinevents.co.uk/up`.

Future pushes to `main` repeat this process automatically. Pull requests run CI only and never receive production secrets.

## 10. Configure the required cPanel cron job

This project runs `app:cleanup-ticket-holds` every minute. Without Laravel's scheduler, expired checkout inventory holds will not be released on time.

Open `cPanel > Advanced > Cron Jobs`, choose `Once Per Minute`, and use this command with the confirmed PHP binary and cPanel username:

```bash
/usr/local/bin/php /home/dgwowuwr/dolphinevents.co.uk/artisan schedule:run >> /dev/null 2>&1
```

If the confirmed PHP binary changes later, replace `/usr/local/bin/php`. The project currently sends its OTP and ticket mail directly, so a permanent queue worker is not required by the code found during this setup. Add a queue worker later if mail or jobs are changed to implement `ShouldQueue`.

## 11. Local development requirement

The frontend uses Vite 7. Install Node 22 LTS locally, then verify:

```powershell
node --version
npm ci
npm run build
```

The Node version must start with `v22`. CI reads the same version from `.nvmrc`.

## 12. Rollback

The latest five backups remain under `.deploy/backups`. To restore code files from a backup, first identify the backup:

```bash
ls -1dt /home/dgwowuwr/dolphinevents.co.uk/.deploy/backups/*
```

Then restore one backup path:

```bash
BACKUP_PATH=/home/dgwowuwr/dolphinevents.co.uk/.deploy/backups/REPLACE_WITH_BACKUP_FOLDER
rsync -a --delete --exclude='.env' --exclude='storage/' --exclude='.deploy/' "$BACKUP_PATH/" /home/dgwowuwr/dolphinevents.co.uk/
cd /home/dgwowuwr/dolphinevents.co.uk
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan optimize
```

Database migrations are not automatically reversed during code rollback. Take a database backup before risky schema releases, and review backward compatibility before restoring old code.

## Deployment behavior and security

- Tests must pass before deployment starts.
- Production secrets are scoped to the GitHub `production` environment.
- Strict SSH host checking is enabled.
- `.env`, runtime storage, tests, Git metadata, and `node_modules` are not uploaded.
- Dependencies and compiled frontend assets are built by GitHub, so Composer and Node are not required on the cPanel server.
- The application briefly enters maintenance mode only while files are synced and migrations run.
- A failed activation restores the previous code files from the latest `.deploy/backups` folder where possible.
- Dependabot checks GitHub Actions, Composer, and npm dependencies weekly.
- Composer security advisories are reported in CI. They are currently informational because the existing lockfile contains package versions that the configured remote repository cannot safely update or reproduce as a complete dependency update.
