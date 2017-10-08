

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


Datastructure TypoScript
^^^^^^^^^^^^^^^^^^^^^^^^


Overview
""""""""

TemplaVoilà! Plus offers various special registers to access some context
information such as information about the enclosing record, the
current field or the section.

Register overview:

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Register name
         Register name

   Description
         Description


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.parentRec.<field>

   Description
         Access field data from the database record. See examples below.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.current\_field

   Description
         Contains the name of the datastructure field.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.nested\_fields

   Description
         Contains the list of all fields your TypoScript is rendered in and
         provides a way to retriev the context of your rendered element.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.sectionPos

   Description
         Retrieve the current position of your element within a section. See
         example below.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.sectionCount

   Description
         Retrieve the total amount of elements in the current section. See
         example below.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.sectionIsFirstItem

   Description
         Determine whether the current element is the first element within the
         current section.


.. container:: table-row

   Register name
         tx\_templavoilaplus\_pi1.sectionIsLastItem

   Description
         Determine whether the current element is the last element within the
         current section.


.. ###### END~OF~TABLE ######


Accessing “parent” record from DS TypoScript
""""""""""""""""""""""""""""""""""""""""""""

To access “parent” record from “tt\_content” or “pages” table in the
<TypoScript> section of a field, developer can use special registers.
These registers defined only when <TypoScript> section is executed.
The following example shows how to use these registers:

::

   <TypoScript>
   10 = TEXT
   10.data = register:tx_templavoilaplus_pi1.parentRec.uid
   10.wrap = “uid” field of parent record is |
   </TypoScript>

Thus any field of parent record is defined as
**tx\_templavoilaplus\_pi1.parentRec.XXX** register, where XXX is replaced
by a field name from the corresponding table.

Notice that these registers are undefined for static data structures
because static data structures do not have associated parent record.
If reference to  **tx\_templavoilaplus\_pi1.parentRec.XXX** appears in the
static data structure, result is undefined.


**Section information within DS TypoScript**
""""""""""""""""""""""""""""""""""""""""""""

When using TypoScript for items within sections TemplaVoila 1.4
introduced 4 new registers to determine the position of the current
item within the entire section. They are defined as
**tx\_templavoilaplus\_pi1.sectionPos** ,
**tx\_templavoilaplus\_pi1.sectionCount** ,
**tx\_templavoilaplus\_pi1.sectionIsFirstItem** and
**tx\_templavoilaplus\_pi1.sectionIsLastItem** .

Example:

::

   <TypoScript>
   10 = TEXT
   10.current = 1
   10.dataWrap = {register:tx_templavoilaplus_pi1.sectionPos} / {register:tx_templavoilaplus_pi1.sectionCount}
   10.if.isTrue.data = register:tx_templavoilaplus_pi1.sectionIsFirstItem
   </TypoScript>

