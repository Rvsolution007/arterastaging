const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: "new" });
    const page = await browser.newPage();
    
    // Capture console logs
    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    page.on('requestfailed', request => console.log('REQUEST FAILED:', request.url(), request.failure().errorText));

    await page.goto('http://localhost/brandkit/edit/category/6?design=http%3A%2F%2Flocalhost%2Fbrandkit%2Fpublic%2Fuploads%2Fcf826f0f-fada-48fc-a1f2-5ba2ac2d0589.jpg', { waitUntil: 'networkidle0' });
    
    console.log("Page loaded.");
    await browser.close();
})();
