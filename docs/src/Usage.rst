Usage
=====

This module does not provide any command. Just add this module to your
`configuration`_ and you're done.

After that, the bot will automatically start responding to CTCP requests.

Examples
--------

The listing below shows examples of CTCP requests/responses.

..  sourcecode:: irc

    20:19:16 [ctcp(Erebot)] FINGER
    20:19:16 CTCP FINGER reply from Erebot: foo@localhost (démarré il y a 7 heures, 47 minutes, 4 secondes)
    20:19:27 [ctcp(Erebot)] VERSION
    20:19:28 CTCP VERSION reply from Erebot: Erebot v0.5.1 / PHP 5.3.9 / Linux 2.6.38.2-grsec-xxxx-grs-ipv6-64
    20:19:32 [ctcp(Erebot)] SOURCE
    20:19:32 CTCP SOURCE reply from Erebot: http://pear.erebot.net/
    20:19:35 [ctcp(Erebot)] CLIENTINFO
    20:19:35 CTCP CLIENTINFO reply from Erebot: http://www.erebot.net/
    20:19:42 [ctcp(Erebot)] ERRMSG
    20:19:42 CTCP ERRMSG reply from Erebot: Success
    20:19:49 [ctcp(Erebot)] PING foo
    20:19:50 CTCP PING reply from Erebot: foo
    20:19:52 [ctcp(Erebot)] TIME
    20:19:52 CTCP TIME reply from Erebot: Sun, 15 Jan 2012 20:19:52 +0100


..  _`configuration`:
    Configuration.html

.. vim: ts=4 et
