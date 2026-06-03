import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(root, 'public/build/manifest.json');
const buildAssetsDir = path.join(root, 'public/build/assets');
const targetDir = path.join(root, 'public/assets/portal');
const fontsDir = path.join(targetDir, 'fonts');

if (!fs.existsSync(manifestPath)) {
    console.error('Run npm run build first (manifest missing).');
    process.exit(1);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const cssEntry = manifest['resources/css/portal.css']?.file;
const jsEntries = {
    'portal-theme.js': manifest['resources/js/portal-theme.js']?.file,
    'portal-announcements.js': manifest['resources/js/portal-announcements.js']?.file,
    'portal-custom-calculator.js': manifest['resources/js/portal-custom-calculator.js']?.file,
    'portal-plan-countdown.js': manifest['resources/js/portal-plan-countdown.js']?.file,
    'portal-live-usage.js': manifest['resources/js/portal-live-usage.js']?.file,
};

if (!cssEntry || !jsEntries['portal-theme.js']) {
    console.error('Portal entries not found in Vite manifest.');
    process.exit(1);
}

fs.mkdirSync(fontsDir, { recursive: true });

/** Copy every font file Vite emitted (Inter, Hanken, JetBrains, Material Symbols). */
for (const file of fs.readdirSync(buildAssetsDir)) {
    if (/\.(woff2?|ttf|eot|otf)$/i.test(file)) {
        fs.copyFileSync(path.join(buildAssetsDir, file), path.join(fontsDir, file));
    }
}

let css = fs.readFileSync(path.join(root, 'public/build', cssEntry), 'utf8');
css = css.replace(/url\(\/build\/assets\//g, 'url(fonts/');
css = css.replace(/url\(["']?\.\/([^"')]+\.(woff2?|ttf))["']?\)/gi, 'url(fonts/$1)');
fs.writeFileSync(path.join(targetDir, 'portal.css'), css);

for (const [targetName, sourceName] of Object.entries(jsEntries)) {
    if (!sourceName) {
        continue;
    }
    fs.copyFileSync(
        path.join(root, 'public/build', sourceName),
        path.join(targetDir, targetName),
    );
}

const fontCount = fs.readdirSync(fontsDir).length;
console.log(`Offline bundle ready: ${targetDir}`);
console.log(`  portal.css + ${Object.keys(jsEntries).filter((k) => jsEntries[k]).length} JS files`);
console.log(`  fonts/: ${fontCount} files (text + Material Symbols icons)`);
