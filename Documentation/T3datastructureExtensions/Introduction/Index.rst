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


Introduction
^^^^^^^^^^^^

TemplaVoila extends the Data Structure XML with a set of tags which
defines two things related to TemplaVoila:

- **Mapping:** Definition of mapping rules, descriptions, sample data,
  and field type preset

- **Rendering:** Definition of TypoScript code, Object Path, processing
  flags and constants


<T3DataStructure> extensions for “<tx\_templavoila>”
""""""""""""""""""""""""""""""""""""""""""""""""""""

“ **Array” Elements:**

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Element
         Element

   Description
         Description

   Sub-elements
         Sub-elements


.. container:: table-row

   Element
         <[application tag]>

   Description
         In this case the application tag is “<tx\_templavoila>”

   Sub-elements
         <title>

         <description>

         <tags>

         <sample\_data>

         <sample\_order>

         <eType>

         <TypoScriptObjPath>

         <TypoScript>

         <proc>

         <ruleConstants>

         <ruleRegEx>

         <ruleDefaultElements>

         <langOverlayMode>

         <preview>


.. container:: table-row

   Element
         <ROOT><tx\_templavoila>

   Description
         For <ROOT> elements in the DS

   Sub-elements
         <title>

         <description>

         <pageModule>


.. container:: table-row

   Element
         <pageModule>

   Description
         A bunch of config options which take influence on the rendering in the
         page module.

   Sub-elements
         <displayHeaderFields>

         <titleBarColor>


.. container:: table-row

   Element
         <sample\_data>

   Description
         Sample data, defined in numeric array. Sample data is selected
         randomly from these options

   Sub-elements
         <n[0-x]>


.. container:: table-row

   Element
         <sample\_order>

   Description
         For <section>s: Defines a set of array objects to display as sample
         data. Each value in this numerical array points to a fieldname in the
         object <el> array.

   Sub-elements
         <n[0-x]>


.. container:: table-row

   Element
         <TypoScript\_constants>

   Description


   Sub-elements
         <[constant\_name]>


.. container:: table-row

   Element
         <proc>

   Description
         Processing options (during rendering)

   Sub-elements
         <stdWrap>

         <int>

         <HSC>


.. ###### END~OF~TABLE ######

“ **Value” Elements:**

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Element
         Element

   Format
         Format

   Description
         Description


.. container:: table-row

   Element
         <meta><sheetSelector>

   Format
         string

   Description
         Defining a file/class with PHP code to evaluation sheet selection in
         frontend.

         Its a getUserObject reference a la “EXT:user\_myext/class.user\_myext\
         _selectsheet.php:&amp;user\_myext\_selectsheet” where the class
         user\_myext\_selectsheet contains a function, selectSheet(), which
         returns the sheet key, eg. “sDEF” for default sheet.

         **Notice about using sheets in frontend rendering (pi1):**

         This feature is fairly advanced and still needs some development and
         documentation. Here are some points to observe:

         - When sheets are defined the template also needs to be remapped!

         - If no mapping exists for other keys than “sDEF” then they will default
           to use the mapping for “sDEF”. Thus it can save you a little on
           mapping the same over and over again if all sheets use the same
           template.

         - When using sheets the local processing XML also needs to be wrapped in
           eg. “<sheet><sDEF> .... </sheet></sDEF>”

         - The selection of sheets should be careful to select only based on
           parameters that are safely cached. This can be done if parameters are
           known to be cHash protected - or if the page cache is disabled of
           course.


.. container:: table-row

   Element
         <meta><disableDataPreview>

   Format
         boolean 0/1

   Description
         If configured the datapreview within the page-module for the element
         is turned off.


.. container:: table-row

   Element
         <meta><noEditOnCreation>

   Format
         boolean 0/1

   Description
         If configured the editing form, which would usually show up after a
         new content element was created, is skipped. This can be used for
         elements which server the purpose of a container.

         This setting can be overwritten with local processing setup.


.. container:: table-row

   Element
         <meta><default><TCEForms>

   Format


   Description
         Can be used to define default-values for the parent-record.

         Example (mostly used for container elements):

         <meta type="array">

         <langDisable>1</langDisable>

         <default>

         <TCEForms>

         <sys\_language\_uid>-1</sys\_language\_uid>

         </TCEForms>

         </default>

         </meta>


.. container:: table-row

   Element
         <title>

   Format
         string

   Description
         The title displayed in the mapping view


.. container:: table-row

   Element
         <description>

   Format
         string

   Description
         Mapping instructions / description, shown in mapping view.


.. container:: table-row

   Element
         <tags>

   Format
         string

   Description
         commalist of tag rules. A tag rule is defined as [tagname]:[mapping-
         mode]:[attribute]

         **Examples are:**

         - table:outer,div,body:inner,td:inner

         - \*:attr:href

         - a:attr:\*

         - \*:inner,a:attr:href,a:attr:src


.. container:: table-row

   Element
         <eType>

   Format
         string

   Description
         Value pointing to a TCEforms preset. Used for building of Data
         Structures with templavoila. Automatically set and controlled. This
         tag only used internally by the mapping tool.


.. container:: table-row

   Element
         <oldStyleColumnNumber>

   Format
         integer

   Description
         By setting this tag to an integer value (usually between 0 and 3), you
         define to which tt\_content column number this field relates. This
         information is used by the list module, frontend editing and all other
         places which work with the older column paradigm.

         If you want to convert a pre-TemplaVoila site to a TemplaVoila site
         with the migration wizard you also have to make sure setting
         oldStyleColumnNumber tags for your content areas.

         **Note:** Each value can only be used once in a data structure and
         this usage makes only sense in page templates!

         **Note:** By default this setting is also used for content areas
         within flexible content elements. The elements which are nested within
         these flexible content element will use their parent's setting. If you
         want to avoid this, just remove the setting from the flexible content
         element.

         **Background information:**

         Before TemplaVoila existed, the content on a page was arranged by
         assigning each content element to a certain column id. By default four
         columns were available: “Normal” (id=0), “Left” (id=1), “Right” (id=2)
         and “Border” (id=3).

         Some parts of TYPO3 and some extensions are not aware of the different
         way TemplaVoila structures content. If you create or move a content
         element with the List module, the element possibly does not appear at
         the position where you expect it, because the list module doesn't know
         which content area reflects the “Normal” column.

         **Example:**

         ::

            <T3DataStructure>
               <ROOT>
                  <el>
                     <field_maincontent>
                        <tx_templavoilaplus>
                           <oldStyleColumnNumber>0</oldStyleColumnNumber>
            ...


.. container:: table-row

   Element
         <TypoScriptObjPath>

   Format
         string

   Description
         TypoScript object path pointing to a TypoScript Template Content
         Object which will render the content represented by the element.

         Very useful if you want to insert a menu which is defined by eg.
         “lib.myMenu” in the TypoScript Template of a website.


.. container:: table-row

   Element
         <TypoScript>

   Format
         string

   Description
         TypoScript content.

         Constants can be inserted

         - which are defined locally in <TypoScript\_constants>, see below

         - In the TypoScript template of the website; In the Setup field you can
           set constants as properties (first level only) in
           “plugin.tx\_templavoila\_pi1.TSconst” - those can be inserted by
           {$TSconst.[constant name]} in the <TypoScript> data!

         **General example:**

         ::

            <TypoScript>
            <![CDATA[

            10 = USER
            10.userFunc = user_3dsplm_pi2->testtest
            10.imageConfig {
              file.import.current = 1
              file.width = 100
            }

            ]]>
            </TypoScript>

         **Access other fields in the same data structure:**

         ::

            <TypoScript>
               10 = TEXT
               10.field = field_myotherfield
            </TypoScript>

         **Display the page title:**

         ::

            <TypoScript>
               10 = TEXT
               10.data = page:title
            </TypoScript>


.. container:: table-row

   Element
         <[constant\_name]>

   Format
         string

   Description
         A local TypoScript constant which can be inserted by
         {$[constant\_name]} in <TypoScript> (see above)

         Instead of setting a plain value you can also reference object path
         values from the sites TypoScript template by inserting a value like
         “{$lib.myConstant}”.  **Notice** , the value will come from the
         Templates Setup field.

         **Example:**

         ::

            <TypoScript_constants>
              <backGroundColor>red</backGroundColor>
              <fontFile>{$_CONSTANTS.resources.fontFile}</fontFile>
            </TypoScript_constants>

         Here “\_CONSTANTS.resources.fontFile” must be an object path with a
         value in the TypoScript template of the website!


.. container:: table-row

   Element
         <int>

   Format
         boolean, 0/1

   Description
         Pass through intval() before output


.. container:: table-row

   Element
         <HSC>

   Format
         boolean, 0/1

   Description
         Pass through htmlspecialchars() before output


.. container:: table-row

   Element
         <stdWrap>

   Format
         string

   Description
         StdWrap properties as TypoScript, eg:

         ::

            <proc>
                    <stdWrap>
                    trim = 1
                    br = 1
                    </stdWrap>
            </proc>


.. container:: table-row

   Element
         <langOverlayMode>

   Format
         string, keyword

   Description
         Setting the mode for content fallback when <meta><langChildren> and
         other languages are used in flexforms.

         Normally inheritance from default language is enabled by default and
         globally disabled by the TypoScript setting
         “dontInheritValueFromDefault” if needed.

         However through the Data Structure and TO / Local Processing XML you
         can overrule this per-field by this keyword.

         In any case it only affects values from other languages than default
         and only if <langChildren> is enabled (thus using “vDEF” and sibling
         fields named “vXXX” for localization).

         **Keywords:**

         **ifFalse** - Content is inherited if it evaluates to false in PHP
         (meaning that zero and blank string falls back)

         **ifBlank** - Content is inherited if it matched a blank string
         (trimmed)

         **never** - Content is never inherited from default language!

         **removeIfBlank** - If the value of this field is blank then the
         *whole group* of fields (element) is removed! This is a way of
         removing single elements for localizations in <langChildren>=1
         constructions instead of inheriting content from default language.

         **[default]** - If no keyword matches it uses the global mode.


.. container:: table-row

   Element
         <displayHeaderFields>

   Format
         string

   Description
         A list of page-related fields which should be displayed as a header in
         the edit page view of the page module. By now, only table “page” is
         allowed / makes sense.

         **Note:** This tag only takes effect when used in the top-level
         <tx\_templavoila> section, ie. one level below the <ROOT> tag.

         |img-8|

         **Example:**

         ::

            <T3DataStructure>
               <ROOT>
                  <tx_templavoilaplus>
                     <pageModule>
                        <displayHeaderFields>
                           pages.keywords
                           pages.mycustomfield
                        </displayHeaderFields>
                     </pageModule>
            ...


.. container:: table-row

   Element
         <titleBarColor>

   Format
         color

   Description
         If you want to help your editors determining which data structure is
         used for the page they are currently working on, you may specify a
         color by using this tag. The title bar at the very top of the edit
         page screen will be displayed in that color.

         You may use any value which is allowed in CSS (ie. “red” as well as
         “#FC2300” etc.)

         **Note:** This tag only takes effect when used in the top-level
         <tx\_templavoila> section, ie. one level below the <ROOT> tag.

         |img-9|

         **Example:**

         ::

            <T3DataStructure>
               <ROOT>
                  <tx_templavoilaplus>
                     <pageModule>
                        <titleBarColor>orange</titleBarColor>
            ...


.. container:: table-row

   Element
         <preview>

   Format
         String, keyword

   Description
         **Keywords:**

         **disable –** Avoid that the templavoila page module includes the
         field into it's data preview. That's mainly meant keep the page modul
         nice and clean.


.. container:: table-row

   Element
         **Extensions to tags in the Data Structure**


.. container:: table-row

   Element
         <[field-name]><type>

   Format
         string

   Description
         In the Data Structure only “array” or blank makes sense. However for
         TemplaVoila there is additional values possible, “attr” and “no\_map”.
         This is a complete TemplaVoila related overview of the <type> /
         <section> meanings:

         - <type>array</type> = Renders an array or objects

         - <type>array</type> + <section>1</section> = Renders a section which
           must contain other array-types (without <section> set!)

         - <type>attr</type> = The object is mapped to a HTML tag  *attribute* .

         - <type>[blank]</type> = The object is mapped to a HTML tag  *element* .

         - <type>no\_map</type> = The object is not mappable (only editing in
           FlexForms eg.)


.. ###### END~OF~TABLE ######


Sheets and TemplaVoila
""""""""""""""""""""""

TemplaVoila is compatible with definition of sheets. In that case a
sheet <ROOT> element is shown in the mapping structure containing each
sheet as <ROOT> elements under it. Even if multiple sheets are used
TemplaVoila renders only one sheet either determined by the
sheetSelector or using the “sDEF” sheet by default.

