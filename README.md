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