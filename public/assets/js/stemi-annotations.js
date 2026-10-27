(() => {
  'use strict';
  const form = document.getElementById('ecgAnnotationForm');
  if (!form) return;
  const config = JSON.parse(document.getElementById('annotationData').textContent);
  const api = window.EcgLeadExtractor;
  const byId = id => document.getElementById(id);
  const preview = byId('ecgPreviewImage');
  const editable = config.editable;
  const autosave = editable ? new window.StemiAutosaveQueue({send: (id,data) => sendAnnotation(id,data,false), onChange:updateStatus, onSaved:recordSaved}) : null;
  const leadButtons = [...document.querySelectorAll('[data-lead]')];
  const sourceButtons = [...document.querySelectorAll('[data-ecg-image]')];
  let imageId = null, selectedLead = 'I', extraction = null, saving = false, generation = 0, imageReady = false;
  const clone = value => JSON.parse(JSON.stringify(value));
  const pathFor = name => name.replace(/\]/g, '').split('[').filter(Boolean);
  const valueAt = (data, name) => pathFor(name).reduce((v, key) => v?.[key], data);
  const fields = () => [...form.querySelectorAll('select[name],textarea[name],input[name]')].filter(el => /^(lead_findings|overall|stemi|notes)(\[|$)/.test(el.name));
  const showError = message => { byId('extractionError').textContent = message; byId('extractionError').hidden = !message; };
  function capture() {
    const data = {lead_findings:{}, overall:{}, stemi:{}, notes:'', extraction: extraction ? clone(extraction) : null};
    fields().forEach(el => {
      const path = pathFor(el.name);
      let target = data;
      path.slice(0,-1).forEach(key => { target = target[key] ??= {}; });
      const key = path.at(-1);
      if (el.type === 'checkbox') {
        target[key] ??= [];
        if (el.checked) target[key].push(el.value);
      } else target[key] = el.value;
    });
    return data;
  }
  function dirty(immediate = false) {
    if (!editable || !imageId) return;
    autosave.set(imageId,capture(),immediate);
  }
  function updateStatus() {
    if (!editable) return;
    const pending=autosave.dirtyEntries(), failed=pending.find(([,entry])=>entry.error);
    let message='All changes saved automatically';
    if(failed) message=`Changes not saved: ${failed[1].error.message}${failed[1].error.retryable ? ' Retrying automatically…' : ''}`;
    else if(saving || autosave.savingId || pending.length) message='Saving changes…';
    [byId('draftStatus'),byId('autosaveStatus')].filter(Boolean).forEach(status=>{status.textContent=message;status.classList.toggle('unsaved',pending.length>0);});
    document.querySelectorAll('[data-retry-autosave]').forEach(button=>button.hidden=!failed);
  }
  async function sendAnnotation(id,data,complete) {
    const payload=clone(data);
    payload.ecg_image_id=id; payload.complete=complete?1:0;
    const elevation=payload.stemi?.elevation_leads || [];
    if(payload.stemi?.elevation_measurements) {
      for(const lead of Object.keys(payload.stemi.elevation_measurements)) if(!elevation.includes(lead))delete payload.stemi.elevation_measurements[lead];
    }
    let response;
    try {
      response=await fetch(form.action,{method:'POST',body:JSON.stringify(payload),headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.elements.namedItem('_token').value},credentials:'same-origin',keepalive:true});
    } catch(cause) { const error=new Error('Connection unavailable. Your edits remain on this page.');error.retryable=true;throw error; }
    const result=await response.json().catch(()=>({message:response.status===419?'Your session expired. Sign in again before retrying.':'The server could not confirm the save.'}));
    if(!response.ok || !result.interpretation) {
      const messages=Object.values(result.errors || {}).flat();
      const error=new Error(messages.length ? messages.slice(0,4).join(' ') + (messages.length>4 ? ` ${messages.length-4} other fields need review.` : '') : result.message || 'The save could not be confirmed.');
      error.fields=Object.keys(result.errors || {}); error.retryable=response.status>=500 || response.status===429;
      throw error;
    }
    return result;
  }
  function recordSaved(id,result) {
    const saved=result.interpretation;
    config.reviews=config.reviews.filter(review=>review.id!==saved.id);
    config.reviews.unshift({...saved,image_id:saved.ecg_image_id,doctor:'Your interpretation',updated:'Just autosaved'});
    if(id===imageId) {
      byId('annotationReviewStatus').textContent='Progress autosaved. Unfinished leads can be reviewed later.';
      byId('infoReview').textContent=saved.annotation_complete?'Documented':'Examination in progress';
    }
  }
  function measurementVisibility() {
    const leads = fields().filter(el => el.name === 'stemi[elevation_leads][]' && el.checked).map(el => el.value);
    document.querySelectorAll('[data-measurement-lead]').forEach(label => { label.hidden = !leads.includes(label.dataset.measurementLead); });
  }
  function highlightAnswers() {
    // An entered answer stays blue after autosave/reload; clearing it restores the default.
    // The separate autosave status is the source of truth for whether changes reached the server.
    form.querySelectorAll('.annotation-field,[data-measurement-lead]').forEach(field => {
      const answered = [...field.querySelectorAll('select,input,textarea')].some(control =>
        control.type === 'checkbox' ? control.checked : control.value.trim() !== ''
      );
      field.classList.toggle('annotation-answered', answered);
      const status = field.querySelector('[data-answer-state]');
      if (status) status.textContent = answered ? 'Answered' : 'Not answered';
    });
  }
  function progress() {
    let done = 0;
    config.leads.forEach(lead => {
      const controls = [...byId(`lead-panel-${lead}`).querySelectorAll('select')];
      const count = controls.filter(el => el.value).length;
      const badge = document.querySelector(`[data-lead-progress="${lead}"]`);
      badge.textContent = `${count} / ${controls.length} findings`;
      badge.classList.toggle('lead-done', count === controls.length);
      const complete=count===controls.length && [...byId(`lead-panel-${lead}`).querySelectorAll('input[type="number"]')].every(el=>el.validity.valid);
      const button=byId(`lead-tab-${lead}`);
      button.classList.toggle('lead-finished',complete);button.classList.toggle('lead-unfinished',!complete);
      button.title=`Lead ${lead}: ${complete?'Finished examining':'Unfinished examination'}`;
      document.querySelector(`[data-lead-state="${lead}"]`).textContent=complete?'Examined':'Unfinished';
      if (complete) done++;
    });
    byId('leadCompletionSummary').textContent = `${done} of 12 leads documented. Measurements may be left blank when not measurable.`;
  }
  function fill(data) {
    fields().forEach(el => {
      const value = valueAt(data, el.name);
      if (el.type === 'checkbox') el.checked = Array.isArray(value) && value.includes(el.value);
      else el.value = value ?? '';
    });
    extraction = data.extraction ? clone(data.extraction) : null;
    if (extraction) {
      for (const key of ['left','top','width','height']) extraction[key] = Number(extraction[key]);
      extraction.column_breaks = extraction.column_breaks?.map(Number);
      extraction.row_breaks = extraction.row_breaks?.map(Number);
      extraction.confirmed = extraction.confirmed === true || extraction.confirmed === 1 || extraction.confirmed === '1';
      if (!api.validate(extraction)) extraction = null;
    }
    measurementVisibility(); highlightAnswers(); progress(); updateStatus();
  }
  function putHidden(name, value) {
    const input = document.createElement('input'); input.type='hidden'; input.name=name; input.value=value;
    byId('extractionInputs').append(input);
  }
  function syncExtraction() {
    byId('extractionInputs').replaceChildren();
    if (!extraction) return;
    ['left','top','width','height'].forEach(key => putHidden(`extraction[${key}]`, extraction[key]));
    ['column_breaks','row_breaks'].forEach(key => extraction[key].forEach((value,i) => putHidden(`extraction[${key}][${i}]`,value)));
    putHidden('extraction[confirmed]', extraction.confirmed ? '1' : '0');
    const badge = byId('cropStatus');
    badge.textContent = extraction.confirmed ? 'Crops confirmed' : 'Check & confirm crops';
    badge.classList.toggle('confirmed', !!extraction.confirmed);
    const values = {...extraction, col0:extraction.column_breaks[0],col1:extraction.column_breaks[1],col2:extraction.column_breaks[2],row0:extraction.row_breaks[0],row1:extraction.row_breaks[1]};
    document.querySelectorAll('[data-crop-control]').forEach(el => { el.value = (values[el.dataset.cropControl]*100).toFixed(2); });
  }
  function overview() {
    if (!extraction || !preview.naturalWidth) return;
    const canvas = byId('cropOverview'), scale = Math.min(1, 1200/preview.naturalWidth);
    canvas.width = Math.round(preview.naturalWidth*scale); canvas.height = Math.round(preview.naturalHeight*scale);
    const ctx = canvas.getContext('2d');
    ctx.drawImage(preview,0,0,canvas.width,canvas.height);
    Object.entries(api.regions(extraction)).forEach(([lead,region]) => {
      const x=region.left*canvas.width, y=region.top*canvas.height, w=region.width*canvas.width, h=region.height*canvas.height;
      ctx.strokeStyle='#007fcb'; ctx.lineWidth=2; ctx.strokeRect(x,y,w,h);
      ctx.fillStyle='#007fcb'; ctx.fillRect(x+2,y+2,48,25);
      ctx.fillStyle='#fff'; ctx.font='bold 16px sans-serif'; ctx.fillText(lead,x+9,y+20);
    });
  }
  function render() {
    if (!extraction || !preview.complete || !preview.naturalWidth) return;
    try {
      document.querySelectorAll('[data-lead-canvas]').forEach(canvas => api.render(preview,extraction,canvas.dataset.leadCanvas,canvas));
      api.render(preview,extraction,selectedLead,byId('selectedLeadCanvas'));
      overview(); syncExtraction(); showError('');
    } catch (error) { showError('Lead extraction could not be displayed. Check the image and extraction boundaries.'); }
  }
  function chooseLead(lead, focus = false) {
    selectedLead = lead;
    leadButtons.forEach(button => { const selected=button.dataset.lead === lead; button.setAttribute('aria-selected',String(selected)); button.tabIndex=selected?0:-1; });
    config.leads.forEach(item => { byId(`lead-panel-${item}`).hidden = item !== lead; });
    byId('activeLeadTitle').textContent = `— ${lead}`;
    byId('selectedLeadCanvas').setAttribute('aria-label', `Enlarged ECG lead ${lead}`);
    if (imageReady && extraction && preview.complete && preview.naturalWidth) api.render(preview,extraction,lead,byId('selectedLeadCanvas'));
    if (focus) byId(`lead-tab-${lead}`).focus();
  }
  async function loadCrops() {
    const token = ++generation;
    imageReady = false;
    byId('cropStatus').textContent = 'Loading lead images…';
    byId('cropStatus').classList.remove('confirmed');
    form.querySelectorAll('canvas').forEach(canvas => canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height));
    if (byId('confirmCrops')) byId('confirmCrops').disabled = true;
    showError('');
    try {
      await preview.decode();
      if (token !== generation) return;
      imageReady = true;
      if (!extraction) extraction = api.detect(preview);
      render();
      if (byId('confirmCrops')) byId('confirmCrops').disabled = false;
    } catch (error) { if (token === generation) { byId('cropStatus').textContent='Image unavailable'; showError('Unable to load this ECG image. Reload the page or ask the administrator to check the uploaded file.'); } }
  }
  function currentReviews() { return config.reviews.filter(review => String(review.image_id) === imageId); }
  function loadReview(review) {
    fill(review || {});
    const legacy = byId('legacyInterpretation');
    if (legacy) { legacy.textContent = review?.legacy ? `Earlier format: ${review.legacy}. Structured lead annotations were not recorded.` : ''; legacy.hidden = !review?.legacy; }
    byId('annotationReviewStatus').textContent = review ? `${review.doctor} · ${review.updated} · ${review.annotation_complete ? 'All fields documented' : 'Draft / incomplete'}` : editable ? 'New interpretation for this ECG image.' : 'No interpretation recorded for this ECG image.';
    byId('infoReview').textContent = review ? (review.annotation_complete ? 'Documented' : 'Draft / incomplete') : 'Not documented';
    loadCrops();
  }
  function selectImage(button) {
    if (saving) return;
    if (imageId && editable && autosave.get(imageId)) autosave.flush();
    imageId = String(button.dataset.imageId);
    byId('selectedEcgImageId').value=imageId;
    const reviews = currentReviews();
    if (editable) {
      const existing = reviews.find(r => Number(r.examination_id) === Number(config.examinationId));
      const draft = autosave.get(imageId);
      loadReview(draft ? {...existing, ...draft.data} : existing);
    } else {
      const selector=byId('reviewSelector'); selector.replaceChildren();
      if (!reviews.length) selector.add(new Option('No recorded interpretation',''));
      reviews.forEach(r => selector.add(new Option(`${r.doctor} · ${r.updated} · ${r.annotation_complete ? 'Documented' : 'Draft / earlier format'}`, r.id)));
      loadReview(reviews[0]);
    }
    selectedLead = 'I';
    chooseLead('I'); updateStatus();
  }
  document.addEventListener('ecg:selected', event => selectImage(event.detail.button));
  byId('reviewSelector')?.addEventListener('change',event=>loadReview(currentReviews().find(r=>String(r.id)===event.target.value)));
  leadButtons.forEach((button,index) => {
    button.addEventListener('click',()=>chooseLead(button.dataset.lead));
    button.addEventListener('keydown',event=>{
      let next;
      if (event.key==='ArrowRight') next=(index+1)%12;
      if (event.key==='ArrowLeft') next=(index+11)%12;
      if (event.key==='ArrowDown') next=(index+4)%12;
      if (event.key==='ArrowUp') next=(index+8)%12;
      if (event.key==='Home') next=0;
      if (event.key==='End') next=11;
      if (next !== undefined) { event.preventDefault(); chooseLead(leadButtons[next].dataset.lead,true); }
    });
  });
  byId('previousLead').addEventListener('click',()=>chooseLead(config.leads[(config.leads.indexOf(selectedLead)+11)%12]));
  byId('nextLead').addEventListener('click',()=>chooseLead(config.leads[(config.leads.indexOf(selectedLead)+1)%12]));
  form.addEventListener('input',event=>{
    const name = event.target.name || '';
    const leadMatch = name.match(/^lead_findings\[([^\]]+)\]\[st_elevation_mm\]$/);
    const stemiMatch = name.match(/^stemi\[elevation_measurements\]\[([^\]]+)\]$/);
    if (leadMatch) form.elements.namedItem(`stemi[elevation_measurements][${leadMatch[1]}]`).value = event.target.value;
    if (stemiMatch) form.elements.namedItem(`lead_findings[${stemiMatch[1]}][st_elevation_mm]`).value = event.target.value;
    measurementVisibility(); highlightAnswers(); progress(); if (name) dirty(event.target.tagName==='SELECT' || event.target.type==='checkbox');
  });
  document.querySelectorAll('[data-crop-control]').forEach(input => input.addEventListener('change',()=>{
    if (!extraction) return;
    const candidate=clone(extraction), key=input.dataset.cropControl, value=Number(input.value)/100;
    if (key.startsWith('col')) candidate.column_breaks[Number(key.at(-1))]=value;
    else if (key.startsWith('row')) candidate.row_breaks[Number(key.at(-1))]=value;
    else candidate[key]=value;
    candidate.confirmed=false;
    try { api.regions(candidate); extraction=candidate; render(); dirty(); }
    catch { showError('Keep the crop inside the image and its dividers in increasing order.'); syncExtraction(); }
  }));
  byId('redetectCrops')?.addEventListener('click',()=>{try { extraction=api.detect(preview); render(); dirty(); } catch { showError('Load the ECG image before detecting its leads.'); }});
  byId('confirmCrops')?.addEventListener('click',()=>{if (extraction && imageReady) { extraction.confirmed=true; render(); dirty(); byId('cropEditor').open=false; }});
  const overviewCanvas=byId('cropOverview'); let dragStart=null;
  const pointer = event => {const rect=overviewCanvas.getBoundingClientRect();return {x:Math.max(0,Math.min(1,(event.clientX-rect.left)/rect.width)),y:Math.max(0,Math.min(1,(event.clientY-rect.top)/rect.height))};};
  overviewCanvas.addEventListener('pointerdown',event=>{if (!editable || !extraction) return; dragStart=pointer(event); overviewCanvas.setPointerCapture(event.pointerId);});
  overviewCanvas.addEventListener('pointermove',event=>{if (!dragStart) return; overview(); const end=pointer(event),ctx=overviewCanvas.getContext('2d'); ctx.fillStyle='rgba(0,140,220,.16)'; ctx.fillRect(dragStart.x*overviewCanvas.width,dragStart.y*overviewCanvas.height,(end.x-dragStart.x)*overviewCanvas.width,(end.y-dragStart.y)*overviewCanvas.height);});
  overviewCanvas.addEventListener('pointerup',event=>{if (!dragStart) return; const end=pointer(event),start=dragStart; dragStart=null; const candidate={...extraction,left:Math.min(start.x,end.x),top:Math.min(start.y,end.y),width:Math.abs(start.x-end.x),height:Math.abs(start.y-end.y),confirmed:false}; if(candidate.width>=.05&&candidate.height>=.05) {extraction=candidate; render(); dirty();} else overview();});
  overviewCanvas.addEventListener('pointercancel',()=>{dragStart=null;overview();});
  window.addEventListener('beforeunload',event=>{if(autosave?.dirtyEntries().length) {autosave.flush();event.preventDefault();event.returnValue='';}});
  window.addEventListener('online',()=>autosave?.flush());
  document.querySelectorAll('[data-retry-autosave]').forEach(button=>button.addEventListener('click',()=>autosave.flush()));
  // Save pending changes before following an in-app navigation link.
  document.addEventListener('click',async event=>{
    const link=event.target.closest('a[href]');
    if(!link || !autosave?.dirtyEntries().length || event.defaultPrevented || event.button!==0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download'))return;
    const url=new URL(link.href,window.location.href);
    if(url.origin!==window.location.origin || (url.pathname===location.pathname && url.search===location.search && url.hash))return;
    event.preventDefault();
    if(await autosave.flush())window.location.assign(url.href);
  });
  const flushedForms=new WeakSet();
  document.addEventListener('submit',async event=>{
    const target=event.target;
    if(target===form || !autosave?.dirtyEntries().length || flushedForms.has(target))return;
    event.preventDefault();
    if(await autosave.flush()) {flushedForms.add(target);target.requestSubmit(event.submitter);}
  },true);
  form.addEventListener('invalid', event => {
    const panel=event.target.closest('[role="tabpanel"]');
    if(panel) chooseLead(panel.id.replace('lead-panel-',''));
    if(event.target.closest('#cropEditor'))byId('cropEditor').open=true;
    const measurement=event.target.closest('[data-measurement-lead]');
    if(measurement)measurement.hidden=false;
  },true);
  form.addEventListener('submit',async event=>{
    event.preventDefault();
    if (!editable || saving) return;
    if(event.submitter?.id!=='completeExamination') {autosave.flush();return;}
    if(!imageReady) {showError('Wait for the ECG image to load before completing its review.');return;}
    syncExtraction();
    autosave.set(imageId,capture(),true);
    saving=true; updateStatus(); sourceButtons.forEach(b=>b.disabled=true);
    const lockedControls=[...form.querySelectorAll('input:not([type="hidden"]),select,textarea,button')].map(control=>[control,control.disabled]);
    lockedControls.forEach(([control])=>control.disabled=true);
    try {
      if(!await autosave.flush())return;
      const result=await sendAnnotation(imageId,capture(),true);
      if(result.completed)window.location.assign(result.redirect || window.location.href);
    } catch(error) {
      showError(`Your progress is autosaved. To complete the examination: ${error.message}`);
      const key=error.fields?.[0] || '', lead=key.match(/^lead_findings\.([^.]+)/)?.[1];
      if(lead && config.leads.includes(lead))chooseLead(lead);
      if(key.startsWith('extraction'))byId('cropEditor').open=true;
      byId('extractionError').scrollIntoView({block:'center'});
    }
    finally {saving=false;sourceButtons.forEach(b=>b.disabled=false);lockedControls.forEach(([control,wasDisabled])=>control.disabled=wasDisabled);updateStatus();}
  });
  if(editable && config.old?.ecg_image_id)autosave.set(String(config.old.ecg_image_id),config.old);
  const initial=sourceButtons.find(button=>button.dataset.imageId===String(config.selectedImage)) || sourceButtons[0];
  initial.click();
})();
