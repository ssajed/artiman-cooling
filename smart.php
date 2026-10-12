<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('index.php');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم هوشمند - آرتیمان</title>
    <link rel="stylesheet" href="static/css/style.css">
    <style>
        .smart-header { background: linear-gradient(135deg, #6c3483, #8e44ad) !important; }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
            margin: 15px 0;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        .checkbox-item:hover { border-color: #8e44ad; background: #f3e8ff; }
        .checkbox-item.checked { border-color: #6c3483; background: #e8d5f5; }
        .checkbox-item input[type="checkbox"] { width: 20px; height: 20px; accent-color: #6c3483; cursor: pointer; pointer-events: none; }
        .btn-smart { background: linear-gradient(135deg, #6c3483, #8e44ad) !important; color: white; border: none; padding: 12px 28px; border-radius: 30px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s; font-family: 'Vazirmatn', Tahoma, sans-serif; }
        .btn-smart:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(108, 52, 131, 0.3); }
        .selected-count { background: #6c3483; color: white; padding: 4px 14px; border-radius: 20px; font-size: 13px; display: inline-block; }
        .result-box { background: #1e293b; color: #ccfbf1; padding: 24px; border-radius: 16px; font-family: 'Courier New', monospace; font-size: 16px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; direction: ltr; text-align: left; margin: 20px 0; line-height: 2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="header smart-header">
                <h1>🤖 سیستم هوشمند برج خنک‌کننده</h1>
                <p>پیکربندی سیستم‌های کنترل هوشمند آرتیمان</p>
            </div>
            <div class="form-container">
                <div class="card">
                    <h2>📋 سیستم‌های هوشمند</h2>
                    <div class="checkbox-grid" id="smartSystems">
                        <?php
                        $systems = [
                            ['code' => 'SAF', 'name' => 'کنترل دور هوشمند فن محوری'],
                            ['code' => 'AFS', 'name' => 'راه انداز نرم فن محوری'],
                            ['code' => 'AFP', 'name' => 'قدرت فن محوری'],
                            ['code' => 'SEP', 'name' => 'کنترل دور هوشمند الکتروپمپ'],
                            ['code' => 'EPS', 'name' => 'راه انداز نرم الکتروپمپ'],
                            ['code' => 'EPP', 'name' => 'قدرت الکتروپمپ'],
                            ['code' => 'SFV', 'name' => 'کنترل هوشمند لرزش فن ماشینری'],
                            ['code' => 'CDC', 'name' => 'کنترل پکیج دوزینگ مواد شیمیایی'],
                            ['code' => 'FPC', 'name' => 'کنترل پکیج فیلتراسیون'],
                            ['code' => 'BAF', 'name' => 'ضد یخ زدگی حوضچه'],
                            ['code' => 'CWM', 'name' => 'کنترل و پایش آب در گردش']
                        ];
                        foreach ($systems as $s):
                        ?>
                        <div class="checkbox-item" onclick="toggleCheckbox(this)">
                            <input type="checkbox" value="<?= $s['code'] ?>">
                            <div>
                                <strong><?= $s['name'] ?></strong><br>
                                <span style="color:#6c3483;font-weight:bold;"><?= $s['code'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:15px;">
                        <span class="selected-count" id="selectedCount">۰ سیستم انتخاب شده</span>
                    </div>
                </div>

                <div class="form-row" style="margin-top:20px;">
                    <div class="form-group third">
                        <label>⚡ توان موتور (kW)</label>
                        <select id="motorPower">
                            <option value="">انتخاب کنید</option>
                            <?php foreach ([0.55, 0.75, 1.1, 1.5, 2.2, 3, 4, 5, 7.5, 9, 11, 15, 18.5, 22, 30] as $p): ?>
                            <option value="<?= $p ?>"><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group third">
                        <label>📦 نوع تابلو</label>
                        <select id="panelType">
                            <option value="">انتخاب کنید</option>
                            <option value="WEP">دیواری (WEP)</option>
                            <option value="FEP">ایستاده (FEP)</option>
                        </select>
                    </div>
                    <div class="form-group third">
                        <label>🔒 آی‌پی تابلو</label>
                        <select id="ipRating">
                            <option value="">انتخاب کنید</option>
                            <option value="42">IP42</option>
                            <option value="54">IP54</option>
                            <option value="55">IP55</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>📁 شماره پرونده</label>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <span style="font-weight:bold; color:#6c3483; font-size:18px;">S</span>
                        <input type="number" id="caseNumber" placeholder="مثال: 50" min="0" max="99999" style="flex:1; min-width:200px; padding:12px 16px; border:2px solid #e5e7eb; border-radius:12px; font-size:16px;">
                        <span style="font-size:12px; color:#666;">(عدد ۰ تا ۹۹۹۹۹ - به صورت ۵ رقمی)</span>
                    </div>
                </div>

                <div class="button-group">
                    <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; display:inline-block; padding:14px 30px; border-radius:30px; background:#6c757d; color:white;">↩️ بازگشت</a>
                    <button type="button" class="btn-smart" onclick="generateSmartCode()">🚀 تولید کد سیستم هوشمند</button>
                </div>

                <div id="smart-result" style="display:none; margin-top:30px;">
                    <h3 style="color:#6c3483;">✅ نتیجه پیکربندی</h3>
                    <div class="result-box" id="smart-output"></div>
                    <div class="button-group">
                        <button onclick="copySmartCode()" class="btn-smart">📋 کپی کد</button>
                        <button onclick="resetSmartForm()" class="btn-secondary" style="background:#6c757d; color:white; border:none; padding:14px 30px; border-radius:30px; cursor:pointer;">🔄 طراحی مجدد</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleCheckbox(element) {
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        element.classList.toggle('checked', checkbox.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('#smartSystems .checkbox-item input:checked');
        document.getElementById('selectedCount').textContent = checked.length + ' سیستم انتخاب شده';
    }

    function generateSmartCode() {
        const checked = document.querySelectorAll('#smartSystems .checkbox-item input:checked');
        if (checked.length === 0) { alert('لطفاً حداقل یک سیستم را انتخاب کنید'); return; }
        const systems = Array.from(checked).map(el => el.value).join('.');
        const motor = document.getElementById('motorPower').value;
        const panel = document.getElementById('panelType').value;
        const ip = document.getElementById('ipRating').value;
        const caseNum = document.getElementById('caseNumber').value.trim();

        if (!motor || !panel || !ip || !caseNum) {
            alert('لطفاً تمام فیلدها را تکمیل کنید');
            return;
        }
        const num = parseInt(caseNum);
        if (isNaN(num) || num < 0 || num > 99999) {
            alert('شماره پرونده باید بین ۰ تا ۹۹۹۹۹ باشد');
            return;
        }
        const caseFormatted = 'S' + String(num).padStart(5, '0');
        const output = `سیستم تابلو برق Artiman/${systems}/${motor} KW/${panel}/IP ${ip}/${caseFormatted}`;
        document.getElementById('smart-output').textContent = output;
        document.getElementById('smart-result').style.display = 'block';
        document.getElementById('smart-result').scrollIntoView({ behavior: 'smooth' });
    }

    function copySmartCode() {
        const text = document.getElementById('smart-output').textContent;
        navigator.clipboard.writeText(text).then(() => alert('✅ کد سیستم هوشمند کپی شد!'));
    }

    function resetSmartForm() {
        document.getElementById('smart-result').style.display = 'none';
        document.querySelectorAll('#smartSystems .checkbox-item').forEach(el => {
            el.classList.remove('checked');
            el.querySelector('input[type="checkbox"]').checked = false;
        });
        updateSelectedCount();
        document.getElementById('motorPower').value = '';
        document.getElementById('panelType').value = '';
        document.getElementById('ipRating').value = '';
        document.getElementById('caseNumber').value = '';
    }
    </script>
</body>
</html>