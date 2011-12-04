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

/**
 * \brief
 *      A module that responds to CTCP requests.
 */
class   Erebot_Module_CtcpResponder
extends Erebot_Module_Base
{
    /// Maps CTCP types to the callable returning a response for them.
    protected $_supportedTypes = array();

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot_Module_Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function _reload($flags)
    {
        if ($flags & self::RELOAD_HANDLERS) {
            $handler = new Erebot_EventHandler(
                new Erebot_Callable(array($this, 'handleCtcp')),
                new Erebot_Event_Match_InstanceOf(
                    'Erebot_Interface_Event_Base_CtcpMessage'
                )
            );
            $this->_connection->addEventHandler($handler);
        }

        if ($flags & self::RELOAD_MEMBERS) {
            // For more information on standard CTCP messages, see:
            // http://www.irchelp.org/irchelp/rfc/ctcpspec.html
            // We map each one of those to a "ctcp<TYPE>" method
            // in this module.
            $types = array(
                'FINGER',
                'VERSION',
                'SOURCE',
                'CLIENTINFO',
                'ERRMSG',
                'PING',
                'TIME',
            );
            $callableCls = $this->getFactory('!Callable');
            foreach ($types as $type) {
                $this->_supportedTypes[$type] = new $callableCls(
                    array($this, 'ctcp'.$type)
                );
            }
        }
    }

    /// \copydoc Erebot_Module_Base::_unload()
    protected function _unload()
    {
    }

    /**
     * Handles CTCP requests.
     *
     * \param Erebot_Interface_EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \note
     *      The following types of CTCP requests are
     *      currently supported:
     *      - FINGER
     *      - VERSION
     *      - SOURCE
     *      - CLIENTINFO
     *      - ERRMSG
     *      - PING
     *      - TIME
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function handleCtcp(
        Erebot_Interface_EventHandler           $handler,
        Erebot_Interface_Event_Base_CtcpMessage $event
    )
    {
        if ($event instanceof Erebot_Interface_Event_Base_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else if (!$this->parseBool('allow_chan_ctcp', TRUE))
            return;
        else
            $target = $chan = $event->getChan();

        $ctcpType   = $event->getCtcpType();
        try {
            $response = $this->parseString('ctcp_'.$ctcpType);
        }
        catch (Erebot_NotFoundException $e) {
            $response = NULL;
        }

        if ($response !== NULL) {
            // Ignore this CTCP request.
            if ($response == "")
                return;

            return $this->sendMessage(
                $target,
                $ctcpType.' '.$response,
                'CTCPREPLY'
            );
        }

        if (isset($this->_supportedTypes[$ctcpType])) {
            $callable = $this->_supportedTypes[$ctcpType];
            $response = $callable->invoke($event);
        }

        if ($response !== NULL)
            return $this->sendMessage(
                $target,
                $ctcpType.' '.$response,
                'CTCPREPLY'
            );
    }

    /**
     * Creates the answer for a CTCP FINGER request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpFINGER(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        $chan = ($event instanceof Erebot_Interface_Event_Base_Private)
                ? NULL
                : $event->getChan();

        $fmt            = $this->getFormatter($chan);
        $bot            = $this->_connection->getBot();
        $runningTime    = $bot->getRunningTime();
        $uptime         =   ($runningTime === FALSE)
                            ? '???'
                            : new Erebot_Styling_Duration($runningTime);
        $response = $fmt->_(
            '<var name="user"/>@<var name="host"/> (started '.
            '<var name="uptime"/> ago)',
            array(
                'user' => get_current_user(),
                'host' => php_uname('n'),
                'uptime' => $uptime,
            )
        );
        return $response;
    }

    /**
     * Creates the answer for a CTCP VERSION request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpVERSION(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        $bot        = $this->_connection->getBot();
        $response   =
            $bot->getVersion().' / '.
            'PHP '.PHP_VERSION.' / '.
            php_uname('s').' '.php_uname('r');
        return $response;
    }

    /**
     * Creates the answer for a CTCP SOURCE request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpSOURCE(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        return "http://pear.erebot.net/";
    }

    /**
     * Creates the answer for a CTCP CLIENTINFO request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpCLIENTINFO(
        Erebot_Interface_Event_Base_CtcpMessage $event
    )
    {
        return "http://www.erebot.net/";
    }

    /**
     * Creates the answer for a CTCP ERRMSG request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpERRMSG(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        $hasPosix = in_array('posix', get_loaded_extensions());

        // Latest low-level (POSIX) error.
        if ($hasPosix)
            $response = posix_strerror(posix_errno());

        // Nothing to worry about.
        else {
            $chan = ($event instanceof Erebot_Interface_Event_Base_Private)
                    ? NULL
                    : $event->getChan();

            $fmt = $this->getFormatter($chan);
            $response = $fmt->_("No error");
        }
        return $response;
    }

    /**
     * Creates the answer for a CTCP PING request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpPING(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        return (string) $event->getText();
    }

    /**
     * Creates the answer for a CTCP TIME request.
     *
     * \param Erebot_Interface_Event_Base_CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpTIME(Erebot_Interface_Event_Base_CtcpMessage $event)
    {
        return date('r');
    }
}

