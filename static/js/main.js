// ====== تنظیم مسیر API به صورت پویا ======
if (typeof API_URL === 'undefined') {
    const API_URL = (() => {
        // اگر در مسیر subdirectory هستیم
        const path = window.location.pathname;
        if (path.includes('/artiman-cooling/')) {
            return '/artiman-cooling/api.php';
        }
        // اگر در ریشه هستیم
        return 'api.php';
    })();
}

let currentStep = 1;
const stepsLabels = ["نوع برج", "مدل برج", "متریال", "پکینگ/کویل", "پره فن", "موتور", "پمپ", "فن", "لوور/قطره‌گیر", "نازل/لوله/فلنج", "نردبان/اکسس", "ژل‌کوت/فیتینگ", "جانمایی", "نتیجه"];

function loadStep(step) {
    const formData = new FormData();
    formData.append('action', 'load_step');
    formData.append('step', step);

    fetch(API_URL, { method: 'POST', body: formData })
        .then(response => {
            if (!response.ok) {
                throw new Error('خطای HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.html) {
                document.getElementById('step-content').innerHTML = data.html;
                updateProgress(step);
                currentStep = step;
                initStepScripts();
            } else {
                throw new Error('پاسخ نامعتبر از سرور');
            }
        })
        .catch(error => {
            console.error('Error loading step:', error);
            document.getElementById('step-content').innerHTML = `
                <div class="card">
                    <h2>⚠️ خطا در ارتباط با سرور</h2>
                    <p style="color: #666; padding: 20px 0;">
                        خطا: ${error.message}<br>
                        <small>لطفاً اتصال اینترنت خود را بررسی کنید و دوباره تلاش کنید.</small>
                    </p>
                    <button onclick="loadStep(${currentStep})" style="background:#1a237e; color:white; border:none; padding:10px 25px; border-radius:8px; cursor:pointer;">
                        تلاش مجدد
                    </button>
                </div>
            `;
        });
}

function submitStep(formId, nextStep) {
    const form = document.getElementById(formId);
    if (!form) {
        console.error('فرم با ID ' + formId + ' پیدا نشد');
        return;
    }
    
    if (!form.checkValidity()) { 
        form.reportValidity(); 
        return; 
    }

    const formData = new FormData(form);
    formData.append('action', 'save_step');
    formData.append('step', currentStep);

    // نمایش وضعیت بارگذاری
    const submitBtn = form.querySelector('button[type="button"]:last-child');
    if (submitBtn) {
        submitBtn.textContent = '⏳ در حال ذخیره...';
        submitBtn.disabled = true;
    }

    fetch(API_URL, { method: 'POST', body: formData })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                loadStep(nextStep);
            } else {
                alert(data.message || 'خطا در ذخیره اطلاعات');
                if (submitBtn) {
                    submitBtn.textContent = 'مرحله بعدی';
                    submitBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error submitting step:', error);
            alert('خطا در ارسال اطلاعات: ' + error.message);
            if (submitBtn) {
                submitBtn.textContent = 'مرحله بعدی';
                submitBtn.disabled = false;
            }
        });
}

function updateProgress(step) {
    const stepsContainer = document.getElementById('progressSteps');
    const progressFill = document.getElementById('progressFill');
    if (!stepsContainer || !progressFill) return;

    stepsContainer.innerHTML = '';
    stepsLabels.forEach((label, index) => {
        const num = index + 1;
        const cls = num < step ? 'completed' : (num === step ? 'active' : '');
        stepsContainer.innerHTML += `
            <div class="progress-step ${cls}">
                <div class="step-circle">${num < step ? '✓' : num}</div>
                <div class="step-label">${label}</div>
            </div>
        `;
    });
    const progressPercent = ((step - 1) / (stepsLabels.length - 1) * 100);
    progressFill.style.width = Math.min(progressPercent, 100) + '%';
}

function initStepScripts() {
    // پکینگ
    const packingType = document.getElementById('packing_type');
    if (packingType) {
        packingType.addEventListener('change', loadHeights);
        if (packingType.value) loadHeights();
    }

    // فیلدهای کاهش دور
    const reductionType = document.getElementById('reduction_type');
    if (reductionType) {
        reductionType.addEventListener('change', toggleReductionFields);
        toggleReductionFields();
    }

    // گزینه‌های فن (برای مرحله ۸ جدید)
    const fanTypeSelect = document.getElementById('fan_selection_type');
    if (fanTypeSelect) {
        fanTypeSelect.addEventListener('change', toggleFanSelection);
        toggleFanSelection();
    }
}

function loadHeights() {
    const type = document.getElementById('packing_type')?.value;
    const heightSelect = document.getElementById('packing_height');
    if (!type || !heightSelect) return;
    
    heightSelect.innerHTML = '<option value="">در حال بارگذاری...</option>';

    const formData = new FormData();
    formData.append('action', 'get_packing_heights');
    formData.append('type', type);

    fetch(API_URL, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            heightSelect.innerHTML = '<option value="" disabled selected>انتخاب ارتفاع</option>';
            if (data.heights && data.heights.length > 0) {
                data.heights.forEach(h => {
                    heightSelect.innerHTML += `<option value="${h}">${h} mm</option>`;
                });
            } else {
                heightSelect.innerHTML += '<option value="">ارتفاع موجود نیست</option>';
            }
        })
        .catch(error => {
            console.error('Error loading heights:', error);
            heightSelect.innerHTML = '<option value="">خطا در بارگذاری</option>';
        });
}

function startNewDesign() {
    if (confirm('شروع طراحی جدید؟ اطلاعات قبلی پاک می‌شود.')) {
        fetch(API_URL, { 
            method: 'POST', 
            body: new URLSearchParams({ action: 'clear_session' }) 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('step-content').innerHTML = '';
                currentStep = 1;
                loadStep(1);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(error => {
            console.error('Error clearing session:', error);
            alert('خطا در پاک کردن اطلاعات');
        });
    }
}

function toggleReductionFields() {
    var reductionType = document.getElementById('reduction_type');
    if (!reductionType) return;
    
    var val = reductionType.value;
    var lowRpmGroup = document.getElementById('low_rpm_group');
    var beltGroup = document.getElementById('belt_group');
    var gearboxGroup = document.getElementById('gearbox_group');
    var ratioGroup = document.getElementById('ratio_group');
    
    if (lowRpmGroup) lowRpmGroup.style.display = 'none';
    if (beltGroup) beltGroup.style.display = 'none';
    if (gearboxGroup) gearboxGroup.style.display = 'none';
    if (ratioGroup) ratioGroup.style.display = 'none';
    
    if (val === 'low_rpm' && lowRpmGroup) {
        lowRpmGroup.style.display = 'block';
    } else if (val === 'belt' && beltGroup) {
        beltGroup.style.display = 'block';
        if (ratioGroup) ratioGroup.style.display = 'block';
    } else if (val === 'gearbox' && gearboxGroup) {
        gearboxGroup.style.display = 'block';
        if (ratioGroup) ratioGroup.style.display = 'block';
    }
}

function toggleFanSelection() {
    var defaultDetails = document.getElementById('default_fan_details');
    var customFields = document.getElementById('custom_fan_fields');
    var radios = document.querySelectorAll('input[name="fan_selection_type"]');
    
    if (!defaultDetails || !customFields) return;
    
    radios.forEach(function(radio) {
        if (radio.checked && radio.value === 'default') {
            defaultDetails.style.display = 'block';
            customFields.style.display = 'none';
        } else if (radio.checked && radio.value === 'custom') {
            defaultDetails.style.display = 'none';
            customFields.style.display = 'block';
        }
    });
}

function confirmDefaultFan() {
    var formData = new FormData();
    formData.append('action', 'save_step');
    formData.append('step', 8);
    formData.append('fan_selection_type', 'default');
    formData.append('essential_fan_code', 'F107');
    fetch(API_URL, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ فن پیش‌فرض با موفقیت ذخیره شد!');
                loadStep(9);
            }
        })
        .catch(error => {
            console.error('Error confirming default fan:', error);
            alert('خطا در ذخیره فن پیش‌فرض');
        });
}

function rejectDefaultFan() {
    var customRadio = document.querySelector('input[name="fan_selection_type"][value="custom"]');
    if (customRadio) {
        customRadio.checked = true;
        toggleFanSelection();
    }
}

function submitFanStep() {
    var selectedType = document.querySelector('input[name="fan_selection_type"]:checked');
    if (!selectedType) {
        alert('لطفاً یکی از گزینه‌ها را انتخاب کنید.');
        return;
    }
    
    var formData = new FormData(document.getElementById('form-step8'));
    formData.append('action', 'save_step');
    formData.append('step', 8);
    
    if (selectedType.value === 'default') {
        if (!confirm('آیا از مشخصات فن پیش‌فرض تأیید می‌کنید؟')) return;
        formData.append('essential_fan_code', 'F107');
    } else {
        var fanCode = document.getElementById('fan_code_select')?.value;
        if (!fanCode) {
            alert('لطفاً یک کد فن از لیست انتخاب کنید.');
            return;
        }
        formData.append('essential_fan_code', fanCode);
    }
    
    fetch(API_URL, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadStep(9);
            } else {
                alert(data.message || 'خطا در ذخیره کد فن');
            }
        })
        .catch(error => {
            console.error('Error submitting fan step:', error);
            alert('خطا در ارسال اطلاعات');
        });
}

document.addEventListener('DOMContentLoaded', function() {
    loadStep(1);
});


// ====== تابع کپی برای مرحله ۱۴ ======
function copyResult() {
    var btn = document.getElementById("copyBtn");
    var icon = document.getElementById("copy-icon");
    var textSpan = document.getElementById("copy-text");
    
    // گرفتن متن از data attribute
    var copyText = btn.getAttribute('data-copy-text') || '';
    
    if (!copyText) {
        alert('متنی برای کپی وجود ندارد!');
        return;
    }
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(copyText).then(function() {
            btn.classList.add("copied");
            icon.textContent = "✅";
            textSpan.textContent = "کپی شد!";
            setTimeout(function() {
                btn.classList.remove("copied");
                icon.textContent = "📋";
                textSpan.textContent = "کپی کردن اطلاعات";
            }, 2000);
        }).catch(function(err) {
            fallbackCopy(copyText, btn, icon, textSpan);
        });
    } else {
        fallbackCopy(copyText, btn, icon, textSpan);
    }
}

function fallbackCopy(text, btn, icon, textSpan) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand("copy");
        btn.classList.add("copied");
        icon.textContent = "✅";
        textSpan.textContent = "کپی شد!";
        setTimeout(function() {
            btn.classList.remove("copied");
            icon.textContent = "📋";
            textSpan.textContent = "کپی کردن اطلاعات";
        }, 2000);
    } catch (err) {
        alert("خطا در کپی کردن. لطفاً دستی کپی کنید.");
    }
    
    document.body.removeChild(textArea);
}