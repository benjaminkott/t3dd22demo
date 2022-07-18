## Steps

### 1. Create DDEV Configuration

```bash
ddev config \
    --project-name=t3dd22demo \
    --project-type=php \
    --php-version=8.1 \
    --docroot=public \
    --create-docroot \
    --webserver-type=apache-fpm \
    --database=mysql:5.7
```

### 2. Start the Project

```bash
ddev start
```

### 3. Initialize Composer Project

```bash
ddev composer init \
    --name=bk2k/t3dd22demo \
    --type=project \
    --no-interaction
```

### 4. Require minimal packages

```bash
ddev composer require \
    php:~8.1 \
    vlucas/phpdotenv \
    typo3/minimal \
    typo3/cms-introduction
```

Create `.gitignore` in root directory.

```
/var/*
!/var/labels
/vendor
/public/*
!/public/fileadmin
/public/fileadmin/*
!/public/.htaccess
!/public/typo3conf
/public/typo3conf/*
!/public/typo3conf/LocalConfiguration.php
!/public/typo3conf/AdditionalConfiguration.php
```

### 5. Install TYPO3

Create `public/typo3conf/AdditionalConfiguration.php` with the following contents.

```php
<?php

$dotenv = \Dotenv\Dotenv::createUnsafeMutable(__DIR__ . '/../../');
$dotenv->load();

if (file_exists(__DIR__ . '/../../.env.local')) {
    $dotenv = \Dotenv\Dotenv::createUnsafeMutable(__DIR__ . '/../../', '.env.local');
    $dotenv->load();
}

// Database Credentials
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = getenv('TYPO3_DB_CONNECTIONS_DEFAULT_HOST');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] = getenv('TYPO3_DB_CONNECTIONS_DEFAULT_PORT');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = getenv('TYPO3_DB_CONNECTIONS_DEFAULT_USER');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = getenv('TYPO3_DB_CONNECTIONS_DEFAULT_PASSWORD');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = getenv('TYPO3_DB_CONNECTIONS_DEFAULT_DBNAME');

// Graphics
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = getenv('TYPO3_GFX_PROCESSOR');
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = getenv('TYPO3_GFX_PROCESSOR_PATH');
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = getenv('TYPO3_GFX_PROCESSOR_PATH_LZW');

// Mail
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = getenv('TYPO3_MAIL_TRANSPORT');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = getenv('TYPO3_MAIL_TRANSPORT_SMTP_SERVER');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_sendmail_command'] = getenv('TYPO3_MAIL_TRANSPORT_SENDMAIL_COMMAND');

// System
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = getenv('TYPO3_SYS_TRUSTED_HOSTS_PATTERN');
$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = getenv('TYPO3_BE_INSTALL_TOOL_PASSWORD');
```

Create `.env` with the following contents.

```bash
# Database Credentials
TYPO3_DB_CONNECTIONS_DEFAULT_HOST="db"
TYPO3_DB_CONNECTIONS_DEFAULT_PORT=3306
TYPO3_DB_CONNECTIONS_DEFAULT_USER="db"
TYPO3_DB_CONNECTIONS_DEFAULT_PASSWORD="db"
TYPO3_DB_CONNECTIONS_DEFAULT_DBNAME="db"

# Graphics
TYPO3_GFX_PROCESSOR="ImageMagick"
TYPO3_GFX_PROCESSOR_PATH="/usr/bin/"
TYPO3_GFX_PROCESSOR_PATH_LZW="/usr/bin/"

# Mail
TYPO3_MAIL_TRANSPORT="smtp"
TYPO3_MAIL_TRANSPORT_SMTP_SERVER="localhost:1025"
TYPO3_MAIL_TRANSPORT_SENDMAIL_COMMAND="/usr/local/bin/mailhog sendmail test@example.org --smtp-addr 127.0.0.1:1025"

# Site
SITE_BASE="https://t3dd22demo.ddev.site/"
TYPO3_SYS_TRUSTED_HOSTS_PATTERN=".*(\\.)?ddev\\..*"

# Install Tool Password (password)
TYPO3_BE_INSTALL_TOOL_PASSWORD="$argon2i$v=19$m=16384,t=16,p=2$ZThuN2Z0UlVYV0RyY2hwTQ$t7PSIiGFSRze6ffIxXOjBLbU81VYtB4SzZhy1yOY1HQ"
```

Create ` public/FIRST_INSTALL` file to enable installation.

```bash
ddev exec touch public/FIRST_INSTALL
```

Launch the project and follow the installation instructions.

```bash
ddev launch
```

Setup Extensions

```bash
ddev exec vendor/bin/typo3 extension:setup
```

### Generate SSH key and copy it to the server

Make sure to backup your ssh keypair.

```bash
# Generate SSH Key Pair
ddev exec ssh-keygen -t rsa -b 4096 -f ./ssh/id_rsa

# Copy it to the server
ddev exec ssh-copy-id -i ./ssh/id_rsa <username>@<host>

# Test the connection
ddev exec ssh -i ./ssh/id_rsa <username>@<host>
```

### Prepare Database

Prepare your remote Database and upload the dump from the local project.

### Download Deployer and save it to bin

We are downloading deployer, so it does not 
mix up with our existing dependencies.

```bash
ddev exec curl -LO https://github.com/deployphp/deployer/releases/download/v7.0.0-rc.8/deployer.phar
ddev exec mkdir ./bin
ddev exec mv deployer.phar ./bin/dep
ddev exec chmod +x ./bin/dep
```

Add deployment configuration.

```php
<?php

namespace Deployer;

require 'recipe/common.php';
require 'contrib/rsync.php';
require 'contrib/cachetool.php';

// Project name
set('application', 't3dd22demo');
set('application_path', '~/html/{{application}}');
set('application_public', '~/html/{{application}}/data');
set('rsync', [
    'exclude' => [
        '/.ddev',
        '/.github',
        '/.git',
        '/bin',
        '/assets',
        '/data',
        '/ssh',
        '/var',
        '/.editorconfig',
        '/.gitattributes',
        '/.gitignore',
        '/public/fileadmin',
        '/public/typo3temp',
        '/.env.local',
        '/.php-cs-fixer.cache',
        '/.php-cs-fixer.dist.php',
        '/deploy.php',
        '/README.md',
    ],
    'exclude-file' => false,
    'include'      => [],
    'include-file' => false,
    'filter'       => [],
    'filter-file'  => false,
    'filter-perdir' => false,
    'flags'        => 'az',
    'options'      => ['delete', 'delete-after', 'force'],
    'timeout'      => 3600,
]);
set('shared_files', [
    '.env.local'
]);
set('shared_dirs', [
    'public/fileadmin',
    'public/typo3temp',
    'var/lock',
    'var/log',
]);

set('cachetool_args', '--web --web-path=./public --web-url=https://{{hostname}}');

set(
    'bin/typo3',
    '{{bin/php}} {{release_or_current_path}}/vendor/bin/typo3'
);

// Hosts
host(getenv('SSH_HOST'))
    ->set('remote_user', getenv('SSH_USER'))
    ->set('keep_releases', '2')
    ->set('deploy_path', '{{application_path}}/site')
    ->set('rsync_src', __DIR__)
    ->set('rsync_dest','{{release_path}}')
    ->set('ssh_arguments', ['-o UserKnownHostsFile=/dev/null']);

// TYPO3 Tasks
task('typo3:backend:lock', function () { run("{{bin/typo3}} backend:lock"); });
task('typo3:extension:setup', function () { run("{{bin/typo3}} extension:setup"); });
task('typo3:cache:flush', function () { run("{{bin/typo3}} cache:flush"); });
task('typo3:cache:warmup', function () { run("{{bin/typo3}} cache:warmup"); });
task('typo3:upgrade:run', function () { run("{{bin/typo3}} upgrade:run"); });
task('typo3:backend:unlock', function () { run("{{bin/typo3}} backend:unlock"); });
task('typo3', [
    'typo3:backend:lock',
    'typo3:extension:setup',
    'typo3:cache:flush',
    'typo3:cache:warmup',
    'typo3:upgrade:run',
    'typo3:backend:unlock'
]);

// Upload fileadmin
task('upload:fileadmin', function() {
    upload('public/fileadmin', '{{deploy_path}}/shared/public');
});

// Setup Task
task('site:setup', function () {
    run(<<<EOF
[ -d {{application_path}} ] || mkdir -p {{application_path}};
EOF
);
    // If application_public points to something like "/var/www/html/data", make sure it is
    // a symlink and not a directory.
    if (test('[ ! -L {{application_public}} ] && [ -d {{application_public}} ]')) {
        throw error("There is a directory (not symlink) at {{application_public}}.\n Remove this directory so it can be replaced with a symlink for atomic deployments.");
    }
    run('[ -L {{application_public}} ] || {{bin/symlink}} {{current_path}}/public {{application_public}}');

    // Protect deployment folders
    set('hostnameCondition', str_replace('.', '.\\', get('hostname')));
    info('Create .htaccess in application folder to protect directory structure.');
    run(<<<EOF
cd {{application_path}};
echo "
RewriteEngine on
RewriteCond %{HTTP_HOST} !^{{ hostnameCondition }}$ [NC]
RewriteRule ^(.*)$ https://{{ hostname }}/$1 [L,R=301]
" > .htaccess
EOF
);

    // Create .env.local if not exists
    if (test('[ ! -f {{deploy_path}}/shared/.env.local ]')) {
        info('Create .env.local from .env.example');
        upload('.env.example', '{{deploy_path}}/shared/.env.local');
    }

    // Create .htaccess in fileadmin if not exists
    if (test('[ ! -f {{deploy_path}}/shared/public/fileadmin/.htaccess ]')) {
        info('Create public/fileadmin/.htaccess');
        upload('public/fileadmin/.htaccess', '{{deploy_path}}/shared/public/fileadmin/.htaccess');
    }
});
after(
    'deploy:setup',
    'site:setup'
);

// Task to only deploy code
task('deploy:data', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
]);

// Main Task
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'cachetool:clear:opcache',
    'cachetool:clear:apcu',
    'typo3',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
])->desc('Deploy your project');

// Unlock after failed
after(
    'deploy:failed',
    'deploy:unlock'
);
```


Deploying only the code for the first deployment, create
the folder structure, upload current assets and adjust the config.

```bash
# 1. Deploy Code
ddev exec SSH_HOST=<host> SSH_USER=<username> bin/dep deploy:data
# 2. Upload current assets
ddev exec SSH_HOST=<host> SSH_USER=<username> bin/dep upload:fileadmin
# 3. Edit configuration
ddev exec SSH_HOST=<host> SSH_USER=<username> bin/dep ssh
vim .env.local
```

### Connect the domain

To validate the results, connect the production domain or use a temporary one.
Now run the full deployment with the `SSH_HOST` set to the domain that is public facing.

```bash
ddev exec SSH_HOST=<public-facing-domain> SSH_USER=<username> bin/dep deploy
```

## Adding deployment to main branch

Since the manual process is not really helpful on a day to day basis.
We can utilize Github Actions to run the deployment for us.


```yaml
name: Deployment

on:
  push:
    branches:
      - main

concurrency:
  group: deployment-${{ github.ref }}
  cancel-in-progress: true

jobs:
  deploy:
    name: Deploy
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v3

      - name: Setup PHP 8.1
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1

      - name: Get Composer Cache Directory
        id: composer-cache
        run: |
          echo "::set-output name=dir::$(composer config cache-files-dir)"

      - name: Use Cache
        uses: actions/cache@v3
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      - name: Install dependencies
        run: |
          composer install --no-dev --no-progress --optimize-autoloader

      - name: Deploy
        uses: deployphp/action@v1
        with:
          private-key: ${{ secrets.SSH_PRIVATE_KEY }}
          dep: deploy
          deployer-version: "7.0.0-rc.8"
        env:
          SSH_HOST: ${{ secrets.SSH_HOST }}
          SSH_USER: ${{ secrets.SSH_USER }}
```


## BONUS: Adding auto update to the project

Since this is a small project, we want to keep it updated with no effort. 
We do not expect any issues here we opt-in to react instead of checking 
each update manually.

It is set to run every Monday at 10 am and will commit the changes
to the defined default branch of our repository.

`.github/workflows/autoupdate.yaml` 

```yaml
name: Auto Update

on:
  schedule:
    - cron: '0 10 * * 1'
  workflow_dispatch:

jobs:
  update:
    name: Update
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
        uses: actions/checkout@v3
        with:
          token: ${{ secrets.PRIVATE_ACCESS_TOKEN }}

      - name: Setup PHP 8.1
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1

      - name: Get Composer Cache Directory
        id: composer-cache
        run: |
          echo "::set-output name=dir::$(composer config cache-files-dir)"

      - name: Use Cache
        uses: actions/cache@v3
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      - name: Install dependencies
        run: |
          composer install --no-progress --no-suggest --no-interaction

      - name: Update dependencies
        run: |
          composer update --no-progress --no-interaction --with-all-dependencies

      - name: Check for modified files
        id: git-check
        run: echo ::set-output name=modified::$(if git diff-index --quiet HEAD --; then echo "false"; else echo "true"; fi)

      - name: Commit changes
        if: steps.git-check.outputs.modified == 'true'
        run: |
          git config user.name GitHub Action
          git config user.email action@github.com
          git add --all .
          git commit -m "[AUTO UPDATE]"
          git push
```

## Demo Data

### Export Data

```bash
ddev export-db --file data/database.sql.gz
```

### Import Data

```bash
ddev import-db --src=data/database.sql.gz
```

### Import Assets

```bash
ddev import-files --src=assets/introduction
```

### Backend User

```
Username: admin
Password: password
Email: noreply@typo3.com
```