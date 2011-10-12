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
    /// \copydoc Erebot_Module_Base::_reload()
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
    }

    /// \copydoc Erebot_Module_Base::_unload()
    protected function _unload()
    {
    }

    /**
     * Handles CTCP requests.
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

        $translator = $this->getTranslator($chan);
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

        // For more information on valid CTCP messages, see:
        // http://www.irchelp.org/irchelp/rfc/ctcpspec.html
        switch ($ctcpType) {
            case 'FINGER':
                $bot            = $this->_connection->getBot();
                $runningTime    = $bot->getRunningTime();
                $uptime = ($runningTime === FALSE ? '???' :
                    $translator->formatDuration($runningTime));
                $msg = $translator->gettext(
                    '<var name="user"/>@<var name="host"/> (started '.
                    '<var name="uptime"/> ago)'
                );
                $cls = $this->getFactory('!Styling');
                $formatter = new $cls($msg, $translator);
                $formatter->assign('user', get_current_user());
                $formatter->assign('host', php_uname('n'));
                $formatter->assign('uptime', $uptime);
                $response = $formatter->render();
                break;

            case 'VERSION':
                $bot =& $this->_connection->getBot();
                $response =
                    $bot->getVersion().' ($Rev$) / '.
                    'PHP '.PHP_VERSION.' / '.
                    php_uname('s').' '.php_uname('r');
                unset($bot);
                break;

            case 'SOURCE':
                $response = "http://pear.erebot.net/";
                break;

            case 'CLIENTINFO':
                $response = "http://www.erebot.net/";
                break;

            case 'ERRMSG':
                $hasPosix = in_array('posix', get_loaded_extensions());
                // Latest low-level (POSIX) error.
                if ($hasPosix)
                    $response = posix_strerror(posix_errno());

                // Nothing to worry about.
                else
                    $response = $translator->gettext("No error");

                break;

            case 'PING':
                $response = (string) $event->getText();
                break;

            case 'TIME':
                $response = date('r');
                break;
        }

        if ($response !== NULL)
            return $this->sendMessage(
                $target,
                $ctcpType.' '.$response,
                'CTCPREPLY'
            );
    }
}

