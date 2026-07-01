const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const jsonPath = 'C:/xampp/htdocs/Artera/public/uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed/json/Hiring_101.json';
const zipPath = 'C:/xampp/htdocs/Artera/public/uploads/custom_frames_zips/50e71b01-fa59-4672-a4e3-9fef53984aed.zip';
const extractDir = 'C:/xampp/htdocs/Artera/public/uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed';

if(fs.existsSync(jsonPath)) {
    let data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
    
    function fixLayerDimensions(arr) {
        for (let i = 0; i < arr.length; i++) {
            if (arr[i].w !== undefined && arr[i].width === undefined) {
                arr[i].width = arr[i].w;
            }
            if (arr[i].h !== undefined && arr[i].height === undefined) {
                arr[i].height = arr[i].h;
            }
            if (arr[i].children) {
                fixLayerDimensions(arr[i].children);
            }
        }
    }
    
    fixLayerDimensions(data.layers);
    
    fs.writeFileSync(jsonPath, JSON.stringify(data, null, 2));
    console.log('Fixed width/height keys in JSON inside template dir');
    
    try {
        if(fs.existsSync(zipPath)) fs.unlinkSync(zipPath); // delete old zip
        execSync(`powershell Compress-Archive -Path "${extractDir}/*" -DestinationPath "${zipPath}" -Force`);
        console.log('Successfully re-zipped for native app');
    } catch(e) {
        console.error('Failed to zip:', e);
    }
} else {
    console.log("JSON not found at path:", jsonPath);
}
