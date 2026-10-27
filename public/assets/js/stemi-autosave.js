/* Serial, revision-aware saving: an older response never acknowledges a newer edit. */
(() => {
  'use strict';
  class StemiAutosaveQueue {
    constructor({send, onChange = () => {}, onSaved = () => {}, delay = 400, retryDelay = 2000}) {
      Object.assign(this, {send, onChange, onSaved, delay, retryDelay});
      this.entries = new Map(); this.timer = null; this.flight = null; this.savingId = null;
    }
    set(id, data, immediate = false) {
      id = String(id);
      const snapshot = JSON.parse(JSON.stringify(data));
      const old = this.entries.get(id);
      if (old && JSON.stringify(old.data) === JSON.stringify(snapshot)) return;
      this.entries.set(id, {data:snapshot, revision:(old?.revision || 0)+1, savedRevision:old?.savedRevision || 0,
        due:Date.now()+(immediate?0:this.delay), error:null, failures:0});
      this.onChange(); this.schedule();
    }
    get(id) { return this.entries.get(String(id)); }
    dirtyEntries() { return [...this.entries.entries()].filter(([,e]) => e.revision > e.savedRevision); }
    schedule() {
      clearTimeout(this.timer); this.timer = null;
      if (this.flight) return;
      const pending = this.dirtyEntries().filter(([,e]) => !e.error || e.error.retryable);
      if (!pending.length) return;
      const due = Math.min(...pending.map(([,e]) => e.due));
      this.timer = setTimeout(() => { this.timer=null; this.drain(); }, Math.max(0,due-Date.now()));
    }
    async drain() {
      if (this.flight) return this.flight;
      this.flight = Promise.resolve().then(async () => {
        while (true) {
          const candidate = this.dirtyEntries().find(([,e]) => (!e.error || e.error.retryable) && e.due <= Date.now());
          if (!candidate) break;
          const [id,entry] = candidate, revision=entry.revision, snapshot=JSON.parse(JSON.stringify(entry.data));
          this.savingId=id; this.onChange();
          try {
            const result=await this.send(id,snapshot);
            const latest=this.entries.get(id);
            latest.savedRevision=Math.max(latest.savedRevision,revision);
            if(latest.revision===revision) {latest.error=null;latest.failures=0;}
            this.onSaved(id,result,snapshot);
          } catch(error) {
            const latest=this.entries.get(id);
            // A rejected older snapshot must not block a newer, corrected edit.
            if(latest.revision===revision) {
              latest.error=error; latest.failures++;
              latest.due=Date.now()+Math.min(this.retryDelay*2**(latest.failures-1),30000);
            }
          }
          this.savingId=null; this.onChange();
        }
      });
      try { await this.flight; }
      finally { this.flight=null; this.savingId=null; this.onChange(); this.schedule(); }
    }
    async flush() {
      clearTimeout(this.timer); this.timer=null;
      for(const [,entry] of this.dirtyEntries()) {entry.due=0;entry.error=null;}
      if(this.flight) await this.flight;
      await this.drain();
      return this.dirtyEntries().length===0;
    }
    dispose() { clearTimeout(this.timer); }
  }
  globalThis.StemiAutosaveQueue=StemiAutosaveQueue;
})();
