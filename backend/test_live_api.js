const http = require('http');

async function testLoginAndCurriculum() {
  const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

  // 1. Solve challenge
  const initialHtml = await new Promise((resolve, reject) => {
    http.get('http://bsla.infy.click/backend/api/index.php?route=auth/login', { headers: { 'User-Agent': userAgent } }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  const matchA = initialHtml.match(/a=toNumbers\("([0-9a-f]+)"\)/);
  const matchB = initialHtml.match(/b=toNumbers\("([0-9a-f]+)"\)/);
  const matchC = initialHtml.match(/c=toNumbers\("([0-9a-f]+)"\)/);

  const aesCode = await new Promise((resolve, reject) => {
    http.get('http://bsla.infy.click/aes.js', { headers: { 'User-Agent': userAgent } }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  const vm = require('vm');
  const sandbox = { location: { href: '' }, document: { cookie: '' }, window: {} };
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

  // 2. Post login
  const postData = JSON.stringify({ username: 'coordinator', password: 'password123' });
  const loginRes = await new Promise((resolve, reject) => {
    const req = http.request('http://bsla.infy.click/backend/api/index.php?route=auth/login', {
      method: 'POST',
      headers: {
        'User-Agent': userAgent,
        'Cookie': `__test=${testCookie}`,
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    });
    req.on('error', reject);
    req.write(postData);
    req.end();
  });

  console.log("Login Response:\n", loginRes);
  const loginJson = JSON.parse(loginRes);
  const token = loginJson.data?.token;

  if (!token) {
    console.log("No token obtained!");
    return;
  }

  // 3. Fetch coordinator/curriculum
  const currRes = await new Promise((resolve, reject) => {
    http.get('http://bsla.infy.click/backend/api/index.php?route=coordinator/curriculum', {
      headers: {
        'User-Agent': userAgent,
        'Cookie': `__test=${testCookie}`,
        'Authorization': `Bearer ${token}`
      }
    }, (res) => {
      let data = '';
      console.log("Curriculum HTTP Status:", res.statusCode, res.headers);
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });

  console.log("Curriculum Response Raw:\n", currRes);
  const currJson = JSON.parse(currRes);
  console.log("Subjects count:", currJson.data?.subjects?.length);
  console.log("Tracks count:", currJson.data?.tracks?.length);
  console.log("Strands count:", currJson.data?.strands?.length);

  // 4. Fetch coordinator/sections
  const secRes = await new Promise((resolve, reject) => {
    http.get('http://bsla.infy.click/backend/api/index.php?route=coordinator/sections', {
      headers: {
        'User-Agent': userAgent,
        'Cookie': `__test=${testCookie}`,
        'Authorization': `Bearer ${token}`
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
  console.log("Sections Response Raw:\n", secRes);
  const secJson = JSON.parse(secRes);
  console.log("Sections count:", secJson.data?.sections?.length || secJson.data?.length);

  // 5. Test SMTP dispatch
  const smtpPayload = JSON.stringify({
    type: 'registration',
    recipient_email: 'jerjerkings09@gmail.com',
    first_name: 'Juan',
    last_name: 'Dela Cruz'
  });
  const smtpRes = await new Promise((resolve, reject) => {
    const sReq = http.request('http://bsla.infy.click/backend/api/index.php?route=auth/test-smtp', {
      method: 'POST',
      headers: {
        'User-Agent': userAgent,
        'Cookie': `__test=${testCookie}`,
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(smtpPayload)
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    });
    sReq.on('error', reject);
    sReq.write(smtpPayload);
    sReq.end();
  });
  console.log("Live SMTP Dispatch Response:\n", smtpRes);
}

testLoginAndCurriculum().catch(console.error);
