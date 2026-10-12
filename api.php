<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً وارد شوید']);
    exit;
}

$action = $_POST['action'] ?? '';
$data = loadData();

// ====== LOAD STEP ======
if ($action == 'load_step') {
    $step = (int)($_POST['step'] ?? 1);
    $html = '';

    // ====== STEP 1: TYPE ======
    if ($step == 1) {
        $open_checked = ($_SESSION['circuit_type'] ?? '') == 'open' ? 'checked' : '';
        $closed_checked = ($_SESSION['circuit_type'] ?? '') == 'closed' ? 'checked' : '';
        $html = '
        <div class="card">
            <h2>مرحله ۱: نوع برج خنک‌کننده</h2>
            <form id="form-step1">
                <div class="form-row radio-group">
                    <div class="form-group half">
                        <label><input type="radio" name="circuit_type" value="open" ' . $open_checked . ' required> مدار باز (Open Circuit)</label>
                    </div>
                    <div class="form-group half">
                        <label><input type="radio" name="circuit_type" value="closed" ' . $closed_checked . ' required> مدار بسته (Closed Circuit)</label>
                    </div>
                </div>
                <div class="button-group">
                    <button type="button" onclick="submitStep(\'form-step1\', 2)">مرحله بعدی</button>
                </div>
            </form>
        </div>';
    }

    // ====== STEP 2: MODEL ======
    elseif ($step == 2) {
        $circuit = $_SESSION['circuit_type'] ?? 'open';
        $key = $circuit == 'open' ? 'open' : 'closed';
        $models = $data['models'][$key] ?? [];
        $options = '';
        $selected_model = $_SESSION['model_full'] ?? '';
        foreach ($models as $m) {
            $sel = ($m == $selected_model) ? 'selected' : '';
            $options .= "<option value=\"$m\" $sel>$m</option>";
        }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۲: مدل برج</h2>
            <form id=\"form-step2\">
                <div class=\"form-group\">
                    <label>مدل:</label>
                    <select name=\"model_full\" required>
                        <option value=\"\" disabled selected>انتخاب کنید</option>
                        $options
                    </select>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(1)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step2', 3)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 3: MATERIAL ======
    elseif ($step == 3) {
        $materials = $data['materials'] ?? [];
        $resins = array_unique(array_column($materials, 'resin')); sort($resins);
        $bolts = array_unique(array_column($materials, 'bolt_body')); sort($bolts);
        $packings = array_unique(array_column($materials, 'chassis_packing_drop')); sort($packings);
        $fan_chassis = array_unique(array_column($materials, 'chassis_fan')); sort($fan_chassis);

        $selected_resin = $_SESSION['resin'] ?? '';
        $selected_bolt = $_SESSION['bolt_body'] ?? '';
        $selected_packing = $_SESSION['chassis_packing_drop'] ?? '';
        $selected_fan = $_SESSION['chassis_fan'] ?? '';

        $resin_opts = ''; foreach ($resins as $r) { $sel = ($r == $selected_resin) ? 'selected' : ''; $resin_opts .= "<option value=\"$r\" $sel>$r</option>"; }
        $bolt_opts = ''; foreach ($bolts as $b) { $sel = ($b == $selected_bolt) ? 'selected' : ''; $bolt_opts .= "<option value=\"$b\" $sel>$b</option>"; }
        $packing_opts = ''; foreach ($packings as $p) { $sel = ($p == $selected_packing) ? 'selected' : ''; $packing_opts .= "<option value=\"$p\" $sel>$p</option>"; }
        $fan_opts = ''; foreach ($fan_chassis as $f) { $sel = ($f == $selected_fan) ? 'selected' : ''; $fan_opts .= "<option value=\"$f\" $sel>$f</option>"; }

        $html = "
        <div class=\"card\">
            <h2>مرحله ۳: درجه کیفی متریال</h2>
            <form id=\"form-step3\">
                <div class=\"form-row\">
                    <div class=\"form-group half\"><label>رزین بدنه:</label><select name=\"resin\" required><option value=\"\" disabled selected>انتخاب کنید</option>$resin_opts</select></div>
                    <div class=\"form-group half\"><label>پیچ و مهره بدنه:</label><select name=\"bolt_body\" required><option value=\"\" disabled selected>انتخاب کنید</option>$bolt_opts</select></div>
                </div>
                <div class=\"form-row\">
                    <div class=\"form-group half\"><label>شاسی پکینگ و قطره‌گیر:</label><select name=\"chassis_packing_drop\" required><option value=\"\" disabled selected>انتخاب کنید</option>$packing_opts</select></div>
                    <div class=\"form-group half\"><label>شاسی فن و ماشینی:</label><select name=\"chassis_fan\" required><option value=\"\" disabled selected>انتخاب کنید</option>$fan_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(2)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step3', 4)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 4: PACKING / COIL ======
    elseif ($step == 4) {
        $circuit = $_SESSION['circuit_type'] ?? 'open';
        if ($circuit == 'open') {
            $packing_types = $data['packing_types'] ?? [];
            $options = '';
            $selected_packing = $_SESSION['packing_type'] ?? '';
            foreach ($packing_types as $k => $v) {
                $sel = ($k == $selected_packing) ? 'selected' : '';
                $options .= "<option value=\"$k\" $sel>" . ($v['name'] ?? $k) . "</option>";
            }
            $html = "
            <div class=\"card\">
                <h2>مرحله ۴: پکینگ (مدار باز)</h2>
                <form id=\"form-step4\">
                    <div class=\"form-row\">
                        <div class=\"form-group half\"><label>نوع پکینگ:</label><select name=\"packing_type\" id=\"packing_type\" required><option value=\"\" disabled selected>انتخاب کنید</option>$options</select></div>
                        <div class=\"form-group half\"><label>ارتفاع پکینگ:</label><select name=\"packing_height\" id=\"packing_height\" required><option value=\"\">ابتدا نوع پکینگ را انتخاب کنید</option></select></div>
                    </div>
                    <div class=\"button-group\">
                        <button type=\"button\" onclick=\"loadStep(3)\">بازگشت به مرحله قبل</button>
                        <button type=\"button\" onclick=\"submitStep('form-step4', 5)\">مرحله بعدی</button>
                    </div>
                </form>
            </div>";
        } else {
            $coil_options = $data['coil_options'] ?? [];
            $model = $_SESSION['model'] ?? '';
            $passes = $data['coil_passes']["CCS-$model"] ?? ['1', '2'];
            $coil_opts = ''; $selected_coil = $_SESSION['coil_code'] ?? '';
            foreach ($coil_options as $k => $v) { $sel = ($k == $selected_coil) ? 'selected' : ''; $coil_opts .= "<option value=\"$k\" $sel>$k - " . ($v['desc'] ?? '') . "</option>"; }
            $pass_opts = ''; $selected_pass = $_SESSION['pass_count'] ?? '';
            foreach ($passes as $p) { $sel = ($p == $selected_pass) ? 'selected' : ''; $pass_opts .= "<option value=\"$p\" $sel>$p</option>"; }
            $selected_rows = $_SESSION['rows'] ?? '2';
            $html = "
            <div class=\"card\">
                <h2>مرحله ۴: کویل (مدار بسته)</h2>
                <form id=\"form-step4\">
                    <div class=\"form-row\">
                        <div class=\"form-group third\"><label>نوع کویل:</label><select name=\"coil_code\" required><option value=\"\" disabled selected>انتخاب کنید</option>$coil_opts</select></div>
                        <div class=\"form-group third\"><label>تعداد ردیف:</label><select name=\"rows\" required>
                            <option value=\"2\" " . ($selected_rows == '2' ? 'selected' : '') . ">2</option>
                            <option value=\"3\" " . ($selected_rows == '3' ? 'selected' : '') . ">3</option>
                            <option value=\"4\" " . ($selected_rows == '4' ? 'selected' : '') . ">4</option>
                            <option value=\"5\" " . ($selected_rows == '5' ? 'selected' : '') . ">5</option>
                            <option value=\"6\" " . ($selected_rows == '6' ? 'selected' : '') . ">6</option>
                        </select></div>
                        <div class=\"form-group third\"><label>تعداد پاس:</label><select name=\"pass_count\" required><option value=\"\" disabled selected>انتخاب کنید</option>$pass_opts</select></div>
                    </div>
                    <div class=\"button-group\">
                        <button type=\"button\" onclick=\"loadStep(3)\">بازگشت به مرحله قبل</button>
                        <button type=\"button\" onclick=\"submitStep('form-step4', 5)\">مرحله بعدی</button>
                    </div>
                </form>
            </div>";
        }
    }

    // ====== STEP 5: FAN BLADES ======
    elseif ($step == 5) {
        $fan_type = $_SESSION['fan_type'] ?? 'FRP';
        $blades = $data['fan_blades'][$fan_type] ?? ['4', '6', '8'];
        $options = ''; $selected_blade = $_SESSION['fan_blades'] ?? '';
        foreach ($blades as $b) { $sel = ($b == $selected_blade) ? 'selected' : ''; $options .= "<option value=\"$b\" $sel>$b پره</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۵: تعداد پره فن</h2>
            <form id=\"form-step5\">
                <div class=\"form-group\"><label>تعداد پره:</label><select name=\"fan_blades\" required><option value=\"\" disabled selected>انتخاب کنید</option>$options</select></div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(4)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step5', 6)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 6: MOTOR & REDUCTION ======
    elseif ($step == 6) {
        $powers = $data['motor_powers'] ?? []; $brands = $data['motor_brands'] ?? [];
        $belt_types = $data['belt_types'] ?? []; $gearbox_brands = $data['gearbox_brands'] ?? [];
        $selected_brand = $_SESSION['motor_brand'] ?? ''; $selected_power = $_SESSION['motor_power'] ?? '';
        $selected_reduction = $_SESSION['reduction_type'] ?? ''; $selected_low_rpm = $_SESSION['low_rpm_type'] ?? '';
        $selected_belt = $_SESSION['belt_type'] ?? ''; $selected_gearbox = $_SESSION['gearbox_brand'] ?? ''; $selected_ratio = $_SESSION['ratio'] ?? '';

        $power_opts = ''; foreach ($powers as $p) { $sel = ($p == $selected_power) ? 'selected' : ''; $power_opts .= "<option value=\"$p\" $sel>$p kW</option>"; }
        $brand_opts = ''; foreach ($brands as $k => $v) { $sel = ($k == $selected_brand) ? 'selected' : ''; $brand_opts .= "<option value=\"$k\" $sel>" . ($v['name'] ?? $k) . "</option>"; }
        $belt_opts = ''; foreach ($belt_types as $k => $v) { $sel = ($k == $selected_belt) ? 'selected' : ''; $belt_opts .= "<option value=\"$k\" $sel>" . ($v['name'] ?? $k) . "</option>"; }
        $gearbox_opts = ''; foreach ($gearbox_brands as $k => $v) { $sel = ($k == $selected_gearbox) ? 'selected' : ''; $gearbox_opts .= "<option value=\"$k\" $sel>" . ($v['name'] ?? $k) . "</option>"; }

        $display_low = ($selected_reduction == 'low_rpm') ? 'block' : 'none';
        $display_belt = ($selected_reduction == 'belt') ? 'block' : 'none';
        $display_gearbox = ($selected_reduction == 'gearbox') ? 'block' : 'none';
        $display_ratio = ($selected_reduction == 'belt' || $selected_reduction == 'gearbox') ? 'block' : 'none';

        $html = '
        <div class="card">
            <h2>مرحله ۶: مشخصات الکتروموتور</h2>
            <form id="form-step6">
                <div class="form-row">
                    <div class="form-group third"><label>برند الکتروموتور:</label><select name="motor_brand" required><option value="" disabled selected>انتخاب کنید</option>' . $brand_opts . '</select></div>
                    <div class="form-group third"><label>توان موتور (kW):</label><select name="motor_power" required><option value="" disabled selected>انتخاب کنید</option>' . $power_opts . '</select></div>
                    <div class="form-group third"><label>سیستم کاهش دور:</label><select name="reduction_type" id="reduction_type" required onchange="toggleReductionFields()">
                        <option value="" disabled selected>انتخاب کنید</option>
                        <option value="low_rpm" ' . ($selected_reduction == 'low_rpm' ? 'selected' : '') . '>موتور دور پایین</option>
                        <option value="belt" ' . ($selected_reduction == 'belt' ? 'selected' : '') . '>تسمه پولی</option>
                        <option value="gearbox" ' . ($selected_reduction == 'gearbox' ? 'selected' : '') . '>گیربکس</option>
                    </select></div>
                </div>
                <div class="form-row">
                    <div class="form-group half" id="low_rpm_group" style="display:' . $display_low . ';"><label>نوع موتور دور پایین:</label><select name="low_rpm_type">
                        <option value="M4" ' . ($selected_low_rpm == 'M4' ? 'selected' : '') . '>M4 (1500 rpm)</option>
                        <option value="M6" ' . ($selected_low_rpm == 'M6' ? 'selected' : '') . '>M6 (1000 rpm)</option>
                        <option value="M8" ' . ($selected_low_rpm == 'M8' ? 'selected' : '') . '>M8 (750 rpm)</option>
                    </select></div>
                    <div class="form-group half" id="belt_group" style="display:' . $display_belt . ';"><label>نوع تسمه پولی:</label><select name="belt_type"><option value="" disabled selected>انتخاب کنید</option>' . $belt_opts . '</select></div>
                    <div class="form-group half" id="gearbox_group" style="display:' . $display_gearbox . ';"><label>برند گیربکس:</label><select name="gearbox_brand"><option value="" disabled selected>انتخاب کنید</option>' . $gearbox_opts . '</select></div>
                    <div class="form-group half" id="ratio_group" style="display:' . $display_ratio . ';"><label>نسبت کاهش دور:</label><input type="text" id="ratio_input" name="ratio" value="' . $selected_ratio . '" placeholder="مثال: 2.5"></div>
                </div>
                <div class="button-group">
                    <button type="button" onclick="loadStep(5)">بازگشت به مرحله قبل</button>
                    <button type="button" onclick="submitStep(\'form-step6\', 7)">مرحله بعدی</button>
                </div>
            </form>
        </div>
        <script>
        function toggleReductionFields() {
            var val = document.getElementById("reduction_type").value;
            var lowRpmGroup = document.getElementById("low_rpm_group");
            var beltGroup = document.getElementById("belt_group");
            var gearboxGroup = document.getElementById("gearbox_group");
            var ratioGroup = document.getElementById("ratio_group");
            if (lowRpmGroup) lowRpmGroup.style.display = "none";
            if (beltGroup) beltGroup.style.display = "none";
            if (gearboxGroup) gearboxGroup.style.display = "none";
            if (ratioGroup) ratioGroup.style.display = "none";
            if (val === "low_rpm" && lowRpmGroup) lowRpmGroup.style.display = "block";
            else if (val === "belt" && beltGroup) { beltGroup.style.display = "block"; if (ratioGroup) ratioGroup.style.display = "block"; }
            else if (val === "gearbox" && gearboxGroup) { gearboxGroup.style.display = "block"; if (ratioGroup) ratioGroup.style.display = "block"; }
        }
        </script>';
    }

    // ====== STEP 7: PUMP ======
    elseif ($step == 7) {
        $circuit = $_SESSION['circuit_type'] ?? 'open';
        if ($circuit == 'open') {
            $html = '<div class="card"><h2>مرحله ۷: پمپ سیرکولاسیون</h2><p style="text-align:center; padding:30px; color:#666;">⚠️ این مرحله <strong>فقط مخصوص مدار بسته</strong> است.<br>در مدار باز نیازی به پمپ سیرکولاسیون ندارید.</p><div class="button-group"><button type="button" onclick="loadStep(6)">بازگشت به مرحله قبل</button><button type="button" onclick="loadStep(8)" class="btn-primary">رد شدن از این مرحله</button></div></div>';
        } else {
            $powers = $data['motor_powers'] ?? []; $power_opts = ''; $selected_pump_power = $_SESSION['pump_power'] ?? '';
            foreach ($powers as $p) { $sel = ($p == $selected_pump_power) ? 'selected' : ''; $power_opts .= "<option value=\"$p\" $sel>$p kW</option>"; }
            $selected_pump_count = $_SESSION['pump_count'] ?? '1';
            $html = "
            <div class=\"card\">
                <h2>مرحله ۷: پمپ سیرکولاسیون (مدار بسته)</h2>
                <form id=\"form-step7\">
                    <div class=\"form-row\">
                        <div class=\"form-group half\"><label>تعداد پمپ:</label><select name=\"pump_count\" required>
                            <option value=\"1\" " . ($selected_pump_count == '1' ? 'selected' : '') . ">۱ پمپ</option>
                            <option value=\"2\" " . ($selected_pump_count == '2' ? 'selected' : '') . ">۲ پمپ</option>
                        </select></div>
                        <div class=\"form-group half\"><label>توان هر پمپ (kW):</label><select name=\"pump_power\" required><option value=\"\" disabled selected>انتخاب کنید</option>$power_opts</select></div>
                    </div>
                    <div class=\"button-group\">
                        <button type=\"button\" onclick=\"loadStep(6)\">بازگشت به مرحله قبل</button>
                        <button type=\"button\" onclick=\"submitStep('form-step7', 8)\">مرحله بعدی</button>
                    </div>
                </form>
            </div>";
        }
    }

    // ====== STEP 8: FAN CODE ======
    elseif ($step == 8) {
        $fan_type = $_SESSION['fan_type'] ?? 'FRP';
        $model = $_SESSION['model'] ?? '';
        $fan_selection_type = $_SESSION['fan_selection_type'] ?? 'default';
        $default_code = ($model == '15') ? 'F201' : 'F107';
        
        $default_fan_info = [
            'F107' => ['resin' => 'پلی استر اورتوفتالیک', 'profile' => 'پروفیل 5', 'hab' => 'HDG', 'clamp' => 'AL', 'nafi' => 'CI', 'bolt' => 'SS304', 'chassis_motor' => 'HDG'],
            'F201' => ['resin' => 'پلی آمید', 'profile' => 'فن پلی آمید پروفیل W', 'hab' => 'AL', 'clamp' => 'AL', 'nafi' => 'AL', 'bolt' => 'HDG', 'chassis_motor' => 'HDG']
        ];
        $default_info = $default_fan_info[$default_code] ?? $default_fan_info['F107'];
        
        $fan_table = $data['fan_table'] ?? [];
        $chassis_fan = $_SESSION['chassis_fan'] ?? 'HDG';
        $filtered_fans = [];
        foreach ($fan_table as $f) {
            if ($fan_type == 'PA' && strpos($f['resin'], 'پلی آمید') === false) continue;
            if ($fan_type == 'FRP') {
                if (strpos($f['profile'], 'پروفیل 5') === false && strpos($f['profile'], 'پروفیل 1 شنک فلزی') === false) continue;
                if (strpos($f['resin'], 'ایزوفتالیک') === false && strpos($f['resin'], 'اورتوفتالیک') === false) continue;
            }
            if ($f['chassis_motor'] != $chassis_fan) continue;
            $filtered_fans[] = $f;
        }
        
        $fan_options = '';
        $selected_fan_code = ($fan_selection_type == 'custom') ? ($_SESSION['essential_fan_code'] ?? '') : '';
        foreach ($filtered_fans as $f) {
            $sel = ($f['code'] == $selected_fan_code) ? 'selected' : '';
            $fan_options .= "<option value=\"{$f['code']}\" $sel>{$f['code']} - {$f['resin']} - {$f['profile']}</option>";
        }
        if (empty($fan_options)) $fan_options = "<option value=\"$default_code\" selected>فن پیش‌فرض آرتیمان ($default_code)</option>";

        $_SESSION['filtered_fans_json'] = json_encode($filtered_fans);
        $_SESSION['default_fan_code_step8'] = $default_code;

        $html = '
        <div class="card">
            <h2>مرحله ۸: کد فن</h2>
            <form id="form-step8">
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; padding:12px 16px; border:2px solid ' . ($fan_selection_type == 'default' ? '#4caf50' : '#e5e7eb') . '; border-radius:12px; cursor:pointer; background:' . ($fan_selection_type == 'default' ? '#e8f5e9' : 'white') . ';">
                        <input type="radio" name="fan_selection_type" value="default" ' . ($fan_selection_type == 'default' ? 'checked' : '') . ' onchange="toggleFanSelection()">
                        <span style="font-weight:bold;">🔹 فن پیش‌فرض آرتیمان</span>
                        <span style="display:inline-block; background:#2e7d32; color:white; padding:2px 10px; border-radius:20px; font-size:12px; margin-right:10px;">' . $default_code . '</span>
                    </label>
                    <label style="display:block; padding:12px 16px; border:2px solid ' . ($fan_selection_type == 'custom' ? '#4caf50' : '#e5e7eb') . '; border-radius:12px; cursor:pointer; margin-top:10px; background:' . ($fan_selection_type == 'custom' ? '#e8f5e9' : 'white') . ';">
                        <input type="radio" name="fan_selection_type" value="custom" ' . ($fan_selection_type == 'custom' ? 'checked' : '') . ' onchange="toggleFanSelection()">
                        <span style="font-weight:bold;">🔸 انتخاب دستی</span>
                    </label>
                </div>
                <div id="default_fan_details" style="display:' . ($fan_selection_type == 'default' ? 'block' : 'none') . '; background:#f8f9fa; padding:20px; border-radius:12px; margin:15px 0; border:1px solid #4caf50;">
                    <p style="font-weight:bold; color:#2e7d32;">✅ مشخصات فن پیش‌فرض آرتیمان (' . $default_code . '):</p>
                    <ul style="list-style:none; padding:0; line-height:2.2; font-size:14px;">
                        <li>🔹 <strong>جنس پره:</strong> ' . $default_info['resin'] . '</li>
                        <li>🔹 <strong>پروفیل:</strong> ' . $default_info['profile'] . '</li>
                        <li>🔹 <strong>هاب:</strong> ' . $default_info['hab'] . '</li>
                        <li>🔹 <strong>کلمپ:</strong> ' . $default_info['clamp'] . '</li>
                        <li>🔹 <strong>نافی:</strong> ' . $default_info['nafi'] . '</li>
                        <li>🔹 <strong>پیچ و مهره:</strong> ' . $default_info['bolt'] . '</li>
                        <li>🔹 <strong>شاسی موتور:</strong> ' . $default_info['chassis_motor'] . '</li>
                    </ul>
                    <div style="margin-top:15px; padding:15px; background:#e8f5e9; border-radius:8px; border:1px solid #4caf50;">
                        <p style="margin:0; color:#2e7d32; font-weight:bold;">✅ آیا از این مشخصات تأیید می‌کنید؟</p>
                        <div style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;">
                            <button type="button" onclick="confirmDefaultFan()" style="background:#4caf50; color:white; border:none; padding:10px 25px; border-radius:8px; cursor:pointer; font-weight:bold;">بله، تأیید می‌کنم</button>
                            <button type="button" onclick="rejectDefaultFan()" style="background:#f44336; color:white; border:none; padding:10px 25px; border-radius:8px; cursor:pointer; font-weight:bold;">خیر، انتخاب دستی</button>
                        </div>
                    </div>
                </div>
                <div id="custom_fan_fields" style="display:' . ($fan_selection_type == 'custom' ? 'block' : 'none') . ';">
                    <div class="form-group">
                        <label>🔧 کد فن مورد نظر را انتخاب کنید:</label>
                        <select name="essential_fan_code" id="fan_code_select" style="width:100%; padding:12px; border-radius:12px; border:2px solid #e5e7eb;">
                            <option value="" disabled selected>انتخاب کنید</option>' . $fan_options . '
                        </select>
                    </div>
                    <div id="custom_fan_details" style="display:none; background:#f8f9fa; padding:15px; border-radius:12px; margin:10px 0; border:1px solid #ff9800;">
                        <p style="font-weight:bold; color:#e65100;">✅ مشخصات فن انتخابی:</p>
                        <div id="custom_fan_info" style="line-height:2.2; font-size:14px;"></div>
                    </div>
                </div>
                <div class="button-group" style="margin-top:25px;">
                    <button type="button" onclick="loadStep(7)" style="background:#6c757d; color:white; border:none; padding:12px 30px; border-radius:30px; cursor:pointer;">⬅️ بازگشت</button>
                    <button type="button" onclick="submitFanStep()" style="background:#2e7d32; color:white; border:none; padding:12px 30px; border-radius:30px; cursor:pointer;">مرحله بعدی ➡️</button>
                </div>
            </form>
        </div>';
    }

    // ====== STEP 9: LOUVER & DROP ELIMINATOR ======
    elseif ($step == 9) {
        $louvers = $data['louvers'] ?? []; $drop_eliminators = $data['drop_eliminators'] ?? [];
        $louver_opts = ''; $selected_louver = $_SESSION['essential_louver'] ?? '';
        foreach ($louvers as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_louver) ? 'selected' : ''; $louver_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $drop_opts = ''; $selected_drop = $_SESSION['essential_drop'] ?? '';
        foreach ($drop_eliminators as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_drop) ? 'selected' : ''; $drop_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۹: لوور و قطره‌گیر</h2>
            <form id=\"form-step9\">
                <div class=\"form-row\">
                    <div class=\"form-group half\"><label>لوور:</label><select name=\"essential_louver\" required><option value=\"\" disabled selected>انتخاب کنید</option>$louver_opts</select></div>
                    <div class=\"form-group half\"><label>قطره‌گیر:</label><select name=\"essential_drop\" required><option value=\"\" disabled selected>انتخاب کنید</option>$drop_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(8)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step9', 10)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 10: NOZZLE, PIPE, FLANGE ======
    elseif ($step == 10) {
        $nozzles = $data['nozzles'] ?? []; $inlet_pipes = $data['inlet_pipes'] ?? []; $flanges = $data['flanges'] ?? [];
        $nozzle_opts = ''; $selected_nozzle = $_SESSION['essential_nozzle'] ?? '';
        foreach ($nozzles as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $k; $sel = ($k == $selected_nozzle) ? 'selected' : ''; $nozzle_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $pipe_opts = ''; $selected_pipe = $_SESSION['essential_pipe'] ?? '';
        foreach ($inlet_pipes as $k => $v) { $sel = ($k == $selected_pipe) ? 'selected' : ''; $pipe_opts .= "<option value=\"$k\" $sel>$k - $v</option>"; }
        $flange_opts = ''; $selected_flange = $_SESSION['essential_flange'] ?? '';
        foreach ($flanges as $k => $v) { $sel = ($k == $selected_flange) ? 'selected' : ''; $flange_opts .= "<option value=\"$k\" $sel>$k - $v</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۱۰: نازل، لوله ورودی و فلنج خروجی</h2>
            <form id=\"form-step10\">
                <div class=\"form-row\">
                    <div class=\"form-group third\"><label>نازل:</label><select name=\"essential_nozzle\" required><option value=\"\" disabled selected>انتخاب کنید</option>$nozzle_opts</select></div>
                    <div class=\"form-group third\"><label>لوله ورودی آب گرم:</label><select name=\"essential_pipe\" required><option value=\"\" disabled selected>انتخاب کنید</option>$pipe_opts</select></div>
                    <div class=\"form-group third\"><label>فلنج خروجی:</label><select name=\"essential_flange\" required><option value=\"\" disabled selected>انتخاب کنید</option>$flange_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(9)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step10', 11)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 11: LADDER & ACCESS PANEL ======
    elseif ($step == 11) {
        $ladders = $data['ladders'] ?? []; $access_panels = $data['access_panels'] ?? [];
        $ladder_opts = ''; $selected_ladder = $_SESSION['essential_ladder'] ?? '';
        foreach ($ladders as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_ladder) ? 'selected' : ''; $ladder_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $access_opts = ''; $selected_access = $_SESSION['essential_access'] ?? '';
        foreach ($access_panels as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_access) ? 'selected' : ''; $access_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۱۱: نردبان و اکسس پنل</h2>
            <form id=\"form-step11\">
                <div class=\"form-row\">
                    <div class=\"form-group half\"><label>نردبان/ایمنی:</label><select name=\"essential_ladder\" required><option value=\"\" disabled selected>انتخاب کنید</option>$ladder_opts</select></div>
                    <div class=\"form-group half\"><label>اکسس پنل:</label><select name=\"essential_access\" required><option value=\"\" disabled selected>انتخاب کنید</option>$access_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(10)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step11', 12)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 12: GELCOAT, FITTINGS, THREADS ======
    elseif ($step == 12) {
        $gelcoats = $data['gelcoats'] ?? []; $fittings = $data['fittings'] ?? []; $threads = $data['threads'] ?? [];
        $gelcoat_opts = ''; $selected_gelcoat = $_SESSION['essential_gelcoat'] ?? '';
        foreach ($gelcoats as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_gelcoat) ? 'selected' : ''; $gelcoat_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $fitting_opts = ''; $selected_fitting = $_SESSION['essential_fitting'] ?? '';
        foreach ($fittings as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_fitting) ? 'selected' : ''; $fitting_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $thread_opts = ''; $selected_thread = $_SESSION['essential_thread'] ?? '';
        foreach ($threads as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_thread) ? 'selected' : ''; $thread_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۱۲: ژل‌کوت، فیتینگ و استاندارد رزوه</h2>
            <form id=\"form-step12\">
                <div class=\"form-row\">
                    <div class=\"form-group third\"><label>ژل‌کوت:</label><select name=\"essential_gelcoat\" required><option value=\"\" disabled selected>انتخاب کنید</option>$gelcoat_opts</select></div>
                    <div class=\"form-group third\"><label>استاندارد فیتینگ:</label><select name=\"essential_fitting\" required><option value=\"\" disabled selected>انتخاب کنید</option>$fitting_opts</select></div>
                    <div class=\"form-group third\"><label>استاندارد رزوه:</label><select name=\"essential_thread\" required><option value=\"\" disabled selected>انتخاب کنید</option>$thread_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(11)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step12', 13)\">مرحله بعدی</button>
                </div>
            </form>
        </div>";
    }

    // ====== STEP 13: LOCATIONS ======
    elseif ($step == 13) {
        $ladder_locations = $data['ladder_locations'] ?? []; $flange_locations = $data['flange_locations'] ?? []; $access_locations = $data['access_locations'] ?? [];
        $ladder_loc_opts = ''; $selected_ladder_loc = $_SESSION['ladder_loc'] ?? '';
        foreach ($ladder_locations as $k => $v) { $name = is_array($v) ? ($v['name'] ?? $k) : $v; $sel = ($k == $selected_ladder_loc) ? 'selected' : ''; $ladder_loc_opts .= "<option value=\"$k\" $sel>$k - $name</option>"; }
        $flange_loc_opts = ''; $selected_flange_loc = $_SESSION['flange_loc'] ?? '';
        foreach ($flange_locations as $k => $v) { $sel = ($k == $selected_flange_loc) ? 'selected' : ''; $flange_loc_opts .= "<option value=\"$k\" $sel>$k - $v</option>"; }
        $access_loc_opts = ''; $selected_access_loc = $_SESSION['access_loc'] ?? '';
        foreach ($access_locations as $k => $v) { $sel = ($k == $selected_access_loc) ? 'selected' : ''; $access_loc_opts .= "<option value=\"$k\" $sel>$k - $v</option>"; }
        $html = "
        <div class=\"card\">
            <h2>مرحله ۱۳: جانمایی</h2>
            <form id=\"form-step13\">
                <div class=\"form-row\">
                    <div class=\"form-group third\"><label>محل نردبان:</label><select name=\"ladder_loc\" required><option value=\"\" disabled selected>انتخاب کنید</option>$ladder_loc_opts</select></div>
                    <div class=\"form-group third\"><label>محل فلنج خروجی:</label><select name=\"flange_loc\" required><option value=\"\" disabled selected>انتخاب کنید</option>$flange_loc_opts</select></div>
                    <div class=\"form-group third\"><label>محل اکسس پنل:</label><select name=\"access_loc\" required><option value=\"\" disabled selected>انتخاب کنید</option>$access_loc_opts</select></div>
                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"loadStep(12)\">بازگشت به مرحله قبل</button>
                    <button type=\"button\" onclick=\"submitStep('form-step13', 14)\">نمایش نتیجه نهایی</button>
                </div>
            </form>
        </div>";
    }

       // ====== STEP 14: RESULT ======
    elseif ($step == 14) {
        $d = $_SESSION;
        $circuit_name = ($d['circuit_type'] ?? '') == 'closed' ? 'مدار بسته' : 'مدار باز';
        $prefix = ($d['circuit_type'] ?? '') == 'closed' ? 'CCS' : 'OCS';
        $model = $d['model'] ?? '';
        $material_code = $d['material_code'] ?? 'M01';
        
        $grade = 'P';
        if (strpos($material_code, 'M') === 0) {
            $grade = (strpos($material_code, '14') !== false || strpos($material_code, '15') !== false) ? 'H' : 'P';
        } elseif (strpos($material_code, 'E') === 0) {
            $grade = (strpos($material_code, '01') !== false) ? 'M' : 'E';
        }
        
        $base = "$prefix-$model/$grade";
        $middle = '';
        if (($d['circuit_type'] ?? '') == 'open') {
            $middle .= '/' . ($d['packing_display'] ?? '');
        } else {
            $middle .= '/' . ($d['coil_display'] ?? '');
        }
        $middle .= '/' . ($d['fan_size'] ?? '') . '-' . ($d['fan_blades'] ?? '');
        
        $motor_power = $d['motor_power'] ?? '';
        $reduction_display = $d['reduction_display'] ?? 'M4';
        $motor_brand = $d['motor_brand'] ?? '';
        if ($motor_brand) {
            $middle .= '/' . $motor_power . '-' . $reduction_display . '/' . $motor_brand;
        } else {
            $middle .= '/' . $motor_power . '-' . $reduction_display;
        }
        if (($d['circuit_type'] ?? '') == 'closed' && isset($d['pump_display'])) {
            $middle .= '/' . $d['pump_display'];
        }
        
        $product_code = "$base$middle";
        $full_name = "برج خنک کننده $circuit_name $base$middle";
        
        $materials = $data['materials'] ?? [];
        $mat_info = null;
        foreach ($materials as $m) { if ($m['code'] == $material_code) { $mat_info = $m; break; } }
        
        $fan_code = $d['essential_fan_code'] ?? '';
        $fan_table = $data['fan_table'] ?? [];
        $fan_info = null;
        foreach ($fan_table as $f) { if ($f['code'] == $fan_code) { $fan_info = $f; break; } }
        
        $motor_brand_name = $data['motor_brands'][$d['motor_brand']]['name'] ?? $d['motor_brand'];
        $reduction_type = $d['reduction_type'] ?? '';
        
        $louver_raw = $data['louvers'][$d['essential_louver']] ?? '';
        $louver_name = is_array($louver_raw) ? ($louver_raw['name'] ?? '') : $louver_raw;
        $drop_raw = $data['drop_eliminators'][$d['essential_drop']] ?? '';
        $drop_name = is_array($drop_raw) ? ($drop_raw['name'] ?? '') : $drop_raw;
        
        $nozzle_raw = $data['nozzles'][$d['essential_nozzle']] ?? '';
        $nozzle_name = is_array($nozzle_raw) ? ($nozzle_raw['name'] ?? '') : $nozzle_raw;
        $pipe_name = $data['inlet_pipes'][$d['essential_pipe']] ?? '';
        $flange_name = $data['flanges'][$d['essential_flange']] ?? '';
        
        $ladder_loc_raw = $data['ladder_locations'][$d['ladder_loc']] ?? '';
        $ladder_loc_name = is_array($ladder_loc_raw) ? ($ladder_loc_raw['name'] ?? '') : $ladder_loc_raw;
        $flange_loc_name = $data['flange_locations'][$d['flange_loc']] ?? '';
        $access_loc_raw = $data['access_locations'][$d['access_loc']] ?? '';
        $access_loc_name = is_array($access_loc_raw) ? ($access_loc_raw['name'] ?? '') : $access_loc_raw;
        
        $packing_info = ''; $packing_text = '';
        if (($d['circuit_type'] ?? '') == 'open') {
            $packing_type = $d['packing_type'] ?? ''; $packing_height = $d['packing_height'] ?? '';
            $packing_name_raw = $data['packing_types'][$packing_type]['name'] ?? $packing_type;
            $packing_name = str_replace(['پکینگ فیلمی', 'پکینگ', 'نوع', 'فیلم'], '', $packing_name_raw);
            $packing_info = "پکینگ فیلمی <strong>$packing_name</strong> با ارتفاع <strong>$packing_height میلی‌متر</strong>";
            $packing_text = "پکینگ فیلمی $packing_name با ارتفاع $packing_height میلی‌متر";
        } else {
            $coil_code = $d['coil_code'] ?? ''; $coil_info = $data['coil_options'][$coil_code] ?? [];
            $coil_desc = $coil_info['desc'] ?? ''; $rows = $d['rows'] ?? ''; $pass = $d['pass_count'] ?? '';
            $packing_info = "کویل حرارتی <strong>$coil_desc</strong> با <strong>$rows ردیف</strong> و <strong>$pass پاس</strong>";
            $packing_text = "کویل حرارتی $coil_desc با $rows ردیف و $pass پاس";
        }
        
        $reduction_text = ''; $reduction_text_plain = '';
        if ($reduction_type == 'low_rpm') {
            $reduction_text = "موتور دور پایین <strong>{$d['low_rpm_type']}</strong>";
            $reduction_text_plain = "موتور دور پایین {$d['low_rpm_type']}";
        } elseif ($reduction_type == 'belt') {
            $belt_name = $data['belt_types'][$d['belt_type']]['name'] ?? $d['belt_type'];
            $reduction_text = "تسمه و پولی <strong>$belt_name</strong> (نسبت: <strong>{$d['ratio']}</strong>)";
            $reduction_text_plain = "تسمه و پولی $belt_name (نسبت: {$d['ratio']})";
        } elseif ($reduction_type == 'gearbox') {
            $gearbox_name = $data['gearbox_brands'][$d['gearbox_brand']]['name'] ?? $d['gearbox_brand'];
            $reduction_text = "گیربکس <strong>$gearbox_name</strong> (نسبت: <strong>{$d['ratio']}</strong>)";
            $reduction_text_plain = "گیربکس $gearbox_name (نسبت: {$d['ratio']})";
        }
        
        $ess_list = [$d['material_code'] ?? '', $d['essential_fan_code'] ?? '', $d['essential_nozzle'] ?? '', $d['essential_louver'] ?? '', $d['essential_drop'] ?? '', $d['essential_ladder'] ?? '', $d['essential_access'] ?? '', $d['essential_flange'] ?? '', $d['essential_pipe'] ?? ''];
        if ($reduction_type == 'belt' && isset($d['belt_type'])) $ess_list[] = $d['belt_type'];
        elseif ($reduction_type == 'gearbox' && isset($d['gearbox_brand'])) $ess_list[] = $d['gearbox_brand'];
        $ess_list[] = $d['essential_gelcoat'] ?? ''; $ess_list[] = $d['essential_fitting'] ?? ''; $ess_list[] = $d['essential_thread'] ?? '';
        if (($d['circuit_type'] ?? '') == 'closed' && isset($d['coil_code'])) array_splice($ess_list, 1, 0, $d['coil_code']);
        
        $essential_str = implode('.', array_filter($ess_list));
        $layout_str = implode('.', [$d['ladder_loc'] ?? '', $d['flange_loc'] ?? '', $d['access_loc'] ?? '']);
        
        // ساخت متن برای کپی
        $copy_text = "طراحی شما با موفقیت انجام شد\n\n";
        $copy_text .= "$product_code\n";
        $copy_text .= "آپشن‌های ضروری: $essential_str\n";
        $copy_text .= "آپشن‌های جانمایی: $layout_str\n\n";
        $copy_text .= "═══════════════════════════════\n📋 توضیحات کامل:\n═══════════════════════════════\n\n";
        if ($mat_info) {
            $copy_text .= "🧪 مشخصات متریال\n🔹 رزین: {$mat_info['resin']}\n🔹 پیچ و مهره: {$mat_info['bolt_body']}\n🔹 شاسی پکینگ: {$mat_info['chassis_packing_drop']}\n🔹 شاسی فن: {$mat_info['chassis_fan']}\n\n";
        }
        $copy_text .= (($d['circuit_type'] ?? '') == 'open' ? "🌊 پکینگ\n" : "🔥 کویل حرارتی\n") . "$packing_text\n\n";
        if ($fan_info) {
            $profile_clean = str_replace(['فن', 'پلی آمید'], '', $fan_info['profile']);
            $copy_text .= "🌀 مشخصات فن\n🔹 قطر: {$d['fan_size']} متر\n🔹 تعداد پره: {$d['fan_blades']} پره\n🔹 جنس: {$fan_info['resin']}\n🔹 پروفیل: $profile_clean\n🔹 هاب: {$fan_info['hab']} | کلمپ: {$fan_info['clamp']}\n\n";
        }
        $copy_text .= "⚡ الکتروموتور\n🔹 برند: $motor_brand_name\n🔹 توان: $motor_power کیلووات\n🔹 سیستم کاهش دور: $reduction_text_plain\n\n";
        if (($d['circuit_type'] ?? '') == 'closed' && isset($d['pump_display'])) {
            $copy_text .= "💧 پمپ سیرکولاسیون\n🔹 تعداد: {$d['pump_count']} دستگاه\n🔹 توان هر پمپ: {$d['pump_power']} کیلووات\n\n";
        }
        if ($louver_name && $drop_name) $copy_text .= "🛡️ لوور و قطره‌گیر\n🔹 لوور: $louver_name\n🔹 قطره‌گیر: $drop_name\n\n";
        if ($nozzle_name && $pipe_name && $flange_name) $copy_text .= "🚿 سیستم توزیع آب\n🔹 نازل: $nozzle_name\n🔹 لوله ورودی: $pipe_name\n🔹 فلنج: $flange_name\n\n";
        if ($ladder_loc_name && $flange_loc_name && $access_loc_name) {
            $copy_text .= "📐 جانمایی تجهیزات\n🔹 نردبان: $ladder_loc_name\n🔹 فلنج خروجی: $flange_loc_name\n🔹 اکسس پنل: $access_loc_name\n";
        }
        
        $copy_text_json = json_encode($copy_text, JSON_UNESCAPED_UNICODE);
        
        // ====== تولید HTML با چیدمان جدید درخواستی ======
        $html = '
        <style>
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
            @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
            .result-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; border-radius: 20px; text-align: center; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); animation: fadeInUp 0.6s ease-out; position: relative; overflow: hidden; }
            .result-hero::before { content: ""; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
            @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .result-hero h2 { margin: 0 0 10px 0; font-size: 24px; position: relative; z-index: 1; }
            .result-hero .model-badge { display: inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 12px 25px; border-radius: 50px; font-size: 18px; font-weight: bold; margin-top: 15px; border: 2px solid rgba(255,255,255,0.3); position: relative; z-index: 1; letter-spacing: 1px; }
            
            .summary-codes { text-align: center; margin: 20px auto; max-width: 800px; padding: 20px; background: #f8fafc; border-radius: 16px; border: 2px solid #e2e8f0; }
            .summary-codes .code-line { margin-bottom: 12px; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; }
            .summary-codes .code-value { font-family: monospace; color: #0369a1; background: #e0f2fe; padding: 6px 12px; border-radius: 8px; font-weight: bold; letter-spacing: 0.5px; }
            
            .copy-btn { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
            .copy-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
            .copy-btn.copied { background: linear-gradient(135deg, #10b981 0%, #059669 100%); animation: pulse 0.5s ease; }
            
            .spec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 25px; }
            .spec-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-right: 4px solid; transition: all 0.3s ease; animation: slideInRight 0.6s ease-out; animation-fill-mode: both; }
            .spec-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
            .spec-card:nth-child(1) { border-color: #3b82f6; animation-delay: 0.1s; }
            .spec-card:nth-child(2) { border-color: #10b981; animation-delay: 0.2s; }
            .spec-card:nth-child(3) { border-color: #f59e0b; animation-delay: 0.3s; }
            .spec-card:nth-child(4) { border-color: #ef4444; animation-delay: 0.4s; }
            .spec-card:nth-child(5) { border-color: #8b5cf6; animation-delay: 0.5s; }
            .spec-card:nth-child(6) { border-color: #ec4899; animation-delay: 0.6s; }
            .spec-card:nth-child(7) { border-color: #06b6d4; animation-delay: 0.7s; }
            .spec-card:nth-child(8) { border-color: #84cc16; animation-delay: 0.8s; }
            .spec-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 2px dashed #e5e7eb; }
            .spec-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
            .spec-card:nth-child(1) .spec-icon { background: #dbeafe; } .spec-card:nth-child(2) .spec-icon { background: #d1fae5; } .spec-card:nth-child(3) .spec-icon { background: #fef3c7; } .spec-card:nth-child(4) .spec-icon { background: #fee2e2; } .spec-card:nth-child(5) .spec-icon { background: #ede9fe; } .spec-card:nth-child(6) .spec-icon { background: #fce7f3; } .spec-card:nth-child(7) .spec-icon { background: #cffafe; } .spec-card:nth-child(8) .spec-icon { background: #ecfccb; }
            .spec-card-title { font-size: 16px; font-weight: bold; color: #1f2937; margin: 0; }
            .spec-card-content { font-size: 14px; line-height: 2; color: #4b5563; }
            .spec-card-content strong { color: #111827; background: #f3f4f6; padding: 2px 8px; border-radius: 6px; font-weight: 600; }
            
            .action-buttons { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin-top: 25px; }
            .btn-action { padding: 14px 28px; border: none; border-radius: 50px; font-size: 15px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
            .btn-back { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; }
            .btn-new { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
            .btn-print { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
            @media print { .action-buttons, .btn-action, .copy-btn { display: none !important; } .spec-card { break-inside: avoid; } }
        </style>
        
        <div class="result-hero">
            <h2>✅ نتیجه نهایی طراحی</h2>
            <p style="margin: 8px 0; opacity: 0.95; position: relative; z-index: 1;">طراحی شما با موفقیت انجام شد</p>
            <div class="model-badge">🏭 ' . $full_name . '</div>
        </div>

        <div class="summary-codes">
            <div class="code-line">
                <span>📦 آپشن‌های ضروری:</span>
                <span class="code-value">' . $essential_str . '</span>
            </div>
            <div class="code-line">
                <span>📍 آپشن‌های جانمایی:</span>
                <span class="code-value">' . $layout_str . '</span>
            </div
        </div>

        <div style="text-align: center; margin-bottom: 30px;">
<button type="button" class="copy-btn" id="copyBtn" onclick="copyResult()" data-copy-text="' . htmlspecialchars($copy_text, ENT_QUOTES, 'UTF-8') . '">
    <span id="copy-icon">📋</span>
    <span id="copy-text">کپی کردن اطلاعات</span>
</button>
        </div>

        <div class="spec-grid">';
        
        if ($mat_info) {
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">🧪</div><h3 class="spec-card-title">مشخصات متریال</h3></div><div class="spec-card-content"><div>🔹 رزین: <strong>' . $mat_info['resin'] . '</strong></div><div>🔹 پیچ و مهره: <strong>' . $mat_info['bolt_body'] . '</strong></div><div>🔹 شاسی پکینگ: <strong>' . $mat_info['chassis_packing_drop'] . '</strong></div><div>🔹 شاسی فن: <strong>' . $mat_info['chassis_fan'] . '</strong></div></div></div>';
        }
        $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">' . (($d['circuit_type'] ?? '') == 'open' ? '🌊' : '🔥') . '</div><h3 class="spec-card-title">' . (($d['circuit_type'] ?? '') == 'open' ? 'پکینگ' : 'کویل حرارتی') . '</h3></div><div class="spec-card-content">' . $packing_info . '</div></div>';
        if ($fan_info) {
            $profile_clean = str_replace(['فن', 'پلی آمید'], '', $fan_info['profile']);
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">🌀</div><h3 class="spec-card-title">مشخصات فن</h3></div><div class="spec-card-content"><div>🔹 قطر: <strong>' . $d['fan_size'] . ' متر</strong></div><div>🔹 تعداد پره: <strong>' . $d['fan_blades'] . ' پره</strong></div><div>🔹 جنس: <strong>' . $fan_info['resin'] . '</strong></div><div>🔹 پروفیل: <strong>' . $profile_clean . '</strong></div><div>🔹 هاب: <strong>' . $fan_info['hab'] . '</strong> | کلمپ: <strong>' . $fan_info['clamp'] . '</strong></div></div></div>';
        }
        $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">⚡</div><h3 class="spec-card-title">الکتروموتور</h3></div><div class="spec-card-content"><div>🔹 برند: <strong>' . $motor_brand_name . '</strong></div><div>🔹 توان: <strong>' . $motor_power . ' کیلووات</strong></div><div>🔹 سیستم کاهش دور: <strong>' . $reduction_text . '</strong></div></div></div>';
        if (($d['circuit_type'] ?? '') == 'closed' && isset($d['pump_display'])) {
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">💧</div><h3 class="spec-card-title">پمپ سیرکولاسیون</h3></div><div class="spec-card-content"><div>🔹 تعداد: <strong>' . $d['pump_count'] . ' دستگاه</strong></div><div>🔹 توان هر پمپ: <strong>' . $d['pump_power'] . ' کیلووات</strong></div></div></div>';
        }
        if ($louver_name && $drop_name) {
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">🛡️</div><h3 class="spec-card-title">لوور و قطره‌گیر</h3></div><div class="spec-card-content"><div>🔹 لوور: <strong>' . $louver_name . '</strong></div><div>🔹 قطره‌گیر: <strong>' . $drop_name . '</strong></div></div></div>';
        }
        if ($nozzle_name && $pipe_name && $flange_name) {
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">🚿</div><h3 class="spec-card-title">سیستم توزیع آب</h3></div><div class="spec-card-content"><div>🔹 نازل: <strong>' . $nozzle_name . '</strong></div><div>🔹 لوله ورودی: <strong>' . $pipe_name . '</strong></div><div>🔹 فلنج: <strong>' . $flange_name . '</strong></div></div></div>';
        }
        if ($ladder_loc_name && $flange_loc_name && $access_loc_name) {
            $html .= '<div class="spec-card"><div class="spec-card-header"><div class="spec-icon">📐</div><h3 class="spec-card-title">جانمایی تجهیزات</h3></div><div class="spec-card-content"><div>🔹 نردبان: <strong>' . $ladder_loc_name . '</strong></div><div>🔹 فلنج خروجی: <strong>' . $flange_loc_name . '</strong></div><div>🔹 اکسس پنل: <strong>' . $access_loc_name . '</strong></div></div></div>';
        }
        
        $html .= '</div>
        
        <div class="action-buttons">
            <button type="button" class="btn-action btn-back" onclick="loadStep(13)">⬅️ بازگشت به مرحله قبل</button>
            <button type="button" class="btn-action btn-print" onclick="window.print()">🖨️ چاپ / ذخیره PDF</button>
            <button type="button" class="btn-action btn-new" onclick="startNewDesign()">🔄 شروع طراحی جدید</button>
        </div>
        
        <script>
        function copyResult() {
            var btn = document.getElementById("copyBtn");
            var icon = document.getElementById("copy-icon");
            var textSpan = document.getElementById("copy-text");
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(copyText).then(function() {
                    btn.classList.add("copied"); icon.textContent = "✅"; textSpan.textContent = "کپی شد!";
                    setTimeout(function() { btn.classList.remove("copied"); icon.textContent = "📋"; textSpan.textContent = "کپی کردن اطلاعات"; }, 2000);
                }).catch(function(err) { fallbackCopy(copyText, btn, icon, textSpan); });
            } else { fallbackCopy(copyText, btn, icon, textSpan); }
        }
        function fallbackCopy(text, btn, icon, textSpan) {
            var textArea = document.createElement("textarea");
            textArea.value = text; textArea.style.position = "fixed"; textArea.style.left = "-999999px"; textArea.style.top = "-999999px";
            document.body.appendChild(textArea); textArea.focus(); textArea.select();
            try {
                document.execCommand("copy");
                btn.classList.add("copied"); icon.textContent = "✅"; textSpan.textContent = "کپی شد!";
                setTimeout(function() { btn.classList.remove("copied"); icon.textContent = "📋"; textSpan.textContent = "کپی کردن اطلاعات"; }, 2000);
            } catch (err) { alert("خطا در کپی کردن. لطفاً دستی کپی کنید."); }
            document.body.removeChild(textArea);
        }
        </script>';
    }



    // ====== DEFAULT ======
    else {
        $html = '<div class="card"><h2>مرحله در حال توسعه است</h2><p>این مرحله هنوز کامل نشده است.</p></div>';
    }

    echo json_encode(['html' => $html]);
    exit;
}

// ====== SAVE STEP ======
if ($action == 'save_step') {
    $step = (int)($_POST['step'] ?? 1);
    foreach ($_POST as $key => $value) {
        if ($key != 'action' && $key != 'step') {
            $_SESSION[$key] = trim($value);
        }
    }

    if ($step == 2) {
        $circuit = $_SESSION['circuit_type'] ?? 'open';
        $prefix = $circuit == 'open' ? 'OCS' : 'CCS';
        $model_full = $_SESSION['model_full'] ?? '';
        $model = explode('-', $model_full)[1] ?? $model_full;
        $_SESSION['model'] = $model; $_SESSION['prefix'] = $prefix;
        $base_model = str_replace('CCS-', 'OCS-', $model_full);
        $fan_info = $data['fan_sizes'][$base_model] ?? [];
        $_SESSION['fan_size'] = $fan_info['size'] ?? ''; $_SESSION['fan_type'] = $fan_info['type'] ?? 'FRP';
    }
    if ($step == 3) {
        $materials = $data['materials'] ?? [];
        $resin = $_SESSION['resin'] ?? ''; $bolt = $_SESSION['bolt_body'] ?? '';
        $packing = $_SESSION['chassis_packing_drop'] ?? ''; $fan = $_SESSION['chassis_fan'] ?? '';
        foreach ($materials as $mat) {
            if ($mat['resin'] == $resin && $mat['bolt_body'] == $bolt && $mat['chassis_packing_drop'] == $packing && $mat['chassis_fan'] == $fan) {
                $_SESSION['material_code'] = $mat['code']; break;
            }
        }
    }
    if ($step == 4) {
        if (($_SESSION['circuit_type'] ?? 'open') == 'open') {
            $ptype = $_SESSION['packing_type'] ?? ''; $pheight = $_SESSION['packing_height'] ?? '';
            if ($ptype && $pheight) $_SESSION['packing_display'] = "$ptype-$pheight";
        } else {
            $coil_code = $_SESSION['coil_code'] ?? ''; $rows = $_SESSION['rows'] ?? ''; $pass = $_SESSION['pass_count'] ?? '';
            if ($coil_code && $rows && $pass) {
                $material = $data['coil_options'][$coil_code]['material'] ?? 'HDG';
                $_SESSION['coil_display'] = "$material-$rows$pass"; $_SESSION['coil_code'] = $coil_code;
            }
        }
    }
    if ($step == 6) {
        $reduction_type = $_SESSION['reduction_type'] ?? '';
        if ($reduction_type == 'low_rpm') $_SESSION['reduction_display'] = $_SESSION['low_rpm_type'] ?? 'M4';
        elseif ($reduction_type == 'belt') { $_SESSION['belt_type'] = $_SESSION['belt_type'] ?? ''; $_SESSION['reduction_display'] = "BP " . ($_SESSION['ratio'] ?? '2.5'); }
        elseif ($reduction_type == 'gearbox') { $_SESSION['gearbox_brand'] = $_SESSION['gearbox_brand'] ?? ''; $_SESSION['reduction_display'] = "GB " . ($_SESSION['ratio'] ?? '2.5'); }
    }
    if ($step == 7) {
        if (($_SESSION['circuit_type'] ?? 'open') == 'closed') {
            $count = $_SESSION['pump_count'] ?? '1'; $power = $_SESSION['pump_power'] ?? '';
            if ($power) $_SESSION['pump_display'] = $count . 'P-' . $power;
        }
    }
    if ($step == 8) {
        $fan_selection_type = $_SESSION['fan_selection_type'] ?? 'default';
        if ($fan_selection_type == 'default') {
            $model = $_SESSION['model'] ?? '';
            $_SESSION['essential_fan_code'] = ($model == '15') ? 'F201' : 'F107';
        } else {
            $fan_table = $data['fan_table'] ?? []; $fan_code = $_SESSION['essential_fan_code'] ?? ''; $found = false;
            foreach ($fan_table as $f) { if ($f['code'] == $fan_code) { $found = true; break; } }
            if (!$found) { echo json_encode(['success' => false, 'message' => 'کد فن انتخاب شده نامعتبر است']); exit; }
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// ====== GET PACKING HEIGHTS ======
if ($action == 'get_packing_heights') {
    $type = $_POST['type'] ?? '';
    $heights = $data['packing_types'][$type]['heights'] ?? [];
    echo json_encode(['heights' => $heights]);
    exit;
}

// ====== CLEAR SESSION ======
if ($action == 'clear_session') {
    $user_id = $_SESSION['user_id'] ?? null; $username = $_SESSION['username'] ?? null;
    $fullname = $_SESSION['fullname'] ?? null; $is_admin = $_SESSION['is_admin'] ?? null;
    session_unset(); session_destroy(); session_start();
    if ($user_id) {
        $_SESSION['user_id'] = $user_id; $_SESSION['username'] = $username;
        $_SESSION['fullname'] = $fullname; $_SESSION['is_admin'] = $is_admin;
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);