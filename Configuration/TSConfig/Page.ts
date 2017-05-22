mod.web_txtemplavoilaplusCenter.templatePath = templates,default/templates
mod.web_txtemplavoilaplusLayout.enableDeleteIconForLocalElements = 0
mod.web_txtemplavoilaplusLayout.enableContentAccessWarning = 1
mod.web_txtemplavoilaplusLayout.enableLocalizationLinkForFCEs = 0
mod.web_txtemplavoilaplusLayout.useLiveWorkspaceForReferenceListUpdates = 1
mod.web_txtemplavoilaplusLayout.adminOnlyPageStructureInheritance = fallback

# Add FCE to the newContentElement wizard and reorder plugin to the end
tempWizardItems < mod.wizards.newContentElement.wizardItems
tempWizardItems.plugins >
tempWizardItems.fce {
    header = LLL:EXT:templavoilaplus/Resources/Private/Language/BackendLayout.xlf:fce
    show = *
}
tempWizardItems.plugins < mod.wizards.newContentElement.wizardItems.plugins
mod.wizards.newContentElement.wizardItems < tempWizardItems
tempWizardItems >
