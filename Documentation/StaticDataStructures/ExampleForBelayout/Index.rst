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


Example for beLayout
^^^^^^^^^^^^^^^^^^^^

Our example page has 3 content areas: a top content
area(field\_headercontent), a main content area (field\_content), and
a right content area (field\_rightcontent). Using beLayout, we can
design a backend layout for these 3 content areas which follows the
structure of the frontend page.

<link rel="stylesheet" type="text/css" href="../../../../fileadmin/css
/belayout-startpage.css" />

<divid= *"pm-startpage"* style= *"border: 1px solid #333333"* >

<tablewidth= *"99%"* >

<tr>

<tdwidth= *"99%"* colspan= *"2"* class= *"header"* style=
*"background: #CAE0DE; border-bottom: 1px solid black"* >

###field\_topcontent###

</td>

</tr>

<tr>

<tdwidth= *"60%"* valign= *"top"* style= *"border-right: 2px solid
black"* >

###field\_content###

</td>

<tdwidth= *"40%"* valign= *"top"* class= *"right"* style=
*"background: #F6FFC6;"* >

###field\_rightcontent###

</td>

</tr>

</table>

</div>

|img-11| After applying the beLayout, the page module will look like
the following screenshot.

