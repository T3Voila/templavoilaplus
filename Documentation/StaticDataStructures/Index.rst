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


Static Data Structures
----------------------

With version 1.4.2 Static Data Structures are fully supported. This
means that no database record is used for a Data Structure; instead a
file contains the XML data. This makes maintenance much easier as
external editors can be used for the Data Structures and the files can
be managed with version control systems such as Subversion.

To enable Static Data Structures, you can use the wizard in the
Extension Manager to convert existing records to Static Data
Structures.

Before using the wizard, you'll need to select the directory where
Static Data Structures are stored. By default, this is set to
fileadmin/templates/ds/ with directories inside for pages and Flexible
Content Elements.

|img-10|

When you enable the first option, Templavoila will start usin the
Static Data Structures. Even with Static Data Structures disabled, the
wizard can be useful as a backup system for your Data Structures that
are stored in the database.

There are three possible files for every Data Structure, each
corresponding to fields in a normal Data Structure record.

[ds-name].xml Data Structure XML[ds-name].gif Data Structure Preview
Icon[ds-name].html Data Structure Backend Layout (beLayout)

The Data Sturcture XML will be created by the wizard, but the icon and
backend layout are optional and must be added manually.

There are some drawbacks to using Static Data Structures:

- The scope must be last part of the file name. For example, in “Main
  Template (page).xml” the scope is page

- The parentRec property cannot be used from TypoScript as there is no
  parent record.

These shortcomings may be improved in future versions.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   UsingStaticDataStructuresInExtensions/Index
   ExampleForBelayout/Index

