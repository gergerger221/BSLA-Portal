const https = require('https');
const http = require('http');

async function solveAndFetch(targetUrl) {
  const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
  
  // Step 1: Initial GET
  const initialHtml = await new Promise((resolve, reject) => {
    http.get(targetUrl, { headers: { 'User-Agent': userAgent } }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  // Extract a, b, c from script
  const matchA = initialHtml.match(/a=toNumbers\("([0-9a-f]+)"\)/);
  const matchB = initialHtml.match(/b=toNumbers\("([0-9a-f]+)"\)/);
  const matchC = initialHtml.match(/c=toNumbers\("([0-9a-f]+)"\)/);

  if (!matchA || !matchB || !matchC) {
    console.log("No challenge found, direct response:\n", initialHtml);
    return;
  }

  // Fetch aes.js
  const aesCode = await new Promise((resolve, reject) => {
    http.get('http://bsla.infy.click/aes.js', { headers: { 'User-Agent': userAgent } }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  // Evaluate challenge using VM or eval
  const vm = require('vm');
  const sandbox = {
    location: { href: '' },
    document: { cookie: '' },
    window: {}
  };
  vm.createContext(sandbox);
  vm.runInContext(aesCode, sandbox);
  
  const challengeCode = `
    function toNumbers(d){var e=[];d.replace(/(..)/g,function(d){e.push(parseInt(d,16))});return e}
    function toHex(){for(var d=[],d=1==arguments.length&&arguments[0].constructor==Array?arguments[0]:arguments,e="",f=0;f<d.length;f++)e+=(16>d[f]?"0":"")+d[f].toString(16);return e.toLowerCase()}
    var a=toNumbers("${matchA[1]}"),b=toNumbers("${matchB[1]}"),c=toNumbers("${matchC[1]}");
    cookieVal = toHex(slowAES.decrypt(c,2,a,b));
  `;
  vm.runInContext(challengeCode, sandbox);
  const testCookie = sandbox.cookieVal;
  console.log("Calculated __test cookie:", testCookie);

  // Step 2: Request targetUrl with __test cookie
  const responseData = await new Promise((resolve, reject) => {
    http.get(targetUrl, {
      headers: {
        'User-Agent': userAgent,
        'Cookie': `__test=${testCookie}`
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  console.log("Target Response:\n", responseData);
}

const url = process.argv[2] || 'http://bsla.infy.click/backend/db_status.php';
solveAndFetch(url).catch(console.error);
