/**
 * ThermalPrinter — Centralized printing module for POS Web App
 * Supports: USB (WebUSB), Bluetooth (Web Bluetooth), Browser Print (window.print)
 */
const ThermalPrinter = (() => {
    // ─── State ───
    let _usbDevice = null;
    let _usbEndpoint = null;
    let _btCharacteristic = null;
    const CHUNK_SIZE = 100; // Safe chunk size for both USB & BT
    const CHUNK_DELAY = 50; // ms delay between chunks

    // ─── Feature Detection ───
    const isUSBSupported = () => !!navigator.usb;
    const isBluetoothSupported = () => !!navigator.bluetooth;

    // ─── Preference (localStorage) ───
    const PREF_KEY = 'pos_printer_preference';
    const getSavedPreference = () => localStorage.getItem(PREF_KEY); // 'usb' | 'bluetooth' | 'browser' | null
    const savePreference = (type) => localStorage.setItem(PREF_KEY, type);

    // ─── USB Printing (WebUSB API) ───
    async function connectUSB() {
        if (!isUSBSupported()) throw new Error('Browser tidak mendukung WebUSB. Gunakan Chrome atau Edge.');

        // If already connected, reuse
        if (_usbDevice && _usbDevice.opened) {
            return _usbDevice;
        }

        const device = await navigator.usb.requestDevice({
            filters: [
                { classCode: 7 }, // Printer class
            ]
        });

        await device.open();

        // Select configuration (usually config 1)
        if (device.configuration === null) {
            await device.selectConfiguration(1);
        }

        // Find the printer interface & claim it
        let printerInterface = null;
        let endpointOut = null;

        for (const iface of device.configuration.interfaces) {
            for (const alt of iface.alternates) {
                // Class 7 = Printer
                if (alt.interfaceClass === 7) {
                    printerInterface = iface;
                    // Find OUT endpoint
                    for (const ep of alt.endpoints) {
                        if (ep.direction === 'out') {
                            endpointOut = ep;
                            break;
                        }
                    }
                    break;
                }
            }
            if (printerInterface) break;
        }

        // Fallback: if no class-7 interface, try vendor-specific or first interface with OUT endpoint
        if (!printerInterface) {
            for (const iface of device.configuration.interfaces) {
                for (const alt of iface.alternates) {
                    for (const ep of alt.endpoints) {
                        if (ep.direction === 'out') {
                            printerInterface = iface;
                            endpointOut = ep;
                            break;
                        }
                    }
                    if (endpointOut) break;
                }
                if (endpointOut) break;
            }
        }

        if (!printerInterface || !endpointOut) {
            await device.close();
            throw new Error('Tidak ditemukan interface printer pada perangkat USB ini.');
        }

        await device.claimInterface(printerInterface.interfaceNumber);

        _usbDevice = device;
        _usbEndpoint = endpointOut.endpointNumber;

        console.log(`USB Printer connected: ${device.productName || 'Unknown'}`);
        return device;
    }

    async function printUSB(data) {
        if (!_usbDevice || !_usbDevice.opened) {
            await connectUSB();
        }

        const bytes = (data instanceof Uint8Array) ? data : new TextEncoder().encode(data);

        // Send in chunks
        for (let i = 0; i < bytes.byteLength; i += CHUNK_SIZE) {
            const chunk = bytes.slice(i, i + CHUNK_SIZE);
            await _usbDevice.transferOut(_usbEndpoint, chunk);
            await _delay(CHUNK_DELAY);
        }

        console.log('USB Print: Data sent successfully');
    }

    async function disconnectUSB() {
        if (_usbDevice && _usbDevice.opened) {
            await _usbDevice.close();
            console.log('USB Printer disconnected');
        }
        _usbDevice = null;
        _usbEndpoint = null;
    }

    // ─── Bluetooth Printing (Web Bluetooth API) ───
    const BT_SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
    const BT_CHAR_UUID = '00002af1-0000-1000-8000-00805f9b34fb';

    async function connectBluetooth() {
        if (!isBluetoothSupported()) throw new Error('Browser tidak mendukung Web Bluetooth.');

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: [BT_SERVICE_UUID]
        });

        const server = await device.gatt.connect();
        const service = await server.getPrimaryService(BT_SERVICE_UUID);
        _btCharacteristic = await service.getCharacteristic(BT_CHAR_UUID);

        console.log(`Bluetooth Printer connected: ${device.name || 'Unknown'}`);
        return device;
    }

    async function printBluetooth(data) {
        if (!_btCharacteristic) {
            await connectBluetooth();
        }

        const bytes = (data instanceof Uint8Array) ? data : new TextEncoder().encode(data);

        for (let i = 0; i < bytes.byteLength; i += CHUNK_SIZE) {
            const chunk = bytes.slice(i, i + CHUNK_SIZE);
            await _btCharacteristic.writeValue(chunk);
            await _delay(CHUNK_DELAY);
        }

        console.log('Bluetooth Print: Data sent successfully');
    }

    // ─── Browser Print Fallback ───
    function printBrowser(receiptHTML) {
        const printArea = document.getElementById('print-area');
        if (!printArea) {
            // Create print area if it doesn't exist
            const div = document.createElement('div');
            div.id = 'print-area';
            div.className = 'hidden';
            document.body.appendChild(div);
        }

        const area = document.getElementById('print-area');
        area.innerHTML = receiptHTML;

        const cleanup = () => {
            area.innerHTML = '';
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        window.print();
    }

    // ─── ESC/POS Receipt Generator ───
    function generateReceipt(tx, storeSettings, cashierName) {
        const ESC = '\x1B', GS = '\x1D';
        const isUtang = tx.payment_method === 'utang';
        const paymentLabels = {
            'cash': 'Tunai', 'utang': 'UTANG', 'card': 'Kartu',
            'ewallet': 'E-Wallet', 'transfer': 'Transfer', 'qris': 'QRIS'
        };

        const fmtRp = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);

        let r = '';
        r += ESC + '@';                          // Initialize
        r += ESC + 'a' + '\x01';                 // Center
        r += ESC + '!' + '\x18';                 // Double height+width
        r += `${storeSettings.store_name}\n`;
        r += ESC + '!' + '\x00';                 // Normal
        r += `${storeSettings.store_address}\n`;
        r += `Telp: ${storeSettings.store_phone}\n`;
        r += '================================\n';

        r += ESC + 'a' + '\x00';                 // Left
        r += `No: ${tx.transaction_code}\n`;

        const txDate = new Date(tx.created_at || Date.now());
        r += `Tgl: ${txDate.toLocaleDateString('id-ID')} ${txDate.toLocaleTimeString('id-ID')}\n`;
        r += `Kasir: ${cashierName}\n`;

        if (tx.customer_name && tx.customer_name !== 'Umum') {
            r += `Customer: ${tx.customer_name}\n`;
        }
        r += '================================\n';

        // Items
        const items = tx.items || [];
        items.forEach(item => {
            const name = item.product ? item.product.name : (item.name || 'Item');
            const price = parseFloat(item.price);
            const qty = parseInt(item.quantity);
            r += `${name}\n`;
            r += `  ${qty} x ${fmtRp(price)} = ${fmtRp(qty * price)}\n`;
        });

        r += '================================\n';
        r += ESC + 'a' + '\x02';                 // Right

        r += `Subtotal: ${fmtRp(tx.subtotal)}\n`;
        if (parseFloat(tx.discount) > 0) r += `Diskon: -${fmtRp(tx.discount)}\n`;
        if (parseFloat(tx.tax) > 0) r += `Pajak: ${fmtRp(tx.tax)}\n`;

        r += `Total: ${fmtRp(tx.total_amount)}\n`;
        r += `Metode: ${paymentLabels[tx.payment_method] || tx.payment_method}\n`;

        if (!isUtang) {
            r += `Bayar: ${fmtRp(tx.amount_paid)}\n`;
            r += `Kembali: ${fmtRp(tx.change_amount)}\n`;
        }

        r += ESC + 'a' + '\x01';                 // Center
        if (isUtang) {
            r += '--------------------------------\n';
            r += ESC + '!' + '\x08';             // Bold
            r += '** BELUM DIBAYAR - PIUTANG **\n';
            r += ESC + '!' + '\x00';             // Normal
        }
        r += '================================\n';

        if (storeSettings.store_description) {
            r += storeSettings.store_description + '\n\n\n';
        } else {
            r += 'Terima kasih!\n\n\n';
        }

        r += GS + 'V' + '\x41' + '\x03';        // Cut paper

        return new TextEncoder().encode(r);
    }

    // ─── Browser Receipt HTML Generator ───
    function generateReceiptHTML(tx, storeSettings, cashierName) {
        const fmtRp = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
        const isUtang = tx.payment_method === 'utang';
        const paymentLabels = {
            'cash': '💵 Tunai', 'utang': '📝 UTANG', 'card': '💳 Kartu',
            'ewallet': '📱 E-Wallet', 'transfer': '🏦 Transfer', 'qris': '📲 QRIS'
        };
        const txDate = new Date(tx.created_at || Date.now());
        const items = tx.items || [];

        return `
        <div style="font-family: 'Courier New', monospace; font-size: 11px; width: 280px; padding: 10px; color: black;">
            <div style="text-align: center;">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 4px;">${storeSettings.store_name}</div>
                <div>${storeSettings.store_address}</div>
                <div>Telp: ${storeSettings.store_phone}</div>
            </div>
            <div style="border-top: 1px dashed black; margin: 8px 0;"></div>
            <table style="width: 100%; font-size: 11px;">
                <tr><td>No</td><td>: ${tx.transaction_code}</td></tr>
                <tr><td>Tgl</td><td>: ${txDate.toLocaleDateString('id-ID')} ${txDate.toLocaleTimeString('id-ID')}</td></tr>
                <tr><td>Kasir</td><td>: ${cashierName}</td></tr>
                ${tx.customer_name && tx.customer_name !== 'Umum' ? `<tr><td>Customer</td><td>: ${tx.customer_name}</td></tr>` : ''}
            </table>
            <div style="border-top: 1px dashed black; margin: 8px 0;"></div>
            <div>
                ${items.map(item => {
            const name = item.product ? item.product.name : (item.name || 'Item');
            return `
                    <div style="margin-bottom: 5px;">
                        <div>${name}</div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>&nbsp;&nbsp;${item.quantity} x ${fmtRp(item.price)}</span>
                            <span>${fmtRp(item.quantity * item.price)}</span>
                        </div>
                    </div>`;
        }).join('')}
            </div>
            <div style="border-top: 1px dashed black; margin: 8px 0;"></div>
            <div style="text-align: right;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: bold;">Total:</span>
                    <span style="font-weight: bold;">${fmtRp(tx.total_amount)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Metode:</span>
                    <span>${paymentLabels[tx.payment_method] || tx.payment_method}</span>
                </div>
                ${parseFloat(tx.discount) > 0 ? `
                <div style="display: flex; justify-content: space-between;">
                    <span>Diskon:</span><span>-${fmtRp(tx.discount)}</span>
                </div>` : ''}
                ${!isUtang ? `
                <div style="display: flex; justify-content: space-between;">
                    <span>Bayar:</span><span>${fmtRp(tx.amount_paid)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Kembali:</span><span>${fmtRp(tx.change_amount)}</span>
                </div>` : ''}
            </div>
            ${isUtang ? `
            <div style="border-top: 1px dashed black; margin: 8px 0;"></div>
            <div style="text-align: center; font-weight: bold; padding: 8px; background: #f0f0f0; border: 1px dashed black;">
                ⚠️ BELUM DIBAYAR - PIUTANG
            </div>` : ''}
            <div style="border-top: 1px dashed black; margin: 8px 0;"></div>
            <div style="text-align: center; margin-top: 10px;">
                ${storeSettings.store_description ? storeSettings.store_description.replace(/\n/g, '<br>') : 'Terima kasih!'}
            </div>
        </div>`;
    }

    // ─── Print Method Picker Dialog ───
    function showPrintDialog(onSelect) {
        // Remove existing dialog if any
        const existing = document.getElementById('thermal-print-dialog');
        if (existing) existing.remove();

        const supportsUSB = isUSBSupported();
        const supportsBT = isBluetoothSupported();

        const overlay = document.createElement('div');
        overlay.id = 'thermal-print-dialog';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

        overlay.innerHTML = `
        <div style="background:white;border-radius:16px;padding:24px;width:360px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:24px;margin-bottom:8px;">🖨️</div>
                <h3 style="font-size:18px;font-weight:bold;margin:0 0 4px 0;">Pilih Printer</h3>
                <p style="font-size:13px;color:#666;margin:0;">Pilih metode cetak struk</p>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                ${supportsUSB ? `
                <button onclick="ThermalPrinter._dialogSelect('usb')" style="display:flex;align-items:center;gap:12px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:12px;background:white;cursor:pointer;transition:all 0.2s;font-size:14px;" onmouseover="this.style.borderColor='#6366f1';this.style.background='#f5f3ff'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background='white'">
                    <span style="font-size:24px;">🔌</span>
                    <div style="text-align:left;">
                        <div style="font-weight:600;">Printer USB</div>
                        <div style="font-size:12px;color:#888;">Langsung cetak via kabel USB</div>
                    </div>
                </button>` : ''}
                ${supportsBT ? `
                <button onclick="ThermalPrinter._dialogSelect('bluetooth')" style="display:flex;align-items:center;gap:12px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:12px;background:white;cursor:pointer;transition:all 0.2s;font-size:14px;" onmouseover="this.style.borderColor='#3b82f6';this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background='white'">
                    <span style="font-size:24px;">📶</span>
                    <div style="text-align:left;">
                        <div style="font-weight:600;">Printer Bluetooth</div>
                        <div style="font-size:12px;color:#888;">Cetak via koneksi Bluetooth</div>
                    </div>
                </button>` : ''}
                <button onclick="ThermalPrinter._dialogSelect('browser')" style="display:flex;align-items:center;gap:12px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:12px;background:white;cursor:pointer;transition:all 0.2s;font-size:14px;" onmouseover="this.style.borderColor='#10b981';this.style.background='#ecfdf5'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background='white'">
                    <span style="font-size:24px;">📄</span>
                    <div style="text-align:left;">
                        <div style="font-weight:600;">Print Browser</div>
                        <div style="font-size:12px;color:#888;">Via dialog print browser (semua browser)</div>
                    </div>
                </button>
            </div>
            <div style="margin-top:16px;text-align:center;">
                <label style="font-size:12px;color:#888;cursor:pointer;">
                    <input type="checkbox" id="tp-remember" style="margin-right:4px;">
                    Ingat pilihan saya
                </label>
            </div>
            <button onclick="ThermalPrinter._dialogClose()" style="width:100%;margin-top:12px;padding:10px;border:none;border-radius:10px;background:#f3f4f6;color:#666;font-size:14px;cursor:pointer;font-weight:500;">Batal</button>
        </div>`;

        document.body.appendChild(overlay);

        // Store callback
        ThermalPrinter._dialogCallback = onSelect;
    }

    // ─── Smart Print ───
    async function print(receiptBytes, receiptHTML) {
        const pref = getSavedPreference();

        if (pref) {
            try {
                await _printWith(pref, receiptBytes, receiptHTML);
                return;
            } catch (e) {
                console.warn(`Preferred method "${pref}" failed:`, e.message);
                // Fall through to dialog
            }
        }

        // Show picker dialog
        return new Promise((resolve, reject) => {
            showPrintDialog(async (method) => {
                try {
                    await _printWith(method, receiptBytes, receiptHTML);
                    resolve();
                } catch (e) {
                    alert('Cetak gagal: ' + e.message);
                    reject(e);
                }
            });
        });
    }

    async function _printWith(method, receiptBytes, receiptHTML) {
        switch (method) {
            case 'usb':
                await printUSB(receiptBytes);
                _showToast('✅ Struk dikirim ke printer USB');
                break;
            case 'bluetooth':
                await printBluetooth(receiptBytes);
                _showToast('✅ Struk dikirim ke printer Bluetooth');
                break;
            case 'browser':
                printBrowser(receiptHTML);
                break;
            default:
                throw new Error('Metode print tidak valid');
        }
    }

    // ─── USB Test Print ───
    async function testPrintUSB() {
        const ESC = '\x1B', GS = '\x1D';
        let data = ESC + '@';
        data += ESC + 'a' + '\x01';
        data += ESC + '!' + '\x18';
        data += 'TEST PRINT\n';
        data += ESC + '!' + '\x00';
        data += '================================\n';
        data += 'Printer USB terhubung!\n';
        data += 'Tanggal: ' + new Date().toLocaleString('id-ID') + '\n';
        data += '================================\n\n\n';
        data += GS + 'V' + '\x41' + '\x03';

        await printUSB(new TextEncoder().encode(data));
    }

    // ─── Internal Helpers ───
    function _delay(ms) {
        return new Promise(r => setTimeout(r, ms));
    }

    function _showToast(message) {
        // Use SweetAlert if available, otherwise native
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, timer: 2000, showConfirmButton: false });
        } else {
            alert(message);
        }
    }

    function _dialogSelect(method) {
        const remember = document.getElementById('tp-remember');
        if (remember && remember.checked) {
            savePreference(method);
        }
        _dialogClose();
        if (ThermalPrinter._dialogCallback) {
            ThermalPrinter._dialogCallback(method);
        }
    }

    function _dialogClose() {
        const dialog = document.getElementById('thermal-print-dialog');
        if (dialog) dialog.remove();
    }

    // ─── Public API ───
    return {
        // Methods
        connectUSB, printUSB, disconnectUSB, testPrintUSB,
        connectBluetooth, printBluetooth,
        printBrowser,
        print,
        showPrintDialog,

        // Generators
        generateReceipt, generateReceiptHTML,

        // Utils
        isUSBSupported, isBluetoothSupported,
        getSavedPreference, savePreference,

        // Internal (used by dialog inline handlers)
        _dialogSelect, _dialogClose, _dialogCallback: null,
    };
})();
