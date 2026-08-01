# Thiết kế workflow vận hành theo PushSale

Ngày cập nhật: 31/07/2026

## Mục tiêu

Tổ chức lại giao diện Krayin theo workflow thực tế của công ty, dựa trên bốn ảnh
tham chiếu PushSale. Thay đổi hiện tại là lớp điều hướng và nhận diện hệ thống,
không giả lập các chức năng backend chưa tồn tại.

Thanh điều hướng được chia thành chín khối:

1. Quản trị đơn vị.
2. Marketing.
3. Khách hàng 360.
4. Telesale.
5. Kho.
6. Kế toán.
7. CEO.
8. Báo cáo thống kê.
9. Dịch vụ trả phí.

## Những phần đã triển khai

- Thanh trên màu xanh theo phong cách PushSale, hiển thị tên hệ thống, vai trò và
  người đang đăng nhập.
- Sidebar dạng accordion, có số thứ tự module và chức năng con.
- Sidebar thu gọn trên desktop, mở bằng drawer trên mobile.
- Menu được lọc theo vai trò Admin, Marketing, Sale và Trưởng nhóm.
- Route đang tồn tại được nối vào đúng màn hình Krayin/Telesales.
- Chức năng chưa có backend hiển thị nhãn `Chưa có`, không tạo link và không cho
  người dùng bấm nhầm.
- Giữ lại tìm kiếm, tạo nhanh, thông báo data, chuyển giao diện sáng/tối, trợ giúp,
  tài khoản và đăng xuất.
- Toàn bộ override giao diện nằm trong package `Webkul/Telesales`; không sửa
  `vendor` và không thay file giao diện lõi của package Admin.

Tên công ty trên thanh đầu có thể đổi bằng:

```dotenv
TELESALES_COMPANY_NAME="TÊN CÔNG TY"
```

## Phân quyền menu

| Vai trò | Khối hiển thị |
|---|---|
| Admin | Cả 9 khối |
| Marketing | Marketing, Khách hàng 360, Báo cáo thống kê |
| Sale | Khách hàng 360, Telesale, Báo cáo thống kê |
| Trưởng nhóm | Khách hàng 360, Telesale, Báo cáo thống kê |

Việc ẩn menu chỉ là lớp trải nghiệm người dùng. Quyền truy cập backend vẫn phải
được kiểm soát bằng ACL/service hiện có và cần tiếp tục được harden bằng Policy.

## Đối chiếu chức năng

### Đã nối backend hiện có

- Quản trị nhân sự, nhóm, vai trò và cấu hình hệ thống.
- Dashboard Marketing/Sale và bảng xếp hạng.
- Data Marketing đã nhập.
- Hồ sơ Marketing liên kết trực tiếp với Sale phụ trách, lần chăm sóc gần nhất,
  phản hồi được Sale chia sẻ và đơn telesale mới nhất. Số điện thoại được che
  đối với Marketing; ghi chú nội bộ Sale không xuất hiện.
- Web form/landing page sẵn có của Krayin.
- Person, Organization, Activity và Lead.
- Tác nghiệp telesale, đơn telesale và cấu hình phân data.
- Product, warehouse cơ bản và báo giá sẵn có của Krayin.
- Dashboard điều hành và báo cáo doanh thu hiện tại.

### Chỉ có vị trí trong workflow, chưa có backend

- Kết nối Facebook/Messenger trực tiếp.
- Marketing Leader và tiện ích Marketing chuyên sâu.
- Phân nhóm khách hàng 360.
- Kho số thả nổi.
- Báo cáo theo toàn nhóm của Trưởng nhóm.
- Nhập/xuất kho, biên bản kho, vận đơn và báo cáo kho đầy đủ.
- Chi phí đơn vị, đối soát COD hoàn chỉnh và hóa đơn điện tử.
- Hiệu suất phòng ban, báo cáo quản trị/nâng cao.
- Kho ứng dụng và dịch vụ trả phí.

Các mục trên cố ý bị khóa bằng nhãn `Chưa có`. Chúng không được tính là chức năng
đã hoàn thành.

## Lộ trình refactor tiếp theo

1. Hoàn thiện quyền và phạm vi dữ liệu của Trưởng nhóm; gom authorization vào
   Policy/Gate thống nhất.
2. Hoàn thiện vòng đời đơn telesale: nhiều sản phẩm, địa chỉ, state machine, lịch
   sử trạng thái và hủy/hoàn có kiểm soát.
3. Xây module Kho: tồn kho thực, nhập/xuất, giữ hàng, vận đơn và đối soát COD.
4. Xây module Kế toán: chi phí, đối soát, doanh thu thực thu và hóa đơn điện tử.
5. Bổ sung dashboard CEO và báo cáo xuyên phòng ban.
6. Tích hợp Facebook/Messenger, tổng đài và notification realtime sau khi API,
   ACL và audit log được harden.

## Kiểm thử

- Test menu theo bốn vai trò.
- Test đủ chín module cho Admin.
- Test mục chưa có không sinh URL.
- HTTP xác nhận thanh trên và menu theo vai trò trên server nền.
- Playwright xác nhận desktop rộng `1440px` và mobile rộng `390px` không gây
  cuộn ngang; drawer mobile mở được, mục chưa có không sinh link.
- Toàn bộ PHP test: `44 passed`, `268 assertions`, `0 failed`.
