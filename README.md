# TemplaVoilà! Plus

[![license](https://img.shields.io/github/license/pluspol-interactive/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-7.3.3-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)

TeamplaVoilà! Plus is a templating extension for the TYPO3 content management system. It is the follow up of the popular
TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

## Language files

If you like to help with the translation of the extension, please visit https://github.com/pluspol-interactive/templavoilaplus-languagefiles

## Status of development

The master branch is a complete rewrite of the complete code base of TemplaVoilà! Plus.
This also includes a changed handling in handling and configuration of DataStructures, Mapping and Templating.

If you like to test and develop, see the demo theme extension which gives you an orientation and explaination how it work. It get updated while development of TV+ 8 happens as it is the testing reference if all works as exspected.

## DEMO Theme
** https://github.com/extrameile/em_tvplus_theme_demo/ **

## What works:

* There is a ControlCenter Modul, which shows DataStructure/Mapping/Template Places.
    * For DataStructure the core:forms editor is integrated but not correctly configured, as I will remove the eTypes.
    * For Mappings is only the list view implemented.
    * For Templates is only the list view implemented and very little information output.
    * There is a Debug screen which shows more about the internal configuration of the objects.
* There is also a new PageLayout Modul, which isn't puzzled together yet while rewriting
    * Different Output Handler for different doktypes (standard, link, spacer, ...)
    * Loading tree but no handling/output
    * You can't edit in the PageLayout
* Frontend rendering works for sDEF/lDEV/vDEF as you define
    * DataStructure will be taken into account
    * TypoScript can be handled while mapping in process
    * Templates using the XPath Renderer (a.k.a. old TemplaVoilà! template handling)
    * Add Meta/CSS/JS informations from TemplateConfiguration
* Extending
    * You could extend TV+ 8 with new Places types with their own handler to load data
    * You could add new renderer (TYPO3 marker based, Smarty, Twig, Fluid, ...)
    * Working on own themes
