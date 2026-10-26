const fs = require('fs');
const path = require('path');

const srcDir = 'd:/Project/HRMS/company_profile/src/components';
const destDir = 'd:/Project/HRMS/web/src/views/public/company';

if (!fs.existsSync(destDir)) fs.mkdirSync(destDir, { recursive: true });

const files = fs.readdirSync(srcDir).filter(f => f.endsWith('.tsx'));

files.forEach(file => {
  const content = fs.readFileSync(path.join(srcDir, file), 'utf-8');
  let match = content.match(/return\s*\(\s*([\s\S]*?)\s*\);?\s*\}\s*$/);
  if (!match) return;
  
  let t = match[1];
  t = t.replace(/className=/g, 'class=');
  t = t.replace(/htmlFor=/g, 'for=');
  t = t.replace(/onClick=\{([^}]+)\}/g, '@click="$1"');
  t = t.replace(/<img([^>]+)\/>/g, '<img$1 />');
  
  // replace open bracket with double brackets
  t = t.replace(/>\s*\{([^}]+)\}\s*</g, '>\n    {{ $1 }}\n  <');

  fs.writeFileSync(path.join(destDir, file.replace('.tsx', '.vue')), `<template>\n${t}\n</template>\n<script setup></script>`);
});

fs.writeFileSync(path.join(destDir, 'company.css'), fs.readFileSync('d:/Project/HRMS/company_profile/src/index.css', 'utf-8'));
console.log('Done');
