const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const sandbox={setTimeout,clearTimeout,Date};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(__dirname,'../../public/assets/js/stemi-autosave.js'),'utf8'),sandbox);
const Queue=sandbox.StemiAutosaveQueue;
const tick=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
  // Older acknowledgements cannot mark changes typed during a save as saved.
  const requests=[];
  const queue=new Queue({delay:0,send:(id,data)=>new Promise(resolve=>requests.push({id,data,resolve}))});
  queue.set('image-1',{notes:'first'});
  const draining=queue.drain(); await tick();
  assert.equal(requests.length,1);
  queue.set('image-1',{notes:'latest'});
  queue.set('image-2',{notes:'different image'});
  requests[0].resolve({}); await tick();
  assert.equal(queue.get('image-1').savedRevision,1);
  assert.equal(queue.get('image-1').revision,2);
  assert.equal(requests[1].data.notes,'latest');
  requests[1].resolve({}); await tick();
  assert.equal(requests[2].id,'image-2');
  assert.equal(requests[2].data.notes,'different image');
  requests[2].resolve({}); await draining;
  assert.equal(queue.dirtyEntries().length,0); queue.dispose();

  // Validation failures retain the snapshot; correcting the field resumes saving.
  let attempts=0;
  const rejected=new Queue({delay:10000,send:async(id,data)=>{
    attempts++;
    if(data.measurement<0){const e=new Error('Invalid value');e.retryable=false;throw e;}
    return {saved:data};
  }});
  rejected.set('one',{measurement:-1});
  assert.equal(await rejected.flush(),false);
  assert.equal(rejected.dirtyEntries().length,1);
  assert.equal(attempts,1);
  rejected.set('one',{measurement:0.25});
  assert.equal(await rejected.flush(),true);
  assert.equal(attempts,2); rejected.dispose();

  // Transient failures retry automatically and remain unsaved until acknowledged.
  let retries=0;
  const retrying=new Queue({delay:0,retryDelay:5,send:async()=>{
    if(++retries===1){const e=new Error('Offline');e.retryable=true;throw e;}
    return {};
  }});
  retrying.set('one',{notes:'keep me'});
  await retrying.drain();
  assert.equal(retrying.dirtyEntries().length,1);
  await new Promise(resolve=>setTimeout(resolve,30));
  assert.equal(retries,2);assert.equal(retrying.dirtyEntries().length,0);retrying.dispose();

  // Flush (navigation) persists a debounced change immediately, including clearing values.
  let cleared;
  const clearing=new Queue({delay:60000,send:async(id,data)=>{cleared=data;return {};}});
  clearing.set('one',{lead_findings:{V2:{p_wave:''}},overall:{mi_localisation:[]}});
  assert.equal(await clearing.flush(),true);
  assert.equal(cleared.lead_findings.V2.p_wave,'');assert.equal(cleared.overall.mi_localisation.length,0);clearing.dispose();
  console.log('Autosave tests passed: ordered revisions, edits during requests, image isolation, failures, automatic retry, navigation flush, and clearing fields.');
})().catch(error=>{console.error(error);process.exitCode=1;});
