.. include:: ../Includes.txt

Migration from TemplaVoilà! 1.8/1.9/2.0 to TemplaVoilà! Plus 7.x
----------------------------------------------------------------

In short, it is very simple to migrate from old TemplaVoilà! to TemplaVoilà! Plus. You can do it after you migrated from
TYPO3 6.2 LTS to the newer TYPO3 v7 LTS or TYPO3 v8 LTS, but without droping the tables.

.. important:: Do backups as the "Plus" may eat all your database tables and files.

Steps to work
^^^^^^^^^^^^^

#. Backup your database and files!
#. Deactivate old TemplaVoilà! in Extension Manager but do not remove it yet neither its database tables
#. Install and Activate TemplaVoilà! Plus from TYPO3 Extension Repository (TER)
#. Press the Update Button in Extension manager for the TemplaVoilà! Plus extension
#. Press the "Migrate TemplaVoilà 1.8/1.9/2.0" Button
#. Start the migration process
#. Wait a while, till it finishes, it may take a long time on bigger systems
#. After this was done you may need to do the same with the "Update DataStructure from TYPO3 v6.2 LTS to v7 LTS"
#. (This Update script has a own version number, you may recheck it from time to time, you can run it as often you like)
#. After this was done and you are using TYPO3 v7 LTS you may need to do the same with the "Update DataStructure from TYPO3 v7 LTS to v8 LTS"
#. (This Update script has a own version number, you may recheck it from time to time, you can run it as often you like)
#. Do _not_ run the "Convert to Static Data Structure" task, if you don't know what it will do. It isn't needed for updating to TemplaVoilà! Plus
#. Now the automatic part is done
#. Look now through all your TypoScript scripts and replace all occurences of tx_templavoila with tx_templavoilaplus
#. You may also switch your PAGE object definition to

.. code-block:: typoscript

    page = PAGE
    page {
        typeNum = 0
        10 = USER
        10.userFunc = Tvp\TemplaVoilaPlus\Controller\FrontendController->main_page
    }

Now your system should be ready. If all works you can remove the old TemplaVoilà! extension and its database tables from your system.
If you have issues, ask on slack channel or on `github <https://github.com/pluspol-interactive/templavoilaplus>`_
