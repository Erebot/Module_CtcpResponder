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

class   CtcpResponderTest
extends Erebot_Testenv_Module_TestCase
{
    protected $_module = NULL;

    protected function _mockCtcp($source, $query, $text)
    {
        $event = $this->getMock(
            'Erebot_Interface_Event_PrivateCtcp',
            array(), array(), '', FALSE, FALSE
        );

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->_connection));
        $event
            ->expects($this->any())
            ->method('getSource')
            ->will($this->returnValue($source));
        $event
            ->expects($this->any())
            ->method('getCtcpType')
            ->will($this->returnValue($query));
        $event
            ->expects($this->any())
            ->method('getText')
            ->will($this->returnValue($text));
        return $event;
    }

    public function setUp()
    {
        $this->_module = new Erebot_Module_CtcpResponder(NULL);
        parent::setUp();
        $this->_module->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_MEMBERS
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

        // The event deals with an UNKNOWN CTCP request
        // and must therefore be ignored.
        $event = $this->_mockCtcp('foo', 'UNKNOWN', 'foobar');
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));
    }

    public function testIgnoredType()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnValue(''));

        // The event deals with a CTCP request
        // which was configured to be ignored.
        $event = $this->_mockCtcp('foo', 'VERSION', 'foobar');
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));
    }

    public function typeProvider()
    {
        $types = array(
            'FINGER',
            'VERSION',
            'SOURCE',
            'CLIENTINFO',
            'ERRMSG',
            'PING',
            'TIME',
        );
        $result = array();
        foreach ($types as $type)
            $result[] = array($type);
        return $result;
    }

    /**
     * @dataProvider typeProvider
     */
    public function testDefaultResponses($query)
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->throwException(
                new Erebot_NotFoundException('Not found')
            ));
        $this->_bot
            ->expects($this->any())
            ->method('getRunningTime')
            ->will($this->returnValue(FALSE));

        $event = $this->_mockCtcp('foo', $query, 'foobar');
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(1, count($this->_outputBuffer));
        $this->_outputBuffer = array();
    }

    public function testStaticResponse()
    {
        $response = 'And the unknown becomes reknown';
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnValue($response));

        // The event deals with an UNKNOWN CTCP request,
        // which was configured to get an answer.
        // We check that answer.
        $event = $this->_mockCtcp('foo', 'UNKNOWN', 'foobar');
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

        $event = $this->getMock(
            'Erebot_Interface_Event_ChanCtcp',
            array(), array(), '', FALSE, FALSE
        );
        $event
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->_connection));
        $event
            ->expects($this->any())
                ->method('getChan')
                ->will($this->returnValue('#test'));
        $event
            ->expects($this->any())
            ->method('getSource')
            ->will($this->returnValue('foo'));
        $event
            ->expects($this->any())
            ->method('getCtcpType')
            ->will($this->returnValue('SOURCE'));
        $event
            ->expects($this->any())
            ->method('getText')
            ->will($this->returnValue('foobar'));

        // The event deals with a chan CTCP request,
        // which is forbidden here.
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(0, count($this->_outputBuffer));

        // Now, we make the same test, but this time
        // chan-directed CTCP requests are allowed
        // (see mock object).
        $this->_module->handleCtcp($this->_eventHandler, $event);
        $this->assertSame(1, count($this->_outputBuffer));
        $this->assertEquals(
            "NOTICE #test :\001SOURCE http://pear.erebot.net/\001",
            $this->_outputBuffer[0]
        );
    }
}

