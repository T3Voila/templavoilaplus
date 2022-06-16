# TemplaVoilà! Plus

[![license](https://img.shields.io/github/license/T3Voila/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-7.3.6-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)

TemplaVoilà! Plus is a templating extension for the TYPO3 content management system. It is the follow up of the popular
TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

## Language files

If you like to help with the translation of the extension, please visit https://github.com/T3Voila/templavoilaplus-languagefiles

## The next big TV+ version

The next big TV+ version will be 8.0.0, it contains a rewrite of the TV+ code base and a restructuring of template configuration and handling. It will support TYPO3 v8, v9, v10 and v11 LTS.

### Development status

The handling of configuration is mostly done, we now use so called "Places" to define which configuration type comes from which directory. This is needed, as we want later, that you can install a base theme and extend/overwrite it partially with your own configuration data.
The old configuration parts, DataStructure and TemplateObject, have been rearranged. TV+ uses now four configuration types DataConfiguration (with the clean core DataStructure), MappingConfiguration, TemplateConfiguration and BackendLayoutConfiguration. This allows for a better reusage of configuration parts and the possibility to configure different output renderer.
All old entry points to extend TV+ are removed at the moment and some complete new are arising, for example the LoadSaveHandlers, which enables you to write an own configuration loader/saver for your configuration files and your own super duper configuration file format.

If you like to test, develop and/or help documenting, see the demo theme extension which gives you an orientation and explaination how it works. It get updates while development of TV+ 8 happens as it is the testing reference if all works as exspected.

### HINT! Beta Testers

* If you have multilanguage websites, please test and report back, not all multilanguage parts are working yet or are tested completely. As there are to much possible configurations.
* Clipboard is not working yet
* Unused Elements is not working yet
* The TV+ Control Center resides in the admin tools section, it includes the "Update Script" to start the migration process.
* There is no editor anymore, the planed EXT:form editor is very complicated and took to much time. Maybe something else, later as another extension, as this functionality haven't todo with TV+ base.
* Check the extension which is created while migration, it will help to understand the configuration of TV+ 8.0.0.
* Please help with documentation.

### Theme Extensions as WIP for TV+ 8
* https://github.com/extrameile/em_tvplus_theme_demo/ - Demo theme using XPath Renderer

### What works:

* There is a ControlCenter Modul, which shows DataStructure/Mapping/Template Places.
    * For Data- and MappingConfiguration is only the list view implemented.
    * For TemplateConfiguration is only the list view implemented and very little information output.
    * There is a Debug screen which shows more about the internal configuration of the objects.
* There is also a new PageLayout Modul, which isn't puzzled together yet while rewriting
    * No support for multilanguage
    * No support for clipboard
    * No support of unused_elements
    * No support for extending menu
* Frontend rendering works for sDEF/lDEV/vDEF as you define
    * DataStructure will be taken into account
    * TypoScript can be handled while mapping in process
    * Templates using the XPath Renderer (a.k.a. old TemplaVoilà! template handling)
    * You can also use the Fluid Renderer (https://github.com/T3Voila/tvplus_fluid)
    * Add Meta/CSS/JS informations from TemplateConfiguration
    * ~~Subtemplates/Rendertypes not supported yet~~
* Extending
    * You can extend TV+ 8 with new Places with their own handler to load data
    * You can add new renderer (for example Smarty, Twig)
    * You can work on own themes
* Update Script
    * There is an Update Script which already works quite extensive
    * You should select "creating new extension" also if it shows you a green extension to select
    * No support for multiple storage pids yet
    * Step 6, removing old data, is not available yet
    * The feature "ServerDeployment JSON" for better server upgrades isn't available yet
