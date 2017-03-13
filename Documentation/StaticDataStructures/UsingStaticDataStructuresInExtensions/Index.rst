

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


Using Static Data Structures in extensions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to add your own Static Data Structures inside an extension
they can simply be added to a global array (within your
ext\_localconf.php):

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['templavoilaplus']['staticDataStr
uctures'][] =array(

'title'=>'My DS',

'path'=>'EXT:'.$\_EXTKEY.'/myds.xml',

'icon'=>'',

'scope'=>1,

);

 **Static Data Structures have been extensively tested, but are still an experimental feature. We highly recommend backing up your data before enabling Static Data Structures.** 
Using beLayout
--------------

With a special HTML template called “beLayout” you are able to style
the page module. This is a good way to order the content areas in the
same way they will appear on the frontend page. You can use markers
for the field names of your content area. If you have a field for
content elements called “field\_content” you can place it in your HTML
template with “###field\_content###”.

The beLayout file can be assigned to a Data Structure record or a
Template Object record, with the Template Object having priority. When
using Static Data Structures, the beLayout must be in same directory
as the Data Struture file with same name and file extension “html”.

beLayout is useful for:

- Use in Page DataStructuresandTemplateObjects:Give your editors a
  better overview of the available content areas in your DS.This
  isespeciallyusefulif you have more than 3columns.

- Use inFlexible ContentElements DataStructuresandTemplateObject:This is
  especially useful for grid-elements elements, such as a two column
  FCE.

