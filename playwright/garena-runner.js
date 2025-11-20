import fs from "node:fs";
import path from "node:path";
import { chromium } from "@playwright/test";
import "dotenv/config";

const username = process.env.GARENA_USERNAME;
const accountId = process.env.GARENA_ACCOUNT_ID || username;
const password = process.env.GARENA_PASSWORD;
const newPassword = process.env.GARENA_NEW_PASSWORD || "Password#2025";
const headless = process.env.PLAYWRIGHT_HEADLESS !== "false";
const timezone = process.env.PLAYWRIGHT_TIMEZONE || "Asia/Ho_Chi_Minh";
const locale = process.env.PLAYWRIGHT_LOCALE || "vi-VN";

if (!username || !password) {
    console.error("Thiếu GARENA_USERNAME hoặc GARENA_PASSWORD trong môi trường.");
    process.exit(1);
}

if (!passwordMeetsPolicy(newPassword)) {
    console.error(
        "[Garena] Mật khẩu mới không hợp lệ. Yêu cầu 8-16 ký tự và bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt."
    );
    process.exit(1);
}

const loginInputSelector =
    'input[placeholder="Tài khoản Garena, Email hoặc số điện thoại"]';
const passwordInputSelector = 'input[placeholder="Mật khẩu"]';
const oldPasswordSelector = "#J-form-curpwd";
const newPasswordSelector = "#J-form-newpwd";
const confirmPasswordSelector = 'input[placeholder="Xác nhận Mật khẩu mới"]';
const submitButtonRole = { name: /thay/i };

const randomInt = (min, max) =>
    Math.floor(Math.random() * (max - min + 1)) + min;
const humanPause = (min = 350, max = 1100) =>
    new Promise((resolve) => setTimeout(resolve, randomInt(min, max)));
const randomItem = (arr) => arr[randomInt(0, arr.length - 1)];

const userAgents = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
];

const viewportOptions = [
    { width: 1366, height: 768 },
    { width: 1440, height: 900 },
    { width: 1536, height: 864 },
    { width: 1920, height: 1080 },
];

async function humanMouseMove(page) {
    const steps = randomInt(5, 15);
    for (let i = 0; i < steps; i++) {
        await page.mouse.move(randomInt(0, 1200), randomInt(0, 600), {
            steps: randomInt(2, 5),
        });
        await humanPause(80, 180);
    }
}

async function humanScroll(page, distance = 600) {
    const parts = randomInt(2, 4);
    const chunk = Math.ceil(distance / parts);
    for (let i = 0; i < parts; i++) {
        await page.mouse.wheel(0, chunk + randomInt(-40, 40));
        await humanPause(120, 240);
    }
}

async function humanType(page, selector, text, options = { allowTypos: true }) {
    await page.click(selector);
    await humanPause(180, 420);

    for (let i = 0; i < text.length; i++) {
        const char = text[i];

        if (options.allowTypos && i > 1 && randomInt(1, 18) === 1) {
            await page.keyboard.press("Backspace");
            await humanPause(70, 180);
        }

        await page.keyboard.type(char, { delay: randomInt(90, 230) });
    }
}

async function typeExact(page, selector, text) {
    const input = page.locator(selector);
    await input.click();
    const currentValue = await input.inputValue();
    if (currentValue) {
        await page.keyboard.press("End");
        for (let i = 0; i < currentValue.length; i++) {
            await page.keyboard.press("Backspace");
            await humanPause(60, 140);
        }
    }
    await humanPause(120, 240);
    await page.keyboard.type(text, { delay: randomInt(90, 230) });
}

async function run() {
    const profileDir = path.join(
        process.cwd(),
        "storage",
        "playwright-profile",
        Buffer.from(accountId).toString("hex")
    );
    fs.mkdirSync(profileDir, { recursive: true });

    const context = await chromium.launchPersistentContext(profileDir, {
        headless,
        viewport: randomItem(viewportOptions),
        timezoneId: timezone,
        locale,
        userAgent: randomItem(userAgents),
        permissions: ["geolocation"],
        bypassCSP: true,
        deviceScaleFactor: randomInt(1, 2),
        args: [
            "--disable-blink-features=AutomationControlled",
            "--no-default-browser-check",
            "--disable-site-isolation-trials",
        ],
    });

    const page = context.pages()[0] ?? (await context.newPage());
    await page.addInitScript(() => {
        Object.defineProperty(navigator, "webdriver", { get: () => undefined });
        window.chrome = { runtime: {} };
        Object.defineProperty(navigator, "languages", {
            get: () => ["vi-VN", "en-US"],
        });
    });

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
    await humanMouseMove(page);
    await humanPause();

    console.log("[Garena] B2: Điền form đăng nhập");
    await humanType(page, loginInputSelector, username);
    await humanPause(400, 800);
    await humanType(page, passwordInputSelector, password);
    await humanPause(800, 1500);

    console.log("[Garena] B3: Nhấn Đăng Nhập");
    await page.locator('button:has-text("Đăng Nhập Ngay")').click();
    await humanPause(1000, 2000);
    await retryLoginIfNeeded(page, username, password);

    console.log("[Garena] B4: Chờ Account Center tải xong");
    await page.waitForSelector('text=Trang chủ', { timeout: 30000 });
    await humanPause(600, 1200);
    await humanScroll(page, 400);

    console.log("[Garena] B5: Mở form đổi mật khẩu từ trang chủ");
    const changePasswordButton = page.locator('text=Thay đổi Mật khẩu').first();
    await changePasswordButton.click();
    await humanPause(1000, 1800);
    await page.waitForSelector(oldPasswordSelector, { timeout: 20000 });

    console.log("[Garena] B6: Điền form đổi mật khẩu");
    await typeExact(page, oldPasswordSelector, password);
    await humanPause(400, 700);
    await typeExact(page, newPasswordSelector, newPassword);
    await humanPause(350, 650);
    await typeExact(page, confirmPasswordSelector, newPassword);

    console.log("[Garena] B7: Nhấn THAY ĐỔI (submit)");
    const submitButton = page.getByRole("button", submitButtonRole);
    await submitButton.waitFor({ timeout: 15000 });
    await submitButton.scrollIntoViewIfNeeded();
    await submitButton.click();
    await humanPause(1500, 2500);

    const successMessage = "Bạn đã đổi mật khẩu thành công.";
    const verificationSelectors = [
        "text=Xác minh thiết bị",
        "text=Device Verification",
        "text=Thiết bị",
    ];

    try {
        await page.waitForSelector(`text=${successMessage}`, { timeout: 10000 });
        console.log(`[Garena] ${successMessage}`);
    } catch (_) {
        for (const selector of verificationSelectors) {
            try {
                if (await page.locator(selector).first().isVisible()) {
                    throw new Error(
                        "[Garena] Garena yêu cầu xác minh thiết bị sau khi đổi mật khẩu. Vui lòng hoàn tất bước xác minh thủ công."
                    );
                }
            } catch {
                // selector not found, ignore
            }
        }

        throw new Error(
            "[Garena] Không thấy thông báo đổi mật khẩu thành công. Vui lòng kiểm tra lại trang Garena."
        );
    }

    console.log("[Garena] Kết thúc, đợi thêm trước khi đóng.");
    await page.waitForTimeout(5000);

    await context.close();
}

run().catch((error) => {
    console.error("[Garena Playwright] Lỗi:", error);
    process.exit(1);
});

async function retryLoginIfNeeded(page, username, password) {
    const loginSuccessSelector = 'text=Trang chủ';
    try {
        await page.waitForSelector(loginSuccessSelector, { timeout: 8000 });
        return;
    } catch (_) {
        console.log("[Garena] Login không thành công, gõ lại chính xác.");
    }

    await typeExact(page, loginInputSelector, username);
    await humanPause(200, 400);
    await typeExact(page, passwordInputSelector, password);
    await humanPause(200, 400);
    await page.locator('button:has-text("Đăng Nhập Ngay")').click();
    await page.waitForSelector(loginSuccessSelector, { timeout: 15000 });
}

function passwordMeetsPolicy(value) {
    if (!value || value.length < 8 || value.length > 16) {
        return false;
    }

    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasDigit = /\d/.test(value);
    const hasSpecial = /[^A-Za-z0-9]/.test(value);

    return hasUpper && hasLower && hasDigit && hasSpecial;
}
