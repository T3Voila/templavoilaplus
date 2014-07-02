

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Introduction
^^^^^^^^^^^^

You can use TemplaVoilas API for templates in your own plugins if you
like. As an example of this, lets look at how the “mininews” extension
works:

The mininews extension has three displays of content:

- An archive listing of all news in the archive, including a search box
  and links for browsing to the next page if there are more than 20 news
  or so.

- A detail display which showns a single news item in full

- A frontpage teaser listing showing the three most recent news with
  “read more” links.

Each of these displays are by default rendered by hardcoded HTML in
the plugin. The hardcoded HTML is designed to be sufficient in most
cases since you can style it all by CSS styles. Thus you might not
need to make an alternative template!

However if you would like to restructure the output more than you can
do by the CSS styles on the default HTML you can create a TemplaVoila
template. The mininews extension supports this.

