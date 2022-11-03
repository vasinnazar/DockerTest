<?php


namespace Deployer;

require 'recipe/laravel.php';
require 'recipe/cachetool.php';
/**
 *
 * Using Deployer V6.1.0
 *
 */
inventory('deploy-config.yml');

set('shared_files', [
    '.env',
    'laravel-echo-server.json'
]);

set('shared_dirs', [
    'storage',
]);

set('writable_dirs', [
    'storage',
    'vendor',
    'node_modules',
]);

after('deploy:failed', 'deploy:unlock');


task('prod:opcache', ['cachetool:clear:opcache'])->onStage('prod');
host('prod')->set('cachetool', '/var/run/arm_debt_php.sock');

task('build', function () {
    set('repository', host('local')->get('repository'));
    set('branch', host('local')->get('branch'));
    set('keep_releases', host('local')->get('keep_releases'));
    set('deploy_path', __DIR__ . '/.build');
    set('composer_options', host('local')->get('composer_options'));
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:update_code');
    invoke('deploy:vendors');
    invoke('deploy:symlink');
    invoke('cleanup');
})->local();


task('upload', function () {
    upload(__DIR__ . "/.build/current/", '{{release_path}}',['options' => ['-l','--delete','--del']]);
});

task('files:symlink', function () {
    run('ln -s {{release_path}}/storage/app/public/files {{release_path}}/public/files ');
});

task('release', [
    'deploy:prepare',
    'deploy:release',
    'upload',
    'deploy:shared',
]);

task('deploy', [
    'build',
    'release',
    'deploy:symlink',
    'files:symlink',
    'cleanup',
    'prod:opcache',
    'success'
]);
