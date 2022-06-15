.. include:: /Includes.rst.txt

.. _migration:

Migration from 7.x to 8.x
----------------------------------------------------------------

TV+ v8 uses files for all configurations. So you can use your VCS (version control system) to manage changes, to deploy your configuration and so on.
Also theming related things aren't inside DataStructure anymore. We have now different configuration files for every part from
data to template configuration. Every part have his own place (directory) where it lies. So you will have the possibility to use a
base theme which you extend with your own theme without redefining the data structure or the mapping.

.. important:: Do backups as the "Plus" may eat all your database tables and files.

Steps to do for migration
^^^^^^^^^^^^^^^^^^^^^^^^^

#. Backup your database and files!
#. Update your TemplaVoilà! Plus (TV+) installation
#. Go to the new TV+ Control Center which will be inside the "Admin Tools" section
#. Press the yellow "Start Update Script" button
#. Press the "Update configuration to TemplaVoilà! Plus 8" button
#. Follow the migration process
#. You are done, check the generated extension and its files, put them into your VCS
#. Update your TypoScript userFunc entry (main_page to renderPage)

.. code-block:: typoscript

    page = PAGE
    page {
        typeNum = 0
        10 = USER
        10.userFunc = Tvp\TemplaVoilaPlus\Controller\Frontend\FrontendController->renderPage
    }

Now your system should be ready. If all works you can remove the old TemplaVoilà! Plus database tables from your system.
If you have issues, ask on `slack channel <https://typo3.slack.com/archives/C4HCAH659>`_ or on `github <https://github.com/T3Voila/templavoilaplus>`_
