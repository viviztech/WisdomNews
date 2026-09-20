document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
  if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
}));

const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const closeSidebar = () => {
  document.body.classList.remove('sidebar-open');
  sidebarToggle?.setAttribute('aria-expanded', 'false');
};
sidebarToggle?.addEventListener('click', () => {
  const isOpen = document.body.classList.toggle('sidebar-open');
  sidebarToggle.setAttribute('aria-expanded', String(isOpen));
});
document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', closeSidebar));
document.querySelectorAll('.admin-nav a').forEach(link => link.addEventListener('click', closeSidebar));
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeSidebar(); });

const editor = document.querySelector('[data-editor]');
const source = document.querySelector('[data-editor-source]');
if (editor && source) {
  document.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
    document.execCommand(button.dataset.command, false, button.dataset.value || null);
    editor.focus();
  }));
  editor.closest('form')?.addEventListener('submit', () => { source.value = editor.innerHTML; });
}

const title = document.querySelector('[data-slug-title]');
const slug = document.querySelector('[data-slug-input]');
if (title && slug) {
  let manuallyEdited = slug.value.trim() !== '';
  slug.addEventListener('input', () => { manuallyEdited = slug.value.trim() !== ''; });
  title.addEventListener('input', () => {
    if (!manuallyEdited) slug.value = title.value.toLocaleLowerCase().trim().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '');
  });
}
