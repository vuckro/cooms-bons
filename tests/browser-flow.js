/* Executed by browser_checks.py in a private Playwright CLI session with fake video. */
async (page, fixture) => {
  page.on("dialog", (dialog) =>
    dialog.type() === "beforeunload" ? dialog.accept() : dialog.dismiss(),
  );
  const base = fixture.base;
  const captures = "output/browser/";
  const checks = [];
  const assert = (ok, name) => {
    if (!ok) throw new Error(name);
    checks.push(name);
  };
  const settle = async () => {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(800);
  };
  const capture = async (name) => {
    await settle();
    await page.screenshot({ path: captures + name + ".png" });
  };
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base + "/?cooms_scan=1");
  await capture("cooms11-scanner-mobile");
  assert(
    await page.locator("[data-start]").isVisible(),
    "Scanner has an explicit camera action",
  );
  assert(
    !(await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    )),
    "No scanner page overflow at 390px",
  );
  await page.setViewportSize({ width: 320, height: 740 });
  await capture("cooms11-scanner-320");
  assert(
    !(await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    )),
    "No scanner page overflow at 320px",
  );
  await page.setViewportSize({ width: 768, height: 1024 });
  await capture("cooms11-scanner-tablet");
  await page.setViewportSize({ width: 390, height: 844 });

  // Observe, without replacing, the real getUserMedia used by the shipped scanner.
  await page.addInitScript(() => {
    const real = navigator.mediaDevices.getUserMedia.bind(
      navigator.mediaDevices,
    );
    let tracks = [];
    navigator.mediaDevices.getUserMedia = async (constraints) => {
      const stream = await real(constraints);
      tracks = stream.getTracks();
      return stream;
    };
    addEventListener("pagehide", () => {
      sessionStorage.setItem(
        "cooms-camera-test-tracks",
        JSON.stringify(tracks.map((t) => t.readyState)),
      );
    });
  });
  await page.goto(base + "/?cooms_scan=1");
  await page
    .getByRole("button", { name: "Ouvrir la caméra", exact: true })
    .click();
  await page
    .getByRole("heading", { name: "Bon valide", exact: true })
    .waitFor({ timeout: 15000 });
  assert(
    page.url().includes(fixture.bons[7].token),
    "Real QR decoder reads the exported PNG from virtual camera",
  );
  assert(
    await page.evaluate(() =>
      JSON.parse(sessionStorage.getItem("cooms-camera-test-tracks")).every(
        (s) => s === "ended",
      ),
    ),
    "Camera tracks ended before voucher page opened",
  );
  await capture("cooms11-camera-result");
  await page
    .getByRole("button", { name: "Valider la remise du cadeau", exact: true })
    .click();
  await page
    .getByRole("heading", { name: "Remise enregistrée", exact: true })
    .waitFor();
  await capture("cooms11-confirmed-mobile");
  await page
    .getByRole("link", { name: "Scanner le bon suivant", exact: true })
    .click();
  await page
    .getByRole("heading", { name: "Scanner un bon", exact: true })
    .waitFor();
  assert(
    await page
      .getByRole("button", { name: "Ouvrir la caméra", exact: true })
      .isVisible(),
    "Next voucher action returns to scanner",
  );
  await page
    .getByRole("textbox", { name: "Référence du bon", exact: true })
    .fill(fixture.bons[8].code.toLowerCase());
  await page
    .getByRole("button", { name: "Vérifier le bon", exact: true })
    .click();
  await page
    .getByRole("heading", { name: "Bon valide", exact: true })
    .waitFor();
  assert(
    page.url().includes(fixture.bons[8].token),
    "Manual reference opens the matching voucher",
  );
  await page.goto(base + "/?cooms_bon=" + fixture.bons[7].token);
  assert(
    await page
      .getByRole("heading", { name: "Bon déjà utilisé", exact: true })
      .isVisible(),
    "Fresh lookup after confirmation is used",
  );
  await capture("cooms11-used-mobile");

  // Error states use independent contexts; no real camera permission is requested.
  for (const [name, init, expected] of [
    [
      "denied",
      () => {
        navigator.mediaDevices.getUserMedia = async () => {
          throw new DOMException("denied", "NotAllowedError");
        };
      },
      "Accès à la caméra refusé",
    ],
    [
      "missing",
      () => {
        navigator.mediaDevices.getUserMedia = async () => {
          throw new DOMException("missing", "NotFoundError");
        };
      },
      "Aucune caméra détectée",
    ],
    [
      "insecure",
      () => {
        Object.defineProperty(window, "isSecureContext", { value: false });
      },
      "connexion HTTPS",
    ],
  ]) {
    const context = await page
      .context()
      .browser()
      .newContext({
        storageState: await page.context().storageState(),
        viewport: { width: 390, height: 844 },
      });
    const errorPage = await context.newPage();
    await errorPage.addInitScript(init);
    await errorPage.goto(base + "/?cooms_scan=1");
    await errorPage
      .getByRole("button", { name: "Ouvrir la caméra", exact: true })
      .click();
    await errorPage.getByRole("status").filter({ hasText: expected }).waitFor();
    assert(
      await errorPage
        .getByRole("textbox", { name: "Référence du bon", exact: true })
        .isVisible(),
      "Manual fallback remains usable: " + name,
    );
    await errorPage.screenshot({
      path: captures + "cooms11-camera-" + name + ".png",
    });
    await context.close();
  }

  // Cancellation of a pending permission request must also stop a late stream.
  const context = await page
    .context()
    .browser()
    .newContext({
      storageState: await page.context().storageState(),
      viewport: { width: 390, height: 844 },
    });
  const cancel = await context.newPage();
  await cancel.addInitScript(() => {
    window.__cameraTrackStopped = false;
    navigator.mediaDevices.getUserMedia = () =>
      new Promise((resolve) => {
        window.__resolveCamera = () =>
          resolve({
            getTracks: () => [
              {
                stop: () => {
                  window.__cameraTrackStopped = true;
                },
              },
            ],
          });
      });
  });
  await cancel.goto(base + "/?cooms_scan=1");
  await cancel
    .getByRole("button", { name: "Ouvrir la caméra", exact: true })
    .click();
  assert(await cancel.locator('.cb-camera-controls').evaluate(e => {
    const buttons=[...e.querySelectorAll('button')].filter(b=>!b.hidden).map(b=>b.getBoundingClientRect());
    return buttons.length===2 && buttons[1].top>=buttons[0].bottom && Math.abs(buttons[0].width-buttons[1].width)<1;
  }), 'Pending camera buttons stack vertically at full width');
  await cancel.screenshot({path:captures+'cooms16-camera-pending.png',fullPage:true});
  await cancel
    .getByRole("button", { name: "Annuler l’ouverture", exact: true })
    .click();
  await cancel.evaluate(() => window.__resolveCamera());
  await cancel.waitForFunction(() => window.__cameraTrackStopped);
  assert(
    await cancel
      .getByRole("button", { name: "Ouvrir la caméra", exact: true })
      .isEnabled(),
    "Cancelled late permission stream is stopped and can retry",
  );
  await context.close();

  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto(base + "/wp-admin/admin.php?page=cooms-bons");
  await capture("cooms11-lots");
  await page.goto(
    base + "/wp-admin/admin.php?page=cooms-bons&lot=" + fixture.lot,
  );
  await capture("cooms11-lot-detail");
  await page.goto(base + "/wp-admin/admin.php?page=cooms-bons&view=bons");
  await capture("cooms11-bons");
  await page.goto(base + "/wp-admin/admin.php?page=cooms-bons-new");
  await capture("cooms11-new");
  await page.setViewportSize({ width: 390, height: 844 });
  await capture("cooms11-new-mobile");
  assert(
    !(await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    )),
    "No creation page overflow at 390px",
  );
  await page.goto(base + "/wp-admin/admin.php?page=cooms-bons");
  await capture("cooms11-lots-mobile");
  assert(
    !(await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    )),
    "Table overflow remains local on mobile",
  );
  await page.goto(base + "/wp-admin/admin.php?page=cooms-bons-staff");
  await capture("cooms11-staff-mobile");
  assert(
    !(await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    )),
    "No employee management page overflow at 390px",
  );
  return { passed: checks.length, checks };
};
