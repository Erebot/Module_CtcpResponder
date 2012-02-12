Welcome to the documentation for Erebot_Module_CtcpResponder!
=============================================================

Erebot_Module_CtcpResponder is a module for `Erebot`_ that responds to
:abbr:`CTCP (Client-To-Client Protocol)` requests.

The following request types are currently supported by this module:

*   ``FINGER``
*   ``VERSION``
*   ``SOURCE``
*   ``CLIENTINFO``
*   ``ERRMSG``
*   ``PING``
*   ``TIME``

For each type of CTCP request, you can choose to use either the default
response to this request (as provided by this module), to use a static string
(see the documentation on this module's `configuration`_ for more information)
or you may also suppress the response entirely (meaning the request gets
ignored entirely).

Contents:

..  toctree::
    :maxdepth: 2

    Prerequisites
    generic/Installation
    Configuration
    Usage

Current status on http://travis-ci.org/:

..  image:: https://secure.travis-ci.org/fpoirotte/Erebot_Module_CtcpResponder.png
    :alt: unknown
    :target: http://travis-ci.org/#!/fpoirotte/Erebot_Module_CtcpResponder/


..  _`Erebot`:
    https://www.erebot.net/
..  _`configuration`:
    Configuration.html

.. vim: ts=4 et

