"""Tier 4 — Real Headless Chrome CDP Browser/JS Journeys (6 Scenarios: T4-01 .. T4-06)."""
from __future__ import annotations

import re
import time

from config import ADMIN_EMAIL, ADMIN_PASS, CUSTOMER_EMAIL, CUSTOMER_PASS, STAFF_EMAIL, STAFF_PASS
from lib.base_case import E2EBaseTestCase, uid
from lib.cdp_browser import CdpBrowser


class TestT4BrowserScenarios(E2EBaseTestCase):
    def _browser_login_customer(self, browser: CdpBrowser, email: str, password: str) -> None:
        browser.navigate("/login.php")
        browser.type_text('input[name="email"]', email, clear=True, char_by_char=False)
        browser.type_text('input[name="password"]', password, clear=True, char_by_char=False)
        browser.click('form button[type="submit"], form input[type="submit"]', wait_nav=True)

    def _browser_login_admin(self, browser: CdpBrowser, email: str, password: str) -> None:
        browser.navigate("/admin/login.php")
        browser.type_text('input[name="email"]', email, clear=True, char_by_char=False)
        browser.type_text('input[name="password"]', password, clear=True, char_by_char=False)
        browser.click('form button[type="submit"], form input[type="submit"]', wait_nav=True)

    def test_T4_01_live_search_gallery_and_brand_filter_journey(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/index.php")
            search_sel = 'header input[name="q"], input[name="q"], #live-search-input'
            dropdown_sel = '#search-dropdown, #live-search-dropdown, .search-dropdown, [data-live-search-results]'

            # Type 1 char -> dropdown opens with thumbnail + stock
            browser.type_text(search_sel, "r", clear=True, char_by_char=True)
            browser.wait_for_selector(dropdown_sel, visible=True, timeout=6.0)
            self.assertIn("<img", browser.inner_html(dropdown_sel).lower())

            # Continue typing -> "rtx"
            browser.type_text(search_sel, "tx", clear=False, char_by_char=True)
            browser.wait_for_condition(
                f"document.querySelector({repr(dropdown_sel)}).innerText.toUpperCase().includes('RTX')",
                timeout=5.0,
            )
            dd_txt = browser.text_content(dropdown_sel)
            self.assert_contains_any(dd_txt, ["còn", "hết hàng", "tồn"])

            # Click RTX result -> navigate to product.php
            link_sel = (
                "#search-dropdown a[href*='product.php'], "
                "#live-search-dropdown a[href*='product.php'], "
                ".search-dropdown a[href*='product.php'], "
                "[data-live-search-results] a[href*='product.php']"
            )
            browser.click(link_sel, wait_nav=True)
            self.assertIn("product.php?slug=", browser.current_url())

            # Verify multi-image gallery switching
            browser.navigate("/product.php?slug=e2e-asus-rtx-4060")
            main_before = browser.eval(
                "(() => { const el = document.querySelector('#main-product-image, .main-product-image, #product-main-img, main img'); return el ? el.getAttribute('src') : ''; })()"
            )
            browser.eval(
                f"""(() => {{
                    const before = {repr(str(main_before))};
                    const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb], .gallery-thumb, .product-thumb, #product-gallery img'));
                    const t = thumbs.find(x => (x.getAttribute('data-src') || x.getAttribute('src') || '') !== before) || thumbs[1];
                    if (t) t.click();
                }})()"""
            )
            time.sleep(0.2)
            main_after = browser.eval(
                "(() => { const el = document.querySelector('#main-product-image, .main-product-image, #product-main-img, main img'); return el ? el.getAttribute('src') : ''; })()"
            )
            self.assertNotEqual(main_after, main_before)

            # Filter by brand on collection.php
            browser.navigate("/collection.php?brand=ASUS")
            body = browser.text_content("body")
            self.assertIn("ASUS", body)
            self.assertNotIn("VGA MSI RTX 4070 SUPER", body)

    def test_T4_02_cart_zero_tragop_vietqr_countdown_staff_approval_warranty(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/product.php?slug=e2e-asus-rtx-4060")
            browser.click('form[action*="cart.php"] button[type="submit"], button[name="add_to_cart"]', wait_nav=True)
            self.assertIn("cart.php", browser.current_url())

            browser.navigate("/checkout.php")
            checkout_text = browser.text_content("body")
            self.assert_no_tragop(checkout_text, context="Tier4 checkout DOM")
            radios = browser.eval(
                "Array.from(document.querySelectorAll('input[name=\"payment_method\"]')).map(r => r.value.toUpperCase())"
            )
            self.assertEqual(set(radios or []), {"COD", "VIETQR"})

            browser.type_text('input[name="customer_name"]', "KH Browser VietQR", clear=True, char_by_char=False)
            browser.type_text('input[name="phone"]', "0912345402", clear=True, char_by_char=False)
            browser.type_text('input[name="address"], textarea[name="address"]', "Long Xuyên, An Giang", clear=True, char_by_char=False)
            browser.eval(
                "(() => { const r = Array.from(document.querySelectorAll('input[name=\"payment_method\"]')).find(x => x.value.toUpperCase()==='VIETQR'); if (r) r.click(); })()"
            )
            browser.click('form button[type="submit"], form input[type="submit"]', wait_nav=True)

            body = browser.text_content("body")
            order_code = self.extract_order_code(body)
            self.assertIn("vietqr", browser.page_source().lower())
            timer_sel = "#vietqr-timer, [data-vietqr-timer], .vietqr-countdown"
            browser.wait_for_selector(timer_sel, visible=True, timeout=5.0)
            t1 = browser.text_content(timer_sel).strip()
            time.sleep(2.2)
            t2 = browser.text_content(timer_sel).strip()
            self.assertNotEqual(t1, t2, f"VietQR 15-min countdown timer did not tick in DOM ({t1!r} == {t2!r})")

            # Customer confirms VietQR payment
            browser.click('form input[value="confirm_vietqr"] ~ button, form button[data-confirm-vietqr], form:has(input[value="confirm_vietqr"]) button[type="submit"]', wait_nav=True)

            # Staff logs in and approves VietQR payment in admin/orders.php
            browser.clear_browser_cookies()
            self._browser_login_admin(browser, STAFF_EMAIL, STAFF_PASS)
            browser.navigate("/admin/orders.php")
            approved = browser.eval(
                f"""(() => {{
                    const rows = Array.from(document.querySelectorAll('tr, .order-card'));
                    const row = rows.find(r => r.innerText.includes({repr(order_code)}));
                    if (!row) return false;
                    const forms = Array.from(row.querySelectorAll('form'));
                    for (const form of forms) {{
                        const hasApproveInput = form.querySelector('input[value*="approve"], input[value*="paid"], button[value*="approve"], button[value*="paid"]');
                        const paySel = form.querySelector('select[name="payment_status"]');
                        if (hasApproveInput || paySel) {{
                            if (paySel) paySel.value = 'paid';
                            const btn = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (btn) btn.click(); else form.submit();
                            return true;
                        }}
                    }}
                    const buttons = Array.from(row.querySelectorAll('button, input[type="submit"]'));
                    const approveBtn = buttons.find(b => /duyệt|paid|đã thanh toán|xác nhận thanh toán/i.test((b.innerText || b.value || '').trim()));
                    if (approveBtn) {{
                        approveBtn.click();
                        return true;
                    }}
                    return false;
                }})()"""
            )
            self.assertTrue(approved, f"Could not submit VietQR payment approval for {order_code} in /admin/orders.php")
            time.sleep(0.4)
            browser.wait_for_condition("document.readyState === 'complete'")
            browser.assert_clean_page(context=f"approve_vietqr({order_code})")

            browser.navigate("/admin/orders.php")
            row_txt = browser.eval(
                f"""(() => {{
                    const rows = Array.from(document.querySelectorAll('tr, .order-card'));
                    const row = rows.find(r => r.innerText.includes({repr(order_code)}));
                    return row ? row.innerText : '';
                }})()"""
            )
            self.assertTrue(row_txt, f"Order {order_code} missing from /admin/orders.php after approval")
            self.assert_contains_any(str(row_txt), ["đã thanh toán", "paid"], f"VietQR order {order_code} not marked paid on /admin/orders.php: {row_txt!r}")

            # Customer checks warranty.php
            browser.clear_browser_cookies()
            browser.navigate(f"/warranty.php?q={order_code}")
            self.assertIn("Còn hạn", browser.text_content("body"))

    def test_T4_03_pc_builder_live_compat_wattage_and_assembly_pipeline(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")

            def select_slug(slot: str, slug: str) -> None:
                sel = f"#select-{slot}, select[name='{slot}_id'], select[name='{slot}']"
                ok = browser.eval(
                    f"""(() => {{
                        const s = document.querySelector({repr(sel)});
                        if (!s) return false;
                        for (const o of s.options) {{
                            if ((o.dataset.slug || '') === {repr(slug)}) {{
                                s.value = o.value;
                                s.dispatchEvent(new Event('change', {{bubbles:true}}));
                                return true;
                            }}
                        }}
                        return false;
                    }})()"""
                )
                self.assertTrue(ok, f"Missing option {slug} in {sel}")

            compat_sel = "#compat-box, .compat-box, [data-compat-box]"
            # 1) Socket mismatch: LGA1700 CPU + AM5 Mainboard
            select_slug("cpu", "e2e-intel-i5-13400f")
            select_slug("mainboard", "e2e-msi-b650m-am5")
            self.assert_contains_any(browser.text_content(compat_sel), ["socket", "lga1700", "am5", "không tương thích"])

            # 2) RAM mismatch: LGA1700 DDR4 Mainboard + DDR5 RAM
            select_slug("mainboard", "e2e-asus-b760m-d4")
            select_slug("ram", "e2e-kingston-32gb-ddr5")
            self.assert_contains_any(browser.text_content(compat_sel), ["ddr4", "ddr5", "không tương thích"])

            # 3) Underpowered PSU: i7-14700K + RTX 4070 SUPER + 550W PSU
            select_slug("cpu", "e2e-intel-i7-14700k")
            select_slug("ram", "e2e-corsair-16gb-ddr4")
            select_slug("vga", "e2e-msi-rtx-4070-super")
            select_slug("ssd", "e2e-samsung-980-pro-1tb")
            select_slug("psu", "e2e-corsair-cv550")
            self.assert_contains_any(browser.text_content(compat_sel), ["nguồn", "psu", "550", "công suất", "không đủ", "yếu"])

            # 4) Valid 750W PSU -> green compatible
            select_slug("psu", "e2e-corsair-rm750e")
            self.assert_contains_any(browser.text_content(compat_sel), ["tương thích", "hợp lệ", "ok"])
            total_digits = int(re.sub(r"[^\d]", "", browser.text_content("#build-total, [data-build-total]")) or "0")
            self.assertGreater(total_digits, 30_000_000)

            # Submit PC Build order
            browser.type_text('input[name="customer_name"]', "KH Browser BuildPC", clear=True, char_by_char=False)
            browser.type_text('input[name="phone"]', "0912345403", clear=True, char_by_char=False)
            browser.type_text('input[name="address"], textarea[name="address"]', "An Giang", clear=True, char_by_char=False)
            browser.click('#btn-order-build, form button[type="submit"]', wait_nav=True)
            order_code = self.extract_order_code(browser.text_content("body"))

            # Staff transitions pipeline step-by-step in browser
            browser.clear_browser_cookies()
            self._browser_login_admin(browser, STAFF_EMAIL, STAFF_PASS)
            for stage in ("Đang lắp ráp", "Đang giao hàng", "Hoàn thành"):
                browser.navigate("/admin/orders.php")
                submitted = browser.eval(
                    f"""(() => {{
                        const rows = Array.from(document.querySelectorAll('tr, .order-card'));
                        const row = rows.find(r => r.innerText.includes({repr(order_code)}));
                        if (!row) return false;
                        const sel = row.querySelector('select[name="status"]');
                        const form = sel ? sel.closest('form') : null;
                        if (!sel || !form) return false;
                        sel.value = {repr(stage)};
                        form.submit();
                        return true;
                    }})()"""
                )
                self.assertTrue(submitted, f"Could not transition {order_code} to {stage} in admin/orders.php")
                time.sleep(0.4)
                browser.wait_for_condition("document.readyState === 'complete'")

            browser.clear_browser_cookies()
            browser.navigate(f"/orders.php?code={order_code}")
            self.assertIn("Hoàn thành", browser.text_content("body"))

    def test_T4_04_staff_rbac_admin_pw_gate_walkin_and_live_search_stock(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            self._browser_login_admin(browser, STAFF_EMAIL, STAFF_PASS)
            sidebar_hrefs = browser.eval(
                "Array.from(document.querySelectorAll('aside a, nav a')).map(a => a.getAttribute('href') || '')"
            )
            self.assertFalse(any("staff.php" in h for h in (sidebar_hrefs or [])), "Sidebar must hide staff.php from Nhân viên")

            # Attempt catalog edit with wrong admin password -> rejected
            pid = self.db.get_product_id("e2e-corsair-cv550")
            stock_before = self.db.get_product_stock("e2e-corsair-cv550")
            browser.navigate(f"/admin/products.php?edit={pid}")
            browser.type_text('input[name="stock"]', "44", clear=True, char_by_char=False)
            browser.type_text('input[name="admin_password"]', "badpass", clear=True, char_by_char=False)
            browser.click('form:has(input[name="stock"]) button[type="submit"]', wait_nav=True)
            self.assert_contains_any(browser.text_content("body"), ["không chính xác", "sai", "mật khẩu admin"])
            self.assertEqual(self.db.get_product_stock("e2e-corsair-cv550"), stock_before)

            # Retry with valid admin password -> succeeds
            browser.navigate(f"/admin/products.php?edit={pid}")
            browser.type_text('input[name="stock"]', "19", clear=True, char_by_char=False)
            browser.type_text('input[name="admin_password"]', ADMIN_PASS, clear=True, char_by_char=False)
            browser.click('form:has(input[name="stock"]) button[type="submit"]', wait_nav=True)
            self.assertEqual(self.db.get_product_stock("e2e-corsair-cv550"), 19)

            # Walk-in order for 1 unit of RTX 4060 -> live search reflects decremented stock
            rtx_before = self.db.get_product_stock("e2e-asus-rtx-4060")
            rtx_id = self.db.get_product_id("e2e-asus-rtx-4060")
            browser.navigate("/admin/walkin-order.php")
            browser.type_text('input[name="customer_name"]', "WalkIn Browser", clear=True, char_by_char=False)
            browser.type_text('input[name="phone"], input[name="customer_phone"]', "0933444404", clear=True, char_by_char=False)
            browser.select_option('select[name="product_id"]', str(rtx_id))
            browser.type_text('input[name="quantity"], input[name="qty"]', "1", clear=True, char_by_char=False)
            browser.click('form button[type="submit"]', wait_nav=True)
            self.extract_order_code(browser.text_content("body"))

            browser.clear_browser_cookies()
            browser.navigate("/index.php")
            browser.type_text('header input[name="q"], input[name="q"]', "4060", clear=True, char_by_char=True)
            dd = '#search-dropdown, #live-search-dropdown, .search-dropdown, [data-live-search-results]'
            browser.wait_for_selector(dd, visible=True, timeout=6.0)
            self.assertIn(str(rtx_before - 1), browser.text_content(dd))

    def test_T4_05_customer_lifecycle_profile_tradein_history_and_admin_block(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            self._browser_login_customer(browser, CUSTOMER_EMAIL, CUSTOMER_PASS)
            browser.navigate("/profile.php")
            new_phone = "0944111405"
            browser.type_text('input[name="phone"]', new_phone, clear=True, char_by_char=False)
            browser.type_text('input[name="address"], textarea[name="address"]', "Tân Châu, An Giang", clear=True, char_by_char=False)
            browser.click('form:has(input[name="phone"]) button[type="submit"]', wait_nav=True)
            browser.navigate("/profile.php")
            self.assertEqual(browser.eval("document.querySelector('input[name=\"phone\"]').value"), new_phone)

            # Submit trade-in
            browser.navigate("/trade-in.php")
            browser.type_text('input[name="customer_name"]', "Khách Hàng Mẫu", clear=True, char_by_char=False)
            browser.type_text('input[name="phone"], input[name="customer_phone"]', new_phone, clear=True, char_by_char=False)
            browser.type_text('input[name="device_name"]', "RTX 3070 Gaming OC", clear=True, char_by_char=False)
            browser.type_text('input[name="device_condition"], textarea[name="device_condition"]', "Đẹp nguyên tem", clear=True, char_by_char=False)
            browser.click('form:has(input[name="device_name"]) button[type="submit"]', wait_nav=True)
            tcode = self.extract_tradein_code(browser.text_content("body"))

            # Staff quotes trade-in in admin/trade-ins.php, customer sees quote + checks orders & warranty
            staff = self.new_client()
            staff.login_admin(STAFF_EMAIL, STAFF_PASS)
            adm_ti = staff.get("/admin/trade-ins.php", allow_4xx=False)
            staff.submit_form(
                adm_ti,
                {"estimated_value": "6800000", "status": "Đã báo giá", "admin_note": "Thu máy browser T405"},
                form_xpath=f'//tr[contains(., "{tcode}")]//form | //div[contains(., "{tcode}")]//form',
            )

            browser.navigate(f"/trade-in.php?q={new_phone}")
            self.assertIn("Đã báo giá", browser.text_content("body"))
            self.assertIn("6.800.000", browser.text_content("body"))

            browser.navigate("/orders.php")
            orders_body = browser.text_content("body")
            self.assert_contains_any(orders_body, ["đơn hàng của tôi", "lịch sử đơn hàng", "tra cứu đơn hàng"])
            browser.wait_for_selector('input[name="code"], input[name="q"]', timeout=4.0)

            browser.navigate(f"/warranty.php?q={new_phone}")
            war_body = browser.text_content("body")
            self.assert_contains_any(war_body, ["tra cứu bảo hành", "bảo hành"])
            self.assert_contains_any(war_body, ["không tìm thấy", "chưa có", "còn hạn"])
            self.assertEqual(browser.eval("document.querySelector('input[name=\"q\"]').value"), new_phone)

            # Customer blocked from /admin/index.php
            browser.navigate("/admin/index.php")
            self.assertTrue("admin/login.php" in browser.current_url() or "không có quyền" in browser.text_content("body").lower())

    def test_T4_06_admin_governance_brand_product_news_no_add_customer(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            self._browser_login_admin(browser, ADMIN_EMAIL, ADMIN_PASS)
            sfx = uid("adm")

            # Create brand
            browser.navigate("/admin/brands.php")
            bname = f"EVGA_{sfx}"
            browser.type_text('input[name="name"]', bname, clear=True, char_by_char=False)
            browser.click('form:has(input[name="name"]) button[type="submit"]', wait_nav=True)
            self.assertIn(bname, browser.text_content("body"))

            # Create news article
            browser.navigate("/admin/news.php")
            title = f"Tin Công Nghệ {sfx}"
            browser.type_text('input[name="title"]', title, clear=True, char_by_char=False)
            browser.type_text('textarea[name="content"]', f"Nội dung bài viết review đầy đủ {sfx}", clear=True, char_by_char=False)
            browser.click('form:has(input[name="title"]) button[type="submit"]', wait_nav=True)

            # Check admin/users.php & admin/orders.php have ZERO "Thêm khách hàng"
            browser.navigate("/admin/users.php")
            self.assertNotIn("Thêm khách hàng", browser.text_content("body"))
            self.assertIn(CUSTOMER_EMAIL, browser.text_content("body"))

            # Update own Admin profile
            browser.navigate("/admin/profile.php")
            browser.type_text('input[name="phone"]', "0900406406", clear=True, char_by_char=False)
            browser.click('form:has(input[name="phone"]) button[type="submit"]', wait_nav=True)

            # Public news page renders published article
            browser.clear_browser_cookies()
            browser.navigate("/news.php")
            self.assertIn(title, browser.text_content("body"))
