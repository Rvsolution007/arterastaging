const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('[AI-') || text.includes('[AUTO-') || text.includes('solveFlexLayout')) {
            console.log('BROWSER LOG:', text);
        }
    });

    await page.goto('http://localhost/Artera/admin/custom-post', { waitUntil: 'networkidle2' });

    console.log('--- Initial Load Complete ---');

    await page.evaluate(() => {
        const c = window.fCanvas;
        const objs = c.getObjects();
        const getObj = (name) => objs.find(o => (o.text || '').includes(name) || (o.name || '') === name);
        
        const title = getObj('Sale Banner');
        const desc = getObj('Special Discount');
        const icon = objs.find(o => o.type !== 'textbox' && o.type !== 'i-text' && o.top > 300); // A shape/image below text
        
        console.log('Initial Tops:');
        console.log('- Title Y:', title ? title.top : 'not found');
        console.log('- Desc Y:', desc ? desc.top : 'not found');
        console.log('- Icon/Shape Y:', icon ? icon.top : 'not found');

        // Force a massive text to trigger flex layout & compression & shrink
        if (title) {
            title.set('text', 'HUGE TEXT THAT WILL FORCE COMPRESSION\nAND SHRINKING BECAUSE IT IS VERY VERY LONG\nLINE 3\nLINE 4\nLINE 5');
            title.initDimensions();
            window.solveFlexLayout();
            
            console.log('\nAfter Massive Text (Flex Layout triggered):');
            console.log('- Title Y:', title.top, ' FontSize:', title.fontSize, ' Height:', title.getScaledHeight ? title.getScaledHeight() : title.height);
            console.log('- Desc Y:', desc ? desc.top : 'not found');
            console.log('- Icon/Shape Y:', icon ? icon.top : 'not found');
        }
    });

    await page.screenshot({ path: 'test_flex_layout.png' });
    console.log('Screenshot saved to test_flex_layout.png');

    await browser.close();
})();
