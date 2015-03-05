<?php

namespace ride\cli\command\varnish;

use ride\cli\command\AbstractCommand;

use ride\library\varnish\VarnishAdmin;
use ride\library\varnish\VarnishPool;

/**
 * Command to add a Varnish server to the pool
 */
class VarnishAddCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Add a Varnish server to the pool');

        $this->addArgument('host', 'Hostname or IP address of the server', true);
        $this->addArgument('port', 'Port the varnishadm listens to (6082)', false);
        $this->addArgument('secret', 'Secret to authenticate with the server', false);
    }

    /**
     * Executes the command
     * @param \ride\library\varnish\VarnishPool $pool
     * @param string $host Hostname or IP of the server
     * @param integer $port Port the varnishadm listens to
     * @param string $secret Secret to authenticate with the server
     * @return null
     */
    public function invoke(VarnishPool $pool, $host, $port = 6082, $secret = null) {
        $server = new VarnishAdmin($host, $port, $secret);

        $pool->addServer($server);
    }

}
