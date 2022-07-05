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
