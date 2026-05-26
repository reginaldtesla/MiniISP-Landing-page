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
const jsEntry = manifest['resources/js/portal-theme.js']?.file;
const announcementsEntry = manifest['resources/js/portal-announcements.js']?.file;
const customCalculatorEntry = manifest['resources/js/portal-custom-calculator.js']?.file;

if (!cssEntry || !jsEntry) {
    console.error('Portal entries not found in Vite manifest.');
    process.exit(1);
}

fs.mkdirSync(fontsDir, { recursive: true });

for (const file of fs.readdirSync(buildAssetsDir)) {
    if (/\.(woff2?|ttf|eot)$/i.test(file)) {
        fs.copyFileSync(path.join(buildAssetsDir, file), path.join(fontsDir, file));
    }
}

let css = fs.readFileSync(path.join(root, 'public/build', cssEntry), 'utf8');
css = css.replace(/url\(\/build\/assets\//g, 'url(fonts/');
fs.writeFileSync(path.join(targetDir, 'portal.css'), css);

fs.copyFileSync(path.join(root, 'public/build', jsEntry), path.join(targetDir, 'portal-theme.js'));

if (announcementsEntry) {
    fs.copyFileSync(
        path.join(root, 'public/build', announcementsEntry),
        path.join(targetDir, 'portal-announcements.js'),
    );
}

if (customCalculatorEntry) {
    fs.copyFileSync(
        path.join(root, 'public/build', customCalculatorEntry),
        path.join(targetDir, 'portal-custom-calculator.js'),
    );
}

console.log(`Offline bundle ready: ${targetDir}`);
console.log(`Fonts copied: ${fs.readdirSync(fontsDir).length} files`);
