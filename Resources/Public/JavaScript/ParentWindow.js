define([
  'TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter'
], function(FormEngineLinkBrowserAdapter, ElementBrowser) {
  const FormEngineLinkBrowserAdapterParentFunction = FormEngineLinkBrowserAdapter.getParent;
  const getParent = () => {
	if (
      typeof window.parent !== 'undefined' &&
      typeof window.parent.document.list_frame !== 'undefined' &&
      window.parent.frames.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null &&
      window.parent.frames.list_frame.parent.document.querySelectorAll('.t3js-modal-iframe').length > 1
    ) {
	  return window.parent.frames.list_frame.parent.document.querySelector('.t3js-modal-iframe').contentWindow;
    }
    return null;
  }

  FormEngineLinkBrowserAdapter.getParent = () => {
    return getParent() || FormEngineLinkBrowserAdapterParentFunction();
  }
});
