

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


Plugin “pi1” attributes
^^^^^^^^^^^^^^^^^^^^^^^

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:
   
   Data type
         Data type:
   
   Description
         Description:
   
   Default
         Default:


.. container:: table-row

   Property
         TSconst.[constant-name]
   
   Data type
         string
   
   Description
         Defining constants for Data Structure constants. see “<T3Data
         Struction> extensions” section
   
   Default


.. container:: table-row

   Property
         dontInheritValueFromDefault
   
   Data type
   
   
   Description
   
   
   Default


.. container:: table-row

   Property
         advancedHeaderInclusion
   
   Data type
         boolean
   
   Description
         Enables to use the TYPO3 PageRenderer which was introduced with TYPO3
         4.3 for <link>, <style> and <script> header-blocks. Once the TYPO3
         PageRenderer is used further extensions for resource compression or
         CDN handling can be used much easier.
         
         Using this setting also avoids that a single block or file is included
         more that once (e.g. if used among several FCEs).
   
   Default
         FALSE


.. container:: table-row

   Property
         childTemplate
   
   Data type
         string
   
   Description
         Keyword which is used to look for a child-template record.
         
         By default “print” is a value you can set. This is also set
         automatically when “&print=1” is found in the URL
         
         The value is matched with the content of “rendertype” so if you want
         other values than “print” to be available you simply add new items to
         that selector box and use conditions in the TypoScript Template to
         detect the circumstance that should set this value.
   
   Default


.. container:: table-row

   Property
         disableExplosivePreview
   
   Data type
         boolean
   
   Description
         If set, the popup information boxes in the frontend will not appear in
         FE preview mode.
         
         Due to unconvenient behaviour when used with frontend editingthe
         defaults for this setting changes in version 1.4. Use these lines to
         remove the default settings and enabled the explosive preview:
         
         ::
         
            plugin.tx_templavoilaplus_pi1.disableExplosivePreview >
            page.10.disableExplosivePreview >
   
   Default
         TRUE


.. container:: table-row

   Property
         disableErrorMessages
   
   Data type
         boolean
   
   Description
         If set, no error messages will be displayed in the frontend.
         
         **Example:**
         
         ::
         
            plugin.tx_templavoilaplus_pi1.disableErrorMessages = 1
   
   Default
         FALSE


.. container:: table-row

   Property
         renderUnmapped
   
   Data type
         boolean
   
   Description
         Can be used o avoid that unmapped fields are also rendered. This
         option was introduced to gain some performance in the frontend. It
         will be set to “false” as default in version 1.6.x. In earlier version
         the default value is “true”. Setting itself is kept for compatibility
         reasons.
   
   Default
         TRUE


.. ###### END~OF~TABLE ######

[tsref:plugin.tx\_templavoila\_pi1]

