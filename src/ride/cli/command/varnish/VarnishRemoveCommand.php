<?php

namespace ride\cli\command\varnish;

use ride\cli\command\AbstractCommand;

use ride\library\varnish\VarnishPool;

/**
 * Command to remove a Varnish server from the pool
 */
class VarnishRemoveCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Remove a Varnish server from the pool');

        $this->addArgument('host', 'Hostname or IP address of the server', true);
        $this->addArgument('port', 'Port the varnishadm listens to (6082)', false);
    }

    /**
     * Executes the command
     * @param \ride\library\varnish\VarnishPool $pool
     * @param string $host Hostname or IP of the server
     * @param integer $port Port the varnishadm listens to
     * @return null
     */
    public function invoke(VarnishPool $pool, $host, $port = 6082) {
        $pool->removeServer($host . ':' . $port);
    }

}
