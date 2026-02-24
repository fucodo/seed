<?php
namespace fucodo\seed\Command;

/*
 * This file is part of the SBS.SingleSignOn package.
 */

use Doctrine\Common\Util\Debug;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use fucodo\seed\Service\TractorService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;
use Neos\Utility\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class SeedCommandController extends CommandController
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @Flow\InjectConfiguration(path="persistence", package="Neos.Flow")
     * @var array
     */
    protected $persistenceSettings = [];

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\InjectConfiguration(path="jobs", package="fucodo.seed")
     * @var array
     */
    protected $settings = [];

    /**
     * Create a Doctrine DBAL Connection with the configured settings.
     *
     * @return void
     * @throws DBALException
     */
    protected function initializeConnection()
    {
        $this->connection = DriverManager::getConnection($this->persistenceSettings['backendOptions']);
    }

    /**
     * Import Initialization data
     * @return int
     */
    public function dataCommand(string $job = 'default'): int
    {
        $job = trim ($job);

        $this->outputLine(TractorService::getTractor());

        if (ObjectAccess::getPropertyPath($this->settings, $job . '.enabled') !== true) {
            $this->outputLine('Job ' . $job . ' is disabled');
            throw new StopCommandException('Job is disabled', 1);
        }

        $this->initializeConnection();

        if ($this->connection->getDriver()->getSchemaManager($this->connection)->tablesExist('flow_doctrine_migrationstatus')) {
            $this->outputLine('Database was already initialized');
            throw new StopCommandException('Database was already initialized', 0);
        }

        $this->outputLine('Initializing database');

        foreach ($this->settings[$job]['databaseImports'] as $file) {
            if (isset($file['enabled']) && ($file['enabled'] !== true)) {
                $this->outputLine('X Skipping: ' . $file['file'] . ' because it is disabled');
                continue;
            }
            $this->output('✔ Importing: ' . $file['file']);
            $content = file_get_contents($file['file']);
            if (trim($content) === '') {
                $this->outputLine(' - [NO CONTENT FOUND]');
                continue;
            }
            $resultSet = $this->connection->executeStatement($content);
            $this->outputLine(' - [DONE]');
        }

        $this->outputLine('Runnings commands');

        return 0;
    }

    /**
     * Drop all tables in the configured database, ignoring foreign key constraints.
     * Useful to fully clean up the database before a fresh seed/import.
     *
     * Usage: ./flow seed:cleanup
     *
     * @return int
     */
    public function cleanupCommand(): int
    {
        $this->outputLine(TractorService::getCleaningTractor());
        $this->outputLine('Cleaning up database: dropping all tables (ignoring foreign keys)');

        $this->initializeConnection();
        $this->outputLine('Database connection established');

        $schemaManager = $this->connection->getDriver()->getSchemaManager($this->connection);
        $platformName = $this->connection->getDatabasePlatform()->getName() ?? '';

        // Try to disable foreign key checks for engines that support it (e.g., MySQL)
        try {
            if ($platformName === 'mysql') {
                $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            }
        } catch (\Throwable $e) {
            // Best-effort: continue even if we cannot disable FK checks
        }

        // Drop all tables
        $tables = $schemaManager->listTableNames();

        $this->outputLine('Getting Schema Data');

        foreach ($tables as $tableName) {
            $this->output('- Dropping: ' . $tableName);
            try {
                $this->connection->executeStatement('DROP TABLE IF EXISTS ' . $this->connection->quoteIdentifier($tableName));
                $this->outputLine(' - [DONE]');
            } catch (\Throwable $e) {
                $this->outputLine(' - [FAILED] ' . $e->getMessage());
            }
        }

        // Re-enable foreign key checks when possible
        try {
            if ($platformName === 'mysql') {
                $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            }
        } catch (\Throwable $e) {
            // Ignore
        }


        $this->outputLine('Database cleanup finished');
        return 0;
    }
}
