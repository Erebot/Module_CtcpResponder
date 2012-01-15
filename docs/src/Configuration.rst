Configuration
=============

.. _`configuration options`:

Options
-------

This module provides several configuration options.

..  table:: Options for |project|

    +-------------------+-----------+-----------+---------------------------+
    | Name              | Type      | Default   | Description               |
    |                   |           | value     |                           |
    +===================+===========+===========+===========================+
    | allow_chan_ctcp   | boolean   | TRUE      | Whether the bot should    |
    |                   |           |           | respond to CTCP requests  |
    |                   |           |           | sent to IRC channels.     |
    |                   |           |           | If set to FALSE, the bot  |
    |                   |           |           | will only respond to      |
    |                   |           |           | requests which are        |
    |                   |           |           | directly sent to it.      |
    +-------------------+-----------+-----------+---------------------------+
    | ctcp_*            | string    | See notes | The static text to use in |
    |                   |           |           | a reply to a CTCP request |
    |                   |           |           | of type "*".              |
    +-------------------+-----------+-----------+---------------------------+


Notes:

    *   You may use several "ctcp_*" parameters for the different CTCP requests
        you want the bot to handle.
    *   Using an empty string as the value for "ctcp_*" makes the bot ignore
        CTCP requests of that type.
    *   The "*" part of "ctcp_*" is case-sensitive.
        The usual CTCP requests have their name written in uppercase
        (``VERSION``, ``TIME``, ``PING``, ...).
    *   By default, this module handles a few generic CTCP requests,
        listed in the table below:

..  table:: Default responses to the usual CTCP requests

    +-------------------+-----------------------+---------------------------+
    | CTCP type         | Default response      | Example                   |
    +===================+=======================+===========================+
    | FINGER            | Information about who | clicky@madlax (started 23 |
    |                   | started the bot, the  | secondes ago)             |
    |                   | name of the machine   |                           |
    |                   | it is running on and  |                           |
    |                   | its uptime.           |                           |
    +-------------------+-----------------------+---------------------------+
    | VERSION           | The bot's current     | Erebot v0.5.0-dev1 /      |
    |                   | version, as well as   | PHP 5.3.2-1ubuntu4.5 /    |
    |                   | PHP's version and     | Linux 2.6.32-27-generic   |
    |                   | information on the    |                           |
    |                   | operating system the  |                           |
    |                   | bot is running on     |                           |
    |                   | (name and version).   |                           |
    +-------------------+-----------------------+---------------------------+
    | SOURCE            | URL to use to         | http://pear.erebot.net/   |
    |                   | download the bot      |                           |
    +-------------------+-----------------------+---------------------------+
    | CLIENTINFO        | URL to use to get     | http://www.erebot.net/    |
    |                   | information on the    |                           |
    |                   | bot.                  |                           |
    +-------------------+-----------------------+---------------------------+
    | ERRMSG            | The latest error      | Success                   |
    |                   | message detected by   |                           |
    |                   | the bot or "Success". |                           |
    +-------------------+-----------------------+---------------------------+
    | PING              | Exactly the same text | n/a  |
    |                   | as in the request     |                           |
    |                   | (eg. some timestamp). |                           |
    +-------------------+-----------------------+---------------------------+
    | TIME              | The current date and  | Thu, 21 Dec 2000 16:01:07 |
    |                   | time where the bot is | +0200                     |
    |                   | running, using the    |                           |
    |                   | format from           |                           |
    |                   | :rfc:`2822`.          |                           |
    +-------------------+-----------------------+---------------------------+



Example
-------

Here, we make the bot ignore ``FINGER`` and ``ERRMSG`` requests, we replace
the default ``VERSION`` reply and we add a response to a custom type of CTCP
called ``USERINFO`` (which is in fact a type most IRC clients support).


..  parsed-code:: xml

    <?xml version="1.0"?>
    <configuration
      xmlns="http://localhost/Erebot/"
      version="..."
      language="fr-FR"
      timezone="Europe/Paris"
      commands-prefix="!">

      <modules>
        <!-- Other modules ignored for clarity. -->

        <!--
          Configure the module:
          - ignore FINGER/ERRMSG requests.
          - replace VERSION string.
          - add custom CTCP type AWAKENING.
        -->
        <module name="Erebot_Module_CtcpResponder">
          <param name="ctcp_FINGER" value="" />
          <param name="ctcp_ERRMSG" value="" />
          <param name="ctcp_VERSION"  value="Erebot v0.0.1-alpha2" />
          <param name="ctcp_AWAKENING" value="Elda Taruta" />
        </module>
      </modules>
    </configuration>

.. vim: ts=4 et
