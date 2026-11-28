"""Tier 3 — Cross-Feature Pairwise Combinations (12 Scenarios: T3-01 .. T3-12)."""
from __future__ import annotations

from config import ADMIN_EMAIL, ADMIN_PASS, CUSTOMER_EMAIL, CUSTOMER_PASS, STAFF_EMAIL, STAFF_PASS
from lib.base_case import E2EBaseTestCase, uid
from lib.http_client import make_valid_jpeg_bytes, make_valid_png_bytes, parse_html


def _select_opt_by_text(doc, name: str, needle: str) -> str:
    for opt in doc.xpath(f'//select[@name="{name}"]/option'):
        if needle.lower() in (opt.text_content() or "").lower() and opt.get("value"):
            return str(opt.get("value"))
    raise AssertionError(f"Option {needle!r} not found in select[@name='{name}']")


class TestT3Combinations(E2EBaseTestCase):
    def test_T3_01_admin_brand_multi_image_to_live_search_and_collection(self) -> None:
        adm = self.new_client()
        adm.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        sfx = uid("zotac")
        brand_name = f"ZOTAC_{sfx}"
        adm.submit_form(
            "/admin/brands.php",
            {"name": brand_name, "slug": f"zotac-{sfx}", "country": "Hong Kong"},
            form_xpath='//form[.//input[@name="name"]]',
        )
        p_page = adm.get("/admin/products.php", allow_4xx=False)
        p_doc = parse_html(p_page)
        form_xpath = '//form[.//input[@name="name"] and .//input[@name="price"]]'
        prod_name = f"ZOTAC RTX 4060 Twin {sfx}"
        overrides = {"name": prod_name, "price": "7990000", "stock": "7"}
        if p_doc.xpath(f'{form_xpath}//select[@name="brand_id"]'):
            overrides["brand_id"] = _select_opt_by_text(p_doc, "brand_id", brand_name)
        elif p_doc.xpath(f'{form_xpath}//input[@name="brand"] | {form_xpath}//select[@name="brand"]'):
            overrides["brand"] = brand_name
        if p_doc.xpath(f'{form_xpath}//select[@name="category_id"]'):
            overrides["category_id"] = _select_opt_by_text(p_doc, "category_id", "VGA")
        elif p_doc.xpath(f'{form_xpath}//select[@name="category"]'):
            overrides["category"] = _select_opt_by_text(p_doc, "category", "VGA")

        files = []
        if p_doc.xpath(f'{form_xpath}//input[@name="image_file"]'):
            files.append(("image_file", (f"z1_{sfx}.png", make_valid_png_bytes((20, 40, 200)), "image/png")))
        if p_doc.xpath(f'{form_xpath}//input[contains(@name, "gallery")]'):
            gname = p_doc.xpath(f'{form_xpath}//input[contains(@name, "gallery")]/@name')[0]
            files.append((gname, (f"z2_{sfx}.jpg", make_valid_jpeg_bytes((200, 40, 20)), "image/jpeg")))
        adm.submit_form(p_page, overrides, files=files or None, form_xpath=form_xpath)

        vis = self.new_client()
        sr = vis.get("/api/search.php", params={"q": sfx}, allow_4xx=False).json()
        items = sr.get("items") or []
        self.assertTrue(any(prod_name in i.get("name", "") and int(i.get("stock", -1)) == 7 for i in items))

        col = vis.get("/collection.php", params={"brand": brand_name}, allow_4xx=False)
        self.assertIn(prod_name, col.text)
        links = parse_html(col).xpath('//a[contains(@href, "product.php?slug=")]/@href')
        self.assertTrue(links)
        det = vis.get("/" + links[0].lstrip("/"), allow_4xx=False)
        ddoc = parse_html(det)
        gallery_xpath = (
            '//*[@id="product-gallery" or contains(@class,"gallery") '
            'or contains(@class,"product-thumb") or contains(@class,"thumb")]//img[@src] '
            '| //img[@data-gallery-thumb or @id="main-product-image" '
            'or contains(@class,"main-product-image") or @id="product-main-img"]'
        )
        gallery_srcs = {img.get("src") for img in ddoc.xpath(gallery_xpath) if img.get("src")}
        self.assertGreaterEqual(
            len(gallery_srcs),
            2,
            f"Expected >= 2 unique gallery/main product images on product.php, got {sorted(gallery_srcs)!r}",
        )

    def test_T3_02_staff_walkin_order_decrements_live_search_and_product_stock(self) -> None:
        slug = "e2e-asus-rtx-4060"
        stock_before = self.db.get_product_stock(slug)
        pid = self.db.get_product_id(slug)

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        wk = staff.get("/admin/walkin-order.php", allow_4xx=False)
        doc = parse_html(wk)
        form_xpath = '//form[.//input[@name="customer_name"]]'
        overrides = {"customer_name": "WalkIn T302", "payment_method": "COD", "product_id": str(pid)}
        overrides["phone" if doc.xpath(f'{form_xpath}//input[@name="phone"]') else "customer_phone"] = "0933111222"
        overrides["quantity" if doc.xpath(f'{form_xpath}//input[@name="quantity"]') else "qty"] = "2"
        staff.submit_form(wk, overrides, form_xpath=form_xpath)

        vis = self.new_client()
        sr = vis.get("/api/search.php", params={"q": "4060"}, allow_4xx=False).json()
        matched = [i for i in (sr.get("items") or []) if i.get("slug") == slug or "Dual RTX 4060" in i.get("name", "")]
        self.assertTrue(matched)
        self.assertEqual(int(matched[0]["stock"]), stock_before - 2)
        prod = vis.get("/product.php", params={"slug": slug}, allow_4xx=False)
        self.assertIn(str(stock_before - 2), prod.text)

    def test_T3_03_customer_vietqr_confirm_to_staff_approval_and_warranty(self) -> None:
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        stock_before = self.db.get_product_stock("e2e-asus-rtx-4060")
        self.add_product_to_cart(cust, "e2e-asus-rtx-4060", qty=1)
        co = cust.submit_form(
            "/checkout.php",
            {"customer_name": "T303 VietQR", "phone": "0912345003", "address": "An Giang", "payment_method": "VietQR"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        confirm_xpath = '//form[.//input[@name="action" and @value="confirm_vietqr"]]'
        target = co if parse_html(co).xpath(confirm_xpath) else cust.get("/orders.php", params={"code": code}, allow_4xx=False)
        cust.submit_form(target, {}, form_xpath=confirm_xpath)

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        adm = staff.get("/admin/orders.php", allow_4xx=False)
        approve_xpath = (
            f'//tr[contains(., "{code}")]//form[.//input[contains(@value, "approve") or contains(@value, "paid")]] | '
            f'//tr[contains(., "{code}")]//form[.//select[@name="payment_status"]] | '
            f'//div[contains(., "{code}")]//form[.//input[contains(@value, "approve") or contains(@value, "paid")]]'
        )
        if parse_html(adm).xpath(approve_xpath):
            staff.submit_form(adm, {"payment_status": "paid"} if parse_html(adm).xpath(f'{approve_xpath}//select[@name="payment_status"]') else {}, form_xpath=approve_xpath)
        else:
            staff.submit_form(
                adm,
                {"status": "Đang giao hàng"},
                form_xpath=f'//tr[contains(., "{code}")]//form[.//select[@name="status"]]',
            )

        self.assertEqual(self.db.get_product_stock("e2e-asus-rtx-4060"), stock_before - 1)
        war = cust.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertIn("Còn hạn", war.text)

    def test_T3_04_pc_builder_vietqr_full_pipeline_and_6_warranties(self) -> None:
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        page = cust.get("/pc-builder.php", allow_4xx=False)
        co = cust.submit_form(
            page,
            {
                "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")),
                "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
                "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
                "vga_id": str(self.db.get_product_id("e2e-asus-rtx-4060")),
                "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
                "psu_id": str(self.db.get_product_id("e2e-corsair-cv550")),
                "customer_name": "T304 PC VietQR",
                "phone": "0912345004",
                "address": "An Giang",
                "payment_method": "VietQR",
            },
            form_xpath='//form[.//input[@name="customer_name"]]',
        )
        code = self.extract_order_code(co.text)
        confirm_xpath = '//form[.//input[@name="action" and @value="confirm_vietqr"]]'
        if parse_html(co).xpath(confirm_xpath):
            cust.submit_form(co, {}, form_xpath=confirm_xpath)

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        for st in ("Đang lắp ráp", "Đang giao hàng", "Hoàn thành"):
            adm = staff.get("/admin/orders.php", allow_4xx=False)
            staff.submit_form(
                adm,
                {"status": st},
                form_xpath=f'//tr[contains(., "{code}")]//form[.//select[@name="status"]] | //div[contains(., "{code}")]//form[.//select[@name="status"]]',
            )

        ord_view = cust.get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Hoàn thành", ord_view.text)
        war = cust.get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertGreaterEqual(len(self.extract_warranty_codes(war.text)), 6)

    def test_T3_05_pc_build_cancellation_restocks_all_parts_and_voids_warranties(self) -> None:
        cpu_before = self.db.get_product_stock("e2e-intel-i5-13400f")
        ram_before = self.db.get_product_stock("e2e-corsair-16gb-ddr4")
        page = self.http.get("/pc-builder.php", allow_4xx=False)
        co = self.http.submit_form(
            page,
            {
                "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")),
                "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
                "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
                "vga_id": str(self.db.get_product_id("e2e-asus-rtx-4060")),
                "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
                "psu_id": str(self.db.get_product_id("e2e-corsair-cv550")),
                "customer_name": "T305 Cancel",
                "phone": "0912345005",
                "address": "An Giang",
                "payment_method": "COD",
            },
            form_xpath='//form[.//input[@name="customer_name"]]',
        )
        code = self.extract_order_code(co.text)

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        for st in ("Đang lắp ráp", "Đã hủy"):
            adm = staff.get("/admin/orders.php", allow_4xx=False)
            staff.submit_form(
                adm,
                {"status": st},
                form_xpath=f'//tr[contains(., "{code}")]//form[.//select[@name="status"]] | //div[contains(., "{code}")]//form[.//select[@name="status"]]',
            )

        self.assertEqual(self.db.get_product_stock("e2e-intel-i5-13400f"), cpu_before)
        self.assertEqual(self.db.get_product_stock("e2e-corsair-16gb-ddr4"), ram_before)
        war = self.new_client().get("/warranty.php", params={"q": code}, allow_4xx=False)
        self.assertIn("Đã hủy", war.text)

    def test_T3_06_staff_admin_pw_stock_import_enables_pc_builder_part(self) -> None:
        slug = "e2e-intel-i5-13400f"
        orig = self.db.get_product_stock(slug)
        try:
            self.db.set_product_stock(slug, 0)
            staff = self.new_client()
            staff.login_admin(STAFF_EMAIL, STAFF_PASS)
            pid = self.db.get_product_id(slug)
            edit_pg = staff.get("/admin/products.php", params={"edit": pid}, allow_4xx=False)
            staff.submit_form(
                edit_pg,
                {"stock": "5", "admin_password": ADMIN_PASS},
                form_xpath='//form[.//input[@name="name"] and .//input[@name="stock"]]',
            )
            self.assertEqual(self.db.get_product_stock(slug), 5)

            buyer = self.new_client()
            pb = buyer.get("/pc-builder.php", allow_4xx=False)
            self.assertIn("Intel Core i5-13400F", pb.text)
            co = buyer.submit_form(
                pb,
                {
                    "cpu_id": str(pid),
                    "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
                    "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
                    "vga_id": str(self.db.get_product_id("e2e-asus-rtx-4060")),
                    "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
                    "psu_id": str(self.db.get_product_id("e2e-corsair-cv550")),
                    "customer_name": "T306 Build",
                    "phone": "0912345006",
                    "address": "An Giang",
                    "payment_method": "COD",
                },
                form_xpath='//form[.//input[@name="customer_name"]]',
            )
            self.extract_order_code(co.text)
        finally:
            self.db.set_product_stock(slug, orig)

    def test_T3_07_customer_profile_update_prefills_checkout_and_persists_history(self) -> None:
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        new_phone = "0977888999"
        new_addr = "Cần Thơ T307"
        cust.submit_form(
            "/profile.php",
            {"name": "Khách Hàng Mẫu", "phone": new_phone, "address": new_addr},
            form_xpath='//form[.//input[@name="phone"]]',
        )
        self.add_product_to_cart(cust, "e2e-samsung-980-pro-1tb", qty=1)
        co_page = cust.get("/checkout.php", allow_4xx=False)
        self.assertIn(new_phone, co_page.text)
        self.assertIn(new_addr, co_page.text)
        co = cust.submit_form(co_page, {"payment_method": "COD"}, form_xpath='//form[.//input[@name="payment_method"]]')
        code = self.extract_order_code(co.text)
        self.assertIn(code, cust.get("/orders.php", allow_4xx=False).text)
        self.assertIn("Còn hạn", cust.get("/warranty.php", params={"q": new_phone}, allow_4xx=False).text)

    def test_T3_08_customer_tradein_to_staff_quote_to_customer_lookup(self) -> None:
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        phone = "0977888008"
        sub = cust.submit_form(
            "/trade-in.php",
            {"customer_name": "Khách Hàng Mẫu", "phone": phone, "device_name": "RTX 3060 12GB", "device_condition": "Fullbox"},
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        tcode = self.extract_tradein_code(sub.text)

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        adm = staff.get("/admin/trade-ins.php", allow_4xx=False)
        staff.submit_form(
            adm,
            {"estimated_value": "5500000", "status": "Đã báo giá", "admin_note": "Giá tốt T308"},
            form_xpath=f'//tr[contains(., "{tcode}")]//form | //div[contains(., "{tcode}")]//form',
        )

        lk = cust.get("/trade-in.php", params={"q": phone}, allow_4xx=False)
        self.assertIn("Đã báo giá", lk.text)
        self.assertIn("5.500.000", lk.text)

    def test_T3_09_admin_creates_staff_then_new_staff_logs_in_and_makes_walkin_order(self) -> None:
        adm = self.new_client()
        adm.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        email = f"staff2_{uid()}@doan6.local"
        adm.submit_form(
            "/admin/staff.php",
            {"name": "Staff Hai", "email": email, "password": "staff2pass", "phone": "0908887776"},
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )

        s2 = self.new_client()
        s2_home = s2.login_admin(email, "staff2pass")
        self.assertNotIn("staff.php", parse_html(s2_home).xpath("//aside//a/@href"))
        wk = s2.get("/admin/walkin-order.php", allow_4xx=False)
        doc = parse_html(wk)
        fx = '//form[.//input[@name="customer_name"]]'
        overrides = {"customer_name": "WalkIn S2", "payment_method": "COD", "product_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb"))}
        overrides["phone" if doc.xpath(f'{fx}//input[@name="phone"]') else "customer_phone"] = "0933222111"
        overrides["quantity" if doc.xpath(f'{fx}//input[@name="quantity"]') else "qty"] = "1"
        res = s2.submit_form(wk, overrides, form_xpath=fx)
        self.extract_order_code(res.text)

    def test_T3_10_admin_publishes_tech_review_article_rendered_on_storefront(self) -> None:
        adm = self.new_client()
        adm.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        sfx = uid("rev")
        title = f"Review VGA ASUS {sfx}"
        body = f"Phân tích hiệu năng thực tế chi tiết {sfx}"
        adm.submit_form(
            "/admin/news.php",
            {"title": title, "category": "Review", "summary": f"Tóm tắt {sfx}", "content": f"<p>{body}</p>"},
            form_xpath='//form[.//input[@name="title"]]',
        )
        vis = self.new_client()
        news_pg = vis.get("/news.php", allow_4xx=False)
        self.assertIn(title, news_pg.text)
        links = parse_html(news_pg).xpath(f'//a[contains(., "{sfx}")]/@href')
        self.assertTrue(links)
        det = vis.get("/" + links[0].lstrip("/"), allow_4xx=False)
        self.assertIn(body, det.text)

    def test_T3_11_customer_order_cancel_restores_live_search_stock(self) -> None:
        slug = "e2e-msi-rtx-4070-super"
        orig = self.db.get_product_stock(slug)
        try:
            self.db.set_product_stock(slug, 2)
            cust = self.new_client()
            cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
            self.add_product_to_cart(cust, slug, qty=2)
            co = cust.submit_form(
                "/checkout.php",
                {"customer_name": "T311 Cancel", "phone": "0912345011", "address": "An Giang", "payment_method": "COD"},
                form_xpath='//form[.//input[@name="payment_method"]]',
            )
            code = self.extract_order_code(co.text)

            sr_zero = self.new_client().get("/api/search.php", params={"q": "4070"}, allow_4xx=False).json()
            m0 = [i for i in (sr_zero.get("items") or []) if "4070" in i.get("name", "")]
            self.assertTrue(m0 and int(m0[0]["stock"]) == 0)

            ord_pg = cust.get("/orders.php", params={"code": code}, allow_4xx=False)
            cust.submit_form(ord_pg, {}, form_xpath='//form[.//input[@name="action" and @value="cancel_order"]]')

            sr_restored = self.new_client().get("/api/search.php", params={"q": "4070"}, allow_4xx=False).json()
            m2 = [i for i in (sr_restored.get("items") or []) if "4070" in i.get("name", "")]
            self.assertTrue(m2 and int(m2[0]["stock"]) == 2)
        finally:
            self.db.set_product_stock(slug, orig)

    def test_T3_12_cross_role_session_isolation_and_profile_audit(self) -> None:
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        c_addr = f"An Giang Audit {uid()}"
        cust.submit_form("/profile.php", {"name": "Khách Hàng Mẫu", "phone": "0912345678", "address": c_addr}, form_xpath='//form[.//input[@name="phone"]]')

        staff = self.new_client()
        staff.login_admin(STAFF_EMAIL, STAFF_PASS)
        s_phone = "0905556667"
        staff.submit_form("/admin/profile.php", {"name": "Nhân Viên Bán Hàng", "phone": s_phone, "address": "Long Xuyên"}, form_xpath='//form[.//input[@name="phone"]]')

        adm = self.new_client()
        adm.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        users_pg = adm.get("/admin/users.php", allow_4xx=False)
        self.assertIn(c_addr, users_pg.text)
        self.assertNotIn("Thêm khách hàng", users_pg.text)
        staff_pg = adm.get("/admin/staff.php", allow_4xx=False)
        self.assertIn(s_phone, staff_pg.text)
