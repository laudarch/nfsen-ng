<?php

namespace nfsen_ng\common;

use nfsen_ng\datasources\Datasource;
use nfsen_ng\processor\Processor;

abstract class Config {
    /**
     * @var array{
     *     general?: array{ports: int[], sources: string[], db: string, processor: string},
     *     frontend?: array{reload_interval: int, defaults: array<string, array>},
     *     nfdump?: array{binary: string, profiles-data: string, profile: string, max-processes: int},
     *     db?: array<string, array>,
     *     log?: array{priority: int}
     *     }
     */
    public static array $cfg;
    public static string $path;
    public static Datasource $db;
    public static Processor $processorClass;
    private static bool $initialized = false;

    private function __construct() {
    }

    public static function initialize(bool $initProcessor = false): void {
        if (self::$initialized === true) {
            return;
        }

        $settingsFile = \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'settings' . \DIRECTORY_SEPARATOR . 'settings.php';
        if (!file_exists($settingsFile)) {
            throw new \Exception('No settings.php found. Did you rename the distributed settings correctly?');
        }

        $nfsen_config = [];
        include $settingsFile;

        self::$cfg = $nfsen_config;
        self::$path = \dirname(__DIR__);
        self::$initialized = true;

        // find data source
        $dbClass = 'nfsen_ng\\datasources\\' . self::$cfg['general']['db'];
        if (class_exists($dbClass)) {
            self::$db = new $dbClass();
        } else {
            throw new \Exception('Failed loading class ' . self::$cfg['general']['db'] . '. The class doesn\'t exist.');
        }

        // find processor
        $processorClass = \array_key_exists('processor', self::$cfg['general']) ? self::$cfg['general']['processor'] : 'NfDump';
        $processorClass = 'nfsen_ng\\processor\\' . $processorClass;
        if (!class_exists($processorClass)) {
            throw new \Exception('Failed loading class ' . $processorClass . '. The class doesn\'t exist.');
        }

        if (!\in_array(Processor::class, class_implements($processorClass), true)) {
            throw new \Exception('Processor class ' . $processorClass . ' doesn\'t implement ' . Processor::class . '.');
        }

        if ($initProcessor === true) {
            self::$processorClass = new $processorClass();
        }
    }
}
