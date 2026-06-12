const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
    isElectron: true,

    /** Returns array of { name, displayName, isDefault, status } */
    getPrinters: () => ipcRenderer.invoke('get-printers'),

    /**
     * Print the current page silently.
     * @param {string} printerName  - deviceName from getPrinters(); empty = default printer
     * @param {object} options      - optional overrides (copies, color, etc.)
     */
    printReceipt: (printerName, options = {}) =>
        ipcRenderer.invoke('print-receipt', printerName, options),

    /**
     * Open the native Electron printer-selection modal, then print silently
     * to whichever printer the user picks.
     */
    openPrinterDialog: () => ipcRenderer.invoke('open-printer-dialog'),

    /** Open the license key change dialog from within the app */
    changeLicenseKey: () => ipcRenderer.invoke('activation:change-key'),
});
