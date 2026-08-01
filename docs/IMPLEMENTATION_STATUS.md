# Báo cáo trạng thái triển khai Krayin Telesale

Ngày kiểm tra gần nhất: 31/07/2026 (Asia/Bangkok)

## Phạm vi và cách kiểm tra

- Phiên bản source: Krayin `v2.2.4-9-g5b09780e` (commit `5b09780e`).
- Runtime: Laravel `12.58.0`, PHP `8.3.33`, MySQL.
- Locale đang chạy: `vi`.
- Đã kiểm tra Git, migrations, database hiện tại, routes, controllers, requests, services, models, ACL, Blade/Vue và tests.
- Đã kiểm tra HTTP trên server nền có sẵn tại `127.0.0.1:8080`: trang đăng nhập trả `200`; API không có token hợp lệ trả `401`.
- Đã bổ sung lớp UI workflow theo ảnh PushSale trong package Telesales; chi tiết tại `docs/WORKFLOW_REFACTOR.md`.
- Không chạy migration, không reset database, không sửa `vendor` và không khởi động server mới.

## Tóm tắt phân loại

| Chức năng | Trạng thái | Kết luận thực tế |
|---|---|---|
| Nhập data bằng số điện thoại | **Đã hoàn chỉnh** | Form Create Lead đã được thay bằng form phone-first; số điện thoại bắt buộc, tên/sản phẩm/nguồn/ghi chú/nhóm là tùy chọn. Test tạo chỉ bằng số điện thoại đang pass. |
| Chuẩn hóa và chống trùng số | **Có nhưng lỗi hoặc thiếu** | `+849...` và `09...` được chuẩn hóa về cùng giá trị, có unique index và xử lý race bằng lỗi constraint. Tuy nhiên chỉ số đầu tiên trong `contact_numbers` được chuẩn hóa/tìm trùng; migration backfill bỏ qua bản ghi legacy trùng thay vì hợp nhất/cảnh báo. |
| Tạo Person và Lead | **Đã hoàn chỉnh** | `IncomingLeadService` tạo Person không email, Lead, metadata, assignment và notification trong transaction; tự sinh tên `Khách + 4 số cuối`. |
| Pipeline telesale | **Đã hoàn chỉnh** | Database đang có đủ `Data mới`, `Chưa phân bổ`, `Đang chăm sóc`, `Hẹn gọi lại`, `Đã chốt`, `Không thành công`; kết quả gọi cập nhật stage tương ứng. |
| Nhóm sale và round-robin | **Đã hoàn chỉnh** | Có cấu hình nhóm, thứ tự, bộ đếm, lịch sử giao và khóa `lockForUpdate`. Test chia tuần tự hai sale đang pass. Chưa có load test đồng thời thực sự. |
| Bật/tắt nhận data | **Đã hoàn chỉnh** | `telesales_group_members.receives_data` có giao diện cấu hình và được service lọc cùng trạng thái hoạt động của user. |
| Thông báo data mới | **Có nhưng lỗi hoặc thiếu** | Có notification nội bộ, badge chuông, đánh dấu đã đọc và link đúng Lead. Chỉ xuất hiện khi tải lại trang; chưa có polling, WebSocket, web push/PWA hay notification hệ điều hành. |
| Tác nghiệp gọi điện, ghi chú và hẹn gọi lại | **Đã hoàn chỉnh** | Có nút `tel:`, kết quả gọi, ghi chú, callback bắt buộc ngày giờ, lịch sử append-only, Activity và reminder nội bộ. Chưa tích hợp tổng đài/ghi âm cuộc gọi, nhưng không thuộc MVP hiện tại. |
| API `incoming-leads` | **Đã hoàn chỉnh** | `POST /api/v1/incoming-leads` dùng chung service, bearer token từ `.env`, validate payload, chống lặp phone/external ID, trả `201/409/401/422` phù hợp và không log token. Cần hardening trước production, xem phần bảo mật. |
| Đơn hàng telesale | **Có nhưng lỗi hoặc thiếu** | Có bảng order/item, tạo đơn từ Lead, trạng thái, doanh thu và danh sách theo quyền. Mới hỗ trợ một sản phẩm mỗi lần tạo; chưa có sửa/xóa đơn, nhiều dòng hàng, địa chỉ, thanh toán/COD thực, vận đơn, kho, lịch sử đổi trạng thái hoặc state machine. |
| Dashboard Sale/Marketing | **Có nhưng lỗi hoặc thiếu** | Có báo cáo doanh thu theo Sale/Marketing, phạm vi dữ liệu, bộ lọc và test công thức. Báo cáo chỉ đọc các Lead có `telesales_lead_meta`; 10/12 Lead hiện tại bị bỏ ngoài báo cáo. Không có góc nhìn tổng hợp dành riêng cho Trưởng nhóm. |
| Top 10 | **Có nhưng lỗi hoặc thiếu** | Top 10 là dữ liệu backend thật từ `RevenueReportService`, không phải UI giả, và có giao diện kiểu PushSale. Vẫn chịu thiếu dữ liệu legacy và thiếu phạm vi Trưởng nhóm như Dashboard. |
| Phân quyền Admin, Marketing, Sale, Trưởng nhóm | **Có nhưng lỗi hoặc thiếu** | Có bốn role trong DB và ACL theo route/`view_permission`. Admin, Marketing và Sale có scope chính. `TelesalesAccessService` không nhận biết Trưởng nhóm, nên role này bị coi như Sale và Dashboard/Order chỉ thấy dữ liệu cá nhân. Database hiện chỉ có tài khoản Administrator; ba role còn lại chưa có tài khoản thật để vận hành. |
| Giao diện tiếng Việt | **Có nhưng lỗi hoặc thiếu** | Locale `vi` đang bật; audit không phát hiện thiếu translation key trong các package có locale. Tuy nhiên nhiều màn hình telesale dùng chuỗi hard-code thay vì translation key và chưa có kiểm thử trình duyệt toàn bộ UI, nên chưa thể xác nhận “toàn bộ UI” ở mọi trang/trạng thái lỗi. |
| Test tự động | **Có nhưng lỗi hoặc thiếu** | PHPUnit/Pest hiện có và toàn bộ test PHP pass. Chưa có test đồng thời round-robin, upgrade/backfill legacy, Trưởng nhóm, API rate limit, notification realtime, responsive/mobile hoặc E2E riêng cho telesale. Bộ Playwright gốc tồn tại nhưng dependency chưa được cài và không có case telesale. |

Không có chức năng nào trong danh sách được xác định là **hoàn toàn chưa có**. Các mục chưa hoàn chỉnh đều đã có cả UI lẫn backend ở một mức nhất định.

## 1. Chức năng thực sự hoạt động

### Nhập data và tạo Lead

- Giao diện: `packages/Webkul/Admin/src/Resources/views/leads/create.blade.php`.
- Validation: `packages/Webkul/Admin/src/Http/Requests/LeadForm.php`.
- Luồng web: `LeadController::store()` gọi trực tiếp `IncomingLeadService`.
- Form không hiển thị email, organization, giá trị dự kiến và ngày dự kiến chốt.
- Email được lưu là mảng rỗng, không tạo email giả.
- Tên trống được sinh theo bốn số cuối; tiêu đề Lead có số điện thoại.
- Duplicate web chuyển tới Lead/Person đã tồn tại và trả `409` nếu là AJAX.

### Chuẩn hóa, transaction và phân bổ

- `PhoneNormalizer` bỏ ký tự không phải số và đổi số Việt Nam dạng `+84...`/`0084...` phù hợp về `0...`.
- `persons.normalized_phone` có unique index.
- `IncomingLeadService` bao toàn bộ tạo Person, Lead, metadata, assignment và notification trong transaction với tối đa ba lần thử.
- Round-robin khóa bản ghi cấu hình và thành viên trước khi tăng lượt, không dùng random.
- Khi nhóm không có sale hoạt động, Lead chuyển stage `Chưa phân bổ`.

### Tác nghiệp và notification nội bộ

- `lead-panel.blade.php` được nhúng vào trang chi tiết Lead.
- Mỗi kết quả gọi tạo một `telesales_call_histories` mới và một Activity mới, không update lịch sử cũ.
- Callback bắt buộc thời gian trong tương lai và tạo notification có `available_at`.
- Notification chỉ được mở bởi đúng `user_id`, sau đó đánh dấu `read_at`.

### API

- Route thực tế: `POST /api/v1/incoming-leads`.
- Middleware: `VerifyIncomingLeadToken`.
- Request: `IncomingLeadRequest`.
- Controller: `IncomingLeadController`.
- Service dùng chung với web: `IncomingLeadService`.
- HTTP smoke test không token hợp lệ trả `401`, đúng thiết kế.

### Dashboard, Top 10 và order trong phạm vi dữ liệu mới

- `RevenueReportService` tổng hợp data, contact, số đơn, số sản phẩm, khách mới/cũ, gross/discount/net và conversion rate.
- Có scope riêng cho Admin, Marketing và Sale; URL filter trái quyền bị service vô hiệu hóa.
- Top 10 được sắp theo doanh thu gross hoặc net từ query backend.
- Order lưu snapshot Sale/Marketing tại thời điểm tạo, nên đổi owner Lead sau đó không làm sai doanh thu lịch sử.
- Đơn hoàn/hủy bị loại khỏi báo cáo doanh thu.

### Liên kết hồ sơ Marketing với Sale

- Hồ sơ Marketing hiển thị nguồn/data về, khách hàng, tin nhắn ban đầu, Sale
  phụ trách, thời điểm phân, lần tác nghiệp gần nhất, kết quả, phản hồi chia sẻ,
  sản phẩm, đơn hàng và trạng thái giao hàng.
- Sale có hai vùng nội dung riêng: ghi chú nội bộ và phản hồi cho Marketing.
  Chỉ phản hồi chia sẻ được hiển thị cho Marketing.
- Số điện thoại được che trên danh sách và trang tóm tắt Marketing; Admin và Sale
  vẫn dùng số đầy đủ theo phạm vi được cấp.

## 2. Chức năng chỉ có giao diện nhưng chưa có xử lý backend

Không phát hiện form/nút telesale nào trong phạm vi kiểm tra chỉ có giao diện mà không có route/backend:

- Form cấu hình nhóm gọi `GroupConfigurationController`.
- Form ánh xạ Marketing gọi `MarketingMappingController`.
- Form kết quả gọi gọi `OutcomeController`.
- Form owner gọi `OwnershipController`.
- Form order gọi `OrderController`.
- Bộ lọc Dashboard/Top 10 gọi `RankingController` và `RevenueReportService`.
- Chuông notification gọi `NotificationController`.

Các phần dễ bị hiểu nhầm là đã hoàn thiện nhưng thực tế **chưa tồn tại**:

- Notification realtime/web push/PWA.
- Tích hợp tổng đài, trạng thái cuộc gọi tự động và ghi âm.
- Kho, vận đơn, đối soát COD và thanh toán.
- Dashboard theo toàn nhóm dành cho Trưởng nhóm.

## 3. Lỗi và rủi ro bảo mật

### Mức cao

1. **Mã telesale đã được theo dõi trên nhánh cục bộ `stabilize/telesales-phase-0`, nhưng chưa được đẩy lên remote.** Cần review và push/merge theo quy trình của dự án trước khi triển khai sang máy khác.
2. **Thiếu metadata cho dữ liệu hiện hữu.** Database có 14 Lead nhưng chỉ 4 `telesales_lead_meta`; 10 Lead cũ không vào báo cáo, Top 10, ownership snapshot và lịch sử phân bổ. `VietnameseTelesalesDemoSeeder` tạo Lead trực tiếp, không dùng `IncomingLeadService`.
3. **Trưởng nhóm chưa có scope đúng.** Role tồn tại nhưng bị `TelesalesAccessService` phân loại thành Sale; Dashboard và Orders chỉ lọc theo chính user đó.

### Mức trung bình

4. **Không có Policy riêng cho module telesale.** Quyền đang phân tán giữa ACL route, `bouncer()->getAuthorizedUserIds()` và các đoạn `abort(403)` trong controller. Cách này khó audit và dễ tạo IDOR khi thêm route mới.
5. **Nhận diện Marketing dựa vào chuỗi tên role chứa `marketing`.** Đổi tên role hoặc dùng tên tiếng Việt khác có thể làm sai ownership/scope.
6. **API dùng một bearer token tĩnh cho toàn hệ thống và không có rate limiter riêng.** Chưa có token theo nguồn, rotation, timestamp/signature chống replay, audit client hoặc giới hạn tốc độ.
7. **`APP_DEBUG=true`.** Phù hợp máy local, nhưng nếu cấu hình này được đưa ra mạng có thể lộ stack trace, SQL và dữ liệu request.
8. **Số điện thoại đầy đủ vẫn nằm trong notification body của Sale.** Danh sách và trang tóm tắt Marketing đã được masking; cần tiếp tục xác nhận chính sách dữ liệu cá nhân cho notification và log.
9. **Backfill phone chỉ xử lý số đầu tiên.** Số phụ trong JSON không được lập index; bản ghi legacy trùng bị bỏ qua và để `normalized_phone` rỗng thay vì có hàng chờ xử lý. Database hiện tại không còn giá trị null, nhưng migration vẫn rủi ro khi áp dụng trên dữ liệu khác.
10. **Cấu hình mapping nhóm có thể mơ hồ.** Database không unique theo `source_id` hoặc `campaign` giữa nhiều nhóm; service dùng bản ghi đầu tiên nên kết quả không xác định nếu cấu hình trùng.

### Mức thấp/trung bình

11. Notification và reminder chỉ được query trực tiếp trong Blade khi render header; không có tiến trình chủ động báo đúng thời điểm.
12. Order cho phép chuyển trạng thái theo bất kỳ thứ tự nào, chưa có audit lịch sử trạng thái và chưa khóa các bước nghiệp vụ.
13. `VietnameseTelesalesDemoSeeder` đã được giới hạn thành seeder không phá hủy: không ép ID, không xóa Source/Type/Stage và không ghi đè Person có cùng số. Seeder vẫn tạo Lead trực tiếp nên dữ liệu mẫu cũ không tự có đầy đủ metadata telesale.
14. Nhiều chuỗi telesale được hard-code trong PHP/Blade, làm khó bảo trì đa ngôn ngữ và kiểm tra thiếu bản dịch.
15. Khi tạo Lead thật trên PHP hiện tại, `Webkul\Activity\Traits\LogsActivity` phát cảnh báo deprecated do gọi `json_decode(null)`. Luồng vẫn hoàn thành nhưng cần sửa trước khi nâng mức báo lỗi hoặc nâng PHP.
16. Trang chi tiết Lead ở viewport 390px hiển thị đúng nội dung và nút `tel:`, nhưng thanh stage có chiều rộng khoảng 805px nên gây cuộn ngang trên mobile.

## 4. Migration chưa chạy

**Không có migration pending** tại thời điểm kiểm tra.

Các migration mới đều đã chạy:

| Migration | Batch | Trạng thái |
|---|---:|---|
| `2026_07_29_000000_add_normalized_phone_to_persons_table` | 2 | Ran |
| `2026_07_29_000001_rename_new_stage_for_telesales` | 3 | Ran |
| `2026_07_29_100000_create_telesales_tables` | 4 | Ran |
| `2026_07_29_100001_configure_telesales_defaults` | 4 | Ran |
| `2026_07_29_100002_add_available_at_to_telesales_notifications` | 5 | Ran |
| `2026_07_29_100003_add_ownership_reporting_to_telesales_leads` | 6 | Ran |
| `2026_07_29_100004_create_telesales_orders` | 6 | Ran |
| `2026_07_29_100005_make_marketing_mapping_keys_unique` | 7 | Ran |
| `2026_08_01_000000_add_marketing_feedback_to_telesales_call_histories` | 8 | Ran |

Snapshot database lúc audit:

- 15 Person; cả 15 có `normalized_phone`.
- 14 Lead; 4 Lead có `telesales_lead_meta`, còn 10 Lead cũ chưa có metadata.
- 2 cấu hình nhóm, 4 thành viên telesale; nhóm mặc định là `Nhóm Sale Demo` với 2 sale đang nhận data.
- 5 notification, 4 call history, 2 order.
- Có 4 role: Quản trị viên, Marketing, Sale, Trưởng nhóm sale.
- Có 5 user: Administrator và 4 tài khoản nghiệm thu Marketing, Sale 1, Sale 2, Trưởng nhóm.

## 5. Test đang pass/fail

Lệnh đã chạy:

```powershell
.\.runtime\php\php.exe artisan test --compact
```

Kết quả:

- **43 test pass**
- **262 assertion pass**
- **0 fail**
- Thời gian PHPUnit/Pest báo cáo ở lần kiểm tra gần nhất: `4.94s`.

Các nhóm pass:

- Login, dashboard gốc, logout, help.
- Form phone-first và tạo Person/Lead không email.
- Trùng `09...` với `+849...`.
- Round-robin, tắt nhận data, chưa phân bổ.
- Chống Sale xem/thao tác Lead của Sale khác.
- Marketing chỉ xem phần tóm tắt data đã tạo.
- Marketing thấy đúng Sale, kết quả và phản hồi được chia sẻ nhưng không thấy
  ghi chú nội bộ; số điện thoại bị che và data Marketing khác bị loại khỏi scope.
- API sai token và idempotency `external_id`.
- Lịch sử chăm sóc, callback validation/reminder và notification.
- Báo cáo Sale/Marketing, chống filter IDOR, công thức doanh thu, hoàn/hủy, bộ lọc và owner snapshot.
- Demo seeder chạy lặp không xóa cấu hình riêng, không ghi đè Person và không tạo trùng Person/Lead.
- Seeder nghiệm thu tạo đúng bốn tài khoản, đăng nhập được, cấu hình hai sale nhận data và phân hai Lead liên tiếp theo round-robin kèm notification.
- Menu workflow hiển thị đúng theo Admin, Marketing, Sale và Trưởng nhóm; các mục chưa có backend không sinh URL.

Chưa được kiểm thử:

- Hai request phân data chạy đồng thời thật.
- Upgrade/backfill database có số trùng hoặc nhiều số trên một Person.
- Phạm vi toàn nhóm của Trưởng nhóm.
- API rate limit, token rotation và replay.
- Notification realtime.
- Hành trình E2E đầy đủ từ nhập data đến tác nghiệp/đơn hàng; riêng responsive
  workflow đã được smoke test bằng Playwright ở desktop `1440px` và mobile `390px`.
- Order nhiều sản phẩm, lịch sử trạng thái và tích hợp giao hàng/COD.

Bộ Playwright gốc được phát hiện tại `packages/Webkul/Admin/tests/e2e-pw`, nhưng dependency `@playwright/test` chưa được cài trong workspace và hiện không có case telesale. Vì vậy bộ này **không được chạy**, không được tính là pass hoặc fail.

Audit key tiếng Việt:

```powershell
.\.runtime\php\php.exe bin/audit-vietnamese-translations.php
```

Kết quả exit code `0`, không có locale/key bị thiếu. Các giá trị giống tiếng Anh còn lại chủ yếu là thuật ngữ kỹ thuật như Email, SKU, JSON, Webhook và tên model AI.

## 6. Thứ tự đề xuất sửa

1. **Đã hoàn tất bước kiểm soát Git cục bộ**: backup source/database, tạo nhánh riêng, review diff, quét bí mật và chia commit theo phạm vi. Việc push/merge vẫn cần thực hiện theo quy trình repository.
2. **Viết migration/backfill idempotent cho `telesales_lead_meta`**, ownership và assignment của Lead hiện hữu; sửa demo seeder để luôn dùng service chung và không xóa dữ liệu ngoài phạm vi.
3. **Hoàn thiện Trưởng nhóm** bằng role/permission ổn định và scope theo group cho Lead, Order, Dashboard, Top 10; thêm test với role custom thật.
4. **Chuẩn hóa authorization** bằng Policy/Gate hoặc một access service thống nhất cho mọi route telesale; bổ sung test IDOR cho notification, group config, mapping, order và ownership.
5. **Hardening API**: rate limit, token theo client/source, rotation, audit client, chống replay và giới hạn quyền chọn group/source.
6. **Hoàn thiện notification**: polling hoặc broadcast nội bộ trước; web push/PWA để giai đoạn sau.
7. **Hoàn thiện order MVP**: nhiều dòng hàng, state machine, audit status, sửa/hủy có kiểm soát; sau đó mới làm kho/vận chuyển/COD.
8. **Chuyển chuỗi hard-code sang translation key** và chạy Playwright trên các luồng desktop/mobile bằng bốn vai trò.
9. **Bổ sung test concurrency và migration upgrade**, sau đó đưa full PHPUnit + Playwright + translation audit vào CI.

## Kết luận

Hệ thống đã có một MVP telesale chạy được cho **data mới được tạo qua luồng mới**: phone-first, Person/Lead, pipeline, round-robin, tác nghiệp, API, notification nội bộ, order đơn giản và báo cáo Top 10. Phần triển khai đã được theo dõi trên nhánh Git cục bộ, nhưng chưa nên coi là hoàn thiện production vì dữ liệu legacy chưa được tích hợp vào metadata/reporting, quyền Trưởng nhóm chưa đúng, API chưa harden và notification chưa realtime.
