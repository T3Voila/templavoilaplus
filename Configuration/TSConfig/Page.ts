mod.web_txtemplavoilaplusCenter.templatePath = templates,default/templates
mod.web_txtemplavoilaplusLayout.enableDeleteIconForLocalElements = 0
mod.web_txtemplavoilaplusLayout.enableContentAccessWarning = 1
mod.web_txtemplavoilaplusLayout.enableLocalizationLinkForFCEs = 0
mod.web_txtemplavoilaplusLayout.useLiveWorkspaceForReferenceListUpdates = 1
mod.web_txtemplavoilaplusLayout.adminOnlyPageStructureInheritance = fallback

# example for filtering allowed maps allow single string or array
# refers to mappingconfiguration identifier, so look for the prefix in
# yourextenstion/Configuration/TVP/MappingPlaces.php for an identifier like
# vendor/yourextension/Page/MappingConfiguration
# and use that like:
# mod.web_txtemplavoilaplusLayout.filterMaps = vendor/yourextension
# or like:
# mod.web_txtemplavoilaplusLayout.filterMaps.1 = vendor/yourextension/Page
# mod.web_txtemplavoilaplusLayout.filterMaps.2 = vendor/yourotherextension/FCE
