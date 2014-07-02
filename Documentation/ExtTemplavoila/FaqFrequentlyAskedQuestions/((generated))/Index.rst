

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


((generated))
^^^^^^^^^^^^^

Subpages don't inherit datastructure / template object
""""""""""""""""""""""""""""""""""""""""""""""""""""""

**Q:**  *In the frontend, pages will render if I select a
datastructure and a template object, but if I define some DS/TO for a
page's subpages, an error message appears: Couldn't find a Data
Structure set for table/row XXX. Please select a Data Structure and
Template Object first.*

**A:** Make sure that you have configured the frontend plugin
(templavoila\_pi1) correctly in your TypoScript template. The
following configuration will produce the error described above:

::

   page = PAGE
   page.typeNum = 0
   page.10 < plugin.tx_templavoila_pi1

Instead use the following code and have a look at the configuration
section of this manual:

::

   page = PAGE
   page.typeNum = 0
   page.10 = USER
   page.10.userFunc = tx_templavoila_pi1->main_page

**Q:**  *I made “Content Elements” field, added content elements there
but nothing is displayed in FrontEnd. What should I do now?*

**A:** You should check if you have <TypoScriptObjectPath> entry in
that field inside your DS record. If it exists, TemplaVoila will not
show content. This issue exists in TemplaVoila versions up to and
including 1.0 but will be fixed (removed) in future versions. Inspect
DS record and remove any occurrences of <TypoScriptObjectPath> from
the content element fields. Do not forget to clear cache before
checking results.

**Q:**  *I made a new site but frontend is always empty!*

**A:** You forgot to add templates from “CSS static content” extension
in the template record.

**Q:** My template appears to be corrupted in mapping interface. Some
tags loose opening brace.

**A:** Make sure that mbstring.func\_overload is set to 0 in php.ini.

**Q:** My template-mapping was lost during upgrade to version 1.4.x

**A:** During the upgrade the database-definition for the field
“tx\_templavoila\_tmplobj.templatemapping” might have been changed to
“blob”. Usually that's not a proplem but especially when your systems
character-set encoding isn't correct this might have an effect. Try
either to change back the fieldtype to “mediumtext” or check your
system's encoding-settings.

34


