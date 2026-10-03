const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log("Uploading index.html to /htdocs/index.html...");
const localIndex = path.join(__dirname, '../frontend/dist/index.html');
execSync(`curl.exe -s -T "${localIndex}" -u "if0_43064531:UH8WlzJQ6Ny9QnT" "ftp://ftpupload.net/htdocs/index.html"`, { stdio: 'inherit' });

console.log("Uploading assets to /htdocs/assets/...");
const localAssets = path.join(__dirname, '../frontend/dist/assets');
const assetFiles = fs.readdirSync(localAssets);
for (const f of assetFiles) {
  const fullPath = path.join(localAssets, f);
  if (fs.statSync(fullPath).isFile()) {
    console.log(`Uploading ${f}...`);
    execSync(`curl.exe -s -T "${fullPath}" -u "if0_43064531:UH8WlzJQ6Ny9QnT" "ftp://ftpupload.net/htdocs/assets/${f}"`, { stdio: 'inherit' });
  }
}

console.log("Frontend assets deployed to InfinityFree successfully!");
