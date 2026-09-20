document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  root.classList.add('js-enhanced');

  const imageFallback = document.body.dataset.imageFallback;
  if (imageFallback) {
    document.querySelectorAll('img').forEach((image) => {
      image.addEventListener('error', () => {
        if (image.dataset.fallbackApplied === 'true') return;
        image.dataset.fallbackApplied = 'true';
        image.src = imageFallback;
      });
    });
  }

  const header = document.querySelector('.site-header');
  const toggleShadow = () => header?.classList.toggle('is-scrolled', window.scrollY > 10);
  toggleShadow();
  window.addEventListener('scroll', toggleShadow, { passive: true });

  document.querySelectorAll('.news-nav a:not(.dropdown-toggle)').forEach((link) => {
    link.addEventListener('click', () => {
      const menu = document.querySelector('#mainNav.show');
      if (menu && window.bootstrap) bootstrap.Collapse.getOrCreateInstance(menu).hide();
    });
  });

  document.querySelector('.back-to-top')?.addEventListener('click', (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  const floatingTop = document.querySelector('[data-floating-top]');
  const updateFloatingTop = () => floatingTop?.classList.toggle('is-visible', window.scrollY > 650);
  updateFloatingTop();
  window.addEventListener('scroll', updateFloatingTop, { passive: true });
  floatingTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  const progressBar = document.querySelector('[data-reading-progress]');
  const article = document.querySelector('.article-content');
  if (progressBar && article) {
    const updateReadingProgress = () => {
      const start = article.offsetTop;
      const distance = Math.max(1, article.offsetHeight - window.innerHeight);
      const percent = Math.min(100, Math.max(0, ((window.scrollY - start + 120) / distance) * 100));
      progressBar.style.width = `${percent}%`;
    };
    updateReadingProgress();
    window.addEventListener('scroll', updateReadingProgress, { passive: true });
    window.addEventListener('resize', updateReadingProgress);
  }

  document.querySelector('[data-copy-link]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const status = document.querySelector('[data-copy-status]');
    try {
      await navigator.clipboard.writeText(button.dataset.url || window.location.href);
      if (status) status.textContent = 'இணைப்பு நகலெடுக்கப்பட்டது';
    } catch {
      if (status) status.textContent = 'இணைப்பை நகலெடுக்க முடியவில்லை';
    }
  });

  const articleContent = document.querySelector('.article-content');
  let storedReaderSize = null;
  try { storedReaderSize = localStorage.getItem('wisdom-reader-size'); } catch {}
  let readerSize = Number(storedReaderSize || 1.16);
  const applyReaderSize = () => {
    if (articleContent) articleContent.style.setProperty('--reader-size', `${readerSize}rem`);
  };
  applyReaderSize();
  document.querySelectorAll('[data-text-size]').forEach((button) => {
    button.addEventListener('click', () => {
      if (button.dataset.textSize === 'increase') readerSize = Math.min(1.42, readerSize + .08);
      if (button.dataset.textSize === 'decrease') readerSize = Math.max(.98, readerSize - .08);
      if (button.dataset.textSize === 'reset') readerSize = 1.16;
      try { localStorage.setItem('wisdom-reader-size', String(readerSize)); } catch {}
      applyReaderSize();
    });
  });
  document.querySelector('[data-print-article]')?.addEventListener('click', () => window.print());

  const revealItems = document.querySelectorAll('.section-block, .explore-section, .category-lead, .search-result');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      });
    }, { threshold: .08, rootMargin: '0px 0px -35px' });
    revealItems.forEach((item) => {
      item.classList.add('reveal-item');
      revealObserver.observe(item);
    });
  } else {
    revealItems.forEach((item) => item.classList.add('is-revealed'));
  }
});
