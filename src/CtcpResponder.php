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

namespace Erebot\Module;

/**
 * \brief
 *      A module that responds to CTCP requests.
 */
class CtcpResponder extends \Erebot\Module\Base implements \Erebot\Interfaces\HelpEnabled
{
    /// Maps CTCP types to the callable returning a response for them.
    protected $supportedTypes = array();

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot::Module::Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function reload($flags)
    {
        if ($flags & self::RELOAD_HANDLERS) {
            $handler = new \Erebot\EventHandler(
                array($this, 'handleCtcp'),
                new \Erebot\Event\Match\Type(
                    '\\Erebot\\Interfaces\\Event\\Base\\CtcpMessage'
                )
            );
            $this->connection->addEventHandler($handler);
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
            foreach ($types as $type) {
                $this->supportedTypes[$type] = array($this, "ctcp$type");
            }
        }
    }

    /**
     * Provides help about this module.
     *
     * \param Erebot::Interfaces::Event::Base_TextMessage $event
     *      Some help request.
     *
     * \param Erebot::Interfaces::TextWrapper $words
     *      Parameters passed with the request. This is the same
     *      as this module's name when help is requested on the
     *      module itself (in opposition with help on a specific
     *      command provided by the module).
     */
    public function getHelp(
        \Erebot\Interfaces\Event\Base\TextMessage $event,
        \Erebot\Interfaces\TextWrapper $words
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        if (count($words) == 1 && $words[0] === get_called_class()) {
            $msg = $this->getFormatter($chan)->_(
                "This module does not provide any command, but ".
                "it provides responses to CTCP requests."
            );
            $this->sendMessage($target, $msg);
            return true;
        }
    }

    /**
     * Handles CTCP requests.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
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
        \Erebot\Interfaces\EventHandler $handler,
        \Erebot\Interfaces\Event\Base\CtcpMessage $event
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } elseif (!$this->parseBool('allow_chan_ctcp', true)) {
            return;
        } else {
            $target = $chan = $event->getChan();
        }

        $ctcpType   = $event->getCtcpType();
        try {
            $response = $this->parseString('ctcp_'.$ctcpType);
        } catch (\Erebot\NotFoundException $e) {
            $response = null;
        }

        if ($response !== null) {
            // Ignore this CTCP request.
            if ($response == "") {
                return;
            }

            return $this->sendMessage(
                $target,
                $ctcpType.' '.$response,
                'CTCPREPLY'
            );
        }

        if (isset($this->supportedTypes[$ctcpType])) {
            $callable = $this->supportedTypes[$ctcpType];
            $response = $callable($event);
        }

        if ($response !== null) {
            return $this->sendMessage(
                $target,
                $ctcpType.' '.$response,
                'CTCPREPLY'
            );
        }
    }

    /**
     * Creates the answer for a CTCP FINGER request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpFINGER(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        $chan = ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage)
                ? null
                : $event->getChan();

        $fmt            = $this->getFormatter($chan);
        $bot            = $this->connection->getBot();
        $runningTime    = $bot->getRunningTime();
        $cls            = $this->getFactory('!Styling\\Variables\\Duration');
        $uptime         =   ($runningTime === false)
                            ? '???'
                            : new $cls($runningTime);
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
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpVERSION(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        $bot        = $this->connection->getBot();
        $response   =
            'PHP '.PHP_VERSION.' / '.
            php_uname('s').' '.php_uname('r');
        return $response;
    }

    /**
     * Creates the answer for a CTCP SOURCE request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpSOURCE(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        return "https://github.com/Erebot/Erebot_Module_CtcpResponder";
    }

    /**
     * Creates the answer for a CTCP CLIENTINFO request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpCLIENTINFO(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        return "http://www.erebot.net/";
    }

    /**
     * Creates the answer for a CTCP ERRMSG request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpERRMSG(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        $hasPosix = in_array('posix', get_loaded_extensions());

        // Latest low-level (POSIX) error.
        if ($hasPosix) {
            $response = posix_strerror(posix_get_last_error());
        } else {
            // Nothing to worry about.
            $chan = ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage)
                    ? null
                    : $event->getChan();

            $fmt = $this->getFormatter($chan);
            $response = $fmt->_("No error");
        }
        return $response;
    }

    /**
     * Creates the answer for a CTCP PING request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpPING(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        return (string) $event->getText();
    }

    /**
     * Creates the answer for a CTCP TIME request.
     *
     * \param Erebot::Interfaces::Event::Base::CtcpMessage $event
     *      CTCP request to handle.
     *
     * \retval string
     *      Answer to that CTCP request.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ctcpTIME(\Erebot\Interfaces\Event\Base\CtcpMessage $event)
    {
        return date('r');
    }
}
