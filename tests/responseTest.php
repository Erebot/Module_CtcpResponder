<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once(
    dirname(__FILE__) .
    DIRECTORY_SEPARATOR . 'testenv' .
    DIRECTORY_SEPARATOR . 'bootstrap.php'
);

class   CtcpResponderTest
extends ErebotModuleTestCase
{
    protected $_module = NULL;

    public function setUp()
    {
        parent::setUp();
        $this->_module = new Erebot_Module_CtcpResponder(NULL);
        $this->_module->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_ALL
        );
    }

    public function tearDown()
    {
        $this->_module->unload();
        parent::tearDown();
    }

    public function testUnknownType()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->throwException(
                new Erebot_NotFoundException('Not found')
            ));
        $event = new Erebot_Event_PrivateCtcp(
            $this->_connection,
            'foo',
            'UNKNOWN',
            'foobar'
        );
        // The event deals with an UNKNOWN CTCP request
        // and must therefore be ignored.
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));
    }

    public function testIgnoredType()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnValue(''));
        $event = new Erebot_Event_PrivateCtcp(
            $this->_connection,
            'foo',
            'VERSION',
            'foobar'
        );
        // The event deals with a CTCP request
        // which was configured to be ignored.
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));
    }

    public function testDefaultResponses()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->throwException(
                new Erebot_NotFoundException('Not found')
            ));
        $queries = array(
            'FINGER',
            'VERSION',
            'SOURCE',
            'CLIENTINFO',
            'ERRMSG',
            'PING',
            'TIME',
        );
        foreach ($queries as $query) {
            $event = new Erebot_Event_PrivateCtcp(
                $this->_connection,
                'foo',
                $query,
                'foobar'
            );
            $this->_module->handleCtcp($this->_eventHandler, $event);
            $this->assertSame(1, count($this->_outputBuffer));
            $this->_outputBuffer = array();
        }
    }

    public function testStaticResponse()
    {
        $response = 'And the unknown becomes reknown';
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnValue($response));
        $event = new Erebot_Event_PrivateCtcp(
            $this->_connection,
            'foo',
            'UNKNOWN',
            'foobar'
        );
        // The event deals with an UNKNOWN CTCP request,
        // which was configured to get an answer.
        // We check that answer.
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(1, count($this->_outputBuffer));
        $this->assertEquals(
            "NOTICE foo :\001UNKNOWN $response\001",
            $this->_outputBuffer[0]
        );
    }

    public function testChannelResponses()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->throwException(
                new Erebot_NotFoundException('Not found')
            ));
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseBool')
            ->will($this->onConsecutiveCalls(FALSE, TRUE));

        $event = new Erebot_Event_ChanCtcp(
            $this->_connection,
            '#test',
            'foo',
            'SOURCE',
            'foobar'
        );
        // The event deals with a chan CTCP request,
        // which is forbidden here.
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));

        // Now, we make the same test, but this time
        // chan-directed CTCP requests are allowed.
        $event = new Erebot_Event_ChanCtcp(
            $this->_connection,
            '#test',
            'foo',
            'SOURCE',
            'foobar'
        );
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(1, count($this->_outputBuffer));
        $this->assertEquals(
            "NOTICE #test :\001SOURCE http://pear.erebot.net/\001",
            $this->_outputBuffer[0]
        );
    }
}

