"""Tier 1 — Feature Coverage Happy Paths (50 Test Cases: T1-F01-01 .. T1-F10-05)."""
from __future__ import annotations

import re
import time

from config import ADMIN_EMAIL, ADMIN_PASS, CUSTOMER_EMAIL, CUSTOMER_PASS, STAFF_EMAIL, STAFF_PASS
from lib.base_case import E2EBaseTestCase, uid
from lib.cdp_browser import CdpBrowser
from lib.http_client import make_valid_jpeg_bytes, make_valid_png_bytes, parse_html, text_of


def _find_select_option_by_text(doc, select_name: str, needle: str) -> str:
    opts = doc.xpath(f'//select[@name="{select_name}"]/option')
    for opt in opts:
        txt = " ".join((opt.text_content() or "").split())
        if needle.lower() in txt.lower():
            val = opt.get("value")
            if val is not None and val != "":
                return val
    raise AssertionError(f"Option containing {needle!r} not found in <select name='{select_name}'>")


class TestT1F01MultiImageBrand(E2EBaseTestCase):
    def test_T1_F01_01_admin_create_brand(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        suffix = uid("rog")
        brand_name = f"ASUS ROG {suffix}"
        brand_slug = f"asus-rog-{suffix}"

        brands_page = self.http.get("/admin/brands.php", allow_4xx=False)
        doc = parse_html(brands_page)
        form_xpath = '//form[.//input[@name="name"]]'
        forms = doc.xpath(form_xpath)
        self.assertTrue(forms, "Brand creation form not found on /admin/brands.php")
        overrides: dict[str, str] = {"name": brand_name}
        if forms[0].xpath('.//input[@name="slug"]'):
            overrides["slug"] = brand_slug
        if forms[0].xpath('.//input[@name="country"]'):
            overrides["country"] = "Taiwan"
        if forms[0].xpath('.//textarea[@name="description"] | .//input[@name="description"]'):
            overrides["description"] = "Gaming hardware E2E"

        resp = self.http.submit_form(brands_page, overrides, form_xpath=form_xpath)
        self.assertIn(brand_name, resp.text)

        prod_page = self.http.get("/admin/products.php", allow_4xx=False)
        self.assertIn(brand_name, prod_page.text)

    def test_T1_F01_02_admin_create_product_multi_image_and_brand(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        suffix = uid("vga")
        prod_name = f"VGA ASUS RTX 4070 Test {suffix}"
        prod_slug = f"vga-asus-rtx-4070-test-{suffix}"

        page = self.http.get("/admin/products.php", allow_4xx=False)
        doc = parse_html(page)
        form_xpath = '//form[.//input[@name="name"] and .//input[@name="price"]]'
        forms = doc.xpath(form_xpath)
        self.assertTrue(forms, "Product form not found on /admin/products.php")
        form = forms[0]

        overrides: dict[str, str] = {
            "name": prod_name,
            "price": "16500000",
            "stock": "7",
        }
        if form.xpath('.//input[@name="slug"]'):
            overrides["slug"] = prod_slug
        if form.xpath('.//select[@name="brand_id"]'):
            overrides["brand_id"] = _find_select_option_by_text(doc, "brand_id", "ASUS")
        elif form.xpath('.//input[@name="brand"] | .//select[@name="brand"]'):
            overrides["brand"] = "ASUS"
        if form.xpath('.//select[@name="category_id"]'):
            overrides["category_id"] = _find_select_option_by_text(doc, "category_id", "VGA")
        elif form.xpath('.//select[@name="category"]'):
            overrides["category"] = _find_select_option_by_text(doc, "category", "VGA")

        files = []
        if form.xpath('.//input[@name="image_file"]'):
            files.append(("image_file", (f"primary_{suffix}.png", make_valid_png_bytes((10, 80, 200)), "image/png")))
        elif form.xpath('.//input[@name="image" and @type="file"]'):
            files.append(("image", (f"primary_{suffix}.png", make_valid_png_bytes((10, 80, 200)), "image/png")))

        if form.xpath('.//input[contains(@name, "gallery")]'):
            g_name = form.xpath('.//input[contains(@name, "gallery")]/@name')[0]
            files.append((g_name, (f"gal1_{suffix}.png", make_valid_png_bytes((30, 180, 90)), "image/png")))
            files.append((g_name, (f"gal2_{suffix}.jpg", make_valid_jpeg_bytes((200, 90, 30)), "image/jpeg")))

        resp = self.http.submit_form(page, overrides, files=files or None, form_xpath=form_xpath)
        self.assertIn(prod_name, resp.text)

        # Locate created slug via search/collection if backend auto-generated slug
        detail_resp = self.http.get("/product.php", params={"slug": prod_slug})
        if detail_resp.status_code == 404 or prod_name not in detail_resp.text:
            col = self.http.get("/collection.php", params={"q": suffix}, allow_4xx=False)
            cdoc = parse_html(col)
            links = cdoc.xpath('//a[contains(@href, "product.php?slug=")]/@href')
            self.assertTrue(links, f"Created product {prod_name} not reachable on collection.php")
            detail_resp = self.http.get("/" + links[0].lstrip("/"), allow_4xx=False)

        self.assertEqual(detail_resp.status_code, 200)
        self.assertIn(prod_name, detail_resp.text)
        ddoc = parse_html(detail_resp)
        gallery_xpath = (
            '//*[@id="product-gallery" or contains(@class,"gallery") '
            'or contains(@class,"product-thumb") or contains(@class,"thumb")]//img[@src] '
            '| //img[@data-gallery-thumb or @id="main-product-image" '
            'or contains(@class,"main-product-image") or @id="product-main-img"]'
        )
        gallery_srcs = {img.get("src") for img in ddoc.xpath(gallery_xpath) if img.get("src")}
        self.assertGreaterEqual(
            len(gallery_srcs),
            3,
            f"Expected >= 3 unique gallery/main product images on product.php, got {sorted(gallery_srcs)!r}",
        )

    def test_T1_F01_03_product_gallery_thumbnail_switch_dom(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/product.php?slug=e2e-asus-rtx-4060")
            thumb_count = browser.eval(
                "document.querySelectorAll('[data-gallery-thumb], .gallery-thumb, .product-thumb, #product-gallery img').length"
            )
            self.assertGreaterEqual(int(thumb_count or 0), 2, "Expected >= 2 gallery thumbnails on product.php")
            main_before = browser.eval(
                "(() => { const el = document.querySelector('#main-product-image, .main-product-image, #product-main-img, main img'); return el ? el.getAttribute('src') : ''; })()"
            )
            clicked_src = browser.eval(
                f"""(() => {{
                    const before = {repr(str(main_before))};
                    const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb], .gallery-thumb, .product-thumb, #product-gallery img'));
                    const target = thumbs.find(t => (t.getAttribute('data-src') || t.getAttribute('src') || '') !== before) || thumbs[1];
                    if (!target) return '';
                    target.click();
                    return target.getAttribute('data-src') || target.getAttribute('src') || '';
                }})()"""
            )
            time.sleep(0.2)
            main_after = browser.eval(
                "(() => { const el = document.querySelector('#main-product-image, .main-product-image, #product-main-img, main img'); return el ? el.getAttribute('src') : ''; })()"
            )
            self.assertTrue(clicked_src, "Could not find secondary thumbnail to click")
            self.assertNotEqual(main_after, main_before, "Clicking 2nd gallery thumbnail did not update main image src")

    def test_T1_F01_04_collection_filter_by_brand(self) -> None:
        resp = self.http.get("/collection.php", params={"brand": "ASUS"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        doc = parse_html(resp)
        cards = doc.xpath('//*[contains(@class, "product-card") or .//a[contains(@href, "product.php?slug=")]][.//a[contains(@href, "product.php?slug=")]]')
        self.assertTrue(cards, "Expected ASUS product cards on collection.php?brand=ASUS")
        self.assertIn("ASUS", resp.text)
        self.assertNotIn("VGA MSI RTX 4070 SUPER", resp.text)
        self.assertNotIn("Corsair CV550", resp.text)

    def test_T1_F01_05_index_and_collection_category_brand_filter(self) -> None:
        idx = self.http.get("/index.php", allow_4xx=False)
        self.assertEqual(idx.status_code, 200)
        col = self.http.get("/collection.php", params={"cat": "vga", "brand": "ASUS"}, allow_4xx=False)
        self.assertEqual(col.status_code, 200)
        self.assertIn("VGA ASUS Dual RTX 4060", col.text)
        self.assertNotIn("CPU Intel Core i5-13400F", col.text)
        self.assertNotIn("VGA MSI RTX 4070 SUPER", col.text)


class TestT1F02LiveSearch(E2EBaseTestCase):
    def test_T1_F02_01_search_api_1_char(self) -> None:
        resp = self.http.get("/api/search.php", params={"q": "r"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertTrue(data.get("ok"), f"Expected ok=true, got {data!r}")
        items = data.get("items") or []
        self.assertGreater(len(items), 0, "Expected non-empty items for q='r'")
        for item in items:
            for key in ("id", "name", "slug", "price", "stock", "brand", "image"):
                self.assertIn(key, item)

    def test_T1_F02_02_search_api_2_chars(self) -> None:
        resp = self.http.get("/api/search.php", params={"q": "rt"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertTrue(data.get("ok"))
        items = data.get("items") or []
        self.assertTrue(any("rtx" in (i.get("name") or "").lower() for i in items))
        for item in items:
            self.assertTrue(item.get("image"), "Expected non-empty image URL in search API")
            self.assertIsInstance(int(item["stock"]), int)
            self.assertGreaterEqual(int(item["stock"]), 0)

    def test_T1_F02_03_search_api_rtx_image_and_stock(self) -> None:
        resp = self.http.get("/api/search.php", params={"q": "rtx"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertTrue(data.get("ok"))
        items = data.get("items") or []
        self.assertGreater(len(items), 0)
        rtx4060 = [i for i in items if "4060" in (i.get("name") or "")]
        self.assertTrue(rtx4060, "Expected seeded RTX 4060 in api/search.php?q=rtx")
        self.assertGreaterEqual(int(rtx4060[0]["stock"]), 1)
        self.assertTrue(rtx4060[0]["image"])

    def test_T1_F02_04_live_search_dropdown_dom_2_chars(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/index.php")
            search_sel = 'header input[name="q"], input[name="q"], #live-search-input'
            browser.type_text(search_sel, "rt", clear=True, char_by_char=True)
            dropdown_sel = '#search-dropdown, #live-search-dropdown, .search-dropdown, [data-live-search-results]'
            browser.wait_for_selector(dropdown_sel, visible=True, timeout=6.0)
            html = browser.inner_html(dropdown_sel)
            txt = browser.text_content(dropdown_sel)
            self.assertIn("<img", html.lower(), "Live search dropdown must render thumbnail <img> tags")
            self.assertIn("RTX", txt.upper())
            self.assert_contains_any(txt, ["còn", "hết hàng", "tồn"], "Dropdown must show stock indicator")

    def test_T1_F02_05_live_search_click_navigates_to_product(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/index.php")
            search_sel = 'header input[name="q"], input[name="q"], #live-search-input'
            browser.type_text(search_sel, "rtx", clear=True, char_by_char=True)
            dropdown_sel = '#search-dropdown, #live-search-dropdown, .search-dropdown, [data-live-search-results]'
            link_sel = (
                "#search-dropdown a[href*='product.php'], "
                "#live-search-dropdown a[href*='product.php'], "
                ".search-dropdown a[href*='product.php'], "
                "[data-live-search-results] a[href*='product.php']"
            )
            browser.wait_for_selector(link_sel, visible=True, timeout=6.0)
            browser.click(link_sel, wait_nav=True)
            self.assertIn("product.php?slug=", browser.current_url())
            self.assertIn("RTX", browser.text_content("body").upper())


class TestT1F03CheckoutCodVietQR(E2EBaseTestCase):
    def test_T1_F03_01_checkout_shows_only_cod_and_vietqr_no_tragop(self) -> None:
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        resp = self.http.get("/checkout.php", allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assert_no_tragop(resp.text, context="/checkout.php")
        doc = parse_html(resp)
        methods = [v.upper() for v in doc.xpath('//input[@name="payment_method"]/@value')]
        self.assertIn("COD", methods)
        self.assertIn("VIETQR", methods)
        self.assertEqual(set(methods), {"COD", "VIETQR"}, f"Unexpected payment methods: {methods!r}")

    def test_T1_F03_02_checkout_cod_order_created_and_cart_cleared(self) -> None:
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        resp = self.http.submit_form(
            "/checkout.php",
            {
                "customer_name": "Nguyễn Văn COD",
                "phone": "0912345678",
                "address": "123 Lê Lợi, Long Xuyên, An Giang",
                "payment_method": "COD",
            },
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(resp.text)
        self.assertIn("Chờ xác nhận", resp.text)
        cart_resp = self.http.get("/cart.php", allow_4xx=False)
        self.assertNotIn("e2e-intel-i5-13400f", cart_resp.text)
        lookup = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn(code, lookup.text)

    def test_T1_F03_03_checkout_vietqr_qr_amount_code_and_15m_timer(self) -> None:
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        resp = self.http.submit_form(
            "/checkout.php",
            {
                "customer_name": "Trần Thị VietQR",
                "phone": "0912345679",
                "address": "45 Trần Hưng Đạo, An Giang",
                "payment_method": "VietQR",
            },
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(resp.text)
        self.assertIn("vietqr", resp.text.lower())
        self.assertIn("4890000", resp.text.replace(".", "").replace(",", ""))
        self.assertIn(code, resp.text)
        self.assert_contains_any(resp.text, ["15:00", "14:5", "vietqr-timer", "countdown"], "Missing 15-min VietQR countdown")

    def test_T1_F03_04_vietqr_simulated_confirm_pending_confirm(self) -> None:
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        resp = self.http.submit_form(
            "/checkout.php",
            {
                "customer_name": "Lê VietQR Confirm",
                "phone": "0912345680",
                "address": "78 Nguyễn Trãi, An Giang",
                "payment_method": "VietQR",
            },
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(resp.text)
        doc = parse_html(resp)
        confirm_xpath = '//form[.//input[@name="action" and @value="confirm_vietqr"]]'
        if not doc.xpath(confirm_xpath):
            resp = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
            doc = parse_html(resp)
        self.assertTrue(doc.xpath(confirm_xpath), "Missing confirm_vietqr form after VietQR order creation")
        c_resp = self.http.submit_form(resp, {}, form_xpath=confirm_xpath)
        self.assert_contains_any(
            c_resp.text,
            ["pending_confirm", "chờ xác nhận thanh toán", "chờ nhân viên", "chờ duyệt", "đã gửi xác nhận"],
            "Expected pending_confirm status after VietQR confirmation",
        )

    def test_T1_F03_05_storefront_pages_contain_zero_tragop(self) -> None:
        for path in (
            "/index.php",
            "/product.php?slug=e2e-asus-rtx-4060",
            "/info.php?page=thu-cu",
            "/info.php?page=bao-hanh",
        ):
            r = self.http.get(path, allow_4xx=False)
            self.assertEqual(r.status_code, 200)
            self.assert_no_tragop(r.text, context=path)


class TestT1F04WarrantyAndOrderHistory(E2EBaseTestCase):
    def test_T1_F04_01_order_auto_generates_per_unit_warranties(self) -> None:
        phone = "0918881112"
        self.add_product_to_cart(self.http, "e2e-asus-rtx-4060", qty=2)
        resp = self.http.submit_form(
            "/checkout.php",
            {
                "customer_name": "Phạm Bảo Hành",
                "phone": phone,
                "address": "An Giang",
                "payment_method": "COD",
            },
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(resp.text)
        w_resp = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertEqual(w_resp.status_code, 200)
        self.assertIn("Còn hạn", w_resp.text)
        w_codes = self.extract_warranty_codes(w_resp.text)
        self.assertGreaterEqual(len(w_codes), 2, f"Expected 2 auto-generated warranties for qty=2, got {w_codes!r}")

    def test_T1_F04_02_warranty_lookup_by_warranty_code(self) -> None:
        phone = "0918881113"
        self.add_product_to_cart(self.http, "e2e-asus-rtx-4060", qty=1)
        resp = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "KH BH Code", "phone": phone, "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(resp.text)
        w_by_order = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        w_codes = self.extract_warranty_codes(w_by_order.text)
        self.assertTrue(w_codes, "Expected warranty code BH...")
        w_by_code = self.http.get("/warranty.php", params={"q": w_codes[0]}, allow_4xx=False)
        self.assertIn(w_codes[0], w_by_code.text)
        self.assertIn("24", w_by_code.text)
        self.assertIn("Còn hạn", w_by_code.text)

    def test_T1_F04_03_warranty_lookup_by_customer_phone(self) -> None:
        phone = "0918881114"
        self.add_product_to_cart(self.http, "e2e-asus-rtx-4060", qty=1)
        self.http.submit_form(
            "/checkout.php",
            {"customer_name": "KH BH Phone", "phone": phone, "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        w_resp = self.http.get("/warranty.php", params={"q": phone}, allow_4xx=False)
        self.assertIn("VGA ASUS Dual RTX 4060", w_resp.text)
        self.assertIn("Còn hạn", w_resp.text)

    def test_T1_F04_04_customer_logged_in_order_history(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Khách Hàng Mẫu", "phone": "0912345678", "address": "Châu Đốc", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        hist = self.http.get("/orders.php", allow_4xx=False)
        self.assertIn(code, hist.text)

    def test_T1_F04_05_customer_cancel_pending_order_restocks_and_voids_warranty(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        stock_before = self.db.get_product_stock("e2e-samsung-980-pro-1tb")
        self.add_product_to_cart(self.http, "e2e-samsung-980-pro-1tb", qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Khách Hàng Mẫu", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        orders_resp = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        cancel_xpath = '//form[.//input[@name="action" and @value="cancel_order"]]'
        c_resp = self.http.submit_form(orders_resp, {}, form_xpath=cancel_xpath)
        self.assertIn("Đã hủy", c_resp.text)
        self.assertEqual(self.db.get_product_stock("e2e-samsung-980-pro-1tb"), stock_before)
        w_resp = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertIn("Đã hủy", w_resp.text)


class TestT1F05TradeIn(E2EBaseTestCase):
    def test_T1_F05_01_tradein_page_renders_form_and_lookup(self) -> None:
        resp = self.http.get("/trade-in.php", allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        doc = parse_html(resp)
        self.assertTrue(doc.xpath('//form[.//input[@name="device_name"]]'), "Missing trade-in submission form")

    def test_T1_F05_02_submit_tradein_generates_code_cho_dinh_gia(self) -> None:
        resp = self.http.submit_form(
            "/trade-in.php",
            {
                "customer_name": "Nguyễn Văn A",
                "phone": "0987654321",
                "device_name": "GTX 1660 Super 6GB",
                "device_condition": "Nguyên zin, còn hộp, hoạt động tốt",
                "notes": "Muốn lên RTX 4060",
            },
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        code = self.extract_tradein_code(resp.text)
        self.assertIn("Chờ định giá", resp.text)
        self.assertTrue(code.startswith("TC"))

    def test_T1_F05_03_lookup_tradein_by_phone_or_code(self) -> None:
        phone = "0987650003"
        sub = self.http.submit_form(
            "/trade-in.php",
            {
                "customer_name": "Trần TradeIn",
                "phone": phone,
                "device_name": "RX 6600 XT 8GB",
                "device_condition": "Còn bảo hành",
            },
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        code = self.extract_tradein_code(sub.text)
        lk = self.http.get("/trade-in.php", params={"q": phone}, allow_4xx=False)
        self.assertIn(code, lk.text)
        self.assertIn("RX 6600 XT 8GB", lk.text)
        self.assertIn("Chờ định giá", lk.text)

    def test_T1_F05_04_admin_quotes_tradein_da_bao_gia(self) -> None:
        phone = "0987650004"
        sub = self.http.submit_form(
            "/trade-in.php",
            {
                "customer_name": "KH Báo Giá",
                "phone": phone,
                "device_name": "RTX 2060 Super",
                "device_condition": "Đẹp 98%",
            },
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        code = self.extract_tradein_code(sub.text)

        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        adm = self.http.get("/admin/trade-ins.php", allow_4xx=False)
        self.assertIn(code, adm.text)
        form_xpath = f'//tr[contains(., "{code}")]//form | //div[contains(., "{code}")]//form'
        upd = self.http.submit_form(
            adm,
            {
                "estimated_value": "3200000",
                "status": "Đã báo giá",
                "admin_note": "Thu lại 3.2 triệu nếu test ok",
            },
            form_xpath=form_xpath,
        )
        self.assertIn("Đã báo giá", upd.text)
        lk = self.http.get("/trade-in.php", params={"q": phone}, allow_4xx=False)
        self.assertIn("Đã báo giá", lk.text)
        self.assertIn("3.200.000", lk.text)

    def test_T1_F05_05_admin_completes_tradein_hoan_tat(self) -> None:
        phone = "0987650005"
        sub = self.http.submit_form(
            "/trade-in.php",
            {
                "customer_name": "KH Hoàn Tất",
                "phone": phone,
                "device_name": "Core i5-12400F",
                "device_condition": "Fullbox",
            },
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        code = self.extract_tradein_code(sub.text)
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        adm = self.http.get("/admin/trade-ins.php", allow_4xx=False)
        form_xpath = f'//tr[contains(., "{code}")]//form | //div[contains(., "{code}")]//form'
        adm = self.http.submit_form(adm, {"estimated_value": "2100000", "status": "Đã báo giá"}, form_xpath=form_xpath)
        upd = self.http.submit_form(adm, {"status": "Hoàn tất"}, form_xpath=form_xpath)
        self.assertIn("Hoàn tất", upd.text)
        lk = self.http.get("/trade-in.php", params={"q": phone}, allow_4xx=False)
        self.assertIn("Hoàn tất", lk.text)


class TestT1F06AdminRbac(E2EBaseTestCase):
    def test_T1_F06_01_admin_login_and_full_sidebar(self) -> None:
        resp = self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        self.assertEqual(resp.status_code, 200)
        for href in ("staff.php", "products.php", "categories.php", "brands.php", "orders.php", "news.php", "users.php", "profile.php"):
            self.assertIn(href, resp.text, f"Admin sidebar missing link to {href}")

    def test_T1_F06_02_admin_creates_staff_and_staff_can_login(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        email = f"nv_{uid()}@doan6.local"
        page = self.http.get("/admin/staff.php", allow_4xx=False)
        resp = self.http.submit_form(
            page,
            {
                "name": "Nhân Viên Mới",
                "email": email,
                "password": "nv123456",
                "phone": "0909111222",
            },
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )
        self.assertIn(email, resp.text)

        new_staff_client = self.new_client()
        login_resp = new_staff_client.login_admin(email, "nv123456")
        self.assertIn("orders.php", login_resp.text)

    def test_T1_F06_03_admin_adds_category_and_product_without_pw_prompt(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        sfx = uid("cat")
        cat_name = f"Tản nhiệt {sfx}"
        cat_resp = self.http.submit_form(
            "/admin/categories.php",
            {"name": cat_name, "slug": f"tan-nhiet-{sfx}"},
            form_xpath='//form[.//input[@name="name"]]',
        )
        self.assertIn(cat_name, cat_resp.text)

        prod_name = f"Tản khí RGB {sfx}"
        p_page = self.http.get("/admin/products.php", allow_4xx=False)
        p_doc = parse_html(p_page)
        p_form = '//form[.//input[@name="name"] and .//input[@name="price"]]'
        overrides = {"name": prod_name, "price": "790000", "stock": "15"}
        if p_doc.xpath(f'{p_form}//select[@name="category_id"]'):
            overrides["category_id"] = _find_select_option_by_text(p_doc, "category_id", cat_name)
        elif p_doc.xpath(f'{p_form}//select[@name="category"]'):
            overrides["category"] = _find_select_option_by_text(p_doc, "category", cat_name)
        p_resp = self.http.submit_form(p_page, overrides, form_xpath=p_form)
        self.assertIn(prod_name, p_resp.text)

    def test_T1_F06_04_admin_creates_tech_review_article_rendered_on_news(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        sfx = uid("art")
        title = f"Đánh giá RTX 4070 Super {sfx}"
        body_txt = f"Chi tiết hiệu năng thực tế benchmark 1440p {sfx}"
        n_resp = self.http.submit_form(
            "/admin/news.php",
            {
                "title": title,
                "category": "Review",
                "summary": f"Tóm tắt {sfx}",
                "content": f"<p>{body_txt}</p>",
            },
            form_xpath='//form[.//input[@name="title"]]',
        )
        self.assertIn(title, n_resp.text)
        pub_list = self.new_client().get("/news.php", allow_4xx=False)
        self.assertIn(title, pub_list.text)
        ndoc = parse_html(pub_list)
        links = ndoc.xpath(f'//a[contains(., "{sfx}")]/@href')
        self.assertTrue(links, f"Expected link to article detail containing {sfx!r} on /news.php")
        detail = self.new_client().get("/" + links[0].lstrip("/"), allow_4xx=False)
        self.assertIn(body_txt, detail.text)

    def test_T1_F06_05_admin_views_profiles_orders_no_add_customer_updates_own_profile(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        users_resp = self.http.get("/admin/users.php", allow_4xx=False)
        self.assertIn(CUSTOMER_EMAIL, users_resp.text)
        self.assertNotIn("Thêm khách hàng", users_resp.text)
        orders_resp = self.http.get("/admin/orders.php", allow_4xx=False)
        self.assertEqual(orders_resp.status_code, 200)
        self.assertNotIn("Thêm khách hàng", orders_resp.text)

        new_phone = "0909888777"
        prof = self.http.submit_form(
            "/admin/profile.php",
            {"name": "Quản Trị Viên", "phone": new_phone, "address": "An Giang Admin"},
            form_xpath='//form[.//input[@name="phone"]]',
        )
        self.assertIn(new_phone, prof.text)
        reload_prof = self.http.get("/admin/profile.php", allow_4xx=False)
        self.assertIn(new_phone, reload_prof.text)


class TestT1F07StaffRbac(E2EBaseTestCase):
    def test_T1_F07_01_staff_login_hides_staff_permissions_sidebar(self) -> None:
        resp = self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        self.assertEqual(resp.status_code, 200)
        doc = parse_html(resp)
        staff_links = doc.xpath('//aside//a[contains(@href, "staff.php")] | //nav//a[contains(@href, "staff.php")]')
        self.assertEqual(len(staff_links), 0, "Staff sidebar must hide link to admin/staff.php")
        for href in ("orders.php", "walkin-order.php", "profile.php"):
            self.assertIn(href, resp.text, f"Staff sidebar missing {href}")

    def test_T1_F07_02_staff_edits_product_with_valid_admin_password(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        pid = self.db.get_product_id("e2e-corsair-cv550")
        page = self.http.get("/admin/products.php", params={"edit": pid}, allow_4xx=False)
        form_xpath = '//form[.//input[@name="name"] and .//input[@name="stock"]]'
        resp = self.http.submit_form(
            page,
            {"stock": "18", "admin_password": ADMIN_PASS},
            form_xpath=form_xpath,
        )
        self.assertEqual(self.db.get_product_stock("e2e-corsair-cv550"), 18)
        self.assertNotIn("không chính xác", resp.text.lower())

    def test_T1_F07_03_staff_adds_category_or_brand_with_valid_admin_password(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        bname = f"StaffBrand_{uid()}"
        resp = self.http.submit_form(
            "/admin/brands.php",
            {"name": bname, "country": "Japan", "admin_password": ADMIN_PASS},
            form_xpath='//form[.//input[@name="name"]]',
        )
        self.assertIn(bname, resp.text)

    def test_T1_F07_04_staff_creates_walkin_order_deducts_stock_and_creates_warranties(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        stock_before = self.db.get_product_stock("e2e-asus-rtx-4060")
        pid = self.db.get_product_id("e2e-asus-rtx-4060")
        page = self.http.get("/admin/walkin-order.php", allow_4xx=False)
        doc = parse_html(page)
        form_xpath = '//form[.//input[@name="customer_name"]]'
        forms = doc.xpath(form_xpath)
        self.assertTrue(forms, "Walk-in order form not found on /admin/walkin-order.php")
        overrides = {
            "customer_name": "Khách Tại Quầy",
            "payment_method": "COD",
        }
        if forms[0].xpath('.//input[@name="phone"]'):
            overrides["phone"] = "0933444555"
        elif forms[0].xpath('.//input[@name="customer_phone"]'):
            overrides["customer_phone"] = "0933444555"
        if forms[0].xpath('.//select[@name="product_id"]'):
            overrides["product_id"] = str(pid)
        if forms[0].xpath('.//input[@name="quantity"]'):
            overrides["quantity"] = "2"
        elif forms[0].xpath('.//input[@name="qty"]'):
            overrides["qty"] = "2"

        resp = self.http.submit_form(page, overrides, form_xpath=form_xpath)
        code = self.extract_order_code(resp.text)
        self.assertEqual(self.db.get_product_stock("e2e-asus-rtx-4060"), stock_before - 2)
        w_resp = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertGreaterEqual(len(self.extract_warranty_codes(w_resp.text)), 2)

    def test_T1_F07_05_staff_updates_own_profile_persists(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        new_phone = "0933999888"
        self.http.submit_form(
            "/admin/profile.php",
            {"name": "Nhân Viên Bán Hàng", "phone": new_phone, "address": "Châu Thành, An Giang"},
            form_xpath='//form[.//input[@name="phone"]]',
        )
        reloaded = self.http.get("/admin/profile.php", allow_4xx=False)
        self.assertIn(new_phone, reloaded.text)


class TestT1F08CustomerRbac(E2EBaseTestCase):
    def test_T1_F08_01_customer_register_authenticates(self) -> None:
        email = f"cust_{uid()}@example.com"
        reg_page = self.http.get("/register.php", allow_4xx=False)
        doc = parse_html(reg_page)
        form_xpath = '//form[.//input[@name="email"] and .//input[@name="password"]]'
        overrides = {
            "name": "Lê Thị B",
            "email": email,
            "phone": "0911222333",
            "password": "pass1234",
        }
        if doc.xpath(f'{form_xpath}//input[@name="confirm_password"]'):
            overrides["confirm_password"] = "pass1234"
        elif doc.xpath(f'{form_xpath}//input[@name="confirm"]'):
            overrides["confirm"] = "pass1234"
        resp = self.http.submit_form(reg_page, overrides, form_xpath=form_xpath)
        self.assertIn("Lê Thị B", resp.text)

    def test_T1_F08_02_customer_login_shows_profile_and_orders_links(self) -> None:
        resp = self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        self.assertIn("profile.php", resp.text)
        self.assertIn("orders.php", resp.text)

    def test_T1_F08_03_customer_updates_profile_persists_and_prefills_checkout(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        new_phone = "0944555666"
        new_addr = "Long Xuyên, An Giang E2E"
        self.http.submit_form(
            "/profile.php",
            {"name": "Khách Hàng Mẫu", "phone": new_phone, "address": new_addr},
            form_xpath='//form[.//input[@name="phone"]]',
        )
        self.http.logout_customer()
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        prof = self.http.get("/profile.php", allow_4xx=False)
        self.assertIn(new_phone, prof.text)
        self.assertIn(new_addr, prof.text)
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        co = self.http.get("/checkout.php", allow_4xx=False)
        self.assertIn(new_phone, co.text)

    def test_T1_F08_04_customer_views_order_history_and_warranty_lookup(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        self.add_product_to_cart(self.http, "e2e-asus-rtx-4060", qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Khách Hàng Mẫu", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        hist = self.http.get("/orders.php", allow_4xx=False)
        self.assertIn(code, hist.text)
        war = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertIn("Còn hạn", war.text)

    def test_T1_F08_05_customer_blocked_from_admin_pages(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        for path in ("/admin/index.php", "/admin/orders.php"):
            resp = self.http.get(path, allow_redirects=True)
            is_blocked = resp.status_code == 403 or "admin/login.php" in resp.url or "đăng nhập" in resp.text.lower()
            self.assertTrue(is_blocked, f"Customer was not blocked from {path} (url={resp.url}, status={resp.status_code})")


class TestT1F09PcBuilder(E2EBaseTestCase):
    def test_T1_F09_01_pc_builder_renders_db_backed_components_with_stock(self) -> None:
        resp = self.http.get("/pc-builder.php", allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        for name in ("Intel Core i5-13400F", "ASUS PRIME B760M-K D4", "Corsair Vengeance LPX 16GB", "ASUS Dual RTX 4060", "Samsung 980 PRO", "Corsair CV550"):
            self.assertIn(name, resp.text, f"Missing DB-backed component {name!r} in pc-builder.php")

    def _select_compatible_build(self, browser: CdpBrowser) -> int:
        slots = [
            ("cpu", "e2e-intel-i5-13400f", "i5-13400F"),
            ("mainboard", "e2e-asus-b760m-d4", "B760M-K"),
            ("ram", "e2e-corsair-16gb-ddr4", "LPX 16GB"),
            ("vga", "e2e-asus-rtx-4060", "4060"),
            ("ssd", "e2e-samsung-980-pro-1tb", "980 PRO"),
            ("psu", "e2e-corsair-cv550", "CV550"),
        ]
        total = 0
        for slot, slug, model_hint in slots:
            sel = f"#select-{slot}, select[name='{slot}_id'], select[name='{slot}']"
            val_price = browser.eval(
                f"""(() => {{
                    const s = document.querySelector({repr(sel)});
                    if (!s) return null;
                    for (const o of s.options) {{
                        if ((o.dataset.slug || '') === {repr(slug)} || o.text.toUpperCase().includes({repr(model_hint.upper())})) {{
                            s.value = o.value;
                            s.dispatchEvent(new Event('change', {{bubbles:true}}));
                            return Number(o.dataset.price || 0);
                        }}
                    }}
                    return null;
                }})()"""
            )
            self.assertIsNotNone(val_price, f"Could not select {slug} in {sel}")
            total += int(val_price)
        return total

    def test_T1_F09_02_pc_builder_compatible_parts_show_green_compat(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")
            self._select_compatible_build(browser)
            compat_txt = browser.text_content("#compat-box, .compat-box, [data-compat-box]")
            self.assert_contains_any(compat_txt, ["tương thích", "hợp lệ", "ok"], f"Expected green compat box, got {compat_txt!r}")

    def test_T1_F09_03_pc_builder_live_total_equals_sum_of_selected_parts(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")
            expected_total = self._select_compatible_build(browser)
            total_txt = browser.text_content("#build-total, [data-build-total]")
            digits = int(re.sub(r"[^\d]", "", total_txt) or "0")
            self.assertEqual(digits, expected_total, f"Live total {digits} != expected {expected_total}")

    def test_T1_F09_04_pc_builder_direct_order_creates_pc_build_deducts_stock_creates_warranties(self) -> None:
        cpu_before = self.db.get_product_stock("e2e-intel-i5-13400f")
        vga_before = self.db.get_product_stock("e2e-asus-rtx-4060")
        page = self.http.get("/pc-builder.php", allow_4xx=False)
        form_xpath = '//form[.//input[@name="customer_name"]]'
        overrides = {
            "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")),
            "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
            "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
            "vga_id": str(self.db.get_product_id("e2e-asus-rtx-4060")),
            "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
            "psu_id": str(self.db.get_product_id("e2e-corsair-cv550")),
            "customer_name": "KH Build PC Direct",
            "phone": "0917776665",
            "address": "Long Xuyên, An Giang",
            "payment_method": "COD",
        }
        resp = self.http.submit_form(page, overrides, form_xpath=form_xpath)
        code = self.extract_order_code(resp.text)
        self.assertEqual(self.db.get_product_stock("e2e-intel-i5-13400f"), cpu_before - 1)
        self.assertEqual(self.db.get_product_stock("e2e-asus-rtx-4060"), vga_before - 1)
        w_resp = self.http.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertGreaterEqual(len(self.extract_warranty_codes(w_resp.text)), 6)

    def test_T1_F09_05_pc_builder_add_bulk_to_cart(self) -> None:
        csrf = self.http.extract_csrf(self.http.get("/pc-builder.php", allow_4xx=False))
        pids = [
            str(self.db.get_product_id("e2e-intel-i5-13400f")),
            str(self.db.get_product_id("e2e-asus-b760m-d4")),
            str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
        ]
        data = [("csrf_token", csrf), ("action", "add_bulk")] + [("product_ids[]", pid) for pid in pids]
        resp = self.http.post("/cart.php", data=data, allow_4xx=False)
        self.assertIn("Intel Core i5-13400F", resp.text)
        self.assertIn("ASUS PRIME B760M-K D4", resp.text)
        self.assertIn("Corsair Vengeance LPX 16GB", resp.text)


class TestT1F10PcPipeline(E2EBaseTestCase):
    def _create_pc_build_order(self) -> str:
        page = self.http.get("/pc-builder.php", allow_4xx=False)
        resp = self.http.submit_form(
            page,
            {
                "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")),
                "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
                "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
                "vga_id": str(self.db.get_product_id("e2e-asus-rtx-4060")),
                "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
                "psu_id": str(self.db.get_product_id("e2e-corsair-cv550")),
                "customer_name": "KH Pipeline PC",
                "phone": "0916665554",
                "address": "An Giang",
                "payment_method": "COD",
            },
            form_xpath='//form[.//input[@name="customer_name"]]',
        )
        return self.extract_order_code(resp.text)

    def _update_order_status(self, code: str, new_status: str) -> str:
        adm = self.http.get("/admin/orders.php", allow_4xx=False)
        form_xpath = f'//tr[contains(., "{code}")]//form[.//select[@name="status"]] | //div[contains(., "{code}")]//form[.//select[@name="status"]]'
        resp = self.http.submit_form(adm, {"status": new_status}, form_xpath=form_xpath)
        return resp.text

    def test_T1_F10_01_admin_orders_shows_pc_build_badge_specs_and_assembly_stage(self) -> None:
        code = self._create_pc_build_order()
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        adm = self.http.get("/admin/orders.php", allow_4xx=False)
        self.assertIn(code, adm.text)
        self.assert_contains_any(adm.text, ["Build PC", "pc_build", "Lắp ráp PC"])
        self.assertIn("Đang lắp ráp", adm.text)

    def test_T1_F10_02_pipeline_cho_xac_nhan_to_dang_lap_rap(self) -> None:
        code = self._create_pc_build_order()
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        txt = self._update_order_status(code, "Đang lắp ráp")
        self.assertIn("Đang lắp ráp", txt)
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Đang lắp ráp", cust.text)

    def test_T1_F10_03_pipeline_dang_lap_rap_to_dang_giao_hang(self) -> None:
        code = self._create_pc_build_order()
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        self._update_order_status(code, "Đang lắp ráp")
        txt = self._update_order_status(code, "Đang giao hàng")
        self.assertIn("Đang giao hàng", txt)
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Đang giao hàng", cust.text)

    def test_T1_F10_04_pipeline_dang_giao_hang_to_hoan_thanh(self) -> None:
        code = self._create_pc_build_order()
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        self._update_order_status(code, "Đang lắp ráp")
        self._update_order_status(code, "Đang giao hàng")
        txt = self._update_order_status(code, "Hoàn thành")
        self.assertIn("Hoàn thành", txt)

    def test_T1_F10_05_pipeline_cancel_dang_lap_rap_restocks_and_voids_warranties(self) -> None:
        cpu_before = self.db.get_product_stock("e2e-intel-i5-13400f")
        vga_before = self.db.get_product_stock("e2e-asus-rtx-4060")
        code = self._create_pc_build_order()
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        self._update_order_status(code, "Đang lắp ráp")
        txt = self._update_order_status(code, "Đã hủy")
        self.assertIn("Đã hủy", txt)
        self.assertEqual(self.db.get_product_stock("e2e-intel-i5-13400f"), cpu_before)
        self.assertEqual(self.db.get_product_stock("e2e-asus-rtx-4060"), vga_before)
        war = self.new_client().get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertIn("Đã hủy", war.text)
