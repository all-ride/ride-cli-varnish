<?php

namespace ride\cli\command\varnish;

use ride\library\reflection\Boolean;
use ride\cli\command\AbstractCommand;

use \Exception;

/**
 * Command to generate varnish configuration for a set of redirects
 */
class VarnishGenerateRedirectCommand extends AbstractCommand {

    /**
     * Initializes the command
     * @return null
     */
    protected function initialize() {
        $this->setDescription('Generates varnish configuration to redirect a set of URL\'s');

        $this->addArgument('file', 'Path to a CSV file with the old URL as first column, the destination as second column, ' .
            'an optional HTTP status code (301 or 302) as third column, an optional flag (yes or no) to see if everything ' .
            'starting with the old URL should be matched as fourth column and an optional flag (yes or no) to see if the ' .
            'original path should be appended to the destination as fifth column.'
        );
        $this->addFlag('baseUrl', 'Base URL for the old URL or destination, used for relative URL\'s');
        $this->addFlag('ignoreHeader', 'Add this flag to ignore the first row');
        $this->addFlag('statusCode', 'Default status code, used when 3rd column is empty');
    }

    /**
     * Executes the command
     * @param string $file Path to the CSV file
     * @param boolean $ignoreHeader Flag to see if the first row is a header row
     * @param string $baseUrl Base URL for all relative paths
     * @param integer $statusCode Default status code
     * @return null
     */
    public function invoke($file, $ignoreHeader = false, $baseUrl = null, $statusCode = 302) {
        // initialize variabels
        $baseUrl = rtrim($baseUrl, '/');

        // read and process the CSV file
        $rules = $this->readFile($file, $ignoreHeader, $baseUrl, $statusCode);
        $vcl = $this->generateVcl($rules);

        $this->output->writeLine($vcl);
    }

    /**
     * Reads the provided CSV handle
     * @param string $file Path to the CSV file
     * @param boolean $ignoreHeader
     * @param string $baseUrl
     * @param integer $defaultStatusCode
     * @return array Map with the redirect rules
     */
    private function readFile($file, $ignoreHeader, $baseUrl, $defaultStatusCode) {
        // open the CSV file
        $handle = @fopen($file, 'r');
        if ($handle === false) {
            throw new Exception('Could not open ' . $file . ' for reading');
        }

        // ignore the column header by reading a row
        if ($ignoreHeader) {
            $this->readRow($handle);
        }

        $result = array();

        while ($row = $this->readRow($handle)) {
            if (count($row) < 2) {
                continue;
            }

            $source = $this->normalizeUrl(trim($row[0]), $baseUrl);
            $destination = $this->normalizeUrl(trim($row[1]), $baseUrl);

            $statusCode = isset($row[2]) ? trim($row[2]) : $defaultStatusCode;
            if ($statusCode != 301 && $statusCode != 302) {
                $statusCode = $defaultStatusCode;
            }

            $isRegex = isset($row[3]) && $row[3] ? Boolean::getBoolean($row[3]) : false;
            if ($isRegex) {
                $source = '^' . $source;
            }

            $hasSamePath = isset($row[4]) && $row[4] ? Boolean::getBoolean($row[4]) : false;

            $destination = '"' . $destination . '"';
            if ($hasSamePath) {
                $destination .= ' + req.url';
            }

            $result[$statusCode][$destination][$source] = $isRegex;
        }
        ksort($result);

        return $result;
    }

    /**
     * Reads a row from the provided CSV handle
     * @param resource $handle Handle of the CSV file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return array
     */
    private function readRow($handle, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        $row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
        if ($row === false) {
            return null;
        }

        if ($row === array(null)) {
            return $this->readRow();
        }

        return $row;
    }

    /**
     * Normalizes the provided URL
     * @param string $url URL or path
     * @param string $baseUrl Base URL of the system
     * @return string Full URL for the provided URL of path
     */
    private function normalizeUrl($url, $baseUrl) {
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false) {
            throw new Exception('Could not parse URL: ' . $url);
        }

        if (substr($url, 0, 1) == '/') {
            $url = $baseUrl . $url;
        }

        return $url;
    }

    /**
     * Generates the VCL for the provided rules
     * @param array $rules Array with VCL values for the rule tokens
     * @return string
     */
    private function generateVcl(array $rules) {
        $vcl = '';

        if (!$rules) {
            return $vcl;
        }

        $rules = $this->generateRulesVcl($rules);

        $isFirst = true;
        foreach ($rules as $host => $redirects) {
            $regularConditions = array();
            $regexConditions = array();

            foreach ($redirects as $redirect => $sources) {
                foreach ($sources as $condition => $isRegex) {
                    if ($isRegex) {
                        $regexConditions[$redirect][$condition] = true;
                    } else {
                        $regularConditions[$redirect][$condition] = true;
                    }
                }
            }

            if (!$isFirst) {
                $vcl .= '} else ';
            }

            $vcl .= 'if (req.http.host == "' . $host . '") {' . "\n";
            $vcl .= $this->generateConditionsVcl($regularConditions);
            $vcl .= $this->generateConditionsVcl($regexConditions);

            $isFirst = false;
        }

        $vcl .= '}' . "\n";

        return $vcl;
    }

    /**
     * Generates VCL snippets of the rule values
     * @param array $rules Rules read from CSV
     * @return array Array with VCL values for the rule tokens
     */
    private function generateRulesVcl(array $rules) {
        $result = array();

        foreach ($rules as $statusCode => $statusRules) {
            $baseRedirect = '';
            switch ($statusCode) {
                case 301:
                    $baseRedirect = 'error 750 ';

                    break;
                case 302:
                    $baseRedirect = 'error 751 ';

                    break;
                default:
                    throw new Exception('Could not parse rules: status code ' . $statusCode . ' is not implemented, try 301 or 302.');
            }

            foreach ($statusRules as $destination => $sources) {
                $redirect = $baseRedirect . $destination . ';';

                foreach ($sources as $source => $isRegex) {
                    if ($isRegex) {
                        $source = substr($source, 1);
                    }

                    $sourceUrl = parse_url($source);
                    if ($sourceUrl === false) {
                        throw new Exception('Could not parse rules: source ' . $source . ' is not a valid URL');
                    }

                    $scheme = isset($sourceUrl['scheme']) ? $sourceUrl['scheme'] : 'http';

                    $host = $sourceUrl['host'];
                    if (isset($sourceUrl['port']) && $sourceUrl['port'] != 80) {
                        $host .= ':' . $sourceUrl['port'];
                    }

                    $path = str_replace($scheme . '://' . $host, '', $source);
                    if (!$path) {
                        $path = '/';
                    }

                    if ($isRegex) {
                        $path = str_replace('.', '\\.', $path);
                        $condition = 'req.url ~ "^' . $path . '"';
                    } else {
                        $condition = 'req.url == "' . $path . '"';
                    }

                    $result[$host][$redirect][$condition] = $isRegex;
                }
            }
        }

        return $result;
    }

    /**
     * Generates the VCL for the provided conditions
     * @param array $rules Array with VCL conditions
     * @return string
     */
    private function generateConditionsVcl(array $conditions) {
        $vcl = '';

        if (!$conditions) {
            return $vcl;
        }

        $isFirst = true;
        foreach ($conditions as $redirect => $redirectConditions) {
            if ($isFirst) {
                $vcl .= '    ';
                $isFirst = false;
            } else {
                $vcl .= '    } else ';
            }

            $hasMultipleConditions = count($redirectConditions) != 1;

            $vcl .= 'if (';
            if ($hasMultipleConditions) {
                $vcl .= "\n        ";
            }

            $vcl .= implode(" ||\n        ", array_keys($redirectConditions));

            if ($hasMultipleConditions) {
                $vcl .= "\n    ";
            }
            $vcl .= ') {' . "\n";

            $vcl .= '        ' . $redirect . "\n";
        }
        $vcl .= '    }' . "\n";

        return $vcl;
    }

}
