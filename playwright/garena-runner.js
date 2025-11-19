import fs from "node:fs";
import path from "node:path";
import { chromium } from "@playwright/test";
import "dotenv/config";

const username = process.env.GARENA_USERNAME;
const password = process.env.GARENA_PASSWORD;
const newPassword = process.env.GARENA_NEW_PASSWORD || "Password#2025";
const headless = process.env.PLAYWRIGHT_HEADLESS !== "false";

if (!username || !password) {
    console.error(
        "Thiếu GARENA_USERNAME hoặc GARENA_PASSWORD trong biến môi trường."
    );
    process.exit(1);
}

const randomInt = (min, max) =>
    Math.floor(Math.random() * (max - min + 1)) + min;
const humanPause = (min = 350, max = 1100) =>
    new Promise((resolve) => setTimeout(resolve, randomInt(min, max)));

async function humanType(page, selector, text) {
    await page.click(selector);
    await humanPause(180, 420);

    for (let i = 0; i < text.length; i++) {
        const char = text[i];

        if (i > 1 && randomInt(1, 18) === 1) {
            await page.keyboard.press("Backspace");
            await humanPause(70, 180);
        }

        await page.keyboard.type(char, { delay: randomInt(90, 230) });
    }
}

async function run() {
    const browser = await chromium.launch({ headless });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 },
        userAgent:
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36",
    });
    const page = await context.newPage();

    console.log("[Garena] B1: Mở https://account.garena.com");
    await page.goto("https://account.garena.com", {
        waitUntil: "load",
        timeout: 60000,
    });
    await humanPause(1000, 2000);

    await page.waitForURL("https://sso.garena.com/universal/login*", {
        timeout: 20000,
    });
    await page.evaluate(() => window.scrollTo(0, 120));
    await humanPause();

    console.log("[Garena] B2: Điền form đăng nhập");
    await humanType(
        page,
        'input[placeholder="Tài khoản Garena, Email hoặc số điện thoại"]',
        username
    );
    await humanPause(400, 800);
    await humanType(page, 'input[placeholder="Mật khẩu"]', password);
    await humanPause(800, 1500);

    console.log("[Garena] B3: Nhấn Đăng Nhập");
    await page.locator('button:has-text("Đăng Nhập Ngay")').click();
    await humanPause(1000, 2000);

    console.log("[Garena] B4: Chờ Account Center tải xong");
    await page.waitForSelector('text=Trang chủ', { timeout: 30000 });
    await humanPause(600, 1200);

    console.log("[Garena] B5: Chuyển sang tab Bảo mật");
    await page.locator('text=Bảo mật').first().click();
    await humanPause(1200, 2000);
    await page.waitForSelector('text=ĐỔI MẬT KHẨU', { timeout: 20000 });

    console.log("[Garena] B6: Điền form đổi mật khẩu");
    await humanType(page, 'input[name="old_password"]', password);
    await humanPause(400, 700);
    await humanType(page, 'input[name="new_password"]', newPassword);
    await humanPause(350, 650);
    await humanType(page, 'input[name="confirm_new_password"]', newPassword);

    const screenshotDir = path.join(process.cwd(), "storage", "logs");
    fs.mkdirSync(screenshotDir, { recursive: true });
    const screenshotPath = path.join(
        screenshotDir,
        `garena-change-form-${Date.now()}.png`
    );
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`[Garena] Đã chụp form đổi mật khẩu tại ${screenshotPath}`);

    console.log("[Garena] Đứng yên tại màn hình đổi mật khẩu, KHÔNG nhấn THAY ĐỔI.");
    console.log(`[Garena] Mật khẩu mới dự kiến: ${newPassword}`);
    // await page.locator('button:has-text("THAY ĐỔI")').click();
    await page.waitForTimeout(20000);

    await browser.close();
}

run().catch((error) => {
    console.error("[Garena Playwright] Lỗi:", error);
    process.exit(1);
});
