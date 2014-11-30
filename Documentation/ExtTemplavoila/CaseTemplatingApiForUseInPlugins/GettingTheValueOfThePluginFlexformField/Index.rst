

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


Getting the value of the Plugin FlexForm field
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In the mininews plugin class we first need to detect if a Template
Object record is pointed at and if so make sure it is used.


Detecting the Template Object (TO) record
"""""""""""""""""""""""""""""""""""""""""

In the “mininews/pi1/class.tx\_mininews\_pi1.php” file the class
contains these two variables:

::

           // TemplaVoila specific:
       var $TA='';                    // If TemplaVoila is used and a TO record is found, this array will be loaded with Template Array.
       var $TMPLobj='';            // Template Object

Later, in the listView function you find this initialization which
detects the record. Comments below

::

      1: function listView($content,$conf)    {
      2:
      3:         // Init FlexForm configuration for plugin:
      4:     $this->pi_initPIflexForm();
      5:
      6:         // Looking for TemplaVoila TO record and if found, initialize template object:
      7:     if (t3lib_extMgm::isLoaded('templavoila'))    {
      8:         $field_templateObject = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'field_templateObject');
      9:         if ((int)$field_templateObject)    {
     10:             $this->TMPLobj = t3lib_div::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);
     11:             $this->TA = $this->TMPLobj->getTemplateArrayForTO((int)$field_templateObject);
     12:             if (is_array($this->TA))    {
     13:                 $this->TMPLobj->setHeaderBodyParts($this->TMPLobj->tDat['MappingInfo_head'],$this->TMPLobj->tDat['MappingData_head_cached']);
     14:             }
     15:         }
     16:     }

- Line 4 initializes the “pi\_flexform” field in $this->cObj->data. This
  will convert the field from being a string with the XML data to being
  an array with the same XML converted to PHP array by
  t3lib\_div::xml2array()

- Line 7 checks if TemplaVoila is loaded -which it must be of course!

- Line 8 requests the value of “field\_templateObject” in the FlexForm
  content of “pi\_flexform”

- Line 9 sees in that value is an integer - which means it points to a
  Template Object record “uid”

- In line 10 we create an instance of the “tx\_templavoila\_htmlmarkup”
  class which will be our API for merging our  *data* from mininews with
  the  *template* from the Template Object record.

- Line 11 loads the Template Array from the TO pointed to by
  $field\_templateObject.

- Line 12 checks if the Template Array was set - this is the case if
  there was mapping information found in the TO.

- Line 13 will set possible header sections if any should be defined in
  the TO.

Now, in the rest of the mininews class we can just check if $this->TA
is an array and if so use templavoilas API for merging data and
template. This is shown next.


Merging Data with Template markup
"""""""""""""""""""""""""""""""""

In order to not make this too lengthy I will just cut out some
examples.

**Repeated list rows**

The first example is how to accumulate content for list rows. This is
basically done by a loop, traversing over the elements and for each
iteration calling an API function in TemplaVoila with two arguments,
the appropriate part of the ->TA variable (Template Array = cached
template markup) and an array with the mininews data.

::

      1:     // Create list of elements:
      2: $elements='';
      3: while($this->internal['currentRow'] = mysql_fetch_assoc($res))    {
      4:     $elements.=$this->TMPLobj->mergeDataArrayToTemplateArray(
      5:         $this->TA['sub']['sArchive']['sub']['field_archiveListing']['sub']['element_even'],
      6:         array(
      7:             'field_date' => $this->getFieldContent('datetime'),
      8:             'field_header' => $this->pi_list_linkSingle($this->getFieldContent('title'),$this->internal['currentRow']['uid'],1),
      9:             'field_teaser' => nl2br(trim(t3lib_div::fixed_lgd($this->getFieldContent('teaser_list'),$this->conf['frontPage.']['teaserLgd'])))
     10:         )
     11:     );
     12: }

In this listing you can see that the array with data from mininews has
three keys, “field\_date”, “field\_header” and “field\_teaser”. These
corresponds with three elements found in the Data Structure XML for
the “ARCHIVE LISTING” template:

::

     10:                   <field_archiveListing>
     11:                           <type>array</type>
     12:                           <section>1</section>
     13:                           <tx_templavoila>
     14:                                   <title>Archive Listing container</title>
     15:                                   <description></description>
     16:                                   <tags>div,table:inner</tags>
     17:                           </tx_templavoila>
     18:                           <el>
     19:                                   <element_even>
     20:                                           <type>array</type>
     21:                                           <tx_templavoila>
     22:                                                   <title>Element Container, Even</title>
     23:                                                   <description></description>
     24:                                                   <tags>*:outer</tags>
     25:                                           </tx_templavoila>
     26:                                           <el>
     27:                                                   <field_date>
     28:                                                           <tx_templavoila>
     29:                                                                   <title>Date</title>
     30:                                                                   <description>News date</description>
     31:                                                                   <tags>*:inner</tags>
     32:                                                                   <sample_data>
     33:                                                                           <n0>6th August 10:34</n0>
     34:                                                                           <n1>29/12 2003</n1>
     35:                                                                   </sample_data>
     36:                                                           </tx_templavoila>
     37:                                                   </field_date>
     38:                                                   <field_header>
     39:                                                           <tx_templavoila>
     40:                                                                   <title>Header</title>
     41:                                                                   <description>Header field.</description>
     42:                                                                   <tags>*:inner</tags>
     43:                                                                   <sample_data>
     44:                                                                           <n0>People on mars!</n0>
     45:                                                                           <n1>Snow in Sydney</n1>
     46:                                                                   </sample_data>
     47:                                                           </tx_templavoila>
     48:                                                   </field_header>
     49:                                                   <field_teaser>
     50:                                                           <tx_templavoila>
     51:                                                                   <title>Teaser</title>
     52:                                                                   <description>Teaser field.</description>
     53:                                                                   <tags>*:inner</tags>
     54:                                                                   <sample_data>
     55:                                                                           <n0>Capthurim Chanaan vero genuit Sidonem primogenitum et Heth Iebuseum quoque </n0>
     56:                                                                   </sample_data>
     57:                                                           </tx_templavoila>
     58:                                                   </field_teaser>

This is a part of the Data Structure which is nested inside of

::

   <T3DataStructure><sheets><sArchive><ROOT><el>

I point this out because you can see the logic of the variable

::

   $this->TA['sub']['sArchive']['sub']['field_archiveListing']['sub']['element_even']

in the code listing from this. Basically, if you substitute “sub” with
“el” you can almost read that this variable will contain the markup
for

::

   <T3DataStructure><sheets><sArchive><ROOT><el><field_archiveListing><el><element_even>

   $this->TA['sub']['sArchive']['sub']['field_archiveListing']['sub']['element_even']

**Putting it all together**

After having accumulated the list rows (and some other stuff) the
values on the outer levels are also composed into a similar API call
whose output is finally returned:

::

      1:     // Wrap the elements in their containers:
      2: $out = $this->TMPLobj->mergeDataArrayToTemplateArray(
      3:         $this->TA['sub']['sArchive'],
      4:         array(
      5:             'field_archiveListing' => $elements,
      6:             'field_browseBox_cellsContainer' => $br_elements,
      7:             'field_searchBox_sword' => htmlspecialchars($this->piVars['sword']),
      8:             'field_searchBox_submitUrl' => htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')),
      9:             'field_browseBox_displayRange' => $rangeLabel,
     10:             'field_browseBox_displayCount' => $this->internal['res_count']
     11:         )
     12:     );
     13:
     14: return $out;

This time you will see that the accumulated content of the list rows
($elements) is added to the key “field\_archiveListing”. For all the
other fields you can look them up in the DS as well:

::

   ...
    105:
    106:                   <!--
    107:                           Defining mappings for the search box:
    108:                   -->
    109:                   <field_searchBox_submitUrl>
    110:                           <type>attr</type>
    111:                           <tx_templavoila>
    112:                                   <title>Search form action</title>
    113:                                   <description>URL of the news-search; Map to the action-attribute of the search form.</description>
    114:                                   <tags>form:attr:action</tags>
    115:                                   <sample_data>
    116:                                           <n0>javascript:alert('Hello, you pressed the search button!');return false;</n0>
    117:                                   </sample_data>
    118:                           </tx_templavoila>
    119:                   </field_searchBox_submitUrl>
    120:                   <field_searchBox_sword>
    121:                           <type>attr</type>
    122:                           <tx_templavoila>
    123:                                   <title>Search word field</title>
    124:                                   <description>Search word; Map to the forms input-fields value-attribute.</description>
    125:                                   <tags>input:attr:value</tags>
    126:                                   <sample_data>
    127:                                           <n0>Strawberry Jam</n0>
    128:                                           <n1>Jack Daniels</n1>
    129:                                           <n2>Flowers</n2>
    130:                                   </sample_data>
    131:                           </tx_templavoila>
    132:                   </field_searchBox_sword>
    133:
    134:                   <!--
    135:                           Defining mappings for the browse box, display note:
    136:                   -->
    137:                   <field_browseBox_displayRange>
    138:                           <tx_templavoila>
    139:                                   <title>Range</title>
    140:                                   <description>Map to position where "x-y" should be outputted (showing which records are displayed)</description>
    141:                                   <tags>*:inner</tags>
    142:                                   <sample_data>
    143:                                           <n0>1-10</n0>
    144:                                           <n1>20 to 30</n1>
    145:                                   </sample_data>
    146:                           </tx_templavoila>
    147:                   </field_browseBox_displayRange>
    148:                   <field_browseBox_displayCount>
    149:                           <tx_templavoila>
    150:                                   <title>Count</title>
    151:                                   <description>Map to position where the total number of found records should be outputted.</description>
    152:                                   <tags>*:inner</tags>
    153:                                   <sample_data>
    154:                                           <n0>123</n0>
    155:                                           <n1>3402</n1>
    156:                                   </sample_data>
    157:                           </tx_templavoila>
    158:                   </field_browseBox_displayCount>
   ...

Thats all!

