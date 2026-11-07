/* Real browser checks for the connected app shell; fake camera only. */
async (page, fixture) => {
  const checks = [];
  const assert = (ok, name) => { if (!ok) throw new Error(name); checks.push(name); };
  const base = fixture.base;
  await page.goto(base + '/?cooms_scan=1');
  const cdp = await page.context().newCDPSession(page);
  const manifest = await cdp.send('Page.getAppManifest');
  assert(manifest.data && JSON.parse(manifest.data).display === 'standalone' && !manifest.errors.some(e => e.critical), 'Chromium parses the install manifest without critical errors');
  await cdp.detach();
  const icon = await page.evaluate(async () => {
    const data = await (await fetch(document.querySelector('link[rel=manifest]').href)).json();
    const image = await createImageBitmap(await (await fetch(data.icons[1].src)).blob());
    return [image.width, image.height];
  });
  assert(icon[0] === 512 && icon[1] === 512, 'Install icon decodes at 512px');
  const help = page.locator('[data-install-help]');
  await help.locator('summary').press('Enter');
  assert(await help.locator('div').isVisible(), 'Installation help opens with the keyboard');
  await page.getByLabel('Référence du bon', {exact:true}).fill('INVALID');
  await page.getByRole('button', {name:'Vérifier le bon',exact:true}).click();
  await page.locator('.cb-scanner > .cb-error').waitFor();
  assert(await page.locator('.cb-scanner > .cb-error').evaluate(error=>{
    const a=error.getBoundingClientRect(),b=document.querySelector('.cb-camera-section').getBoundingClientRect();
    return Math.abs(a.left-b.left)<1&&Math.abs(a.width-b.width)<1;
  }),'Invalid-reference feedback aligns with the camera panel');
  await page.context().setOffline(true);
  await page.waitForFunction(() => !navigator.onLine);
  await page.locator('[data-connection]').waitFor();
  assert(await page.locator('[data-start]').isDisabled() && await page.locator('[data-manual-form] button').isDisabled(), 'Offline scanner actions are blocked');
  let posts = 0;
  const countPosts = request => { if (request.method() === 'POST') posts++; };
  page.on('request', countPosts);
  await page.getByLabel('Référence du bon', { exact: true }).fill(fixture.bons[10].code);
  await page.evaluate(() => document.querySelector('[data-manual-form]').requestSubmit());
  assert(posts === 0 && !(await page.locator('[data-manual-form]').getAttribute('aria-busy')), 'Offline manual submit is prevented before the loading handler');
  await page.context().setOffline(false);
  await page.locator('[data-connection-refresh]').waitFor();
  assert(await page.locator('[data-start]').isDisabled(), 'Network restoration still requires server refresh');
  await page.locator('[data-connection-refresh]').click();
  await page.waitForLoadState();
  assert(await page.locator('[data-start]').isEnabled(), 'Explicit refresh restores connected scanner');
  const bonUrl = base + '/?cooms_bon=' + fixture.bons[10].token;
  await page.goto(bonUrl);
  await page.context().setOffline(true);
  await page.locator('[data-connection]').waitFor();
  assert(await page.locator('.cb-redeem').isDisabled() && await page.locator('h1').innerText() === 'Vérification en pause', 'Offline voucher cannot be redeemed or show a fresh valid verdict');
  await page.evaluate(() => document.querySelector('[data-cb-submit]').requestSubmit());
  assert(posts === 0, 'Offline redemption produces no POST');
  await page.context().setOffline(false);
  await page.locator('[data-connection-refresh]').click();
  await page.getByRole('heading', {name:'Bon valide', exact:true}).waitFor();
  assert(await page.locator('.cb-redeem').isEnabled(), 'Online refresh retrieves fresh available state');
  await Promise.all([
    page.waitForEvent('load'),
    page.evaluate(() => {
      Object.defineProperty(document, 'hidden', {configurable:true, value:true});
      document.dispatchEvent(new Event('visibilitychange'));
      Object.defineProperty(document, 'hidden', {configurable:true, value:false});
      document.dispatchEvent(new Event('visibilitychange'));
    }),
  ]);
  assert(await page.locator('.cb-redeem').isEnabled(), 'Return from background refreshes voucher session/state');
  let navigations = 0;
  const countNavigations = request => { if(request.isNavigationRequest()) navigations++; };
  page.on('request', countNavigations);
  await page.evaluate(() => {
    document.querySelector('form[data-cb-submit]').setAttribute('aria-busy','true');
    Object.defineProperty(document,'hidden',{configurable:true,value:true});document.dispatchEvent(new Event('visibilitychange'));
    Object.defineProperty(document,'hidden',{configurable:true,value:false});document.dispatchEvent(new Event('visibilitychange'));
  });
  await page.waitForTimeout(150);
  assert(navigations === 0 && posts === 0, 'Background recovery never replays a pending operation');
  page.off('request', countNavigations);page.off('request', countPosts);

  const standalone = await page.context().browser().newContext({storageState:await page.context().storageState(), viewport:{width:390,height:844}});
  const standalonePage = await standalone.newPage();
  await standalonePage.addInitScript(() => Object.defineProperty(navigator,'standalone',{value:true}));
  await standalonePage.goto(base+'/?cooms_scan=1');
  assert(await standalonePage.locator('[data-install-help]').isHidden(), 'Installed iOS shell hides redundant installation help');
  await standalone.close();

  const cameraContext = await page.context().browser().newContext({storageState:await page.context().storageState(),viewport:{width:390,height:844}});
  const cameraPage = await cameraContext.newPage();
  await cameraPage.addInitScript(() => {
    const canvas=document.createElement('canvas');canvas.width=32;canvas.height=32;
    canvas.getContext('2d').fillRect(0,0,32,32);
    const stream=canvas.captureStream(1);window.__offlineTracks=stream.getTracks();
    navigator.mediaDevices.getUserMedia=async()=>stream;
  });
  await cameraPage.goto(base+'/?cooms_scan=1');
  await cameraPage.locator('[data-start]').click();
  await cameraPage.locator('[data-video]').waitFor({state:'visible'});
  await cameraPage.waitForFunction(()=>document.querySelector('[data-video]').readyState>=2);
  assert(await cameraPage.locator('[data-video]').evaluate(video=>{const a=video.getBoundingClientRect(),b=video.parentElement.getBoundingClientRect();return Math.abs(a.width-b.width)<1&&Math.abs(a.height-b.height)<1;}),'Active camera video fills the preview without shifting its geometry');
  await cameraContext.setOffline(true);
  await cameraPage.waitForFunction(()=>window.__offlineTracks.every(t=>t.readyState==='ended'));
  assert(await cameraPage.locator('[data-start]').isDisabled(), 'Losing connection stops camera tracks and blocks restart');
  await cameraContext.close();

  for(const [width,height] of [[320,740],[390,844],[768,1024],[844,390],[1024,768],[1280,720],[1440,780],[1440,1000],[1920,1080]]) {
    await page.setViewportSize({width,height});await page.goto(base+'/?cooms_scan=1');
    const layout=await page.evaluate(()=>{
      const panel=document.querySelector('.cb-camera-section').getBoundingClientRect();
      const view=document.querySelector('.cb-viewfinder').getBoundingClientRect();
      const action=document.querySelector('[data-start]').getBoundingClientRect();
      const label=document.querySelector('.cb-camera-idle strong').getBoundingClientRect();
      return {overflow:document.documentElement.scrollWidth>innerWidth,button:action.bottom,aligned:Math.abs(view.left-action.left)<1 && Math.abs(view.width-action.width)<1,centered:Math.abs((label.left+label.right-panel.left-panel.right)/2)<1};
    });
    assert(!layout.overflow && (height<600 || layout.button<=height), 'Scanner layout and main action fit '+width+'x'+height);
    assert(layout.aligned && layout.centered, 'Camera preview and idle text align with the panel '+width+'x'+height);
  }
  // Controlled host token contract: CI does not need AdminKit installed.
  const host = await page.context().browser().newContext({storageState:await page.context().storageState()});
  const hostPage = await host.newPage();
  await hostPage.route('**/wp-admin/admin.php?page=cooms-bons-new', async route => {
    const response=await route.fetch();
    const body=(await response.text()).replace('class="wp-admin','class="adminkit wp-admin').replace('</head>','<style>:root{--ak-primary:#e73523;--ak-on-accent:#f2f2f2;--ak-primary-hover:#f86050;--ak-on-accent-hover:#fff}:root[data-adminkit-theme="dark"]{--ak-primary:#ffc000;--ak-on-accent:#fff}</style></head>');
    await route.fulfill({response,body});
  });
  await hostPage.goto(base+'/wp-admin/admin.php?page=cooms-bons-new');
  const ratio = () => {
    const s=getComputedStyle(document.querySelector('.cb-app .cb-button'));
    const luminance=color=>color.match(/[\d.]+/g).slice(0,3).map(v=>{v=Number(v)/255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0);
    const a=luminance(s.backgroundColor),b=luminance(s.color);
    return (Math.max(a,b)+.05)/(Math.min(a,b)+.05);
  };
  assert(await hostPage.evaluate(ratio)>=4.5,'Unsafe host accent receives a locally accessible primary background');
  await hostPage.locator('.cb-app .cb-button').hover();
  assert(await hostPage.evaluate(ratio)>=4.5,'Primary hover preserves readable host foreground');
  await hostPage.mouse.move(0,0);
  await hostPage.evaluate(()=>document.documentElement.setAttribute('data-adminkit-theme','dark'));
  await hostPage.waitForFunction(()=>document.querySelector('.cb-app').style.getPropertyValue('--cb-button-bg').includes('rgb'));
  // Wait for the mutation-observer animation frame, then sample the final palette.
  await hostPage.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  assert(await hostPage.evaluate(ratio)>=4.5,'Theme switching recalibrates the local button contrast');
  await hostPage.evaluate(()=>document.documentElement.style.setProperty('--ak-primary','#16539b'));
  await hostPage.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  assert(await hostPage.locator('.cb-app .cb-button').evaluate(e=>getComputedStyle(e).backgroundColor)==='rgb(22, 83, 155)','Already accessible host accents remain exact');
  await host.close();
  return {passed:checks.length,checks};
};
