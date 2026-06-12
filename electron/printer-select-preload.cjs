const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('printerAPI', {
    getPrinters: () => ipcRenderer.invoke('get-printers'),
    confirm:     (name) => ipcRenderer.send('printer-confirmed', name),
    cancel:      ()     => ipcRenderer.send('printer-confirmed', null),
});
