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
          token: ${{ secrets.GITHUB_TOKEN }}

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