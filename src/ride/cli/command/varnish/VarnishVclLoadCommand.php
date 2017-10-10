<?php

namespace ride\cli\command\varnish;

use ride\cli\command\AbstractCommand;

use ride\library\varnish\exception\VarnishException;
use ride\library\varnish\VarnishPool;

/**
 * Command to load a VCL on a remote Varnish server
 */
class VarnishVclLoadCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Loads and uses the VCL configuration from the provided file');

        $this->addArgument('file', 'Path to VCL configuration file', true);
        $this->addFlag('server', 'Limit to a single server, provide the server and port');
    }

    /**
     * Executes the command
     * @param \ride\library\varnish\VarnishPool $pool
     * @param string $file Path to VCL configuration file
     * @param boolean $server Limit to a single server
     * @return null
     */
    public function invoke(VarnishPool $pool, $file, $server = null) {
        $configuration = file_get_contents($file);

        try {
            if ($server) {
                $serverName = $server;

                $server = $pool->getServer($serverName);
                if (!$server) {
                    throw new VarnishException('Server ' . $serverName . ' does not exist');
                }

                $name = $server->loadVclFromConfiguration($configuration);
                $server->useVcl($name);
            } else {
                $servers = $pool->getServers();
                foreach ($servers as $server) {
                    $name = $server->loadVclFromConfiguration($configuration);
                    $server->useVcl($name);
                }
            }
        } catch (VarnishException $exception) {
            $this->output->writeErrorLine($exception->getMessage());
            $this->output->writeErrorLine($exception->getResponse());
        }
    }

}
