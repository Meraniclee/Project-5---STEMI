(() => {
  'use strict';
  const imageButtons = Array.from(document.querySelectorAll('[data-ecg-image]'));
  const preview = document.getElementById('ecgPreviewImage');
  if (!preview || !imageButtons.length) return;
  const byId = id => document.getElementById(id);
  const previewName = byId('ecgPreviewName');
  const previewDate = byId('ecgPreviewDate');
  const form = byId('ecgFindingForm');
  const cat = byId('ecgCategory');
  const sub = byId('ecgSubcategory');
  const rhythm = byId('ecgRhythm');
  const assessment = byId('ecgAssessment');
  const notes = byId('ecgNotes');
  const subcategories = {
    'Normal ECG': [],
    'Myocardial Infarction (MI)': ['Anterior STEMI','Inferior STEMI','Lateral STEMI','Posterior STEMI','Other / Unspecified MI'],
    'Arrhythmia': ['Atrial Fibrillation','Atrial Flutter','Supraventricular Tachycardia','Ventricular Tachycardia','Other Arrhythmia'],
    'Conduction Abnormality': ['Left Bundle Branch Block','Right Bundle Branch Block','AV Block','Other Conduction Abnormality'],
    'Other Abnormality': ['ST-T Changes','QT Abnormality','Ventricular Hypertrophy','Other'],
    'Inconclusive': []
  };
  const refreshSubcategories = desired => {
    if (!sub || !cat) return;
    sub.replaceChildren(new Option('Select subcategory',''));
    (subcategories[cat.value] || []).forEach(value => sub.add(new Option(value,value)));
    sub.value = (subcategories[cat.value] || []).includes(desired) ? desired : '';
    sub.disabled = !(subcategories[cat.value] || []).length;
  };
  if (cat) cat.addEventListener('change', () => refreshSubcategories(''));
  let active = imageButtons[0];
  const setText = (id, value) => { const el=byId(id); if(el) el.textContent=value; };
  const select = (button, initial = false) => {
    active = button;
    imageButtons.forEach(el => {el.classList.toggle('selected',el===button);el.setAttribute('aria-pressed',el===button?'true':'false');});
    preview.src = button.dataset.src;
    preview.alt = 'ECG report ' + button.dataset.name;
    previewName.textContent = button.dataset.name;
    previewDate.textContent = button.dataset.date;
    setText('infoFileName',button.dataset.name);
    setText('infoDate',button.dataset.date);
    setText('infoType',button.dataset.type);
    setText('infoSize',button.dataset.size);
    setText('infoResolution',button.dataset.resolution);
    setText('infoReview',button.dataset.category ? 'Documented' : 'Not documented');
    if (form) {
      byId('selectedEcgImageId').value = button.dataset.imageId;
      if (!initial) {
        cat.value = button.dataset.category || '';
        refreshSubcategories(button.dataset.subcategory || '');
        rhythm.value = button.dataset.rhythm || '';
        assessment.value = button.dataset.assessment || '';
        notes.value = button.dataset.notes || '';
      }
    }
    document.dispatchEvent(new CustomEvent('ecg:selected', {detail: {button}}));
  };
  imageButtons.forEach(button => button.addEventListener('click', () => select(button)));
  const hasOldSubmission = !!(cat && (cat.value || rhythm.value || assessment.value));
  const initiallySelected = imageButtons.find(el => el.dataset.imageId === byId('selectedEcgImageId')?.value) || imageButtons[0];
  select(initiallySelected, hasOldSubmission);
  if (cat && hasOldSubmission) refreshSubcategories(form?.dataset.oldSubcategory || '');
  const modal=byId('ecgModal'), modalImage=byId('ecgModalImage');
  if (!modal) return;
  let zoom=1;
  const applyZoom = () => {modalImage.style.width=(Math.round(zoom*100))+'%';byId('zoomResetEcg').textContent=Math.round(zoom*100)+'%';};
  const close=()=>{modal.hidden=true;document.body.style.overflow='';byId('expandEcg').focus();};
  byId('expandEcg').addEventListener('click',()=>{modalImage.src=active.dataset.src;byId('ecgModalTitle').textContent=active.dataset.name;zoom=1;applyZoom();modal.hidden=false;document.body.style.overflow='hidden';byId('closeEcgModal').focus();});
  byId('closeEcgModal').addEventListener('click',close);
  byId('zoomInEcg').addEventListener('click',()=>{zoom=Math.min(zoom+.25,4);applyZoom();});
  byId('zoomOutEcg').addEventListener('click',()=>{zoom=Math.max(zoom-.25,.5);applyZoom();});
  byId('zoomResetEcg').addEventListener('click',()=>{zoom=1;applyZoom();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden)close();});
})();
