<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class BackupCommand extends Command
{
    protected static $defaultName = 'app:backup';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a backup archive (database dump + important files) into var/backups')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectDir = dirname(__DIR__, 2);
        $backupDir = $projectDir . '/var/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        // Parse DATABASE_URL
        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);
        if (!$databaseUrl) {
            $io->error('DATABASE_URL not found in environment. Aborting.');
            return Command::FAILURE;
        }

        $parts = parse_url($databaseUrl);
        if (!$parts || !isset($parts['scheme'])) {
            $io->error('Unable to parse DATABASE_URL.');
            return Command::FAILURE;
        }

        $scheme = $parts['scheme'];
        $timestamp = (new \DateTime())->format('Ymd_His');
        $tmpSql = sys_get_temp_dir() . '/db_dump_' . $timestamp . '.sql';

        // Currently support mysql and sqlite and pgsql basic handling
        if (strpos($scheme, 'mysql') !== false) {
            $user = $parts['user'] ?? 'root';
            $pass = $parts['pass'] ?? '';
            $host = $parts['host'] ?? '127.0.0.1';
            $port = $parts['port'] ?? '3306';
            $path = ltrim($parts['path'] ?? '', '/');
            $dbName = $path;

            $io->text(sprintf('Creating MySQL dump for database `%s` on %s:%s', $dbName, $host, $port));

            $cmd = ['mysqldump', '-h', $host, '-P', (string)$port, '-u', $user, '--skip-comments', $dbName];
            // Prefer MYSQL_PWD env to avoid leaking password in process list
            $env = null;
            if ($pass !== '') {
                $env = array_merge($_ENV, ['MYSQL_PWD' => $pass]);
            }

            $process = new Process($cmd, null, $env);
            $process->setTimeout(300);
            $process->run();
            if (!$process->isSuccessful()) {
                $io->error('mysqldump failed: ' . $process->getErrorOutput());
                return Command::FAILURE;
            }

            file_put_contents($tmpSql, $process->getOutput());
        } elseif (strpos($scheme, 'sqlite') !== false) {
            // sqlite: copy DB file
            $path = str_replace('sqlite://', '', $databaseUrl);
            $dbFile = str_replace('%kernel.project_dir%', $projectDir, $path);
            if (!file_exists($dbFile)) {
                $io->error('SQLite file not found: ' . $dbFile);
                return Command::FAILURE;
            }
            copy($dbFile, $tmpSql);
        } else {
            $io->warning('Unsupported database scheme (' . $scheme . '), skipping DB dump.');
            $tmpSql = null;
        }

        // Create archive
        $archive = $backupDir . '/backup_' . $timestamp . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($archive, \ZipArchive::CREATE) !== true) {
            $io->error('Unable to create archive ' . $archive);
            return Command::FAILURE;
        }

        // Add DB dump
        if ($tmpSql && file_exists($tmpSql)) {
            $zip->addFile($tmpSql, basename($tmpSql));
        }

        // Add important folders if present
        $folders = [
            $projectDir . '/public/uploads',
            $projectDir . '/public/assets',
            $projectDir . '/var',
            $projectDir . '/config/jwt',
        ];

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                continue;
            }
            $this->addFolderToZip($folder, $zip, $projectDir);
        }

        // Add a non-sensitive connection info file (no password)
        $info = [];
        $info[] = 'project_dir: ' . $projectDir;
        if (isset($dbName)) {
            $info[] = 'db_name: ' . ($dbName ?? '');
            $info[] = 'db_host: ' . ($host ?? '');
            $info[] = 'db_port: ' . ($port ?? '');
            $info[] = 'db_user: ' . ($user ?? '');
        }
        $zip->addFromString('connection_info.txt', implode("\n", $info));

        $zip->close();

        // Clean tmp sql
        if ($tmpSql && file_exists($tmpSql)) {
            unlink($tmpSql);
        }

        $io->success('Backup created: ' . $archive);
        return Command::SUCCESS;
    }

    private function addFolderToZip(string $folder, \ZipArchive $zip, string $basePath): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getRealPath();
            if (!$filePath) continue;
            // store path inside zip relative to project dir
            $localName = ltrim(str_replace($basePath, '', $filePath), '/\\');
            $zip->addFile($filePath, $localName);
        }
    }
}
