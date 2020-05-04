<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 * @package Controller
 * @subpackage Common
 */

namespace Aimeos\Controller\Common\Supplier\Import\Csv;

/**
 * Common class for CSV supplier import job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
class Base extends \Aimeos\Controller\Jobs\Base
{
    /**
     * Converts the CSV field data using the available converter objects
     *
     * @param array $convlist Associative list of CSV field indexes and converter objects
     * @param array $data Associative list of supplier codes and lists of CSV field indexes and their data
     * @return array Associative list of CSV field indexes and their converted data
     */
    protected function convertData(array $convlist, array $data)
    {
        foreach ($convlist as $idx => $converter) {
            foreach ($data as $code => $list) {
                if (isset($list[$idx])) {
                    $data[$code][$idx] = $converter->translate($list[$idx]);
                }
            }
        }

        return $data;
    }

    /**
     * Returns the cache object for the given type
     *
     * @param string $type Type of the cached data
     * @param string|null Name of the cache implementation
     * @return \Aimeos\Controller\Common\Supplier\Import\Csv\Cache\Iface Cache object
     */
    protected function getCache($type, $name = null)
    {
        $context = $this->getContext();
        $config = $context->getConfig();

        if (ctype_alnum($type) === false) {
            $classname = is_string($name)
                ? '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Cache\\' .
                    $type
                : '<not a string>';
            throw new \Aimeos\Controller\Jobs\Exception(
                sprintf('Invalid characters in class name "%1$s"', $classname)
            );
        }

        if ($name === null) {
            $name = $config->get(
                'controller/common/supplier/import/csv/cache/' .
                    $type .
                    '/name',
                'Standard'
            );
        }

        if (ctype_alnum($name) === false) {
            $classname = is_string($name)
                ? '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Cache\\' .
                    $type .
                    '\\' .
                    $name
                : '<not a string>';
            throw new \Aimeos\Controller\Jobs\Exception(
                sprintf('Invalid characters in class name "%1$s"', $classname)
            );
        }

        $classname =
            '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Cache\\' .
            ucfirst($type) .
            '\\' .
            $name;

        if (class_exists($classname) === false) {
            throw new \Aimeos\Controller\Jobs\Exception(
                sprintf('Class "%1$s" not found', $classname)
            );
        }

        $object = new $classname($context);

        \Aimeos\MW\Common\Base::checkClass(
            '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Cache\\Iface',
            $object
        );

        return $object;
    }

    /**
     * Returns the list of converter objects based on the given converter map
     *
     * @param array $convmap List of converter names for the values at the position in the CSV file
     * @return array Associative list of positions and converter objects
     */
    protected function getConverterList(array $convmap)
    {
        $convlist = [];

        foreach ($convmap as $idx => $name) {
            $convlist[$idx] = \Aimeos\MW\Convert\Factory::createConverter(
                $name
            );
        }

        return $convlist;
    }

    /**
     * Returns the rows from the CSV file up to the maximum count
     *
     * @param \Aimeos\MW\Container\Content\Iface $content CSV content object
     * @param integer $maxcnt Maximum number of rows that should be retrieved at once
     * @param integer $codePos Column position which contains the unique supplier code (starting from 0)
     * @return array List of arrays with supplier codes as keys and list of values from the CSV file
     */
    protected function getData(
        \Aimeos\MW\Container\Content\Iface $content,
        $maxcnt,
        $codePos
    ) {
        $count = 0;
        $data = [];

        while ($content->valid() && $count++ < $maxcnt) {
            $row = $content->current();
            $data[$row[$codePos]] = $row;
            $content->next();
        }

        return $data;
    }

    /**
     * Returns the default mapping for the CSV fields to the domain item keys
     *
     * Example:
     *  'item' => array(
     *  	0 => 'supplier.code', // e.g. unique EAN code
     *  	1 => 'supplier.label', // UTF-8 encoded text, also used as supplier name
     *		2 => 'supplier.status', // If category should be shown in the frontend
     *  ),
     *  'text' => array(
     *  	3 => 'text.type', // e.g. "short" for short description
     *  	4 => 'text.content', // UTF-8 encoded text
     *  ),
     *  'media' => array(
     *  	5 => 'media.url', // relative URL of the supplier image on the server
     *  ),
     *
     * @return array Associative list of domains as keys ("item" is special for the supplier itself) and a list of
     * 	positions and the domain item keys as values.
     */
    protected function getDefaultMapping()
    {
        return [
            'item' => [
                0 => 'supplier.code',
                1 => 'supplier.label',
                2 => 'supplier.status',
            ],
            'text' => [
                3 => 'text.type',
                4 => 'text.content',
            ],
            'media' => [
                5 => 'media.url',
            ],
            /* 'product' => [],
             'address' => [], */
        ];
    }

    /**
     * Returns the mapped data from the CSV line
     *
     * @param array $data List of CSV fields with position as key and domain item key as value (mapped data is removed)
     * @param array $mapping List of domain item keys with the CSV field position as key
     * @return array List of associative arrays containing the chunked properties
     */
    protected function getMappedChunk(array &$data, array $mapping)
    {
        $idx = 0;
        $map = [];

        foreach ($mapping as $pos => $key) {
            if (isset($map[$idx][$key])) {
                $idx++;
            }

            if (isset($data[$pos])) {
                $map[$idx][$key] = $data[$pos];
                unset($data[$pos]);
            }
        }

        return $map;
    }

    /**
     * Returns the processor object for saving the supplier related information
     *
     * @param array $mappings Associative list of processor types as keys and index/data mappings as values
     * @return \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface Processor object
     */
    protected function getProcessors(array $mappings)
    {
        $context = $this->getContext();
        $config = $context->getConfig();
        $object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Done(
            $context,
            []
        );

        foreach ($mappings as $type => $mapping) {
            if (ctype_alnum($type) === false) {
                $classname = is_string($type)
                    ? '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Processor\\' .
                        $type
                    : '<not a string>';
                throw new \Aimeos\Controller\Jobs\Exception(
                    sprintf(
                        'Invalid characters in class name "%1$s"',
                        $classname
                    )
                );
            }

            $name = $config->get(
                'controller/common/supplier/import/csv/processor/' .
                    $type .
                    '/name',
                'Standard'
            );

            if (ctype_alnum($name) === false) {
                $classname = is_string($name)
                    ? '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Processor\\' .
                        $type .
                        '\\' .
                        $name
                    : '<not a string>';
                throw new \Aimeos\Controller\Jobs\Exception(
                    sprintf(
                        'Invalid characters in class name "%1$s"',
                        $classname
                    )
                );
            }

            $classname =
                '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Processor\\' .
                ucfirst($type) .
                '\\' .
                $name;

            if (class_exists($classname) === false) {
                throw new \Aimeos\Controller\Jobs\Exception(
                    sprintf('Class "%1$s" not found', $classname)
                );
            }

            $object = new $classname($context, $mapping, $object);

            \Aimeos\MW\Common\Base::checkClass(
                '\\Aimeos\\Controller\\Common\\Supplier\\Import\\Csv\\Processor\\Iface',
                $object
            );
        }

        return $object;
    }
}
