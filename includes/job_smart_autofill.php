<?php
/** @var array<string,mixed>|null $prescreenHandoffForJs */
$prescreenHandoffForJs = $prescreenHandoffForJs ?? null;
?>
<style>
.smart-autofill-card {
  border: 1px solid #dbeafe;
  border-radius: 18px;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
  padding: 20px 22px;
  margin-bottom: 24px;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.06);
}
.smart-autofill-pill {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.smart-autofill-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
.smart-autofill-queue { display: none; margin-top: 14px; }
.smart-autofill-queue.is-visible { display: block; }
.smart-autofill-queue-list { list-style: none; margin: 8px 0 0; padding: 0; display: grid; gap: 8px; }
.smart-autofill-queue-item {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;
}
.smart-autofill-remove {
  border: 0; background: transparent; color: #64748b; font-size: 18px; line-height: 1; cursor: pointer;
}
.smart-autofill-progress-panel {
  display: none; align-items: center; gap: 18px; margin-top: 14px;
  padding: 16px 18px; border: 1px solid #dbeafe; border-radius: 18px; background: rgba(255,255,255,.9);
}
.smart-autofill-progress-panel.active { display: flex; }
.smart-autofill-orb { position: relative; width: 92px; height: 92px; flex-shrink: 0; }
.smart-autofill-orb-ring {
  position: absolute; inset: 0; border-radius: 50%;
  background: conic-gradient(from 0deg, #2563eb, #60a5fa, #8b5cf6, #2563eb);
  animation: smartAutofillSpin 1.2s linear infinite;
}
.smart-autofill-orb-ring::after {
  content: ""; position: absolute; inset: 10px; border-radius: 50%; background: #f8fbff;
}
.smart-autofill-orb-core {
  position: absolute; inset: 18px; border-radius: 50%; background: #fff;
  display: flex; align-items: center; justify-content: center; text-align: center;
  padding: 8px; font-size: 11px; font-weight: 700; color: #1e3a8a;
}
.smart-autofill-progress-copy { flex: 1 1 auto; min-width: 0; }
.smart-autofill-stage-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.smart-autofill-stage-pill {
  display: inline-flex; align-items: center; gap: 8px; padding: 8px 11px;
  border-radius: 999px; border: 1px solid #dbeafe; background: #fff;
  color: #64748b; font-size: 12px; font-weight: 600;
}
.smart-autofill-stage-pill.is-active { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
.smart-autofill-stage-pill.is-done { border-color: #86efac; background: #f0fdf4; color: #166534; }
.smart-autofill-stage-pill.is-error { border-color: #fca5a5; background: #fef2f2; color: #991b1b; }
.smart-autofill-results { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.smart-autofill-results li {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 14px;
}
@keyframes smartAutofillSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="smart-autofill-card">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <span class="smart-autofill-pill">AI</span>
      <h6 class="fw-semibold mt-2 mb-2">Smart AI autofill</h6>
      <p class="text-muted small mb-0">Upload passport, CV, photo, certificates, or ID. AI extracts your details, attaches each file to the correct field, and can submit the application when enough information is found.</p>
    </div>
    <div class="text-lg-end">
      <div class="smart-autofill-actions">
        <button type="button" class="btn btn-outline-primary px-4" id="smartAutofillTrigger">Add documents</button>
        <button type="button" class="btn btn-primary px-4" id="smartAutofillStart" disabled>Start analysis</button>
      </div>
      <input type="file" id="smartAutofillInput" class="d-none" multiple accept=".pdf,.docx,.jpg,.jpeg,.png,.webp">
      <div id="smartAutofillHelp" class="form-text mt-2 text-start text-lg-end">
        Add your documents, then click Start analysis.<br>
        Supported: PDF, DOCX, JPG, JPEG, PNG, WEBP
      </div>
    </div>
  </div>

  <div id="smartAutofillStatus" class="alert d-none mt-3 mb-0" role="status" aria-live="polite"></div>
  <div id="smartAutofillQueueWrap" class="smart-autofill-queue">
    <div class="small fw-semibold text-body-secondary">Queued documents</div>
    <div id="smartAutofillQueueHint" class="form-text mt-1">No documents queued yet.</div>
    <ul id="smartAutofillQueue" class="smart-autofill-queue-list"></ul>
  </div>
  <div id="smartAutofillProgressWrap" class="smart-autofill-progress-panel">
    <div class="smart-autofill-orb">
      <div class="smart-autofill-orb-ring"></div>
      <div class="smart-autofill-orb-core" id="smartAutofillProgressText">Ready</div>
    </div>
    <div class="smart-autofill-progress-copy">
      <strong id="smartAutofillProgressLabel">Processing…</strong>
      <small id="smartAutofillProgressSubtext">AI is reading your documents.</small>
      <div id="smartAutofillStagePills" class="smart-autofill-stage-pills"></div>
    </div>
  </div>
  <div id="smartAutofillPanels" class="mt-3 d-none">
    <div class="small fw-semibold text-body-secondary mb-2">Document routing</div>
    <ul id="smartAutofillResults" class="smart-autofill-results"></ul>
    <div id="smartAutofillWarningsWrap" class="mt-3 d-none">
      <div class="small fw-semibold text-body-secondary mb-2">Warnings</div>
      <ul id="smartAutofillWarnings" class="smart-autofill-results"></ul>
    </div>
  </div>
</div>

<script>
(function () {
  const trigger = document.getElementById("smartAutofillTrigger");
  const startButton = document.getElementById("smartAutofillStart");
  const input = document.getElementById("smartAutofillInput");
  const statusEl = document.getElementById("smartAutofillStatus");
  const queueWrap = document.getElementById("smartAutofillQueueWrap");
  const queueHint = document.getElementById("smartAutofillQueueHint");
  const queueEl = document.getElementById("smartAutofillQueue");
  const progressWrap = document.getElementById("smartAutofillProgressWrap");
  const progressText = document.getElementById("smartAutofillProgressText");
  const progressLabel = document.getElementById("smartAutofillProgressLabel");
  const progressSubtext = document.getElementById("smartAutofillProgressSubtext");
  const stagePillsEl = document.getElementById("smartAutofillStagePills");
  const panelsEl = document.getElementById("smartAutofillPanels");
  const resultsEl = document.getElementById("smartAutofillResults");
  const warningsWrapEl = document.getElementById("smartAutofillWarningsWrap");
  const warningsEl = document.getElementById("smartAutofillWarnings");

  if (!trigger || !startButton || !input) return;

  const prescreenHandoff = <?= json_encode($prescreenHandoffForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

  const stageMeta = [
    { id: "queue", label: "Queue", short: "Queue" },
    { id: "batch", label: "AI analysis", short: "AI" },
    { id: "route", label: "Attach files", short: "Files" },
    { id: "submit", label: "Submit", short: "Send" }
  ];

  const pendingFiles = [];
  let isProcessing = false;

  function fileKey(file) {
    return [file.name, file.size, file.lastModified].join("::");
  }

  function setStatus(kind, message) {
    statusEl.className = "alert mt-3 mb-0";
    statusEl.classList.add(kind === "success" ? "alert-success" : kind === "warning" ? "alert-warning" : kind === "danger" ? "alert-danger" : "alert-info");
    statusEl.textContent = message;
    statusEl.classList.remove("d-none");
  }

  function renderStagePills(activeId, kind) {
    stagePillsEl.innerHTML = "";
    const activeIndex = stageMeta.findIndex(s => s.id === activeId);
    stageMeta.forEach((stage, index) => {
      const pill = document.createElement("span");
      pill.className = "smart-autofill-stage-pill";
      pill.textContent = stage.label;
      if (activeIndex > -1 && index < activeIndex) pill.classList.add("is-done");
      if (stage.id === activeId) pill.classList.add(kind === "danger" ? "is-error" : "is-active");
      stagePillsEl.appendChild(pill);
    });
  }

  function setStage(stageId, message, kind, subtext) {
    progressWrap.className = "smart-autofill-progress-panel active";
    const stage = stageMeta.find(s => s.id === stageId);
    progressText.textContent = stage?.short || "AI";
    progressLabel.textContent = message;
    progressSubtext.textContent = subtext || "";
    renderStagePills(stageId, kind || "info");
    setStatus(kind || "info", message);
  }

  function renderQueue() {
    queueWrap.classList.add("is-visible");
    queueEl.innerHTML = "";
    if (!pendingFiles.length) {
      queueHint.textContent = "No documents queued yet.";
      startButton.disabled = isProcessing;
      return;
    }
    queueHint.textContent = `${pendingFiles.length} document(s) ready.`;
    pendingFiles.forEach(file => {
      const li = document.createElement("li");
      li.className = "smart-autofill-queue-item";
      const name = document.createElement("span");
      name.textContent = file.name;
      const remove = document.createElement("button");
      remove.type = "button";
      remove.className = "smart-autofill-remove";
      remove.textContent = "×";
      remove.disabled = isProcessing;
      remove.dataset.fileKey = fileKey(file);
      remove.addEventListener("click", () => {
        const idx = pendingFiles.findIndex(f => fileKey(f) === remove.dataset.fileKey);
        if (idx >= 0) pendingFiles.splice(idx, 1);
        renderQueue();
      });
      li.appendChild(name);
      li.appendChild(remove);
      queueEl.appendChild(li);
    });
    startButton.disabled = isProcessing;
  }

  window.applyAutofillFields = function (fields) {
    if (!fields) return;
    const setVal = (id, val) => {
      if (val == null || String(val).trim() === "") return;
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === "SELECT" && window.jQuery) {
        $(el).val(String(val)).trigger("change");
      } else {
        el.value = val;
        el.dispatchEvent(new Event("change", { bubbles: true }));
      }
    };
    setVal("first_name", fields.first_name);
    setVal("last_name", fields.last_name);
    setVal("email", fields.email);
    setVal("work_country_id", fields.work_country_id);
    setVal("address_country_id", fields.address_country_id);
    setVal("province_state", fields.province_state);
    setVal("district", fields.district);
    setVal("sector", fields.sector);
    setVal("cell_ward", fields.cell_ward);
    setVal("village", fields.village);
    setVal("emergency_full_name", fields.emergency_full_name);
    setVal("emergency_relationship", fields.emergency_relationship);
    setVal("emergency_email", fields.emergency_email);

    if (fields.phone_area_code && fields.phone_number && state.phoneInput) {
      try {
        state.phoneInput.setNumber("+" + String(fields.phone_area_code).replace(/\D/g, "") + String(fields.phone_number));
      } catch (e) { /* ignore */ }
    }
    if (fields.emergency_area_code && fields.emergency_phone_number && state.emergencyPhoneInput) {
      try {
        state.emergencyPhoneInput.setNumber("+" + String(fields.emergency_area_code).replace(/\D/g, "") + String(fields.emergency_phone_number));
      } catch (e) { /* ignore */ }
    }
  };

  function uploadSingleFile(fieldId, file) {
    return new Promise((resolve, reject) => {
      const formData = new FormData();
      formData.append("file", file);
      formData.append("field", fieldId);
      const xhr = new XMLHttpRequest();
      xhr.open("POST", CONFIG.endpoints.upload, true);
      xhr.onload = function () {
        try {
          const res = JSON.parse(xhr.responseText);
          if (res.status === "success" && res.path) {
            state.uploadedFiles[fieldId] = res.path;
            const inputEl = document.getElementById(fieldId);
            if (inputEl) {
              createFilePreview(file, fieldId);
              inputEl.removeAttribute("required");
            }
            resolve(res);
          } else {
            reject(new Error(res.error || "Upload failed"));
          }
        } catch (err) {
          reject(err);
        }
      };
      xhr.onerror = () => reject(new Error("Upload failed"));
      xhr.send(formData);
    });
  }

  function buildUploadQueue(documents, files) {
    const queue = [];
    const warnings = [];
    const usedFields = new Set();
    (documents || []).forEach(doc => {
      const field = doc.field || doc.job_field || "";
      if (!field || usedFields.has(field)) {
        if (field && usedFields.has(field)) {
          warnings.push(`Skipped ${doc.original_name} — already attached ${field}.`);
        }
        return;
      }
      const file = files[Number(doc.client_index)];
      if (!file) return;
      usedFields.add(field);
      queue.push({ ...doc, field, file });
    });
    return { queue, warnings };
  }

  function hasCoreApplicantInfo(fields) {
    const v = fields || {};
    return [v.first_name, v.last_name, v.email].every(x => String(x || "").trim() !== "");
  }

  trigger.addEventListener("click", () => input.click());
  input.addEventListener("change", () => {
    const files = Array.from(input.files || []);
    input.value = "";
    files.forEach(f => {
      if (!pendingFiles.some(p => fileKey(p) === fileKey(f))) pendingFiles.push(f);
    });
    renderQueue();
    setStatus("info", "Documents queued. Click Start analysis when ready.");
  });

  async function runSmartAutofill() {
    if (!pendingFiles.length || isProcessing) return;
    isProcessing = true;
    renderQueue();

    try {
      setStage("batch", "Analyzing documents with AI…", "info", "Extracting name, contact, address, and work preferences.");
      const files = [...pendingFiles];
      const formData = new FormData();
      files.forEach(f => formData.append("documents[]", f));

      const analysisResponse = await fetch(CONFIG.endpoints.autofill, { method: "POST", body: formData });
      const analysisText = await analysisResponse.text();
      let analysisData = null;
      try {
        analysisData = analysisText ? JSON.parse(analysisText) : null;
      } catch (err) {
        throw new Error(analysisText ? analysisText.slice(0, 280) : "AI analysis failed");
      }
      if (!analysisResponse.ok || !analysisData || analysisData.status !== "success") {
        throw new Error(analysisData?.message || "AI analysis failed");
      }

      if (analysisData.fields) window.applyAutofillFields(analysisData.fields);

      const { queue, warnings } = buildUploadQueue(analysisData.documents, files);
      const allWarnings = [...(analysisData.warnings || []), ...warnings];

      setStage("route", "Attaching documents…", "info", "");
      for (const item of queue) {
        try {
          await uploadSingleFile(item.field, item.file);
        } catch (err) {
          allWarnings.push(`Could not attach ${item.original_name}: ${err.message}`);
        }
      }

      if (resultsEl) {
        resultsEl.innerHTML = "";
        (analysisData.documents || []).forEach(doc => {
          const li = document.createElement("li");
          li.innerHTML = `<strong>${doc.original_name}</strong><small class="d-block">${doc.field_label || doc.field || "Unmatched"}</small>`;
          resultsEl.appendChild(li);
        });
        panelsEl.classList.remove("d-none");
      }

      if (allWarnings.length && warningsEl) {
        warningsEl.innerHTML = allWarnings.map(w => `<li>${w}</li>`).join("");
        warningsWrapEl.classList.remove("d-none");
      }

      pendingFiles.length = 0;
      renderQueue();

      setStage("submit", "Submitting application…", "info", "");
      const submitted = await submitForm({ lenient: true });
      if (submitted === true) {
        setStage("submit", "Application submitted successfully.", "success", "");
      }
    } catch (err) {
      setStage("batch", err.message || "Analysis failed", "danger");
    } finally {
      isProcessing = false;
      renderQueue();
    }
  }

  startButton.addEventListener("click", runSmartAutofill);

  async function loadPrescreenHandoff() {
    if (!prescreenHandoff) return;

    if (prescreenHandoff.prefill) {
      window.applyAutofillFields(prescreenHandoff.prefill);
    }

    const docList = Array.isArray(prescreenHandoff.docs) ? prescreenHandoff.docs : [];
    if (!docList.length) {
      if (prescreenHandoff.auto_run) {
        setStatus("warning", "No pre-screening documents were found to analyze. Use Add documents or upload on the form.");
      }
      return;
    }

    setStatus("info", "Loading pre-screening documents for Smart AI…");
    queueWrap.classList.add("is-visible");

    let loaded = 0;
    for (const doc of docList) {
      if (!doc || !doc.url) continue;
      try {
        const res = await fetch(doc.url, { credentials: "same-origin", cache: "no-store" });
        if (!res.ok) {
          console.warn("Prescreen doc HTTP", doc.key, res.status);
          continue;
        }
        const blob = await res.blob();
        const name = doc.filename || (doc.key ? doc.key + ".pdf" : "document");
        const type = blob.type && blob.type !== "application/octet-stream"
          ? blob.type
          : (name.toLowerCase().endsWith(".pdf") ? "application/pdf" : "application/octet-stream");
        pendingFiles.push(new File([blob], name, { type }));
        loaded++;
      } catch (e) {
        console.warn("Prescreen doc failed", doc.key, e);
      }
    }

    renderQueue();

    if (!loaded) {
      setStatus("warning", "Could not load pre-screening files. Use Add documents to upload them manually.");
      return;
    }

    setStatus("info", `${loaded} document(s) from pre-screening queued. ${prescreenHandoff.auto_run ? "Starting AI analysis…" : "Click Start analysis when ready."}`);

    if (prescreenHandoff.auto_run) {
      setTimeout(() => runSmartAutofill(), 600);
    }
  }

  renderQueue();
  if (prescreenHandoff) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => setTimeout(loadPrescreenHandoff, 400));
    } else {
      setTimeout(loadPrescreenHandoff, 400);
    }
  }
})();
</script>
