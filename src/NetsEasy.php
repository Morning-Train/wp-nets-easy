<?php namespace Morningtrain\WpNetsEasy;

use Morningtrain\WP\Database\Database;
use Morningtrain\WP\Database\Migration\Migration;
use Morningtrain\WpNetsEasy\Classes\NetsEasyClient;
use Morningtrain\WpNetsEasy\Classes\Payment\WebhookHandler;

class NetsEasy {

    public static function init(string $netsEasySecretKey) : NetsEasyClient
    {
        $netsEasyClient = NetsEasyClient::init($netsEasySecretKey);

        WebhookHandler::init();

        $migrationsPath = realpath(__DIR__ . '/../database/migrations');
        Database::setup($migrationsPath);
        Migration::migrate([$migrationsPath]);

        return $netsEasyClient;
    }

}