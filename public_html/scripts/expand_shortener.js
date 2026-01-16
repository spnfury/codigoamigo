const puppeteer = require('puppeteer');

async function expandShortener(url, timeout = 10000) {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu'
            ]
        });

        const page = await browser.newPage();

        // Set user agent to look like a real browser
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

        // Navigate to the shortener URL
        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: timeout
        });

        // Wait a bit more to ensure any JavaScript redirects complete
        await page.waitForTimeout(1000);

        const finalUrl = page.url();

        await browser.close();

        return {
            success: true,
            original: url,
            final: finalUrl,
            expanded: finalUrl !== url
        };
    } catch (error) {
        if (browser) {
            await browser.close();
        }
        return {
            success: false,
            original: url,
            error: error.message
        };
    }
}

// CLI interface
if (require.main === module) {
    const url = process.argv[2];
    const timeout = parseInt(process.argv[3]) || 10000;

    if (!url) {
        console.error(JSON.stringify({
            success: false,
            error: 'Usage: node expand_shortener.js <url> [timeout_ms]'
        }));
        process.exit(1);
    }

    expandShortener(url, timeout).then(result => {
        console.log(JSON.stringify(result));
        process.exit(result.success ? 0 : 1);
    }).catch(error => {
        console.error(JSON.stringify({
            success: false,
            error: error.message
        }));
        process.exit(1);
    });
}

module.exports = { expandShortener };
