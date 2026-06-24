const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('urlConfig', {
  getCurrent: ()    => ipcRenderer.sendSync('url-config:get'),
  save:       (url) => ipcRenderer.send('url-config:save', url),
  cancel:     ()    => ipcRenderer.send('url-config:cancel'),
});
