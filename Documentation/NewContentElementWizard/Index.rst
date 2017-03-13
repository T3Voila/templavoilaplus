.. include:: Images.txt

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


New Content Element Wizard
--------------------------

|img-7|

The new content element wizard is completely configurable with
TSConfig, the same methods like in TYPO3 4.3 core. Place it in Page
TSConfig or User TSConfig.

The typoscript key for the configuration in general is
“templavoila.wizards.newContentElement” (TYPO3 core uses
“mod.wizards.newContentElement”).

To show the elemnts grouped in tabs use this configuration:

::

   templavoila.wizards.newContentElement.renderMode = tabs

For general configuration please look at core wizard configuration,
just use “templavoila” instead of “mod”:

`http://typo3.org/documentation/document-
library/references/doc\_core\_tsconfig/4.3.2/view/1/5/#id2505051
<http://typo3.org/documentation/document-
library/references/doc_core_tsconfig/4.3.2/view/1/5/#id2505051>`_

FCE's are named like fce\_[uid of TO]. So FCE with TO uid 17 will be
fce\_17. If you want to allow only this FCE you can do it with
following configuration:

::

   templavoila.wizards.newContentElement.wizardItems.fce.show = fce_17


