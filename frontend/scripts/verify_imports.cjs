const fs = require('fs');
const path = require('path');

const srcDir = path.resolve(__dirname, '../src');
const commonGlobals = [
  { name: 'useRoute', source: 'vue-router' },
  { name: 'useRouter', source: 'vue-router' },
  { name: 'ref', source: 'vue' },
  { name: 'reactive', source: 'vue' },
  { name: 'computed', source: 'vue' },
  { name: 'watch', source: 'vue' },
  { name: 'onMounted', source: 'vue' },
  { name: 'onUnmounted', source: 'vue' },
  { name: 'nextTick', source: 'vue' }
];

let errors = [];

function scanDirectory(dir) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    if (stat.isDirectory()) {
      scanDirectory(fullPath);
    } else if (file.endsWith('.vue') || file.endsWith('.js')) {
      checkFile(fullPath);
    }
  }
}

function checkFile(filePath) {
  const content = fs.readFileSync(filePath, 'utf8');
  let scriptContent = content;

  if (filePath.endsWith('.vue')) {
    const scriptMatch = content.match(/<script[\s\S]*?>([\s\S]*?)<\/script>/);
    if (!scriptMatch) return;
    scriptContent = scriptMatch[1];
  }

  for (const { name, source } of commonGlobals) {
    const callRegex = new RegExp(`\\b${name}\\s*\\(`, 'g');
    if (callRegex.test(scriptContent)) {
      const importRegex = new RegExp(`import\\s+[\\s\\S]*?\\b${name}\\b[\\s\\S]*?from\\s+['"]${source}['"]`);
      if (!importRegex.test(scriptContent)) {
        errors.push({
          file: path.relative(path.resolve(__dirname, '..'), filePath),
          symbol: name,
          expectedModule: source
        });
      }
    }
  }
}

console.log('🔍 Running Pre-Build Static Analysis for Missing Imports...');
scanDirectory(srcDir);

if (errors.length > 0) {
  console.error('\n❌ BUILD FAILED: Found missing import(s) that would crash the browser at runtime:');
  errors.forEach(e => {
    console.error(`   - ${e.file}: [${e.symbol}] is invoked but not imported from '${e.expectedModule}'`);
  });
  console.error('\nPlease import the missing symbols before building.\n');
  process.exit(1);
} else {
  console.log('✅ All Vue & Vue-Router hooks and symbols are properly imported!\n');
}
