<?php
/**
 * Defines the test case for Tox\Application\Dao\Cache\Dao.
 *
 * This file is part of Tox.
 *
 * Tox is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Tox is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tox.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright © 2012-2013 SZen.in
 * @license   GNU General Public License, version 3
 */

namespace Tox\Application\Dao\Cache;

use PHPUnit_Framework_TestCase;

require_once __DIR__ . '/../../../../../../src/tox/core/assembly.php';
require_once __DIR__ . '/../../../../../../src/tox/core/isingleton.php';
require_once __DIR__ . '/../../../../../../src/tox/data/isource.php';
require_once __DIR__ . '/../../../../../../src/tox/application/idao.php';
require_once __DIR__ . '/../../../../../../src/tox/application/dao/dao.php';
require_once __DIR__ . '/../../../../../../src/tox/application/dao/cache/dao.php';
require_once __DIR__ . '/../../../../../../src/tox/data/ikv.php';
require_once __DIR__ . '/../../../../../../src/tox/data/kv/kv.php';
require_once __DIR__ . '/../../../../../../src/tox/data/kv/memcache.php';

require_once __DIR__ . '/../../../../../../src/tox/core/exception.php';
require_once __DIR__ . '/../../../../../../src/tox/application/dao/cache/@exception/datasourceexpected.php';
require_once __DIR__ . '/../../../../../../src/tox/application/dao/cache/@exception/invalidcachingdatadomain.php';

use Tox;
use stdClass;

/**
 * Tests Tox\Application\Dao\Cache\Dao.
 *
 * @internal
 *
 * @package tox.application.dao.cache
 * @author  Trainxy Ho <trainxy@gmail.com>
 */
class DaoTest extends PHPUnit_Framework_TestCase
{

    public function testCacheFlowWouldWorkFine()
    {
        $o_mock_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_mock_dao->expects($this->once())
            ->method('read')
            ->with($this->equalTo('111'))
            ->will($this->returnValue('hello'));

        $s_key = md5(get_class($o_mock_dao) . '-' . '111');

        $o_mock_cache = $this->getMock('Tox\\Data\\KV\\Memcache', array('get', 'set'));
        $o_mock_cache->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo($s_key))
            ->will($this->returnValue(false));
        $o_mock_cache->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo($s_key))
            ->will($this->returnValue('world'));

        $o_cache_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Cache\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_cache_dao::bindDomain($o_mock_cache);
        $o_cache_dao::getInstance();
        $o_cache_dao->bind($o_mock_dao);

        $this->assertEquals('hello', $o_cache_dao->read('111'));
        $this->assertEquals('world', $o_cache_dao->read('111'));
    }

    public function testTransmitToNormalDaoWhenCreateUpdateDeleteOperationCalled()
    {
        $o_mock_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_mock_dao->expects($this->once())
            ->method('create')
            ->with($this->equalTo(array('title' => 'hello', 'description' => 'world')))
            ->will($this->returnValue('111'));
        $o_mock_dao->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('111'),
                $this->equalTo(array('title' => 'new title'))
            );
        $o_mock_dao->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('111'));

        $s_key = md5(get_class($o_mock_dao) . '-' . '111');
        $o_mock_cache = $this->getMock('Tox\\Data\\KV\\Memcache', array('get', 'set', 'delete'));
        $o_mock_cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo($s_key))
            ->will($this->returnValue(array('id' => '111', 'title' => 'original', 'description' => 'bbb')));
        $o_mock_cache->expects($this->at(0))
            ->method('set')
            ->with(
                $this->equalTo($s_key),
                $this->equalTo(array('id' => '111', 'title' => 'hello', 'description' => 'world')),
                null
            );
        $o_mock_cache->expects($this->at(2))
            ->method('set')
            ->with(
                $this->equalTo($s_key),
                $this->equalTo(array('id' => '111', 'title' => 'new title', 'description' => 'bbb')),
                null
            );

        $o_cache_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Cache\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_cache_dao::bindDomain($o_mock_cache);
        $o_cache_dao::getInstance();
        $o_cache_dao->bind($o_mock_dao);

        $o_cache_dao->create(array('title' => 'hello', 'description' => 'world'));
        $o_cache_dao->update('111', array('title' => 'new title'));
        $o_cache_dao->delete('111');
    }

    public function testTransmitToNormalDaoWhenCountByAndListAndSortByOperationCalled()
    {
        $o_mock_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_mock_dao->expects($this->once())
            ->method('countBy');
        $o_mock_dao->expects($this->once())
            ->method('listBy');

        $o_mock_cache = $this->getMock('Tox\\Data\\KV\\Memcache', array('get', 'set', 'delete'));

        $o_cache_dao = $this->getMockBuilder('Tox\\Application\\Dao\\Cache\\Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $o_cache_dao::bindDomain($o_mock_cache);
        $o_cache_dao::getInstance();
        $o_cache_dao->bind($o_mock_dao);

        $o_cache_dao->countBy();
        $o_cache_dao->listBy();
    }
}

// vi:ft=php fenc=utf-8 ff=unix ts=4 sts=4 et sw=4 fen fdm=indent fdl=1 tw=120
