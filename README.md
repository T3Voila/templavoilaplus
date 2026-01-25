# TemplaVoilà! Plus

[![license](https://img.shields.io/github/license/T3Voila/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-12.0.4-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)
[![Tests](https://github.com/T3Voila/templavoilaplus/actions/workflows/ci.yml/badge.svg)](https://github.com/T3Voila/templavoilaplus/actions/workflows/ci.yml)

TemplaVoilà! Plus (TV+) is an extension for the TYPO3 content management system which adds an easy way to extend content elements and rendering to templates in Backend and Frontend.
It is the follow up of the popular TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

## Translation and Documentation

We are using crowdin for translation handling. Please visit https://crowdin.com/project/typo3-extension-templavoilaplu to check state. You can also help there to fix issues.
But also our complete documentation needs a rewrite, please help there.

## Next release TV+ v12

The next TV+ release will be v12, starting with 12.0.0 as first alpha release. It will support TYPO3 v12/v13 LTS.

### Missing parts from old TemplaVoilà!

* The point-and-click mapper as XPath mapping have no feature.
* MultiLanguage as this is very hard and partly confuse, we need a "data donation" for this. Also this isn't realy a core compatible way and so Language Fallback Support isn't easy possible.
* Workspace Support is only somewhat tested, it may be buggy it may be working.
* Documentation and translation parts, please help here.

### On all this missing parts, what the hell is new/better?

* Compatible with TYPO3 v8 to v11 (with TV+ v8) or with TYPO3 v12 and v13 (with TV+ v12)
* No database records for templates and structures anymore, which helps on servers which use deployments and prepare this rollouts on testing/staging systems.
* Split frontend between data organization and rendering, this allows us to integrate different templating engines like [fluid](https://github.com/T3Voila/tvplus_fluid).
* Backend written with fluid templates, which allows us to use fluid templates for the backend layouts or backend previews, instead of the marker based templates.
* Also the good old XPathRenderer got small features which help on recursive data handling.
* Using places, so we could create theme extensions like [em_tvplus_theme_demo](https://github.com/extrameile/em_tvplus_theme_demo/) or [UIkit theme](https://github.com/T3Voila/t3voila_uikit) which will extensible with your own extension.
* Supporting fluid templating in frontend beside old XPath and marker based templating.

## What may come next after v12

Nobody knows and it depends on the requests from outside, from the community, from you.
Planned is TV+ v14 which will be compatible to TYPO3 v14.

## How is the configuration handling differently

The handling of configuration is done inside directories called "Places". This is needed, as we want later, that you can install a base theme and extend/overwrite it partially with your own configuration data.
The old configuration parts, DataStructure and TemplateObject, have been rearranged. TV+ uses now four configuration types DataConfiguration (with the clean core DataStructure), MappingConfiguration, TemplateConfiguration and BackendLayoutConfiguration. This allows for a better reusage of configuration parts and the possibility to configure different output renderer.
All old entry points to extend TV+ are removed at the moment and some complete new are arising, for example the LoadSaveHandlers, which enables you to write an own configuration loader/saver for your configuration files and your own super duper configuration file format.
Please take a look inside the two theme extensions or check the extension which is created while migration to understand how it looks and how it works.

## How to upgrade

The TV+ Control Center resides in the admin tools section, it includes the "Update Script" to start the migration process. The "Update Script" checks first your system and tells you as much as possible what and how it does. At the end a theme extension will be generated which includes all needed parts for the installation.
Afterwards you need to update your TypoScript for starting frontend output. All together can be found in the [documentation](https://docs.typo3.org/p/templavoilaplus/templavoilaplus/8.0/en-us/Migration/Index.html).
If you use deployment strategie you don't have to run the complete migration again, the information for database migration is saved in an json file, a "Server Migration Script" will show up for this.


### Theme Extensions as WIP for TV+ 8
* [em_tvplus_theme_demo](https://github.com/extrameile/em_tvplus_theme_demo/) - Demo theme using XPath Renderer
* [UIkit theme](https://github.com/T3Voila/t3voila_uikit) - Theme using the Fluid Renderer and the UIkit inside frontend output

### What would help us:

* If you need one of the following things, we would need a data donation from your system (from the tx_templavoilaplus_* tables and StaticDS files if you have), so we can't test this and try to work on a solution.
    * Update script for multiple storage pids
    * Multilanguage editing support for lDEF/vDEF systems (Systems which use the TemplaVoilà! language things and not the core ones a.k.a. langDisable/langChildren)
