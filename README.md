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
