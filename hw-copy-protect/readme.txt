=== HW Copy Protect ===
Requires at least: 5.6
Requires PHP: 7.4
Stable tag: 1.0.0

Chặn copy text/ảnh, chuột phải, bôi đen, phím tắt với khách. Admin (hoặc role tuỳ chọn) dùng bình thường.
Không nạp JS/CSS chặn cho Googlebot, Bingbot, Cốc Cốc, GPTBot, ClaudeBot, PerplexityBot... HTML trả về giữ nguyên => không ảnh hưởng index.

== Cài đặt ==
1. Nén thư mục hw-copy-protect thành .zip, vào Plugins > Add New > Upload.
2. Kích hoạt, vào Settings > Copy Protect để tuỳ chỉnh.

== Lưu ý cache ==
Nếu dùng page cache (WP Rocket, LiteSpeed Cache, W3TC): bật "không cache cho user đăng nhập" (mặc định đã bật).
Bản cache cho khách sẽ có JS chặn; bot vẫn đọc được vì nội dung HTML không đổi, JS chỉ chặn thao tác chuột/phím.
