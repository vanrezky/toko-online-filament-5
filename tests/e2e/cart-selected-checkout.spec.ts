import { expect, test } from "@playwright/test";

test("customer checks out all selected Cart items through the single checkout action", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email").fill("vanrezkytest@gmail.com");
    await page.getByRole("textbox", { name: "Kata Sandi" }).fill("Password123@");
    await page.getByRole("button", { name: "Masuk" }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByRole("link", { name: "Akun Saya" })).toBeVisible();

    await page.goto("/products");
    const productLinks = page.locator("main article > a");
    await expect.poll(() => productLinks.count()).toBeGreaterThanOrEqual(2);

    const productUrls = await productLinks.evaluateAll((links) => links.map((link) => link.getAttribute("href")).filter(Boolean));

    expect(productUrls.length).toBeGreaterThanOrEqual(2);

    for (const productUrl of productUrls.sort(() => Math.random() - 0.5).slice(0, 2)) {
        await page.goto(productUrl!);
        const addToCartResponse = page.waitForResponse((response) => response.url().endsWith("/cart/add") && response.request().method() === "POST");
        await page.getByRole("button", { name: "Tambah ke Keranjang" }).click();
        await addToCartResponse;
    }

    await page.goto("/cart");
    const itemCheckboxes = page.getByRole("checkbox", { name: "Pilih produk ini" });
    const cartItemCount = await itemCheckboxes.count();

    expect(cartItemCount).toBeGreaterThanOrEqual(2);

    await page.getByRole("checkbox", { name: "Pilih semua produk" }).check();
    await expect(itemCheckboxes).toHaveCount(cartItemCount);
    await expect.poll(() => itemCheckboxes.evaluateAll((items) => items.every((item) => item.checked))).toBe(true);

    const checkoutButton = page.getByRole("button", { name: "Checkout produk terpilih" });
    await expect(checkoutButton).toHaveCount(1);
    await expect(checkoutButton).toBeEnabled();
    await checkoutButton.click();

    await expect(page).toHaveURL(/\/checkout\?.*cart_item_ids/);

    const submittedItemIds = [...new URL(page.url()).searchParams.keys()].filter((key) => key.startsWith("cart_item_ids"));
    expect(submittedItemIds).toHaveLength(cartItemCount);
});
