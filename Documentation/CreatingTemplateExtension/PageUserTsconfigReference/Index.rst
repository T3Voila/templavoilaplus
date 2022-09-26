.. include:: /Includes.rst.txt

Page / User TSconfig reference
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The following TypoScript configuration can be used either as Page
TSconfig or as User TSconfig (with the :typoscript:`page.`-prefix).

mod.web\_txtemplavoilaplusLayout
""""""""""""""""""""""""""""""""

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         enableDeleteIconForLocalElements

   Data type
         Integer

   Description
         With this option you can specify whether the page-module should render
         a delete icon for an element or whether the unlink icon should be
         used. Whenever you enable the delete icons there might still be
         situations where the unlink icon is the only appropriate option (e.g.
         if a element is only referenced on the page).

         If you want to keep the unlink icons use the following setting:

         **enableDeleteIconForLocalElements = 0**

         If you want to provide the unlink icon and the delete icon side-by-
         side use:

         **enableDeleteIconForLocalElements = 1**

         If you want to avoid the unlink icon as often as possible and provide
         only the delete icon whenever possible use:

         **enableDeleteIconForLocalElements = 2**

   Default
         0


.. container:: table-row

   Property
         useLiveWorkspaceForReferenceListUpdates

   Data type
         boolean

   Description
         Any modification to reference lists will be made in Live Workspace.
         Setting is used to avoid `competitive list <http://dict.tu-chemnitz.de
         /english-german/competitive.html>`_ edits in multiple workspaces.

         For details see: `http://bugs.typo3.org/view.php?id=13165
         <http://bugs.typo3.org/view.php?id=13165>`_

   Default
         1

.. container:: table-row

   Property
         enableContentAccessWarning

   Data type
         boolean

   Description
         A messages is shown whenever an editor opens the page-module without
         proper permissions to edit the content of the page, modify the pages
         table ir modify the pages “Content” field. This setting can be used to
         disable these messages.

   Default
         true


.. container:: table-row

   Property
         enableLocalizationLinkForFCEs

   Data type
         Integer

   Description
         Set the to “1” if you want to show the localization link in the page-
         module for all FCEs with langDisabled = 1 setting.

   Default
         0


.. container:: table-row

   Property
         adminOnlyPageStructureInheritance

   Data type
         string

   Description
         Set to “false” if you want to allow regular users to create separate
         page structures for different languages (if “inheritance” is used).

         Set to “fallback” if you allow users to see inherited structures but
         restrict structure creation.

         Set to “strict” if only admins should be able to see and edit separate
         structures.

   Default
         fallback


.. container:: table-row

   Property
         filterMaps

   Data type
         string/array

   Description
         In order to filter allowed maps you can use this setting with a single
         string or array of strings of identifier prefixes.
         The mapping identifier is defined in
         :file:`yourextension/Configuration/TVP/MappingPlaces.php` and could e.g.
         be `vendor/yourextension/Page/MappingConfiguration`.

         Examples:

         :typoscript:`mod.web_txtemplavoilaplusLayout.filterMaps = vendor/yourextension`

         or

         :typoscript:`mod.web_txtemplavoilaplusLayout.filterMaps.1 = vendor/yourextension/Page`
         :typoscript:`mod.web_txtemplavoilaplusLayout.filterMaps.2 = vendor/yourotherextension/FCE`

   Default
         null




.. container:: table-row

   Property
         additionalRecordData

   Data type
         string/csv

   Description
         In order to add column values as data-attributes to backend preview nodes
         for each table there can be a comma-separated list of columns which should
         be output, e.g. to allow CSS based styling or custom JavaScript.

         Examples:

         :typoscript:`mod.web_txtemplavoilaplusLayout.additionalRecordData.tt_content = layout,imageorient`

   Default
         null


.. ###### END~OF~TABLE ######

[tsconfig:mod.web\_txtemplavoilaplusLayout]


mod.web\_txtemplavoilaplusCenter
""""""""""""""""""""""""""""""""

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         templatePath

   Data type
         List of directories

   Description
         List of paths to directories inside fileadmin/ where templates can be
         found. Must be accessible by the users file mounts.

         **Example:**

         mod.web\_txtemplavoilaplusCenter.templatePath =
         templates,templates/special,templates/main

   Default
         Templates


.. ###### END~OF~TABLE ######

[tsconfig:mod.web\_txtemplavoilaplusCenter]

