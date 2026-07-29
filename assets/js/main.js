(function(){
'use strict';
const body=document.body;
function closeMenus(except){document.querySelectorAll('.dropdown-menu').forEach(m=>{if(m!==except){m.hidden=true;const b=document.querySelector('[data-menu-button="'+m.id+'"]');if(b)b.setAttribute('aria-expanded','false')}})}
document.addEventListener('click',e=>{
 const menuButton=e.target.closest('[data-menu-button]');
 if(menuButton){e.preventDefault();e.stopPropagation();const menu=document.getElementById(menuButton.dataset.menuButton);const open=menu&&menu.hidden;closeMenus(menu);if(menu){menu.hidden=!open;menuButton.setAttribute('aria-expanded',String(open))}return}
 if(!e.target.closest('.menu-wrap'))closeMenus(null);
 const dismiss=e.target.closest('.btn-close,[data-dismiss="alert"]');if(dismiss){const alert=dismiss.closest('.alert');if(alert)alert.remove()}
 const toggle=e.target.closest('[data-sidebar-toggle]');if(toggle){const open=!body.classList.contains('sidebar-open');body.classList.toggle('sidebar-open',open);document.querySelectorAll('[data-sidebar-toggle]').forEach(b=>b.setAttribute('aria-expanded',String(open)))}
});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeMenus(null);body.classList.remove('sidebar-open')}if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();document.getElementById('globalSearch')?.focus()}});
const search=document.getElementById('globalSearch');if(search)search.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();document.querySelectorAll('main table tbody tr, main .card, main .ui-card').forEach(el=>{if(el.closest('.topbar,.sidebar'))return;el.style.display=!q||el.textContent.toLowerCase().includes(q)?'':''})});
window.confirmDelete=function(message='Are you sure you want to continue?'){return window.confirm(message)};
setTimeout(()=>document.querySelectorAll('.alert.auto-dismiss,.alert-dismissible').forEach(a=>a.remove()),6000);
})();
