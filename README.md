# TemplaVoilà! Plus

[![license](https://img.shields.io/github/license/T3Voila/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-7.3.6-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)

TemplaVoilà! Plus (TV+) is an extension for the TYPO3 content management system which adds an easy way to extend content elements and rendering to templates in Backend and Frontend.
It is the follow up of the popular TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

## Language files

If you like to help with the translation of the extension, please visit https://github.com/T3Voila/templavoilaplus-languagefiles

## The big TV+ 8 release

The first stable TV+ 8 release is 8.1.0 which changes many things on the base of TemplaVoilà! and so it needed a complete rewrite. Which will allow us to enhance it with new features. But for the moment some features are cut down, to get the release out.

### Whats missing

* The point-and-click mapper as it is very hard to get all things together and it isn't the base so it may come back as extension later on.
* MultiLanguage as this is very hard and partly confuse, we need a "data donation" for this. Also this isn't realy a core compatible way and so Language Fallback Support isn't easy possible.
* Workspace Support is also missing, or better, not well tested, it may be buggy it may be working.
* Documentational parts, please help here.

### On all this missing parts, what the hell is new/better?

* Compatible with TYPO3 v10 and v11 is new, there are also some bits for v12 already.
* No database records for templates and structures anymore, which helps on servers which use deployments and prepare this rollouts on testing/staging systems.
* Split frontend between data organization and rendering, this allows us to integrate different templating engines like [fluid](https://github.com/T3Voila/tvplus_fluid).
* Backend written with fluid templates, which allows us to use fluid templates for the backend layouts or backend previews, instead of the marker based templates.
* Also the good old XPathRenderer got small features which help on recursive data handling.
* Using places, so we could create theme extensions like [em_tvplus_theme_demo](https://github.com/extrameile/em_tvplus_theme_demo/) or [UIkit theme](https://github.com/T3Voila/t3voila_uikit) which will extensible with your own extension.

## What may come next a.k.a. 8.2.0

After 8.1.0 an update with fixes and readded missing features will come as 8.2.0, which may include support for workspaces, migration for multiple storage pids and maybe MultiLanguage, which depends on testers / data donators to try migration handling and output handling. Also eliminate dependency to fluid_styled_content and get "extending from theme" working.
Afterwards a TV+ 9 release will be skipped as TYPO3 v9 is already out of support at this point. So a TV+ 10 release with support for TYPO3 v10/v11 and v12 is planned. Hopefully also a cleaner handling of flexforms.

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

### What works:

* There is a ControlCenter Modul, which shows DataStructure/Mapping/Template Places.
    * For Data- and MappingConfiguration is only the list view implemented.
    * For TemplateConfiguration is only the list view implemented and very little information output.
    * There is a Debug screen which shows more about the internal configuration of the objects.
* There is also a new PageLayout Modul, which isn't puzzled together yet while rewriting
    * No support for multilanguage
    * ~~No support for clipboard~~
    * ~~No support of unused_elements~~
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
    * You can freely manipulate the NewContentWizard tabs and elements
* Update Script
    * There is an Update Script which works quite well
    * You should select "creating new extension" also if it shows you a green extension to select
    * No support for multiple storage pids yet
    * ~~The feature "ServerDeployment JSON" for better server upgrades isn't available yet~~

### What would help us:

* If you need one of the following things, we would need a data donation from your system (from the tx_templavoilaplus_* tables and StaticDS files if you have), so we can't test this and try to work on a solution.
    * Update script for multiple storage pids
    * Multilanguage editing support for lDEF/vDEF systems (Systems which use the TemplaVoilà! language things and not the core ones a.k.a. langDisable/langChildren)
