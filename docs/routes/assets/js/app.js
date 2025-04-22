(() => {
  'use strict';

  // Highlight code
  hljs.highlightAll();

  // Add [target="_blank"] attribute to external links.
  const externalLinks = document.querySelectorAll('a[href^="http"]');
  externalLinks.forEach(link => link.setAttribute('target', '_blank'));
}) ()

