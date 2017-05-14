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


Page / User TSconfig reference
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The following TypoScript configuration can be used either as Page
TSconfig or as User TSconfig.


tx\_templavoila
"""""""""""""""

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
         storagePid

   Data type
         integer

   Description
         Alternative storage page for DS/TO records. If set, DS/TO will be
         fetched from this page, not from the GRSP

   Default


.. ###### END~OF~TABLE ######

[tsconfig:tx\_templavoila]


mod.web\_txtemplavoilaplusLayout
""""""""""""""""""""""""

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
         createPageWizard.fieldNames

   Data type
         list

   Description
         With this option you may specify a selection of field names of the
         *pages* table to be displayed in the create-new-page-wizard.The “\*”
         value can be used to show all fields.

         **Example:**

         ::

            mod.web_txtemplavoilaplusLayout.createPageWizard {
             fieldNames = hidden, title, author, description, abstract
            }

         This will create an editing form like this:

         |img-3|

   Default
         hidden, title, alias


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
         enableEditIconForRefElements

   Data type
         boolean

   Description
         If set, you get edit icon for referenced content elements

   Default
         Not set


.. container:: table-row

   Property
         sideBarEnable

   Data type
         boolean

   Description
         Defines if the toolbar in the TemplaVoila Page Module is visible or
         not. By default it is visible as a row of tabs.

   Default
         TRUE


.. container:: table-row

   Property
         sideBarPosition

   Data type
         string

   Description
         Defines the position of a toolbar within the TemplaVoila Page Module.
         Possible values:  *toptabs, toprows, left*

         ***toptabs*** generates a toolbar on top of the page module's content
         with dynamic tabs which allow you to switch between the different
         option categories.

         |img-4|

         ***toprows*** also generates a toolbar on the top of the page, but
         uses rows which can be expanded and collapsed instead of tabs.

         |img-5|

         ***left*** instead creates a sidebar at left part of the page module.
         This sidebar may be shown and hidden by clicking at the little plus /
         minus sign at the upper right corner of the sidebar.

         |img-6|

         **Note:** The menu items which are available in this toolbar depend on
         the extensions you have installed as they provide the functionality.

   Default
         toptabs


.. container:: table-row

   Property
         tabList

   Data type
         string

   Description
         If used specific tabs can be hidden. Possible values:localization,vers
         ioning,nonUsedElements,headerFields,advancedFunctions

   Default


.. container:: table-row

   Property
         showTabsIfEmpty

   Data type
         boolean

   Description
         If set, all Tabs are rendered even if they are empty

   Default
         FALSE


.. container:: table-row

   Property
         disableContainerElementLocalizationWarning

   Data type
         boolean

   Description
         Container elements used with TemplaVoila should not be localized.
         Therefore a warning is displayed if <langDisable> is false for such
         data structures. If localization was enabled on purpose this warning
         will be misleading of course and can be disabled by this setting.

   Default
         FALSE


.. container:: table-row

   Property
         disableContainerElementLocalizationWarning\_warningOnly

   Data type
         boolean

   Description
         Sometimes you might like to localize container elements with
         <langChildren> enabled. This is especially the case if the element is
         more than a container but also has content fields that need
         localization.

         The problem is that only the default language values of the reference
         fields (non-content fields) is recognized by TemplaVoila page module
         while in the frontend the rendering depends on inheritance and what
         other references someone might accidentally put into the reference
         fields of other languages! So, if you use localization for such mixed
         records, a) make sure inheritance is enabled (so for all languages the
         references set for default language is used) and b) that no references
         are localized (leave reference fields for other languages empty).

   Default
         FALSE


.. container:: table-row

   Property
         disableElementMoreThanOnceWarning

   Data type
         boolean

   Description
         Elements which are used more than once on a page usually show a
         warning message. If users find this misleading this setting can be
         used to hide it.

   Default
         FALSE


.. container:: table-row

   Property
         disableReferencedElementNotification

   Data type
         boolean

   Description
         Elements which referenced from other pages will show a notification
         message. If users find this misleading this setting can be used to
         hide it.

   Default
         FALSE


.. container:: table-row

   Property
         translationParadigm

   Data type
         string keyword

   Description
         If set to “free” the Page module will act according to a translation
         paradigm called “Free” (opposite to “Bound”) where you use the Page DS
         <langDisable> to indicate whether or not a page can be localized and
         there localizations of default language records are linked into
         separate content structures provided by the data structure either as
         Inheritance or Separate.

         You should read the document “Localization Guide” which includes
         detailed information about these concepts.

   Default


.. container:: table-row

   Property
         disableDisplayMode

   Data type
         list of keywords

   Description
         In the “Bound” translation paradigm, you will see a selector box that
         allows you to filter which languages you see for editing. Here you can
         disable certain of the available options by setting keywords in this
         list.

         Options are: default, selectedLanguage, onlyLocalized

   Default


.. container:: table-row

   Property
         hideCopyForTranslation

   Data type
         boolean

   Description
         If set, the links “Copy for translations” won't be rendered in pafe
         module. This is useful if you configured a special translation model
         eg for seperate content and you don't want the editor translating
         content elements

   Default
         Not set


.. container:: table-row

   Property
         recordDisplay\_tables

   Data type
         list of table names

   Description
         Comma-separated list of table name to shown in "Record list" tab. This
         feature requires TYPO3 version >= 4.0.5.

         Record list allows to edit any record on the page without a need to
         switch to list module.

         If this value is not set, record list will not be shown at all.

   Default
         Not set


.. container:: table-row

   Property
         recordDisplay\_maxItems

   Data type
         integer

   Description
         Maximum number of records to display. If this values is not set, value
         from $TCA is used. If value in $TCA is not set, default is used.

   Default
         10


.. container:: table-row

   Property
         recordDisplay\_alternateBgColors

   Data type
         boolean

   Description
         If set to 1, forces to alternate background colors for records

   Default
         False


.. container:: table-row

   Property
         additionalDoktypesRenderToEditView

   Data type
         list of doktypes

   Description
         Comma-separated list of doktypes that should be rendered to the edit
         view. This is useful if you use a sysfolder as container for content
         elements. To enable the edit view for the sysfolder use this pageTS:

         ::

            mod.web_txtemplavoilaplusLayout {
              additionalDoktypesRenderToEditView = 254
            }

   Default


.. container:: table-row

   Property
         blindIcons

   Data type
         list of keywords

   Description
         Following icons can be blinded:

         new,edit,copy,cut,ref,paste,pasteAfter,pasteSubRef,browse,delete,makeL
         ocal,unlink,hide

         Example:

         ::

            mod.web_txtemplavoilaplusLayout {
              blindIcons = browse,edit,new
            }

   Default


.. container:: table-row

   Property
         stylesheet

   Data type
         string

   Description
         Alternative or additional CSS stylesheet for the pagemodule. Path must
         either contain an extension key using “EXT:foo” notation or must be
         relative to the typo3-directory.

         Example – to replace the main TemplaVoila stylesheet:

         ::

            mod.web_txtemplavoilaplusLayout {
              stylesheet = ../fileadmin/css/tvpagemodule.css
            }

         Example – to add further CSS files without replacing the main
         stylesheet:

         ::

            mod.web_txtemplavoilaplusLayout.stylesheet {
              file1 = EXT:/res/css/file1.css
            }

   Default


.. container:: table-row

   Property
         javascript

   Data type
         Array

   Description
         Additional javascript files for the pagemodule. Path must either
         contain an extension key using “EXT:foo” notation or must be relative
         to the typo3-directory.

         Example:

         ::

            mod.web_txtemplavoilaplusLayout.javascript {
              file1 = ../fileadmin/templates/js/jquery.js
              file2 = ../fileadmin/templates/css/backend.js
            }

   Default


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


.. container:: table-row

   Property
         debug

   Data type
         boolean

   Description
         For develop: with debug flag set the javascript won't be loaded
         minified.

   Default


.. container:: table-row

   Property
         enableOutlineForNonAdmin

   Data type
         boolean

   Description
         Use this setting to provide the option to turn on the outline view
         also for non admins.

   Default
         Not set


.. container:: table-row

   Property
         keepElementsInClipboard

   Data type
         boolean

   Description
         Use this setting to change to clipboard behaviour. After elements are
         copied or referenced they won't be removed from the clipboard.

   Default
         Not set


.. container:: table-row

   Property
         previewTitleMaxLen

   Data type
         integer

   Description
         Limit the size of the title for the content elements.

   Default
         50


.. container:: table-row

   Property
         previewDataMaxLen

   Data type
         integer

   Description
         Limit the size of the text within the preview-area of an content
         element.

   Default
         2000


.. container:: table-row

   Property
         previewDataMaxWordLen

   Data type
         integer

   Description
         Limit the size of single words within the preview text. This is
         supposed to avoid that long words “stretch” the element too much

   Default
         75


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
         true.


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


.. ###### END~OF~TABLE ######

[tsconfig:mod.web\_txtemplavoilaplusLayout]


mod.web\_txtemplavoilaplusCenter
""""""""""""""""""""""""

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


.. container:: table-row

   Property
         dsPreviewIconThumb

   Data type
         string

   Description
         If set, the previewIcon for DS record is displayed as thumb

         dsPreviewIconThumb = 1 will use predefined thumb size (56x56)

         dsPreviewIconThumb = [width]x[height] will resize it to given size.

         Example:

         mod.web\_txtemplavoilaplusCenter.dsPreviewIconThumb = 120x80

   Default


.. container:: table-row

   Property
         toPreviewIconThumb

   Data type
         string

   Description
         If set, the previewIcon for TO record is displayed as thumb

         toPreviewIconThumb = 1 will use predefined thumb size (56x56)

         toPreviewIconThumb = [width]x[height] will resize it to given size.

         Example:

         mod.web\_txtemplavoilaplusCenter.toPreviewIconThumb = 120x80

   Default


.. container:: table-row

   Property
         newTVsiteTemplate

   Data type
         string

   Description
         Path to the xml template for the "New Site Wizard". The original used
         xml template is located in mod2/new\_tv\_site.xml,

         Example:mod.web\_txtemplavoilaplusCenter.newTVsiteTemplate =
         fileadmin/my\_new\_tv\_site.xml

   Default


.. ###### END~OF~TABLE ######

[tsconfig:mod.web\_txtemplavoilaplusCenter]

