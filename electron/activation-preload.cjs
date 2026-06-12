const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('activationAPI', {
  getMac:   ()      => ipcRenderer.invoke('activation:get-mac'),
  activate: (key)   => ipcRenderer.invoke('activation:activate', key),
  done:     ()      => ipcRenderer.send('activation:done'),
});
