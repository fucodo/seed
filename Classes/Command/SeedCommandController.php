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
}
