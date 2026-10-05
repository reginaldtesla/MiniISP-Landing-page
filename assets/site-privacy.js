(function () {
  'use strict';

  var preferenceKey = 'tesnet-external-preview-choice';
  var banner;

  function loadExternalContent() {
    document.querySelectorAll('iframe[data-external-src]').forEach(function (frame) {
      frame.src = frame.getAttribute('data-external-src');
      frame.removeAttribute('data-external-src');
      var fallback = frame.parentElement.querySelector('.external-preview-prompt');
      if (fallback) fallback.remove();
    });
    document.querySelectorAll('img[data-external-src]').forEach(function (image) {
      image.loading = 'eager';
      image.src = image.getAttribute('data-external-src');
      image.removeAttribute('data-external-src');
      var visual = image.closest('.contact-visual');
      if (visual) visual.classList.add('has-external-image');
    });
    document.querySelectorAll('[data-external-background]').forEach(function (element) {
      var imageUrl = element.getAttribute('data-external-background');
      element.style.setProperty('--hero-art', 'url("' + imageUrl.replace(/"/g, '') + '") center/cover no-repeat');
      element.removeAttribute('data-external-background');
    });
  }

  function saveChoice(choice) {
    try {
      localStorage.setItem(preferenceKey, choice);
    } catch (error) {
      // The current page can still load a preview after an explicit click.
    }
    if (choice === 'allow') loadExternalContent();
    if (banner) banner.hidden = true;
  }

  function openPreferences() {
    if (!banner) return;
    banner.hidden = false;
    var firstButton = banner.querySelector('button');
    if (firstButton) firstButton.focus();
  }

  function init() {
    var choice = null;
    try {
      choice = localStorage.getItem(preferenceKey);
    } catch (error) {
      choice = null;
    }

    banner = document.createElement('aside');
    banner.className = 'privacy-banner';
    banner.setAttribute('aria-labelledby', 'privacyBannerTitle');
    banner.innerHTML = '<div class="privacy-banner-copy"><strong id="privacyBannerTitle">External content</strong><p>Unsplash images and the embedded hotspot preview can contact external services. They stay off until you allow them. Your choice is saved in this browser.</p><a href="cookies.html">Privacy and cookie details</a></div><div class="privacy-banner-actions"><button type="button" data-choice="allow">Allow external content</button><button type="button" data-choice="essential">Essential only</button></div>';
    document.body.appendChild(banner);

    banner.addEventListener('click', function (event) {
      var choiceButton = event.target.closest('[data-choice]');
      if (choiceButton) saveChoice(choiceButton.getAttribute('data-choice'));
    });

    document.querySelectorAll('[data-privacy-preferences]').forEach(function (button) {
      button.addEventListener('click', openPreferences);
    });

    document.querySelectorAll('iframe[data-external-src]').forEach(function (frame) {
      var prompt = document.createElement('div');
      prompt.className = 'external-preview-prompt';
      prompt.innerHTML = '<p>The hotspot preview is not loaded because it can make requests to TesNet payment and advert services.</p><button type="button">Load preview once</button>';
      prompt.querySelector('button').addEventListener('click', function () {
        frame.src = frame.getAttribute('data-external-src');
        frame.removeAttribute('data-external-src');
        prompt.remove();
      });
      frame.parentElement.insertBefore(prompt, frame);
    });

    if (choice === 'allow') loadExternalContent();
    if (choice) banner.hidden = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();