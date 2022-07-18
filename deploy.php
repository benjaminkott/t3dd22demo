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
