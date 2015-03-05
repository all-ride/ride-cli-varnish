<?php

namespace ride\cli\command\varnish;

use ride\cli\command\AbstractCommand;

use ride\library\varnish\exception\VarnishException;
use ride\library\varnish\VarnishPool;

/**
 * Command to ban a URL on all the Varnish servers of the pool
 */
class VarnishBanUrlCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Bans a URL on all the Varnish servers of the pool');

        $this->addArgument('url', 'URL to ban', true);
        $this->addFlag('recursive', 'Clear everything starting with the provided URL');
        $this->addFlag('server', 'Limit to a single server, provide the server and port');
        $this->addFlag('force', 'Ignore failures and execute the command on the remaining servers');
    }

    /**
     * Executes the command
     * @param \ride\library\varnish\VarnishPool $pool
     * @param string $url URL to ban
     * @param boolean $recursive Clear everything starting with the provided URL
     * @param boolean $server Limit to a single server
     * @param boolean $force Force the command on all servers, even if one fails
     * @return null
     */
    public function invoke(VarnishPool $pool, $url, $recursive = false, $server = null, $force = null) {
        if ($server) {
            $serverName = $server;

            $server = $pool->getServer($serverName);
            if (!$server) {
                throw new VarnishException('Server ' . $serverName . ' does not exist');
            }
        } else {
            $server = $pool;

            if ($force) {
                $server->setIgnoreOnFail(true);
            }
        }

        $server->banUrl($url, $recursive);
    }

}
