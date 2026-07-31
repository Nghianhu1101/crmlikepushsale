# Nghiệm thu telesale

## Tạo hoặc đặt lại tài khoản thử nghiệm

Seeder chỉ chạy ngoài môi trường production và bắt buộc nhận mật khẩu từ biến môi trường:

```powershell
$env:TELESALES_DEMO_PASSWORD='mat-khau-tu-chon'
.\.runtime\php\php.exe artisan db:seed --class="Database\Seeders\TelesalesTrialAccountsSeeder" --no-interaction
```

Có thể chạy lại lệnh trên để đặt lại mật khẩu mà không tạo trùng user, role hoặc nhóm.

## Tài khoản

| Vai trò | Email | Phạm vi |
|---|---|---|
| Marketing | `marketing.demo@localhost.test` | Nhập data và xem data do mình tạo |
| Sale 1 | `sale1.demo@localhost.test` | Nhận data ở lượt thứ nhất |
| Sale 2 | `sale2.demo@localhost.test` | Nhận data ở lượt thứ hai |
| Trưởng nhóm | `leader.demo@localhost.test` | Xem Lead của thành viên trong `Nhóm Sale Demo`, không nhận data |

Mật khẩu là giá trị `TELESALES_DEMO_PASSWORD` được dùng khi chạy seeder.

## Quy trình thử

1. Đăng nhập Marketing và tạo hai Lead mới chỉ bằng số điện thoại.
2. Kiểm tra Lead thứ nhất được giao Sale 1, Lead thứ hai được giao Sale 2.
3. Đăng nhập từng Sale, mở chuông thông báo và vào đúng Lead được giao.
4. Với Sale 1, chọn `Hẹn gọi lại`, nhập ghi chú và ngày giờ tương lai.
5. Với Sale 2, chọn `Đã chốt đơn`, tạo đơn ở trạng thái `Đã xác nhận`.
6. Mở Bảng xếp hạng để kiểm tra contact, số đơn, tỷ lệ chốt và doanh thu.
7. Đăng nhập Sale 2 và thử mở URL Lead của Sale 1; hệ thống phải chuyển về danh sách Lead.
8. Đăng nhập Trưởng nhóm và kiểm tra có thể mở Lead của cả hai Sale trong nhóm.

## Giới hạn đã xác nhận

- Trưởng nhóm mở được Lead của thành viên trong nhóm, nhưng Dashboard/Top 10 hiện vẫn tính như một Sale cá nhân và chưa tổng hợp doanh thu toàn nhóm.
- Trang chi tiết Lead có nội dung và nút gọi trên màn hình 390px, nhưng thanh stage còn gây cuộn ngang.
- Khi tạo Lead, phần ghi activity của Krayin phát cảnh báo deprecated `json_decode(null)`; giao dịch vẫn được tạo thành công.

Không dùng các tài khoản này trên production.
