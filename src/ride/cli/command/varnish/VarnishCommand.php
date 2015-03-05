<?php

namespace ride\cli\command\varnish;

use ride\cli\command\AbstractCommand;

use ride\library\varnish\VarnishPool;

/**
 * Command to get an overview of the caches
 */
class VarnishCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Gets an overview of the Varnish servers in the pool with their status');
    }

    /**
     * Executes the command
     * @return null
     */
    public function invoke(VarnishPool $pool) {
        $servers = $pool->getServers();
        if (!$servers) {
            $this->output->writeLine('There are no servers configured.');
        }

        ksort($servers);

        foreach ($servers as $server) {
            $this->output->writeLine('[' . ($server->isRunning() ? 'V' : ' ') . '] ' . $server);
        }
    }

}
