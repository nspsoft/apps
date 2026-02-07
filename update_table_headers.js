const fs = require('fs');
const path = require('path');

const directory = 'resources/js/Pages';

function getAllFiles(dirPath, arrayOfFiles) {
    const files = fs.readdirSync(dirPath);

    arrayOfFiles = arrayOfFiles || [];

    files.forEach(function (file) {
        if (fs.statSync(dirPath + "/" + file).isDirectory()) {
            arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles);
        } else {
            if (file === 'Index.vue') {
                arrayOfFiles.push(path.join(dirPath, "/", file));
            }
        }
    });

    return arrayOfFiles;
}

const files = getAllFiles(directory);
let updatedCount = 0;

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let originalContent = content;

    // Check if file has a table
    if (!content.includes('<table')) return;

    // Isolate <thead> block
    const theadRegex = /(<thead[^>]*>[\s\S]*?<\/thead>)/;
    const theadMatch = content.match(theadRegex);

    if (theadMatch) {
        let theadBlock = theadMatch[1];
        let originalTheadBlock = theadBlock;
        let blockModified = false;

        // 1. Replace <tr> class inside this block
        // We look for <tr class="..."> inside the block
        // We only replace if it contains bg-slate-50 (header style) AND NOT sticky (already done)
        if (theadBlock.includes('bg-slate-50') && !theadBlock.includes('sticky top-0 z-10')) {
            // The z-10 check is to avoid double replacement if I run it against my previous manual fix (which used z-10 on tr)
            // But my manual fix on Sales/Orders used z-10 on tr. The new script moves it to th.
            // So if I find 'sticky' on TR, I should remove it and add it to TH.
            // But let's simple replace the TR class with the border class.
        }

        // Replace <tr ... class="..."> with new class
        // This regex finds the TR tag and its class attribute
        theadBlock = theadBlock.replace(/(<tr[^>]*)(class="[^"]*")([^>]*>)/, (match, before, classAttr, after) => {
            if (classAttr.includes('bg-slate-50')) {
                blockModified = true;
                return `${before}class="border-b border-slate-200 dark:border-slate-700"${after}`;
            }
            return match;
        });

        // 2. Add sticky class to <th> inside this block
        theadBlock = theadBlock.replace(/<th([^>]*)class="([^"]*)"/g, (match, attrs, classes) => {
            if (classes.includes('sticky')) return match; // Already sticky
            blockModified = true;
            return `<th${attrs}class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm ${classes}"`;
        });

        if (blockModified) {
            content = content.replace(originalTheadBlock, theadBlock);
        }
    }

    if (content !== originalContent) {
        fs.writeFileSync(file, content, 'utf8');
        console.log(`Updated: ${file}`);
        updatedCount++;
    }
});

console.log(`Total files updated: ${updatedCount}`);
