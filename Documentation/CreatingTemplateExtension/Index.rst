.. include:: /Includes.rst.txt


Creating a Template Extension
-----------------------------

If you have a TYPO3 installation using TemplaVoilà! Plus already you can just use the migration
wizard.

If you want to create a new extension or sitepackage which supplies a TVP template or if you need
a reference you can refer to the
`Demo Theme for TYPO3 TemplaVoilà! Plus v8+ <https://github.com/extrameile/em_tvplus_theme_demo>`_
or `TV+ UIkit Theme <https://github.com/T3Voila/t3voila_uikit>`_
for the time being.

Extensions are scanned inside EXT/Configuration/TVP for following files:

Places configuration files:
   * BackendLayoutPlaces.php
   * DataStructurePlaces.php
   * MappingPlaces.php
   * TemplatePlaces.php

Other files:
   * Extending.php (Registering new Handlers)
   * NewContentElementWizard.php (Manipulate the NewContentElementWizard tabs and items)

.. toctree::
   :maxdepth: 5
   :titlesonly:

   PageUserTsconfigReference/Index

