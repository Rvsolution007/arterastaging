const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const jsonPath = 'C:/xampp/htdocs/Artera/public/uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed/json/Hiring_101.json';
const zipPath = 'C:/xampp/htdocs/Artera/public/uploads/custom_frames_zips/50e71b01-fa59-4672-a4e3-9fef53984aed.zip';
const extractDir = 'C:/xampp/htdocs/Artera/public/uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed';

if(fs.existsSync(jsonPath)) {
    let data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
    
    function fixLayerPaths(arr) {
        for (let i = 0; i < arr.length; i++) {
            if (arr[i].src) {
                let parts = arr[i].src.split('/');
                let filename = parts.pop();
                // Replace spaces with hyphens because Photoshop Export creates hyphenated filenames
                filename = filename.replace(/\s+/g, '-'); 
                parts.push(filename);
                arr[i].src = parts.join('/');
            }
            if (arr[i].children) {
                fixLayerPaths(arr[i].children);
            }
        }
    }
    
    fixLayerPaths(data.layers);
    
    fs.writeFileSync(jsonPath, JSON.stringify(data, null, 2));
    console.log('Fixed JSON inside template dir');
    
    try {
        if(fs.existsSync(zipPath)) fs.unlinkSync(zipPath); // delete old zip
        execSync(`powershell Compress-Archive -Path "${extractDir}/*" -DestinationPath "${zipPath}" -Force`);
        console.log('Successfully re-zipped for native app');
    } catch(e) {
        console.error('Failed to zip:', e);
    }
}
