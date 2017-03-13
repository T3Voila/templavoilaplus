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


Creating a Template Object
^^^^^^^^^^^^^^^^^^^^^^^^^^

This is done in the “Storage folder” which should have been configured
to the website. Here you create a new Template Object an select the
*Data Structure* that the mininews extension provides:

|img-13|

After having done this you close the document here, click the Template
Object icon again and select “TemplaVoila”:

|img-14|

Subsequently you can begin the mapping of the Data Structure to the
template file (here the example file
“mininews/template/mininews\_template.html” is used) and after that
process you will see something like this:

|img-15|

Notice in particular how each of the three templates are found in the
*same* Data Structure as  *sheets* where the highest root element
named “ROOT of multitemplate” represents the three sheets inside:

|img-16|

After the mapping process is complete the alternative template is in
place.

The big questions now are:

- How can I define a data structure for my plugin just like “mininews”
  has done?

- How can I use any alternative template represented by a Template
  Object inside my plugin?

These questions are answered next.

