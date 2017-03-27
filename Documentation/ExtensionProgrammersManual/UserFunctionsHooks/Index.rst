

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


User functions / hooks
^^^^^^^^^^^^^^^^^^^^^^

Where it made sense, we have implemented some hooks every here and
there in TemplaVoilà Plus in order to give extension programmers a
chance to override or extend certain functionality. Just register
your own function and you will take over the control or take
influence on that part of TemplaVoilà Plus.

If you need to extend a certain part and don't find a way to include
your own code, just get in touch with us, we might include some API to
implement your own user defined function.

Generally, there are two ways of providing hooks, the ones using
t3lib\_div::getUserObj() and those using
t3lib\_div::callUserFunction(). Hooks going the  ***getUserObj*** way
require a **class name** while ***callUserFunction*** hooks accept a
**class name** and **method name** . Here is an example of how to
register your own function in both ways:

::

        // The getUserObject way:
   $TYPO3_CONF_VARS['EXTCONF']['templavoilaplus'][ sub_key ][subsub_key][] = 'my_class';
   
     // The callUserFunction way:
   $TYPO3_CONF_VARS['EXTCONF']['templavoilaplus'][ sub_key ][subsub_key][] = 'my_class->my_method';

Which type of hook was implemented, is specified in the column  *type*
in the reference below. It also states if only one or multiple
userfunctions are allowed for that hook. In the latter case you'll
have to add your class name (and method) to an **array**  **of
userfunctions** .

**Hint:** You should read the section about hooks in the *TYPO3 core
APIs* document, which is available on `TYPO3.org
<http://typo3.org/documentation/document-library/Matrix/>`_ . And of
course you should have a look at the source code where the hook is
provided before you implement your own userfunction.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Sub key
         Sub key:
   
   Sub-sub key
         Sub-sub key:
   
   Type
         Type:
   
   Purpose / description
         Purpose / description:


.. container:: table-row

   Sub key
         cm1
   
   Sub-sub key
         eTypesConfGen
   
   Type
         callUserFunction / single
   
   Purpose / description
         “eTypes” are presets which are use in the click module (cm1) in order
         to create the field configuration of a data structure. While mapping
         you may choose between these eTypes, examples are “text”, “image”,
         “imagefixed”, “ce” and so on.
         
         The “input” eType for example, results in this configuration within
         the data structure:
         
         ::
         
            <TCEforms>
                <config>
                    <type>input</type>
                    <size>30</size>
                    <eval>trim</eval>
                </config>
                <label>test</label>
         
         </TCEforms>
         
         If you want to override the creation of this configuration for a
         certain eType, you may use eTypesConfGenUserfunctions to specify your
         user defined function.
         
         Provide a user function for the eType “input”, you might specify
         something like this in your extension's page TSconfig:
         
         ::
         
            $TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['cm1']['eTypesConfGen']['input'] = 'tx_myClass->myMethod'
         
         For more information on how to design your user function have a look
         at templavoilaplus/Classes/Module/Cm1/ETypes.php


.. container:: table-row

   Sub key
         cm1
   
   Sub-sub key
         eTypesExtraFormFields
   
   Type
         callUserFunction / single
   
   Purpose / description
         (Also see the explanation about eTypes above)
         
         Using this hook you may specify a user function which will render
         certain extra fields for certain eTypes in the mapping dialogues. One
         popular extra field is the object path for the TypoScriptObject eType.
         
         **Example:**
         
         ::
         
            $TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['cm1']['eTypesExtraFormFields']['input'] = 'tx_myClass->myMethod';


.. container:: table-row

   Sub key
         db\_new\_content\_el
   
   Sub-sub key
         wizardItemsHook
   
   Type
         getUserObj / multiple
   
   Purpose / description
         Using this hook enables to modify the elements within the new content
         element wizard.


.. container:: table-row

   Sub key
         mod1
   
   Sub-sub key
         renderTopToolbar
   
   Type
         callUserFunction / multiple
   
   Purpose / description
         Use this hook if you want to output some HTML code at the very top of
         the Edit Page screen in the page module. This was hook was implemented
         for providing a custom toolbar related to the current page.
         
         **Example:**
         
         ::
         
            $TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['mod1']['renderTopToolbar'][] = 'tx_myClass->myMethod';


.. container:: table-row

   Sub key
         mod1
   
   Sub-sub key
         renderPreviewContentClass
   
   Type
         getUserObj / multiple
   
   Purpose / description
         This function contains the following hook:
         
         **renderPreviewContent\_preProcess**
         
         Gives you the chance to render the preview content for an element
         fully on your own.


.. container:: table-row

   Sub key
         mod1
   
   Sub-sub key
         renderPreviewContent
   
   Type
         getUserObj / multiple
   
   Purpose / description
         Use this hook if you want to render the preview of a custom cType or
         override the default preview of a certain cType. This is great if you
         want to provide a preview for your own plugins!
         
         Let's say you wrote a plugin called myext\_pi1. Just create a new
         function your tx\_myext\_pi1 class and register it in
         $TYPO3\_CONF\_VARS (see above). Your own function would look like
         this:
         
         **Example:**
         
         ::
         
            function renderPreviewContent ($row, $table, $output, &$alreadyRendered, &$reference) {
                if (row['CType'] == 'list' && $row['list_type'] == 'myext_pi1') {
                    $content = '<strong>MyExt:</strong> '.htmlspecialchars('my custom preview');
                    $alreadyRendered = true;
                    return $reference->link_edit($content, $table, $row['uid']);
                }
            }


.. container:: table-row

   Sub key
         mod1
   
   Sub-sub key
         render\_editPageScreen
   
   Type
         getUserObj / multiple
   
   Purpose / description
         This function contains the following hook:
         
         **render\_editPageScreen\_addContent**
         
         Provides a way to add further output to the bottom of the edit page
         screen.


.. container:: table-row

   Sub key
         mod1
   
   Sub-sub key
         handleIncomingCommands
   
   Type
         getUserObj / multiple
   
   Purpose / description
         This function contains the following hook:
         
         **handleIncomingCommands\_preProcess**
         
         Provides a way to preprocess or interupt command which are sent from
         the page-module. **handleIncomingCommands\_postProcess**
         
         Provides a way to postprocess or interupt command which are sent from
         the page-module.


.. container:: table-row

   Sub key
         pi1
   
   Sub-sub key
         renderElementClass
   
   Type
         getUserObj / multiple
   
   Purpose / description
         This function contains the following hook:
         
         **renderElement\_preProcessRow**
         
         Gives you the chance to modify the row currently being rendered for
         frontend output. One way of using is, is selecting a different
         template object for a flexible content element, based on certain
         conditions.


.. ###### END~OF~TABLE ######

