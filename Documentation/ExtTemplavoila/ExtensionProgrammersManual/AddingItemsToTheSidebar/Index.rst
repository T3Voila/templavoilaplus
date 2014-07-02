

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


Adding items to the sidebar
^^^^^^^^^^^^^^^^^^^^^^^^^^^

[TODO: Explain how other extensions can easily add new items to the
sidebar]

::

   if (t3lib_extMgm::isLoaded('templavoila'))    {
       require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_sidebar.php');
   }
   class tx_myext_templavoila_sidebar {
       function init() {
               // Create / get instances:
           $thisObj =& t3lib_div::getUserObj ('&tx_myext_templavoila_sidebar', '');
           $sideBarObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_sidebar', '');
               // Register sidebar item:
           $sideBarObj->addItem ('tx_myext_templavoila_sidebar_item1', $thisObj, 'renderItem_myext', 'My Extension', 50);
       }
       function renderItem_myext(&$pObj) {
               // Dummy output, just return the current page id:
           return $pObj->id;
       }
   }

::

   if (t3lib_extMgm::isLoaded('templavoila'))    {
       require_once (t3lib_extMgm::extPath($_EXTKEY).'class.tx_myext_templavoila_sidebar.php');
       tx_myext_templavoila_sidebar::init();
   }

