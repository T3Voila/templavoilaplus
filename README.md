# TemplaVoilà! Plus

[![license](https://img.shields.io/github/license/pluspol-interactive/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-7.3.6-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)

TemplaVoilà! Plus is a templating extension for the TYPO3 content management system. It is the follow up of the popular
TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

## Language files

If you like to help with the translation of the extension, please visit https://github.com/pluspol-interactive/templavoilaplus-languagefiles

## The next big TV+ version

The next big TV+ version will be 8.0.0, it contains a rewrite of the TV+ code base and a restructuring of template configuration and handling. It will support TYPO3 v8, v9, v10 and v11 LTS.

### Development status

The handling of configuration is mostly done, we now use so called "Places" to define which configuration type comes from which directory. This is needed, as we want later, that you can install a base theme and extens/overwrite it partially with your own configuration data.
The old configuration parts DataStructure and TemplateObject fall inside a mixer and we got now TV+ uses three types called DataStructure, MappingConfiguration and TemplateConfiguration. (Beside that we also have BackendLayoutConfiguration) This allows us greater reusage of configuration parts and the possibility to change the Renderer of the frontend output, for example against Twig or Smarty.
All old entry points to extend TV+ are removed at the moment and some complete new are arising, for example the LoadSaveHandlers, so you can write an own configuration loader/saver for your configuration files and your own super duper configuration file format.

If you like to test, develop and/or help documenting, see the demo theme extension which gives you an orientation and explaination how it works. It get updated while development of TV+ 8 happens as it is the testing reference if all works as exspected.

### HINT! To DataStructure

The word "DataStructure" will be renamed to DataConfiguration to match the wording Mapping- and TemplateConfiguration.

### HINT! Beta Testers

* If you have multilanguage websites, please test and report back, not all multilanguage parts are working or are tested completely. As there are to much possible configurations.
* Clipboard is not working yet
* Unused Elements is not working yet
* The TV+ Control Center resides in the admin tools section, it includes the "Update Script" to start the migration process.
* There is no editor anymore, the planed EXT:form editor is very complicated and took to much time. Maybe something else, later as another extension, as this functionality haven't todo with TV+ base.
* Check the extension which is created while migration, it will help to understand the configuration of TV+ 8.0.0.
* Please help with documentation.
* Not tested with PHP 8.0/8.1 but bugreports are welcome. See https://github.com/T3Voila/templavoilaplus/issues/346

### Extensions as WIP for TV+ 8
* https://github.com/extrameile/em_tvplus_theme_demo/

### What works:

* There is a ControlCenter Modul, which shows DataStructure/Mapping/Template Places.
    * For Data- and MappingConfiguration is only the list view implemented.
    * For TemplateConfiguration is only the list view implemented and very little information output.
    * There is a Debug screen which shows more about the internal configuration of the objects.
* There is also a new PageLayout Modul, which isn't puzzled together yet while rewriting
    * Different Output Handler -for different doktypes (standard, link, spacer, ...)
    * Editing via Ajax
    * Drag'n'Drop insert/move/delete but only in default language
    * ~~Backend Preview only partially~~
    * ~~Loading tree but only handling page layer (not the content elements)~~
    * ~~BElayout not respected yet~~
    * ~~No support for container yet~~
* Frontend rendering works for sDEF/lDEV/vDEF as you define
    * DataStructure will be taken into account
    * TypoScript can be handled while mapping in process
    * Templates using the XPath Renderer (a.k.a. old TemplaVoilà! template handling)
    * Add Meta/CSS/JS informations from TemplateConfiguration
    * ~~No support for container yet~~
    * Subtemplates/Rendertypes not supported yet
* Extending
    * You could extend TV+ 8 with new Places types with their own handler to load data
    * You could add new renderer (~~TYPO3 marker based,~~ Smarty, Twig, Fluid, ...)
    * You can work on own themes
* Update Script
    * There is an Update Script which already works quite extensive
    * ~~Only support for StaticDS yet! Need to find a project which uses DS in DB to check if it works as it should~~
    * You should select "creating new extension" also if it shows you a green extension to select
    * ~~Subtemplates/Rendertypes won't be processed yet~~
    * ~~BElayout won't be processed yet~~
    * No support for multiple storage pids yet
    * ~~No support for container yet~~
