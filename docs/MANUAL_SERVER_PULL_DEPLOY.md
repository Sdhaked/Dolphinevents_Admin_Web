# Manual Server Pull Deploy

Use this flow when deployments are done from the cPanel Terminal instead of GitHub Actions.

## 1. Server requirements

Run these commands in cPanel Terminal:

```bash
cd /home/dgwowuwr/dolphinevents.co.uk
git --version
/usr/local/bin/php -v
composer --version
node -v
npm -v
which rsync
```

Required versions:

- PHP: `8.2` or newer.
- Node: `22.x` for Vite 7.
- Composer: version 2 recommended.

If Node is missing or older than `22`, enable Node.js 22 in cPanel or ask MilesWeb support. Without Node, `npm run build` cannot create `public/build`.

## 2. Give the server read access to GitHub

Create a GitHub deploy key on the server:

```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
ssh-keygen -t ed25519 -C "dolphinevents-server-pull" -f ~/.ssh/dolphinevents_github
cat ~/.ssh/dolphinevents_github.pub
```

In GitHub, open:

```text
Repository > Settings > Deploy keys > Add deploy key
```

Use:

- Title: `dolphinevents-server-pull`
- Key: paste the public key from `cat ~/.ssh/dolphinevents_github.pub`
- Allow write access: unchecked

Create SSH config on the server:

```bash
cat > ~/.ssh/config <<'EOF'
Host github.com-dolphinevents
    HostName github.com
    User git
    IdentityFile ~/.ssh/dolphinevents_github
    IdentitiesOnly yes
EOF

chmod 600 ~/.ssh/config ~/.ssh/dolphinevents_github
ssh -T git@github.com-dolphinevents
```

GitHub may print a message saying shell access is not provided. That is fine if authentication succeeds.

## 3. First-time setup when the domain folder is not a Git repo

Take a backup before changing the live folder:

```bash
cd /home/dgwowuwr
tar -czf dolphinevents-before-git-$(date +%Y%m%d-%H%M%S).tar.gz dolphinevents.co.uk
```

If `/home/dgwowuwr/dolphinevents.co.uk` is empty, clone directly:

```bash
cd /home/dgwowuwr
git clone git@github.com-dolphinevents:Sdhaked/Dolphinevents_Admin_Web.git dolphinevents.co.uk
cd /home/dgwowuwr/dolphinevents.co.uk
```

If it already has the old live project, use this safer replace flow:

```bash
cd /home/dgwowuwr
mv dolphinevents.co.uk dolphinevents.co.uk_old_$(date +%Y%m%d-%H%M%S)
git clone git@github.com-dolphinevents:Sdhaked/Dolphinevents_Admin_Web.git dolphinevents.co.uk
```

Then copy production-only files from the old backup folder into the new clone:

```bash
cp /home/dgwowuwr/dolphinevents.co.uk_old_YYYYMMDD-HHMMSS/.env /home/dgwowuwr/dolphinevents.co.uk/.env
rsync -a /home/dgwowuwr/dolphinevents.co.uk_old_YYYYMMDD-HHMMSS/storage/ /home/dgwowuwr/dolphinevents.co.uk/storage/
```

Replace `YYYYMMDD-HHMMSS` with the actual old folder suffix.

## 4. Production .env

The production environment file must be here:

```text
/home/dgwowuwr/dolphinevents.co.uk/.env
```

At minimum, verify:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dolphinevents.co.uk
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306

PAYMENT_MODE=live
```

Never commit `.env` to GitHub.

## 5. Deploy after every code change

After code is pushed to GitHub `main`, run on the server:

```bash
cd /home/dgwowuwr/dolphinevents.co.uk
bash scripts/server-pull-deploy.sh
```

The script runs:

1. `php artisan down`
2. `git pull --ff-only origin main`
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci`
5. `npm run build`
6. `php artisan migrate --force`
7. `php artisan optimize`
8. `php artisan up`

To deploy another branch temporarily:

```bash
cd /home/dgwowuwr/dolphinevents.co.uk
DEPLOY_BRANCH=branch-name bash scripts/server-pull-deploy.sh
```

## 6. Domain document root

The domain document root must be:

```text
/home/dgwowuwr/dolphinevents.co.uk/public
```

Do not point the domain to `/home/dgwowuwr/dolphinevents.co.uk`, because Laravel private files should not be web-accessible.

## 7. Cron

Set this cPanel cron job to run once per minute:

```bash
/usr/local/bin/php /home/dgwowuwr/dolphinevents.co.uk/artisan schedule:run >> /dev/null 2>&1
```
