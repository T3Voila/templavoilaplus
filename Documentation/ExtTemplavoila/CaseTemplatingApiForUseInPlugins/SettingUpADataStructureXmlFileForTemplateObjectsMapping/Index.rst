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


Setting up a Data Structure XML file for Template Objects mapping
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In “mininews” the data structure that is used for the mapping of
templates is found in the file “mininews/template\_datastructure.xml”.
The contents look like this:

::

   <T3DataStructure>
          <sheets>
           <!-- The Archive configuration is so large that we have put it into it's own file,
                           and references it from here: -->
             <sArchive>EXT:mininews/template_datastructure_arc.xml</sArchive>
   
                   <!-- Single display of mininews items: -->
             <sSingle>
                          <ROOT>
                                  <tx_templavoila>
                                          <title>SINGLE DISPLAY</title>
                                                  <description>Select the HTML element which is the container of the
                                                   single display of a news article:</description>
                     <tags>div:inner</tags>
                                  </tx_templavoila>
                                  <type>array</type>
                                  <el>
                                          <field_date>
                                                  <tx_templavoila>
                                                          <title>Date</title>
                                                          <description>News date</description>
                                                          <tags>*:inner</tags>
                                                          <sample_data>
                                                                  <n0>6th August 10:34</n0>
                                                                  <n1>29/12 2003</n1>
                                                          </sample_data>
                                                  </tx_templavoila>                                                                                               
                                          </field_date>
                                          <field_header>
                                                  <tx_templavoila>
                                                          <title>Header</title>
                                                          <description>Header field.</description>
                                                                  <tags>*:inner</tags>
                                                                  <sample_data>
                                                                          <n0>People on mars!</n0>
                                                                          <n1>Snow in Sydney</n1>
                                                                  </sample_data>
                                                  </tx_templavoila>                                                                                               
                                          </field_header>
                                          <field_teaser>
                                                  <tx_templavoila>
                                                          <title>Teaser</title>
                                                          <description>Teaser field.</description>
                                                          <tags>*:inner</tags>
                                                          <sample_data>
                                                                  <n0>Capthurim Chanaan vero genuit Sidonem primogenitum et
                                                                                   Heth Iebuseum quoque </n0>
                                                          </sample_data>
                                                  </tx_templavoila>                                                                                               
                                          </field_teaser>
                                          <field_bodytext>
                                                  <tx_templavoila>
                                                          <title>Bodytext</title>
                                                                  <description>Bodytext field</description>
                                                                  <tags>*:inner</tags>
                                                                  <sample_data>
                                                                          <n0><![CDATA[
                                                                                  <p><strong>Filii Ham Chus et Mesraim Phut et Chanaan</strong> filii autem Chus Saba et Evila Sabatha et Rechma et Sabathaca porro filii Rechma Saba et Dadan Chus autem genuit Nemrod iste coepit esse potens in terra Mesraim vero genuit Ludim et Anamim et Laabim et Nepthuim Phethrosim quoque et Chasluim de quibus egressi sunt Philisthim et.</p>
                                                                                  <p>Capthurim Chanaan vero genuit Sidonem primogenitum et Heth Iebuseum quoque et Amorreum et Gergeseum Evheumque et Aruceum et Asineum Aradium quoque et Samareum et Ematheum filii Sem Aelam et Assur et Arfaxad et Lud et Aram et Us et Hul et Gothor et Mosoch Arfaxad autem genuit Sala qui et ipse genuit Heber porro Heber nati sunt duo filii nomen uni Phaleg quia in diebus eius divisa est terra et nomen fratris eius Iectan Iectan autem genuit Helmodad et Saleph et Asermoth et Iare Aduram quoque et Uzal et Decla Ebal etiam et Abimahel et Saba necnon et Ophir et Evila et Iobab omnes isti filii Iectan Sem Arfaxad Sale.</p>
                                                                           ]]></n0>
                                                                  </sample_data>
                                                          </tx_templavoila>                                                                                               
                                                  </field_bodytext>
                                                  <field_url>
                                                          <type>attr</type>
                                                          <tx_templavoila>
                                                                  <title>"Back" URL.</title>
                                                                  <description>Map to a-tags href-attribute of the link back to
                                                                           archive listing.</description>
                                                                  <tags>a:attr:href</tags>
                                                                  <sample_data>
                                                                          <n0>javascript:alert('You click this link!');</n0>
                                                                  </sample_data>
                                                          </tx_templavoila>                                                                       
                                                  </field_url>
                                          </el>                                                                                   
                                  </ROOT>                 
                     </sSingle>
                   
   
                           <!-- Frontpage display of a few mininews teaser items: -->
                     <sFrontpage>
                                  <ROOT>
                                          <tx_templavoila>
                                                  <title>FRONTPAGE LISTING</title>
                                                  <description>Select the HTML element which is the container of the
                                                                                   frontpage listing display of a news articles:</description>
                                                  <tags>div:inner</tags>
                                          </tx_templavoila>
                                          <type>array</type>
                                          <el>
                                                  <field_fpListing>
                                                          <type>array</type>
                                                          <section>1</section>
                                                          <tx_templavoila>
                                                                  <title>Archive Listing container</title>
                                                                  <description></description>
                                                                  <tags>div,table:inner</tags>
                                                          </tx_templavoila>
                                                          <el>
                                                                  <element_even>
                                                                          <type>array</type>
                                                                          <tx_templavoila>
                                                                                  <title>Element Container, Even</title>
                                                                                  <description></description>
                                                                                  <tags>*:outer</tags>
                                                                          </tx_templavoila>
                                                                          <el>
                                                                          <field_date>
                                                                                  <tx_templavoila>
                                                                                          <title>Date</title>
                                                                                          <description>News date</description>
                                                                                          <tags>*:inner</tags>
                                                                                          <sample_data>
                                                                                                  <n0>6th August 10:34</n0>
                                                                                                  <n1>29/12 2003</n1>
                                                                                          </sample_data>
                                                                                  </tx_templavoila>                                                                                               
                                                                          </field_date>
                                                                          <field_header>
                                                                                  <tx_templavoila>
                                                                                          <title>Header</title>
                                                                                          <description>Header field.</description>
                                                                                          <tags>*:inner</tags>
                                                                                          <sample_data>
                                                                                                  <n0>People on mars!</n0>
                                                                                                  <n1>Snow in Sydney</n1>
                                                                                          </sample_data>
                                                                                  </tx_templavoila>                                                                                               
                                                                          </field_header>
                                                                          <field_teaser>
                                                                                  <tx_templavoila>
                                                                                          <title>Teaser</title>
                                                                                          <description>Teaser field.</description>
                                                                                          <tags>*:inner</tags>
                                                                                          <sample_data>
                                                                                                  <n0>Capthurim Chanaan vero genuit Sidonem primogenitum et
                                                                                                                   Heth Iebuseum quoque </n0>
                                                                                          </sample_data>
                                                                                  </tx_templavoila>                                                                                               
                                                                          </field_teaser>
                                                                          <field_url>
                                                                                  <type>attr</type>
                                                                                  <tx_templavoila>
                                                                                          <title>"MORE" URL.</title>
                                                                                          <description>Map to a-tags href-attribute of the link pointing
                                                                                                           to the archive!</description>
                                                                                          <tags>a:attr:href</tags>
                                                                                          <sample_data>
                                                                                                  <n0>javascript:alert('You click this link!');</n0>
                                                                                          </sample_data>
                                                                                  </tx_templavoila>                                                                       
                                                                          </field_url>
                                                                  </el>                                                   
                                                          </element_even>
                                                  </el>
                                          </field_fpListing>
                                  </el>                           
                          </ROOT>                 
             </sFrontpage>           
          </sheets>
   </T3DataStructure>

If you study this long codelisting you will find that it only
configures the DS for the templates “SINGLE DISPLAY” and “FRONTPAGE
LISTING” - the “ARCHIVE LISTING” is actually found in another file
referred to by this line:

::

   <sArchive>EXT:mininews/template_datastructure_arc.xml</sArchive>

You can study the contents of this file by yourself.

Anyways, the question remains: How does mininews configure that this
XML file should be available for Template Objects to point to? This is
found in ext\_tables.php:

::

   // Adding datastructure for Mininews:
   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures'][]=array(
       'title' => 'Mininews Template',
       'path' => 'EXT:'.$_EXTKEY.'/template_datastructure.xml',
       'icon' => '',
       'scope' => 0,
   );

$\_EXTKEY contains the value “mininews” as usual in a ext\_tables.php
file for an extension.

By this configuration the DS will appear in the Data Structure
selector box:

|img-17|

At this point we have:

- FlexForm configuration needed to select template record in the “Insert
  Plugin” type Content Element

- A Data Structure (DS) in an XML file which can be used for mapping a
  template HTML-file to the DS.

- You should also have an example template-HTML file which can
  demonstrate the mapping of your DS.

All that is left is to actually use the template in the plugin.

