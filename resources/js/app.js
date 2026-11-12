import './bootstrap';

// Use the existing table content as readable cards on mobile list pages.
if (document.body.classList.contains('f2f-mobile-card-list')) {
    document.querySelectorAll('.f2f-table').forEach((table) => {
        const labels = [...table.querySelectorAll('thead th')].map((cell) => cell.textContent.trim());
        table.querySelectorAll('tbody tr').forEach((row) => {
            row.querySelectorAll('td').forEach((cell, index) => {
                if (!cell.hasAttribute('colspan')) cell.dataset.label = labels[index] || '';
            });
        });
    });
}

// Mobile Drawer / Sidebar Toggle
const sidebarToggleBtn = document.getElementById('sidebar-toggle');
const mobileMenuToggleBtn = document.getElementById('mobile-menu-toggle');
const sidebarCloseBtn = document.getElementById('sidebar-close');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');
const appSidebar = document.getElementById('app-sidebar');
const dashboardMain = document.querySelector('.f2f-main');
const mobileSidebar = window.matchMedia('(max-width: 860px)');
if (appSidebar) appSidebar.inert = mobileSidebar.matches;

function openSidebar() {
    if (appSidebar) appSidebar.inert = false;
    if (dashboardMain) dashboardMain.inert = true;
    appSidebar?.classList.add('open');
    sidebarBackdrop?.classList.add('visible');
    document.body.style.overflow = 'hidden';
    sidebarToggleBtn?.setAttribute('aria-expanded', 'true');
    mobileMenuToggleBtn?.setAttribute('aria-expanded', 'true');
    sidebarCloseBtn?.focus();
}

function closeSidebar() {
    appSidebar?.classList.remove('open');
    sidebarBackdrop?.classList.remove('visible');
    document.body.style.overflow = '';
    if (dashboardMain) dashboardMain.inert = false;
    if (appSidebar) appSidebar.inert = mobileSidebar.matches;
    sidebarToggleBtn?.setAttribute('aria-expanded', 'false');
    mobileMenuToggleBtn?.setAttribute('aria-expanded', 'false');
    sidebarToggleBtn?.focus();
}

sidebarToggleBtn?.addEventListener('click', openSidebar);
mobileMenuToggleBtn?.addEventListener('click', openSidebar);
sidebarCloseBtn?.addEventListener('click', closeSidebar);
sidebarBackdrop?.addEventListener('click', closeSidebar);

// Close sidebar when pressing ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && appSidebar?.classList.contains('open')) {
        closeSidebar();
    }
});

// Keep keyboard navigation inside the mobile drawer while it is open.
appSidebar?.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || !appSidebar.classList.contains('open')) return;
    const controls = [...appSidebar.querySelectorAll('a[href], button:not([disabled])')]
        .filter((element) => element.getClientRects().length);
    const first = controls[0];
    const last = controls.at(-1);
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
    }
});
window.matchMedia('(min-width: 861px)').addEventListener('change', (event) => {
    if (event.matches && appSidebar?.classList.contains('open')) closeSidebar();
    if (appSidebar) appSidebar.inert = !event.matches && !appSidebar.classList.contains('open');
});
document.querySelector('[data-error-summary]')?.focus();
document.querySelectorAll('.f2f-side-link.active').forEach((link) => link.setAttribute('aria-current', 'page'));

// Compact producer filters open as a bottom sheet on phones.
document.querySelectorAll('[data-mobile-filter-open]').forEach((trigger) => {
    const panel = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!panel) return;
    const close = () => {
        panel.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        trigger.focus();
    };
    trigger.addEventListener('click', () => {
        panel.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        panel.querySelector('[data-mobile-filter-close]')?.focus();
    });
    panel.querySelector('[data-mobile-filter-close]')?.addEventListener('click', close);
    panel.addEventListener('click', (event) => {
        if (event.target === panel) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) close();
    });
});

// Read a GS1 QR with the phone camera or an image, then open its local trace page.
const qrOverlay = document.getElementById('mobile-qr-scanner');
const qrVideo = document.getElementById('mobile-qr-video');
const qrStatus = document.getElementById('mobile-qr-status');
const qrFile = document.getElementById('mobile-qr-file');
let qrStream = null;
let qrTimer = null;
let qrDetector = null;
let qrPreviousFocus = null;
let qrWarehouseMode = false;

function qrMessage(message) {
    if (qrStatus) qrStatus.textContent = message;
}

function stopQrCamera() {
    if (qrTimer) clearInterval(qrTimer);
    qrTimer = null;
    qrStream?.getTracks().forEach((track) => track.stop());
    qrStream = null;
    if (qrVideo) {
        qrVideo.pause();
        qrVideo.srcObject = null;
        qrVideo.hidden = true;
    }
}

function openTraceFromQr(rawValue) {
    if (qrWarehouseMode) {
        const match = String(rawValue).trim().match(/(?:^|\/)00\/(\d{18})(?:\/|$)/) || String(rawValue).trim().match(/^(\d{18})$/);
        if (!match) {
            qrMessage('Không đọc được mã SSCC 18 chữ số. Hãy quét lại hoặc nhập mã bằng ô tìm kiếm.');
            return false;
        }
        stopQrCamera();
        const target = new URL(window.location.href);
        target.searchParams.set('q', match[1]);
        target.searchParams.set('tab', target.searchParams.get('tab') === 'outbound' ? 'outbound' : 'inbound');
        window.location.assign(target.href);
        return true;
    }
    let parsed;
    try {
        parsed = new URL(rawValue, window.location.origin);
    } catch {
        qrMessage('Mã QR này không chứa đường dẫn truy xuất hợp lệ.');
        return false;
    }
    const path = parsed.pathname;
    const isTrace = /^\/trace\/(?:01\/\d{14}\/10\/[A-Za-z0-9_-]+|[A-Za-z0-9_-]+)\/?$/.test(path);
    const isGs1 = /^\/01\/\d{14}\/10\/[A-Za-z0-9_-]+\/?$/.test(path);
    const isSscc = /^\/00\/\d{18}\/?$/.test(path);
    if (!isTrace && !isGs1 && !isSscc) {
        qrMessage('Đây không phải mã QR truy xuất Farm2Fork hoặc GS1 được hỗ trợ.');
        return false;
    }
    stopQrCamera();
    window.location.assign(window.location.origin + path);
    return true;
}

function closeQrScanner() {
    stopQrCamera();
    if (qrOverlay) qrOverlay.hidden = true;
    document.body.style.overflow = '';
    qrPreviousFocus?.focus();
}

document.querySelectorAll('[data-open-qr-scanner]').forEach((button) => button.addEventListener('click', (event) => {
    qrWarehouseMode = event.currentTarget.hasAttribute('data-warehouse-sscc-scan');
    qrPreviousFocus = event.currentTarget;
    if (qrOverlay) qrOverlay.hidden = false;
    document.body.style.overflow = 'hidden';
    qrMessage('Chọn cách quét mã.');
    const manual = document.getElementById('mobile-qr-link');
    if (manual) {
        manual.type = qrWarehouseMode ? 'text' : 'url';
        manual.placeholder = qrWarehouseMode ? 'Nhập SSCC 18 chữ số hoặc dán đường dẫn /00/...' : 'https://.../01/.../10/...';
        const label = document.querySelector('label[for="mobile-qr-link"]');
        if (label) label.textContent = qrWarehouseMode ? 'Hoặc nhập mã SSCC' : 'Hoặc dán đường dẫn truy xuất';
    }
    qrOverlay?.querySelector('[data-close-qr-scanner]')?.focus();
}));
qrOverlay?.querySelector('[data-close-qr-scanner]')?.addEventListener('click', closeQrScanner);
qrOverlay?.addEventListener('click', (event) => {
    if (event.target === qrOverlay) closeQrScanner();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && qrOverlay && !qrOverlay.hidden) closeQrScanner();
});

document.getElementById('mobile-qr-camera')?.addEventListener('click', async () => {
    if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
        qrMessage('Trình duyệt này chưa hỗ trợ quét bằng camera. Hãy dùng Chrome hoặc Edge mới, hoặc chọn ảnh QR.');
        return;
    }
    try {
        stopQrCamera();
        qrDetector = new window.BarcodeDetector({ formats: qrWarehouseMode ? ['qr_code', 'code_128'] : ['qr_code'] });
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        qrVideo.srcObject = qrStream;
        qrVideo.hidden = false;
        await qrVideo.play();
        qrMessage('Đưa mã QR vào giữa khung hình.');
        qrTimer = setInterval(async () => {
            if (!qrStream || qrVideo.readyState < 2) return;
            try {
                const codes = await qrDetector.detect(qrVideo);
                if (codes[0]) openTraceFromQr(codes[0].rawValue);
            } catch {
                qrMessage('Chưa đọc được mã. Hãy giữ camera ổn định hoặc chọn ảnh QR.');
            }
        }, 450);
    } catch {
        stopQrCamera();
        qrMessage('Không mở được camera. Hãy cho phép dùng camera hoặc chọn ảnh QR.');
    }
});

qrFile?.addEventListener('change', async () => {
    const file = qrFile.files?.[0];
    if (!file) return;
    if (!('BarcodeDetector' in window)) {
        qrMessage('Trình duyệt này chưa hỗ trợ đọc ảnh QR. Hãy dùng Chrome hoặc Edge mới.');
        return;
    }
    try {
        const bitmap = await createImageBitmap(file);
        const detector = new window.BarcodeDetector({ formats: qrWarehouseMode ? ['qr_code', 'code_128'] : ['qr_code'] });
        const codes = await detector.detect(bitmap);
        bitmap.close();
        if (codes[0]) openTraceFromQr(codes[0].rawValue);
        else qrMessage('Không tìm thấy mã QR trong ảnh. Hãy chọn ảnh rõ nét hơn.');
    } catch {
        qrMessage('Không đọc được ảnh QR này. Hãy thử ảnh khác.');
    } finally {
        qrFile.value = '';
    }
});
document.getElementById('mobile-qr-manual')?.addEventListener('submit', (event) => {
    event.preventDefault();
    const link = document.getElementById('mobile-qr-link')?.value.trim();
    if (link) openTraceFromQr(link);
});

// Toggle password visibility
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-toggle-password');
        const input = document.getElementById(targetId);

        if (!input) {
            return;
        }

        const asText = input.getAttribute('type') === 'password';
        input.setAttribute('type', asText ? 'text' : 'password');
        button.textContent = asText ? 'Ẩn' : 'Hiện';
    });
});

// File upload preview
document.querySelectorAll('input[type="file"][data-preview-target]').forEach((input) => {
    input.addEventListener('change', () => {
        const targetId = input.getAttribute('data-preview-target');
        const target = targetId ? document.getElementById(targetId) : null;

        if (!target) {
            return;
        }

        target.innerHTML = '';
        if (input.files.length === 0) {
            return;
        }
        Array.from(input.files || []).forEach((file) => {
            const row = document.createElement('div');
            row.className = 'f2f-file-item';
            row.textContent = `${file.name} (${Math.ceil(file.size / 1024)} KB) — đã chọn`;
            target.appendChild(row);
        });
    });
});

// Organization registration infrastructure items
const coldChainWrapper = document.getElementById('cold-chain-wrapper');
const sensorWrapper = document.getElementById('sensor-wrapper');

function buildItem(prefix, idx, label, fields) {
    const item = document.createElement('div');
    item.className = 'f2f-item';

    const title = document.createElement('div');
    title.className = 'f2f-item-head';

    const name = document.createElement('strong');
    name.textContent = `${label} #${idx + 1}`;
    title.appendChild(name);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'f2f-item-remove';
    remove.textContent = 'Xóa';
    remove.addEventListener('click', () => item.remove());
    title.appendChild(remove);

    const grid = document.createElement('div');
    grid.className = 'f2f-grid-two';
    grid.style.marginTop = '10px';
    fields.forEach(([key, placeholder]) => {
        const field = document.createElement('input');
        field.className = 'f2f-input';
        field.name = `${prefix}[${idx}][${key}]`;
        field.placeholder = placeholder;
        grid.appendChild(field);
    });

    item.appendChild(title);
    item.appendChild(grid);
    return item;
}

const COLD_CHAIN_FIELDS = [
    ['name', 'Tên kho / tủ đông / phương tiện'],
    ['type', 'Loại (kho lạnh, xe đông lạnh...)'],
    ['identifier', 'Mã định danh thiết bị'],
    ['description', 'Mô tả thêm (sức chứa, tuyến...)'],
];

const SENSOR_FIELDS = [
    ['name', 'Tên cảm biến / GPS tracker'],
    ['type', 'Loại (nhiệt độ, độ ẩm, GPS...)'],
    ['serial', 'Serial / IMEI hoặc định danh'],
    ['description', 'Mô tả vị trí lắp đặt'],
];

function itemCount(wrapper) {
    return wrapper ? wrapper.querySelectorAll('.f2f-item').length : 0;
}

function removePlaceholder(wrapper) {
    wrapper?.querySelector('.f2f-empty')?.remove();
}

document.querySelector('[data-add-cold-chain]')?.addEventListener('click', () => {
    if (!coldChainWrapper) return;
    removePlaceholder(coldChainWrapper);
    const idx = itemCount(coldChainWrapper);
    coldChainWrapper.appendChild(buildItem('cold_chain_items', idx, 'Hạ tầng lạnh', COLD_CHAIN_FIELDS));
});

document.querySelector('[data-add-sensor]')?.addEventListener('click', () => {
    if (!sensorWrapper) return;
    removePlaceholder(sensorWrapper);
    const idx = itemCount(sensorWrapper);
    sensorWrapper.appendChild(buildItem('sensors', idx, 'Cảm biến/GPS', SENSOR_FIELDS));
});

// ==========================================
// FARMING LOGS: DYNAMIC SUPPLIES & HARVEST LOT
// ==========================================
const btnAddSupply = document.getElementById('btn-add-supply');
const suppliesWrapper = document.getElementById('supplies-wrapper');
const supplyTemplate = document.getElementById('supply-item-template');
const suppliesEmpty = document.getElementById('supplies-empty-placeholder');

function setupSupplyRow(row) {
    const select = row.querySelector('.supply-select');
    const unitInput = row.querySelector('.supply-unit');
    const phiInput = row.querySelector('.supply-phi');
    const removeBtn = row.querySelector('.btn-remove-supply');
    const hint = row.querySelector('.supply-phi-hint');

    select?.addEventListener('change', () => {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption) {
            const unit = selectedOption.getAttribute('data-unit');
            const minPhi = parseInt(selectedOption.getAttribute('data-phi') || '0', 10);
            if (unit && unitInput) unitInput.value = unit;
            if (phiInput) {
                phiInput.min = minPhi;
                if (!phiInput.value || parseInt(phiInput.value, 10) < minPhi) {
                    phiInput.value = minPhi;
                }
            }
            if (hint) {
                hint.textContent = `Thời gian cách ly tối thiểu: ${minPhi} ngày`;
            }
        }
    });

    phiInput?.addEventListener('input', () => {
        const minPhi = parseInt(phiInput.min || '0', 10);
        if (parseInt(phiInput.value, 10) < minPhi) {
            phiInput.setCustomValidity(`Thời gian cách ly không được nhỏ hơn mức tối thiểu ${minPhi} ngày của vật tư.`);
        } else {
            phiInput.setCustomValidity('');
        }
    });

    removeBtn?.addEventListener('click', () => {
        row.remove();
        if (suppliesWrapper && suppliesWrapper.querySelectorAll('.supply-item').length === 0) {
            if (suppliesEmpty) {
                suppliesWrapper.appendChild(suppliesEmpty);
            }
        }
    });
}

// Bind to existing rows
document.querySelectorAll('.supply-item').forEach(setupSupplyRow);

btnAddSupply?.addEventListener('click', () => {
    if (!suppliesWrapper || !supplyTemplate) return;
    suppliesEmpty?.remove();

    const idx = suppliesWrapper.querySelectorAll('.supply-item').length;
    const clone = supplyTemplate.content.cloneNode(true);
    const item = clone.querySelector('.supply-item');

    // Replace [INDEX] in innerHTML
    item.innerHTML = item.innerHTML.replaceAll('[INDEX]', idx);
    setupSupplyRow(item);
    suppliesWrapper.appendChild(item);
});

// Toggle harvest section when activity is THU_HOACH
const activitySelect = document.getElementById('loai_hoat_dong');
const harvestSection = document.getElementById('harvest-section');

function checkHarvestActivity() {
    if (!activitySelect || !harvestSection) return;
    if (activitySelect.value === 'THU_HOACH') {
        harvestSection.style.display = 'block';
    } else {
        harvestSection.style.display = 'none';
    }
}

// Live Quarantine PHI Check on Farming Log Form
const seasonSelect = document.getElementById('mua_vu_id');
const seasonQuarantineWarning = document.getElementById('season-quarantine-warning');
const logSubmitBtn = document.querySelector('#farming-log-form button[type="submit"]');

function updateLogQuarantineState() {
    if (!seasonSelect) return;
    const opt = seasonSelect.options[seasonSelect.selectedIndex];
    if (!opt || !opt.value) {
        if (seasonQuarantineWarning) seasonQuarantineWarning.style.display = 'none';
        return;
    }
    const isQuarantine = opt.getAttribute('data-dang-cach-ly') === '1';
    const safeDate = opt.getAttribute('data-ngay-het-cach-ly');
    const remaining = opt.getAttribute('data-con-lai');
    const supplyName = opt.getAttribute('data-vat-tu');
    const isHarvest = activitySelect && activitySelect.value === 'THU_HOACH';

    if (isQuarantine && seasonQuarantineWarning) {
        seasonQuarantineWarning.style.display = 'block';
        seasonQuarantineWarning.innerHTML = `<strong>CẢNH BÁO CÁCH LY PHI:</strong> Mùa vụ này đang trong thời gian cách ly ${supplyName ? 'thuốc BVTV / phân bón (' + supplyName + ')' : ''}. Ngày kết thúc cách ly an toàn: <strong>${safeDate}</strong>. Nghiêm cấm thu hoạch trước ngày này!`;
    } else if (seasonQuarantineWarning) {
        seasonQuarantineWarning.style.display = 'none';
    }

    if (isQuarantine && isHarvest && logSubmitBtn) {
        logSubmitBtn.disabled = true;
        logSubmitBtn.title = 'Mùa vụ đang trong thời gian cách ly - Không thể thu hoạch';
    } else if (logSubmitBtn) {
        logSubmitBtn.disabled = false;
        logSubmitBtn.title = '';
    }
}

seasonSelect?.addEventListener('change', updateLogQuarantineState);
activitySelect?.addEventListener('change', () => {
    checkHarvestActivity();
    updateLogQuarantineState();
});
checkHarvestActivity();
updateLogQuarantineState();

// Live Quarantine PHI Check on Harvest Lot Form
const lotSeasonSelect = document.querySelector('form[action*="lots"] #mua_vu_id');
const lotQuarantineWarning = document.getElementById('lot-quarantine-warning');
const lotSubmitBtn = document.querySelector('form[action*="lots"] button[type="submit"]');

function updateLotQuarantineState() {
    if (!lotSeasonSelect) return;
    const opt = lotSeasonSelect.options[lotSeasonSelect.selectedIndex];
    if (!opt || !opt.value) {
        if (lotQuarantineWarning) lotQuarantineWarning.style.display = 'none';
        return;
    }
    const isQuarantine = opt.getAttribute('data-dang-cach-ly') === '1';
    const safeDate = opt.getAttribute('data-ngay-het-cach-ly');
    const remaining = opt.getAttribute('data-con-lai');
    const supplyName = opt.getAttribute('data-vat-tu');

    if (isQuarantine && lotQuarantineWarning) {
        lotQuarantineWarning.style.display = 'block';
        lotQuarantineWarning.innerHTML = `<strong>CẢNH BÁO CÁCH LY:</strong> Mùa vụ này đang trong thời gian cách ly thuốc BVTV ${supplyName ? '(' + supplyName + ')' : ''}. Ngày an toàn: <strong>${safeDate}</strong>. Không được phép tạo lô thu hoạch lúc này!`;
        if (lotSubmitBtn) {
            lotSubmitBtn.disabled = true;
            lotSubmitBtn.title = 'Mùa vụ đang cách ly - không thể thu hoạch';
        }
    } else {
        if (lotQuarantineWarning) lotQuarantineWarning.style.display = 'none';
        if (lotSubmitBtn) {
            lotSubmitBtn.disabled = false;
            lotSubmitBtn.title = '';
        }
    }
}

lotSeasonSelect?.addEventListener('change', updateLotQuarantineState);
updateLotQuarantineState();
