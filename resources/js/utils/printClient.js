export function isElectronRuntime() {
    return typeof window !== 'undefined' && !!window.electronAPI?.isElectron;
}

export async function getPrinters() {
    if (!isElectronRuntime()) return [];
    return window.electronAPI.getPrinters();
}

export async function printReceipt(printerName, options = {}) {
    if (isElectronRuntime()) {
        return window.electronAPI.printReceipt(printerName, options);
    }
    window.print();
    return { success: true };
}
