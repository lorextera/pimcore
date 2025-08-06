<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Test\Unit\CustomReports\Tool\Adapter;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\Sql;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use ReflectionClass;
use stdClass;

/**
 * Tests for CustomReports SQL Adapter boolean filter fix
 */
final class SqlTest extends TestCase
{
    private function createSqlAdapter(): Sql
    {
        $config = new Config();
        $config->setDataSourceConfig([
            [
                'sql' => 'SELECT id, name, is_active FROM test_table',
            ],
        ]);

        $stdConfig = new stdClass();
        $stdConfig->sql = 'SELECT id, name, is_active FROM test_table';

        return new Sql($stdConfig, $config);
    }

    public function testBooleanFilterWithEqualsOperator(): void
    {
        $sql = $this->createSqlAdapter();

        // Test with single = operator
        $filters = [
            [
                'property' => 'is_active',
                'value' => '1',
                'type' => 'boolean',
                'operator' => '=',
            ],
        ];

        $reflection = new ReflectionClass($sql);
        $method = $reflection->getMethod('getBaseQuery');
        $method->setAccessible(true);

        $result = $method->invokeArgs($sql, [$filters, []]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertStringContains('is_active = \'1\'', $result['data']);
    }

    public function testBooleanFilterWithDoubleEqualsOperator(): void
    {
        $sql = $this->createSqlAdapter();

        // Test with double == operator (the bug fix)
        $filters = [
            [
                'property' => 'is_active',
                'value' => '1',
                'type' => 'boolean',
                'operator' => '==',
            ],
        ];

        $reflection = new ReflectionClass($sql);
        $method = $reflection->getMethod('getBaseQuery');
        $method->setAccessible(true);

        $result = $method->invokeArgs($sql, [$filters, []]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertStringContains('is_active = \'1\'', $result['data']);
    }

    public function testBothOperatorsProduceSameResult(): void
    {
        $sql = $this->createSqlAdapter();

        $filtersEquals = [
            [
                'property' => 'is_active',
                'value' => '1',
                'type' => 'boolean',
                'operator' => '=',
            ],
        ];

        $filtersDoubleEquals = [
            [
                'property' => 'is_active',
                'value' => '1',
                'type' => 'boolean',
                'operator' => '==',
            ],
        ];

        $reflection = new ReflectionClass($sql);
        $method = $reflection->getMethod('getBaseQuery');
        $method->setAccessible(true);

        $resultEquals = $method->invokeArgs($sql, [$filtersEquals, []]);
        $resultDoubleEquals = $method->invokeArgs($sql, [$filtersDoubleEquals, []]);

        // Both operators should produce the same SQL output
        $this->assertEquals($resultEquals['data'], $resultDoubleEquals['data']);
    }
}
