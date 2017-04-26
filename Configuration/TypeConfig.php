<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Configuration;

class TypeConfig
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     * @param array  $mapping
     * @param array  $config
     */
    public function __construct($name, array $mapping, array $config = [])
    {
        $this->config = $config;
        $this->mapping = $mapping;
        $this->name = $name;
    }

    /**
     * @return bool|null
     */
    public function getDateDetection()
    {
        return $this->getConfig('date_detection');
    }

    /**
     * @return array
     */
    public function getDynamicDateFormats()
    {
        $formats = $this->getConfig('dynamic_date_formats');
        if (empty($formats)) {
            $formats = null;
        }

        return $formats;
    }

    /**
     * @return string|null
     */
    public function getAnalyzer()
    {
        return $this->getConfig('analyzer');
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return string|null
     */
    public function getModel()
    {
        return $this->config['persistence']['model'] ?? null;
    }

    /**
     * @return bool|null
     */
    public function getNumericDetection()
    {
        return $this->getConfig('numeric_detection');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDynamic()
    {
        return $this->getConfig('dynamic');
    }

    /**
     * @return mixed
     */
    public function getStoredFields()
    {
        return $this->getConfig('stored_fields');
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getConfig($key)
    {
        return $this->config[$key] ?? null;
    }
}
