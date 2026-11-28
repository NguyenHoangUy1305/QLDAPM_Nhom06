"""Tier 2 — Boundary, Negative & Security Cases (54 Test Cases: T2-F01-01 .. T2-F10-05)."""
from __future__ import annotations

import re

from config import ADMIN_EMAIL, ADMIN_PASS, CUSTOMER_EMAIL, CUSTOMER_PASS, STAFF_EMAIL, STAFF_PASS
from lib.base_case import E2EBaseTestCase, uid
from lib.cdp_browser import CdpBrowser
from lib.http_client import parse_html


class TestT2F01BrandMediaBoundaries(E2EBaseTestCase):
    def test_T2_F01_01_invalid_image_upload_rejected(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        page = self.http.get("/admin/products.php", allow_4xx=False)
        doc = parse_html(page)
        form_xpath = '//form[.//input[@name="name"] and .//input[@name="price"]]'
        forms = doc.xpath(form_xpath)
        self.assertTrue(forms, "Product form missing on /admin/products.php")
        file_field = "image_file" if forms[0].xpath('.//input[@name="image_file"]') else "image"
        prod_name = f"BadImgProd_{uid()}"
        resp = self.http.submit_form(
            page,
            {"name": prod_name, "price": "1000000", "stock": "5"},
            files=[(file_field, ("shell.php", b"<?php echo 'hacked'; ?>", "application/x-php"))],
            form_xpath=form_xpath,
        )
        self.assert_contains_any(
            resp.text,
            [
                "không hợp lệ",
                "lỗi",
                "không được phép",
                "từ chối",
                "thất bại",
                "không hỗ trợ",
                "không được hỗ trợ",
                "không đúng định dạng",
                "sai định dạng",
                "không chấp nhận",
                "không cho phép",
            ],
            "Expected invalid image error",
        )
        prod_page = self.http.get("/admin/products.php", allow_4xx=False)
        self.assertNotIn(prod_name, prod_page.text, f"Product {prod_name!r} with invalid .php upload was persisted")

    def test_T2_F01_02_nonexistent_brand_empty_state(self) -> None:
        resp = self.http.get("/collection.php", params={"brand": "BrandKhongTonTai999"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assert_contains_any(resp.text, ["không tìm thấy", "không có sản phẩm", "0 sản phẩm"])

    def test_T2_F01_03_empty_brand_name_rejected(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        resp = self.http.submit_form(
            "/admin/brands.php",
            {"name": ""},
            form_xpath='//form[.//input[@name="name"]]',
        )
        self.assert_contains_any(
            resp.text,
            [
                "không được trống",
                "không được để trống",
                "không để trống",
                "không được bỏ trống",
                "bỏ trống",
                "là bắt buộc",
                "vui lòng nhập đầy đủ",
                "vui lòng điền đầy đủ",
                "không hợp lệ",
                "lỗi",
                "chưa nhập",
                "thất bại",
            ],
        )

    def test_T2_F01_04_duplicate_brand_slug_graceful_error(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        resp = self.http.submit_form(
            "/admin/brands.php",
            {"name": "ASUS", "slug": "asus"},
            form_xpath='//form[.//input[@name="name"]]',
        )
        self.assert_contains_any(resp.text, ["tồn tại", "trùng", "lỗi"])

    def test_T2_F01_05_xss_in_brand_or_product_name_escaped(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        marker = f"XSS_{uid()}"
        xss_name = f"{marker}<script>alert('xss')</script>"
        page = self.http.get("/admin/brands.php", allow_4xx=False)
        resp = self.http.submit_form(
            page,
            {"name": xss_name, "slug": f"xss-brand-{uid()}"},
            form_xpath='//form[.//input[@name="name"]]',
        )
        self.assertIn(marker, resp.text)
        self.assertNotIn("<script>alert('xss')</script>", resp.text)

    def test_T2_F01_06_product_without_extra_gallery_renders_fallback_cleanly(self) -> None:
        resp = self.http.get("/product.php", params={"slug": "e2e-corsair-cv550"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assertIn("Corsair CV550", resp.text)
        doc = parse_html(resp)
        self.assertTrue(doc.xpath("//img[@src]"), "Product page must render at least a primary/fallback <img>")


class TestT2F02LiveSearchBoundaries(E2EBaseTestCase):
    def test_T2_F02_01_search_empty_and_whitespace_returns_empty(self) -> None:
        for q in ("", "   "):
            resp = self.http.get("/api/search.php", params={"q": q}, allow_4xx=False)
            self.assertEqual(resp.status_code, 200)
            data = resp.json()
            self.assertTrue(data.get("ok"))
            self.assertEqual(data.get("items"), [])

    def test_T2_F02_02_search_nonexistent_query_returns_empty(self) -> None:
        resp = self.http.get("/api/search.php", params={"q": "xyz999nonexistent"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertTrue(data.get("ok"))
        self.assertEqual(data.get("items"), [])

    def test_T2_F02_03_search_out_of_stock_item_reports_zero_stock(self) -> None:
        resp = self.http.get("/api/search.php", params={"q": "4090"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        items = resp.json().get("items") or []
        oos = [i for i in items if "4090" in (i.get("name") or "")]
        self.assertTrue(oos, "Out-of-stock RTX 4090 test fixture should be discoverable in search")
        self.assertEqual(int(oos[0]["stock"]), 0)

    def test_T2_F02_04_search_special_like_wildcards_escaped(self) -> None:
        for ch in ("%", "_"):
            resp = self.http.get("/api/search.php", params={"q": ch}, allow_4xx=False)
            self.assertEqual(resp.status_code, 200)
            items = resp.json().get("items") or []
            self.assertLess(len(items), 8, f"Wildcard {ch!r} should not dump entire catalog via SQL LIKE")

    def test_T2_F02_05_search_sqli_and_xss_safe(self) -> None:
        sqli = self.http.get("/api/search.php", params={"q": "' OR '1'='1"}, allow_4xx=False)
        self.assertEqual(sqli.status_code, 200)
        self.assertEqual(sqli.json().get("items"), [])
        xss_resp = self.http.get("/collection.php", params={"q": "<img src=x onerror=alert(1)>"}, allow_4xx=False)
        self.assertEqual(xss_resp.status_code, 200)
        self.assertNotIn("<img src=x onerror=alert(1)>", xss_resp.text)


class TestT2F03CheckoutVietQRBoundaries(E2EBaseTestCase):
    def test_T2_F03_01_tampered_tragop_payment_method_rejected(self) -> None:
        stock_before = self.db.get_product_stock("e2e-intel-i5-13400f")
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        co = self.http.get("/checkout.php", allow_4xx=False)
        csrf = self.http.extract_csrf(co)
        resp = self.http.post(
            "/checkout.php",
            data={
                "csrf_token": csrf,
                "customer_name": "Tamper User",
                "phone": "0912345678",
                "address": "An Giang",
                "payment_method": "Trả góp 0%",
            },
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "chỉ hỗ trợ", "lỗi"])
        self.assertIsNone(re.search(r"\bDH\d+\b", resp.text), "Tampered payment method unexpectedly generated an order code!")
        self.assertEqual(self.db.get_product_stock("e2e-intel-i5-13400f"), stock_before)

    def test_T2_F03_02_invalid_phone_or_empty_checkout_fields_rejected(self) -> None:
        stock_before = self.db.get_product_stock("e2e-intel-i5-13400f")
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        resp = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "", "phone": "12345", "address": "", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "vui lòng nhập", "trống", "bắt buộc", "lỗi"])
        self.assertIsNone(re.search(r"\bDH\d+\b", resp.text), "Invalid checkout fields unexpectedly generated an order code!")
        self.assertEqual(self.db.get_product_stock("e2e-intel-i5-13400f"), stock_before)

    def test_T2_F03_03_checkout_out_of_stock_race_rejected(self) -> None:
        slug = "e2e-msi-rtx-4070-super"
        orig = self.db.get_product_stock(slug)
        try:
            self.db.set_product_stock(slug, 1)
            self.add_product_to_cart(self.http, slug, qty=1)
            co_page = self.http.get("/checkout.php", allow_4xx=False)
            # Simulate concurrent exhaustion before POST checkout submits
            self.db.set_product_stock(slug, 0)
            resp = self.http.submit_form(
                co_page,
                {"customer_name": "Race User", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
                form_xpath='//form[.//input[@name="payment_method"]]',
            )
            self.assert_contains_any(resp.text, ["hết hàng", "không đủ", "chỉ còn", "tồn kho", "lỗi"])
            self.assertEqual(self.db.get_product_stock(slug), 0)
        finally:
            self.db.set_product_stock(slug, orig)

    def test_T2_F03_04_expired_vietqr_confirm_rejected(self) -> None:
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Expired QR", "phone": "0912345688", "address": "An Giang", "payment_method": "VietQR"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        # Controlled fixture setup: backdate payment_expires_at beyond 15 minutes
        self.db.expire_vietqr_order(code, minutes_ago=30)

        confirm_xpath = '//form[.//input[@name="action" and @value="confirm_vietqr"]]'
        doc = parse_html(co)
        target_resp = co
        if not doc.xpath(confirm_xpath):
            target_resp = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        c_resp = self.http.submit_form(target_resp, {}, form_xpath=confirm_xpath)
        self.assert_contains_any(c_resp.text, ["hết hạn", "expired", "quá hạn", "không thể xác nhận"])
        check_ord = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        low_ord = check_ord.text.lower()
        self.assertNotIn("pending_confirm", low_ord)
        self.assertNotIn("chờ duyệt", low_ord)
        self.assertNotIn("chờ xác nhận thanh toán", low_ord)

    def test_T2_F03_05_checkout_and_cart_csrf_rejected(self) -> None:
        pid = self.db.get_product_id("e2e-intel-i5-13400f")
        r_cart = self.http.post("/cart.php", data={"action": "add", "product_id": str(pid), "csrf_token": "INVALID_CSRF"})
        self.assertTrue(
            r_cart.status_code == 403 or "csrf" in r_cart.text.lower() or "hết hạn" in r_cart.text.lower(),
            f"Missing/invalid CSRF was not rejected on cart.php (status={r_cart.status_code})",
        )
        self.add_product_to_cart(self.http, "e2e-intel-i5-13400f", qty=1)
        r_co = self.http.post(
            "/checkout.php",
            data={
                "csrf_token": "INVALID_CSRF",
                "customer_name": "No CSRF",
                "phone": "0912345678",
                "address": "An Giang",
                "payment_method": "COD",
            },
        )
        self.assertTrue(
            r_co.status_code == 403 or "csrf" in r_co.text.lower() or "không hợp lệ" in r_co.text.lower(),
            f"Invalid CSRF was not rejected on checkout.php (status={r_co.status_code})",
        )

    def test_T2_F03_06_empty_cart_checkout_rejected(self) -> None:
        self.http.clear_cookies()
        resp = self.http.get("/checkout.php", allow_redirects=True)
        self.assertTrue(
            "cart.php" in resp.url or "trống" in resp.text.lower() or "chưa có sản phẩm" in resp.text.lower(),
            "Empty cart checkout should redirect to cart or show empty cart warning",
        )


class TestT2F04WarrantyOrderBoundaries(E2EBaseTestCase):
    def test_T2_F04_01_nonexistent_warranty_clean_empty_state(self) -> None:
        resp = self.http.get("/warranty.php", params={"q": "BH99999999"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assert_contains_any(resp.text, ["không tìm thấy", "chưa có", "không tồn tại"])

    def test_T2_F04_02_empty_warranty_query_does_not_dump_all_records(self) -> None:
        resp = self.http.get("/warranty.php", allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assertEqual(len(self.extract_warranty_codes(resp.text)), 0, "Empty warranty query must not publicly dump warranty codes")

    def test_T2_F04_03_double_cancel_order_rejected_and_no_double_restock(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        slug = "e2e-samsung-980-pro-1tb"
        stock_before = self.db.get_product_stock(slug)
        self.add_product_to_cart(self.http, slug, qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Double Cancel", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        ord_page = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        cancel_xpath = '//form[.//input[@name="action" and @value="cancel_order"]]'
        # Extract payload before 1st cancel so we can replay 2nd cancel
        doc = parse_html(ord_page)
        forms = doc.xpath(cancel_xpath)
        self.assertTrue(forms, "Cancel form not found")
        oid_vals = forms[0].xpath('.//input[@name="order_id"]/@value') or forms[0].xpath('.//input[@name="id"]/@value')
        first_cancel = self.http.submit_form(ord_page, {}, form_xpath=cancel_xpath)
        self.assertIn("Đã hủy", first_cancel.text)
        self.assertEqual(self.db.get_product_stock(slug), stock_before)

        # Replay cancel a second time
        fresh_csrf = self.http.extract_csrf(self.http.get("/orders.php", params={"code": code}, allow_4xx=False))
        second_payload = {"csrf_token": fresh_csrf, "action": "cancel_order", "order_code": code}
        if oid_vals:
            second_payload["order_id"] = oid_vals[0]
            second_payload["id"] = oid_vals[0]
        second_cancel = self.http.post("/orders.php", data=second_payload)
        self.assertEqual(self.db.get_product_stock(slug), stock_before, "Double cancel must NOT increment stock twice")
        self.assert_contains_any(second_cancel.text, ["không thể", "đã hủy", "lỗi"])

    def test_T2_F04_04_cancel_after_shipping_rejected(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        self.add_product_to_cart(self.http, "e2e-samsung-980-pro-1tb", qty=1)
        co = self.http.submit_form(
            "/checkout.php",
            {"customer_name": "Shipped Cancel", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        ord_page = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        doc = parse_html(ord_page)
        oid = (doc.xpath('//form[.//input[@name="action" and @value="cancel_order"]]//input[@name="order_id" or @name="id"]/@value') or [""])[0]

        adm = self.new_client()
        adm.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        ap = adm.get("/admin/orders.php", allow_4xx=False)
        adm.submit_form(
            ap,
            {"status": "Đang giao hàng"},
            form_xpath=f'//tr[contains(., "{code}")]//form[.//select[@name="status"]] | //div[contains(., "{code}")]//form[.//select[@name="status"]]',
        )

        csrf = self.http.extract_csrf(self.http.get("/orders.php", params={"code": code}, allow_4xx=False))
        r = self.http.post("/orders.php", data={"csrf_token": csrf, "action": "cancel_order", "order_id": oid, "order_code": code})
        self.assert_contains_any(r.text, ["không thể hủy", "đang giao", "lỗi"])
        check = self.http.get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Đang giao", check.text)

    def test_T2_F04_05_orders_sqli_and_cross_user_cancel_blocked(self) -> None:
        sqli = self.http.get("/orders.php", params={"code": "' OR 1=1--"}, allow_4xx=False)
        self.assertEqual(sqli.status_code, 200)
        self.assertIsNone(re.search(r"\bDH\d+\b", sqli.text), "SQLi in /orders.php leaked order codes!")
        self.assert_contains_any(sqli.text, ["không tìm thấy", "chưa có", "không tồn tại"])

        # Customer A creates order; unowned Customer B tries to cancel A's order
        cust_a = self.new_client()
        cust_a.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        self.add_product_to_cart(cust_a, "e2e-samsung-980-pro-1tb", qty=1)
        co = cust_a.submit_form(
            "/checkout.php",
            {"customer_name": "Cust A", "phone": "0912345678", "address": "An Giang", "payment_method": "COD"},
            form_xpath='//form[.//input[@name="payment_method"]]',
        )
        code = self.extract_order_code(co.text)
        a_ord = cust_a.get("/orders.php", params={"code": code}, allow_4xx=False)
        oid = (parse_html(a_ord).xpath('//form[.//input[@name="action" and @value="cancel_order"]]//input[@name="order_id" or @name="id"]/@value') or [""])[0]

        cust_b = self.new_client()
        b_email = f"custb_{uid()}@example.com"
        reg_page = cust_b.get("/register.php", allow_4xx=False)
        cust_b.submit_form(
            reg_page,
            {"name": "Cust B", "email": b_email, "phone": "0919000999", "password": "pass1234", "confirm_password": "pass1234"},
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )
        b_ord = cust_b.get("/orders.php", allow_4xx=False)
        b_csrf = cust_b.extract_csrf(b_ord) if "csrf_token" in b_ord.text else cust_b.extract_csrf(cust_b.get("/profile.php"))
        r_cross = cust_b.post("/orders.php", data={"csrf_token": b_csrf, "action": "cancel_order", "order_id": oid, "order_code": code})
        self.assertTrue(r_cross.status_code == 403 or "không" in r_cross.text.lower())
        a_after = cust_a.get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Chờ xác nhận", a_after.text)

    def test_T2_F04_06_nonexistent_order_code_lookup_clean_empty_state(self) -> None:
        resp = self.http.get("/orders.php", params={"code": "DH9999999"}, allow_4xx=False)
        self.assertEqual(resp.status_code, 200)
        self.assert_contains_any(resp.text, ["không tìm thấy", "chưa có", "không tồn tại"])


class TestT2F05TradeInBoundaries(E2EBaseTestCase):
    def test_T2_F05_01_tradein_empty_required_fields_rejected(self) -> None:
        resp = self.http.submit_form(
            "/trade-in.php",
            {"customer_name": "", "phone": "0987654321", "device_name": "", "device_condition": ""},
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        self.assert_contains_any(
            resp.text,
            [
                "không được trống",
                "không được để trống",
                "không để trống",
                "không được bỏ trống",
                "bỏ trống",
                "là bắt buộc",
                "vui lòng nhập đầy đủ",
                "vui lòng điền đầy đủ",
                "không hợp lệ",
                "lỗi",
                "nhập thiết bị",
                "nhập họ tên",
                "chưa nhập",
            ],
        )
        self.assertIsNone(
            re.search(r"\bTC\d+\b", resp.text),
            "Empty required fields unexpectedly created a trade-in request code!",
        )

    def test_T2_F05_02_tradein_invalid_phone_rejected(self) -> None:
        resp = self.http.submit_form(
            "/trade-in.php",
            {"customer_name": "Bad Phone", "phone": "abc123", "device_name": "RTX 3060", "device_condition": "Good"},
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "sai định dạng", "lỗi"])
        self.assertIsNone(re.search(r"\bTC\d+\b", resp.text), "Invalid phone unexpectedly created trade-in code!")

    def test_T2_F05_03_tradein_negative_estimated_value_rejected(self) -> None:
        sub = self.http.submit_form(
            "/trade-in.php",
            {"customer_name": "Neg Val", "phone": "0987650099", "device_name": "GTX 1080", "device_condition": "OK"},
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        code = self.extract_tradein_code(sub.text)
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        adm = self.http.get("/admin/trade-ins.php", allow_4xx=False)
        resp = self.http.submit_form(
            adm,
            {"estimated_value": "-500000", "status": "Đã báo giá"},
            form_xpath=f'//tr[contains(., "{code}")]//form | //div[contains(., "{code}")]//form',
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "âm", "lớn hơn", "lỗi"])

    def test_T2_F05_04_tradein_xss_in_notes_escaped(self) -> None:
        phone = "0987650088"
        self.http.submit_form(
            "/trade-in.php",
            {
                "customer_name": "XSS TradeIn",
                "phone": phone,
                "device_name": "RX 580",
                "device_condition": "<script>alert(2)</script>",
            },
            form_xpath='//form[.//input[@name="device_name"]]',
        )
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        adm = self.http.get("/admin/trade-ins.php", allow_4xx=False)
        self.assertNotIn("<script>alert(2)</script>", adm.text)

    def test_T2_F05_05_tradein_missing_csrf_and_unauth_admin_blocked(self) -> None:
        r_pub = self.http.post(
            "/trade-in.php",
            data={"customer_name": "A", "phone": "0987654321", "device_name": "GPU", "device_condition": "OK", "csrf_token": "BAD"},
        )
        self.assertTrue(r_pub.status_code == 403 or "csrf" in r_pub.text.lower() or "hết hạn" in r_pub.text.lower())
        r_adm = self.new_client().post("/admin/trade-ins.php", data={"estimated_value": "1000"}, allow_redirects=True)
        self.assertTrue(r_adm.status_code == 403 or "admin/login.php" in r_adm.url)


class TestT2F06AdminRbacBoundaries(E2EBaseTestCase):
    def test_T2_F06_01_admin_add_customer_blocked_ui_and_post(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        users = self.http.get("/admin/users.php", allow_4xx=False)
        self.assertNotIn("Thêm khách hàng", users.text)
        csrf = self.http.extract_csrf(users) if "csrf_token" in users.text else self.http.extract_csrf(self.http.get("/admin/profile.php"))
        r = self.http.post(
            "/admin/users.php",
            data={
                "csrf_token": csrf,
                "action": "create_customer",
                "name": "Forged Cust",
                "email": f"forged_{uid()}@example.com",
                "password": "password123",
                "role": "customer",
            },
        )
        self.assertTrue(
            r.status_code == 403 or "không được phép" in r.text.lower() or "không hỗ trợ" in r.text.lower(),
            f"Admin forged customer creation must be blocked (status={r.status_code})",
        )

    def test_T2_F06_02_duplicate_staff_email_graceful_error(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        resp = self.http.submit_form(
            "/admin/staff.php",
            {"name": "Dup Staff", "email": STAFF_EMAIL, "password": "staff1234", "phone": "0900111222"},
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )
        self.assert_contains_any(resp.text, ["tồn tại", "trùng", "đã được", "lỗi"])

    def test_T2_F06_03_negative_product_price_or_stock_rejected(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        prod_name = f"NegProd_{uid()}"
        resp = self.http.submit_form(
            "/admin/products.php",
            {"name": prod_name, "price": "-1000", "stock": "-5"},
            form_xpath='//form[.//input[@name="name"] and .//input[@name="price"]]',
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "âm", "lớn hơn", "lỗi"])
        prod_page = self.http.get("/admin/products.php", allow_4xx=False)
        self.assertNotIn(prod_name, prod_page.text, f"Rejected negative-price/stock product {prod_name!r} was persisted")

    def test_T2_F06_04_legacy_hardcoded_demo_admin_bypass_removed(self) -> None:
        r_cust = self.http.login_customer("demo@doan6.local", "123456")
        self.assertIn("login.php", r_cust.url)
        self.assert_contains_any(r_cust.text, ["không chính xác", "sai", "thất bại", "lỗi"])
        r_adm = self.new_client().login_admin("demo@doan6.local", "123456")
        self.assertTrue("admin/login.php" in r_adm.url or "không chính xác" in r_adm.text.lower())

    def test_T2_F06_05_admin_mutations_without_csrf_rejected(self) -> None:
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        for ep in ("/admin/staff.php", "/admin/products.php", "/admin/news.php"):
            r = self.http.post(ep, data={"csrf_token": "INVALID", "name": "NoCsrf", "title": "NoCsrf"})
            self.assertTrue(r.status_code == 403 or "csrf" in r.text.lower() or "không hợp lệ" in r.text.lower(), f"CSRF not enforced on {ep}")


class TestT2F07StaffRbacBoundaries(E2EBaseTestCase):
    def test_T2_F07_01_staff_direct_get_post_staff_php_blocked(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        g = self.http.get("/admin/staff.php", allow_redirects=True)
        self.assertTrue(
            g.status_code == 403 or "staff.php" not in g.url or "không có quyền" in g.text.lower(),
            f"Staff accessed /admin/staff.php (url={g.url}, status={g.status_code})",
        )
        csrf = self.http.extract_csrf(self.http.get("/admin/profile.php"))
        p = self.http.post(
            "/admin/staff.php",
            data={"csrf_token": csrf, "name": "HackStaff", "email": f"h_{uid()}@doan6.local", "password": "pass1234"},
            allow_redirects=True,
        )
        self.assertTrue(p.status_code == 403 or "không có quyền" in p.text.lower() or "staff.php" not in p.url)

    def test_T2_F07_02_staff_wrong_admin_password_on_catalog_rejected(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        slug = "e2e-corsair-cv550"
        stock_before = self.db.get_product_stock(slug)
        pid = self.db.get_product_id(slug)
        page = self.http.get("/admin/products.php", params={"edit": pid}, allow_4xx=False)
        resp = self.http.submit_form(
            page,
            {"stock": "99", "admin_password": "wrong_admin_password"},
            form_xpath='//form[.//input[@name="name"] and .//input[@name="stock"]]',
        )
        self.assert_contains_any(resp.text, ["mật khẩu admin", "không chính xác", "không đúng", "quyền"])
        self.assertEqual(self.db.get_product_stock(slug), stock_before)

    def test_T2_F07_03_staff_empty_or_own_password_cannot_unlock_catalog(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        rejection_needles = [
            "không chính xác",
            "không đúng",
            "sai mật khẩu",
            "không hợp lệ",
            "thất bại",
            "lỗi",
            "từ chối",
            "không có quyền",
        ]
        empty_pw_needles = [
            "không được để trống",
            "không được bỏ trống",
            "không được trống",
            "không để trống",
            "chưa nhập",
            "thiếu mật khẩu",
            "cần nhập mật khẩu",
            "vui lòng nhập mật khẩu",
            "vui lòng nhập đầy đủ",
            "vui lòng điền đầy đủ",
        ]
        for bad_pw in ("", STAFF_PASS):
            cat_name = f"BlockedCat_{uid()}"
            resp = self.http.submit_form(
                "/admin/categories.php",
                {"name": cat_name, "slug": f"blocked-{uid()}", "admin_password": bad_pw},
                form_xpath='//form[.//input[@name="name"]]',
            )
            candidates = (empty_pw_needles + rejection_needles) if bad_pw == "" else rejection_needles
            self.assert_contains_any(resp.text, candidates)
            for cell in parse_html(resp).xpath("//table//td[not(.//input) and not(.//textarea)]"):
                self.assertNotIn(cat_name, cell.text_content(), f"Rejected category {cat_name!r} appeared in POST table")
            cat_page = self.http.get("/admin/categories.php", allow_4xx=False)
            self.assertNotIn(cat_name, cat_page.text, f"Rejected category {cat_name!r} was persisted to /admin/categories.php")

    def test_T2_F07_04_staff_walkin_exceeding_stock_rejected(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        slug = "e2e-asus-rtx-4060"
        stock_before = self.db.get_product_stock(slug)
        pid = self.db.get_product_id(slug)
        page = self.http.get("/admin/walkin-order.php", allow_4xx=False)
        doc = parse_html(page)
        form_xpath = '//form[.//input[@name="customer_name"]]'
        overrides = {"customer_name": "Too Many", "payment_method": "COD", "product_id": str(pid)}
        if doc.xpath(f'{form_xpath}//input[@name="phone"]'):
            overrides["phone"] = "0933444999"
        else:
            overrides["customer_phone"] = "0933444999"
        if doc.xpath(f'{form_xpath}//input[@name="quantity"]'):
            overrides["quantity"] = "999"
        else:
            overrides["qty"] = "999"
        resp = self.http.submit_form(page, overrides, form_xpath=form_xpath)
        self.assert_contains_any(resp.text, ["không đủ", "tồn kho", "vượt quá", "hết hàng", "lỗi"])
        self.assertEqual(self.db.get_product_stock(slug), stock_before)

    def test_T2_F07_05_staff_privilege_escalation_and_verify_endpoint_rejected(self) -> None:
        self.http.login_admin(STAFF_EMAIL, STAFF_PASS)
        prof = self.http.get("/admin/profile.php", allow_4xx=False)
        csrf = self.http.extract_csrf(prof)
        # Attempt role tamper on profile
        self.http.submit_form(prof, {"name": "Nhân Viên Bán Hàng", "phone": "0900000002", "role": "admin"}, form_xpath='//form[.//input[@name="phone"]]')
        # Verify endpoint rejects wrong pw
        ver = self.http.post("/admin/verify-admin-password.php", data={"csrf_token": csrf, "admin_password": "wrong", "password": "wrong"})
        if ver.status_code != 403:
            try:
                ver_json = ver.json()
            except ValueError as exc:
                self.fail(
                    f"Expected 403 or JSON from /admin/verify-admin-password.php, got status={ver.status_code}: {ver.text[:300]!r} ({exc})"
                )
            self.assertIs(ver_json.get("ok"), False, f"Expected ok=False for wrong admin password, got {ver_json!r}")
        # Must still be blocked from staff.php
        g = self.http.get("/admin/staff.php", allow_redirects=True)
        self.assertTrue(g.status_code == 403 or "staff.php" not in g.url or "không có quyền" in g.text.lower())


class TestT2F08CustomerRbacBoundaries(E2EBaseTestCase):
    def test_T2_F08_01_customer_blocked_from_all_admin_endpoints(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        for ep in ("/admin/index.php", "/admin/products.php", "/admin/orders.php", "/admin/staff.php", "/admin/walkin-order.php"):
            r = self.http.get(ep, allow_redirects=True)
            self.assertTrue(r.status_code == 403 or "admin/login.php" in r.url, f"Customer accessed {ep} (url={r.url})")

    def test_T2_F08_02_wrong_password_and_sqli_login_rejected(self) -> None:
        r1 = self.http.login_customer(CUSTOMER_EMAIL, "wrong_pass_123")
        self.assertIn("login.php", r1.url)
        self.assert_contains_any(r1.text, ["không chính xác", "sai", "lỗi"])
        c2 = self.new_client()
        r2 = c2.login_customer("' OR 1=1--", "anything")
        self.assertIn("login.php", r2.url, f"SQLi login unexpectedly redirected to {r2.url}")
        self.assert_contains_any(r2.text, ["không chính xác", "sai", "thất bại", "lỗi"])
        prof_unauth = c2.get("/profile.php", allow_redirects=True)
        self.assertIn("login.php", prof_unauth.url, "SQLi login session must remain unauthenticated")

    def test_T2_F08_03_duplicate_register_email_rejected(self) -> None:
        reg_page = self.http.get("/register.php", allow_4xx=False)
        doc = parse_html(reg_page)
        form_xpath = '//form[.//input[@name="email"] and .//input[@name="password"]]'
        overrides = {"name": "Dup User", "email": CUSTOMER_EMAIL, "phone": "0911222444", "password": "password123"}
        if doc.xpath(f'{form_xpath}//input[@name="confirm_password"]'):
            overrides["confirm_password"] = "password123"
        resp = self.http.submit_form(reg_page, overrides, form_xpath=form_xpath)
        self.assert_contains_any(resp.text, ["đã được đăng ký", "tồn tại", "trùng", "lỗi"])

    def test_T2_F08_04_customer_profile_validation_and_role_tamper(self) -> None:
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        resp = self.http.submit_form(
            "/profile.php",
            {"name": "", "phone": "123", "role": "admin"},
            form_xpath='//form[.//input[@name="phone"]]',
        )
        self.assert_contains_any(resp.text, ["không hợp lệ", "vui lòng", "lỗi", "bắt buộc"])
        prof_after = self.http.get("/profile.php", allow_4xx=False)
        pdoc = parse_html(prof_after)
        name_val = (pdoc.xpath('//input[@name="name"]/@value') or [""])[0].strip()
        phone_val = (pdoc.xpath('//input[@name="phone"]/@value') or [""])[0].strip()
        self.assertTrue(name_val, "Invalid empty name overwrote customer profile!")
        self.assertNotEqual(phone_val, "123", "Invalid phone '123' overwrote customer profile!")
        adm = self.http.get("/admin/index.php", allow_redirects=True)
        self.assertTrue(adm.status_code == 403 or "admin/login.php" in adm.url)

    def test_T2_F08_05_unauth_profile_redirect_and_csrf_rejected(self) -> None:
        g = self.http.get("/profile.php", allow_redirects=True)
        self.assertIn("login.php", g.url)
        self.http.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        p = self.http.post("/profile.php", data={"csrf_token": "BAD_CSRF", "name": "Hacker", "phone": "0912345678"})
        self.assertTrue(p.status_code == 403 or "csrf" in p.text.lower() or "hết hạn" in p.text.lower())

    def test_T2_F08_06_register_password_mismatch_and_short_password_rejected(self) -> None:
        c = self.new_client()
        reg_page = c.get("/register.php", allow_4xx=False)
        doc = parse_html(reg_page)
        form_xpath = '//form[.//input[@name="email"] and .//input[@name="password"]]'
        overrides = {"name": "Short Pass", "email": f"short_{uid()}@example.com", "phone": "0911222555", "password": "123"}
        if doc.xpath(f'{form_xpath}//input[@name="confirm_password"]'):
            overrides["confirm_password"] = "456"
        resp = c.submit_form(reg_page, overrides, form_xpath=form_xpath)
        self.assertIn("register.php", resp.url)
        self.assert_contains_any(resp.text, ["6 ký tự", "không khớp", "lỗi"])
        self.assertIn("login.php", c.get("/profile.php", allow_redirects=True).url)


class TestT2F09PcBuilderBoundaries(E2EBaseTestCase):
    def _select_by_slug(self, browser: CdpBrowser, slot: str, slug: str) -> None:
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
        self.assertTrue(ok, f"Option for slug {slug!r} not found in {sel}")

    def test_T2_F09_01_pc_builder_socket_mismatch_lga1700_vs_am5(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")
            self._select_by_slug(browser, "cpu", "e2e-intel-i5-13400f")
            self._select_by_slug(browser, "mainboard", "e2e-msi-b650m-am5")
            txt = browser.text_content("#compat-box, .compat-box, [data-compat-box]")
            self.assert_contains_any(txt, ["socket", "lga1700", "am5", "không tương thích"])

    def test_T2_F09_02_pc_builder_ram_mismatch_ddr4_vs_ddr5(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")
            self._select_by_slug(browser, "cpu", "e2e-intel-i5-13400f")
            self._select_by_slug(browser, "mainboard", "e2e-asus-b760m-d4")
            self._select_by_slug(browser, "ram", "e2e-kingston-32gb-ddr5")
            txt = browser.text_content("#compat-box, .compat-box, [data-compat-box]")
            self.assert_contains_any(txt, ["ddr4", "ddr5", "ram", "không tương thích"])

    def test_T2_F09_03_pc_builder_underpowered_psu_warning(self) -> None:
        with CdpBrowser(self.base_url) as browser:
            browser.navigate("/pc-builder.php")
            self._select_by_slug(browser, "cpu", "e2e-intel-i7-14700k")
            self._select_by_slug(browser, "mainboard", "e2e-asus-b760m-d4")
            self._select_by_slug(browser, "ram", "e2e-corsair-16gb-ddr4")
            self._select_by_slug(browser, "vga", "e2e-msi-rtx-4070-super")
            self._select_by_slug(browser, "ssd", "e2e-samsung-980-pro-1tb")
            self._select_by_slug(browser, "psu", "e2e-corsair-cv550")
            txt = browser.text_content("#compat-box, .compat-box, [data-compat-box]")
            self.assert_contains_any(txt, ["nguồn", "psu", "550", "công suất", "không đủ", "yếu"])

    def test_T2_F09_04_pc_builder_out_of_stock_component_rejected(self) -> None:
        oos_id = self.db.get_product_id("e2e-asus-rtx-4090-oos")
        page = self.http.get("/pc-builder.php", allow_4xx=False)
        csrf = self.http.extract_csrf(page)
        resp = self.http.post(
            "/pc-builder.php",
            data={
                "csrf_token": csrf,
                "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")),
                "mainboard_id": str(self.db.get_product_id("e2e-asus-b760m-d4")),
                "ram_id": str(self.db.get_product_id("e2e-corsair-16gb-ddr4")),
                "vga_id": str(oos_id),
                "ssd_id": str(self.db.get_product_id("e2e-samsung-980-pro-1tb")),
                "psu_id": str(self.db.get_product_id("e2e-corsair-rm750e")),
                "customer_name": "OOS Build",
                "phone": "0912345678",
                "address": "An Giang",
                "payment_method": "COD",
            },
        )
        self.assert_contains_any(resp.text, ["hết hàng", "không đủ", "tồn kho", "lỗi"])

    def test_T2_F09_05_pc_builder_empty_build_and_missing_csrf_rejected(self) -> None:
        page = self.http.get("/pc-builder.php", allow_4xx=False)
        csrf = self.http.extract_csrf(page)
        r_empty = self.http.post(
            "/pc-builder.php",
            data={"csrf_token": csrf, "customer_name": "Empty", "phone": "0912345678", "address": "AG", "payment_method": "COD"},
        )
        self.assertIsNone(re.search(r"\bDH\d+\b", r_empty.text), "Empty PC build unexpectedly created an order!")
        self.assert_contains_any(r_empty.text, ["chưa chọn", "ít nhất", "trống", "lỗi", "không hợp lệ"])
        r_csrf = self.http.post(
            "/pc-builder.php",
            data={"csrf_token": "BAD_CSRF", "cpu_id": str(self.db.get_product_id("e2e-intel-i5-13400f")), "customer_name": "X", "phone": "0912345678", "address": "AG"},
        )
        self.assertTrue(r_csrf.status_code == 403 or "csrf" in r_csrf.text.lower() or "hết hạn" in r_csrf.text.lower())


class TestT2F10PcPipelineBoundaries(E2EBaseTestCase):
    def _create_pc_order(self) -> str:
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
                "customer_name": "Pipeline Bound",
                "phone": "0915554443",
                "address": "An Giang",
                "payment_method": "COD",
            },
            form_xpath='//form[.//input[@name="customer_name"]]',
        )
        return self.extract_order_code(resp.text)

    def _post_status(self, code: str, target_status: str):
        adm = self.http.get("/admin/orders.php", allow_4xx=False)
        doc = parse_html(adm)
        forms = doc.xpath(f'//tr[contains(., "{code}")]//form[.//select[@name="status"]] | //div[contains(., "{code}")]//form[.//select[@name="status"]]')
        if forms:
            oid = (forms[0].xpath('.//input[@name="order_id" or @name="id"]/@value') or [""])[0]
            csrf = self.http.extract_csrf(adm)
            return self.http.post("/admin/orders.php", data={"csrf_token": csrf, "action": "update_status", "id": oid, "order_id": oid, "status": target_status})
        # Order may be locked without form; extract order ID from row text or hidden inputs
        row = doc.xpath(f'//tr[contains(., "{code}")] | //div[contains(., "{code}")]')
        oid = (row[0].xpath('.//@data-order-id') or [""])[0] if row else ""
        if not oid and row:
            m = re.search(r"id=(\d+)", row[0].text_content() + "".join(row[0].xpath(".//@href")))
            if m:
                oid = m.group(1)
        csrf = self.http.extract_csrf(adm)
        return self.http.post("/admin/orders.php", data={"csrf_token": csrf, "action": "update_status", "id": oid, "order_id": oid, "status": target_status})

    def _assert_admin_order_row_status(self, code: str, expected_status: str) -> None:
        adm = self.http.get("/admin/orders.php", allow_4xx=False)
        doc = parse_html(adm)
        rows = doc.xpath(f'//tr[contains(., "{code}")] | //div[contains(., "{code}") and contains(@class, "order")]')
        self.assertTrue(rows, f"Order {code} missing from /admin/orders.php")
        row = rows[0]
        selects = row.xpath('.//select[@name="status"]')
        if selects:
            selected_opts = selects[0].xpath('.//option[@selected]')
            self.assertTrue(
                selected_opts,
                f"Order {code} has <select name='status'> but no <option selected>; cannot verify status {expected_status!r}",
            )
            sel_val = (selected_opts[0].get("value") or selected_opts[0].text_content() or "").strip()
            self.assertEqual(sel_val, expected_status, f"Expected selected status {expected_status!r}, got {sel_val!r}")
        else:
            self.assertIn(expected_status, " ".join(row.text_content().split()))

    def test_T2_F10_01_pipeline_invalid_backward_from_hoan_thanh_rejected(self) -> None:
        code = self._create_pc_order()
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        self._post_status(code, "Đang lắp ráp")
        self._post_status(code, "Đang giao hàng")
        self._post_status(code, "Hoàn thành")
        r = self._post_status(code, "Đang lắp ráp")
        self.assert_contains_any(r.text, ["không thể", "không hợp lệ", "lỗi", "không được phép"])
        self._assert_admin_order_row_status(code, "Hoàn thành")
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Hoàn thành", cust.text)

    def test_T2_F10_02_pipeline_invalid_transition_from_da_huy_rejected(self) -> None:
        code = self._create_pc_order()
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        self._post_status(code, "Đã hủy")
        r = self._post_status(code, "Đang giao hàng")
        self.assert_contains_any(r.text, ["không thể", "không hợp lệ", "lỗi", "không được phép"])
        self._assert_admin_order_row_status(code, "Đã hủy")
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Đã hủy", cust.text)

    def test_T2_F10_03_pipeline_skipping_stage_cho_xac_nhan_to_hoan_thanh_rejected(self) -> None:
        code = self._create_pc_order()
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        r = self._post_status(code, "Hoàn thành")
        self.assert_contains_any(r.text, ["không thể", "không hợp lệ", "lỗi", "không được phép"])
        self._assert_admin_order_row_status(code, "Chờ xác nhận")
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Chờ xác nhận", cust.text)

    def test_T2_F10_04_pipeline_invalid_status_string_rejected(self) -> None:
        code = self._create_pc_order()
        self.http.login_admin(ADMIN_EMAIL, ADMIN_PASS)
        r = self._post_status(code, "TrangThaiGia")
        self.assert_contains_any(r.text, ["không hợp lệ", "lỗi", "không được phép"])
        self._assert_admin_order_row_status(code, "Chờ xác nhận")
        cust = self.new_client().get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Chờ xác nhận", cust.text)

    def test_T2_F10_05_pipeline_unauthorized_status_mutation_blocked(self) -> None:
        code = self._create_pc_order()
        cust = self.new_client()
        cust.login_customer(CUSTOMER_EMAIL, CUSTOMER_PASS)
        r = cust.post("/admin/orders.php", data={"action": "update_status", "order_code": code, "status": "Đang lắp ráp"}, allow_redirects=True)
        self.assertTrue(r.status_code == 403 or "admin/login.php" in r.url)
        check = cust.get("/orders.php", params={"code": code}, allow_4xx=False)
        self.assertIn("Chờ xác nhận", check.text)
