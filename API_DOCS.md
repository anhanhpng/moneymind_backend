# 📚 Danh Sách API Endpoints (be_moneymind)

Tài liệu này tổng hợp chi tiết toàn bộ các đường dẫn (Endpoint) của hệ thống kèm theo từng trạng thái HTTP Code, Thông báo (Message) và Dữ liệu trả về để bạn dễ dàng tích hợp vào Front-end.

---

## 1. 🔐 AUTHENTICATION (Xác thực người dùng)

### `POST /api/register` (Đăng ký tài khoản)
- **`201 Created`**: Thành công
  - **Message**: `Đăng ký tài khoản thành công`
  - **Data**: `{ access_token, token_type, user }`
- **`422 Unprocessable Entity`**: Dữ liệu không hợp lệ (Trùng email, mật khẩu ngắn...)
  - **Message**: `Dữ liệu không hợp lệ`
  - **Errors**: `{ field: [lý do] }`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra khi tạo tài khoản`

### `POST /api/login` (Đăng nhập)
- **`200 OK`**: Thành công
  - **Message**: `Đăng nhập thành công`
  - **Data**: `{ access_token, token_type, user }`
- **`401 Unauthorized`**: Thất bại (Sai email/mật khẩu)
  - **Message**: `Tài khoản hoặc mật khẩu không đúng`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra trong quá trình đăng nhập`

### `POST /api/logout` (Đăng xuất)
*(Yêu cầu Header: `Authorization: Bearer {token}`)*
- **`200 OK`**: Thành công
  - **Message**: `Đăng xuất thành công`
  - **Data**: `null`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra khi đăng xuất`

---

## 2. 💳 WALLETS (Ví Tiền)

*(Tất cả API dưới đây yêu cầu Header: `Authorization: Bearer {token}`)*

### `GET /api/wallets` (Lấy danh sách ví)
- **`200 OK`**: Thành công
  - **Message**: `Lấy danh sách ví thành công`
  - **Data**: `[ { wallet_1 }, { wallet_2 }, ... ]`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra khi lấy danh sách ví`

### `POST /api/wallets` (Thêm ví mới)
- **`201 Created`**: Thành công
  - **Message**: `Thêm mới ví tiền thành công`
  - **Data**: `{ id, name, balance, ... }`
- **`422 Unprocessable Entity`**: Thiếu thông tin bắt buộc
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi tạo ví`

### `GET /api/wallets/{id}` (Lấy thông tin 1 ví cụ thể)
- **`200 OK`**: Thành công
  - **Message**: `Lấy thông tin ví thành công`
  - **Data**: `{ id, name, balance, ... }`
- **`403 Forbidden`**: Xem nhầm ví của người khác
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`404 Not Found`**: Ví không tồn tại

### `PUT /api/wallets/{id}` (Cập nhật thông tin ví)
- **`200 OK`**: Thành công
  - **Message**: `Cập nhật ví thành công`
  - **Data**: `{ thông tin ví vừa update }`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra khi cập nhật ví`

### `DELETE /api/wallets/{id}` (Xoá ví)
- **`200 OK`**: Thành công
  - **Message**: `Xoá ví thành công`
  - **Data**: `null`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**: Lỗi hệ thống
  - **Message**: `Đã có lỗi xảy ra khi xoá ví`

---

## 3. 🏷️ CATEGORIES (Danh mục thu chi)

*(Yêu cầu Header: `Authorization: Bearer {token}`)*

### `GET /api/categories` (Danh sách danh mục)
- **`200 OK`**: Thành công
  - **Message**: `Lấy danh sách danh mục thành công`
  - **Data**: `[ { category_1 }, ... ]`
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi lấy danh sách danh mục`

### `POST /api/categories` (Thêm danh mục mới)
- **`201 Created`**: Thành công
  - **Message**: `Thêm mới danh mục thành công`
  - **Data**: `{ id, name, type, color, icon, ... }`
- **`422 Unprocessable Entity`**: Form lỗi
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi tạo danh mục`

### `GET /api/categories/{id}` (Xem chi tiết)
- **`200 OK`**: Thành công
  - **Message**: `Lấy dữ liệu thành công`
  - **Data**: `{ object category }`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`

### `PUT /api/categories/{id}` (Sửa danh mục)
- **`200 OK`**: Thành công
  - **Message**: `Cập nhật danh mục thành công`
  - **Data**: `{ object category vừa update }`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi cập nhật danh mục`

### `DELETE /api/categories/{id}` (Xóa danh mục)
- **`200 OK`**: Thành công
  - **Message**: `Xoá danh mục thành công`
  - **Data**: `null`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi xoá danh mục`

---

## 4. 📝 TRANSACTIONS (Giao Dịch)

*(Yêu cầu Header: `Authorization: Bearer {token}`)*

### `GET /api/transactions` (Lấy danh sách giao dịch, có phân trang & lọc)
- **`200 OK`**: Thành công
  - **Message**: `Lấy danh sách giao dịch thành công`
  - **Data**: `{ current_page, data: [danh_sách], total, ... }` (Cấu trúc phân trang chuẩn của Laravel)
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi lấy danh sách giao dịch`

### `POST /api/transactions` (Thêm giao dịch mới)
- **`201 Created`**: Thành công (kèm chức năng tự động biến động số dư Ví)
  - **Message**: `Thêm mới giao dịch thành công`
  - **Data**: `{ thông tin transaction kèm category và wallet được tự động Load }`
- **`422 Unprocessable Entity`**: Thiếu số tiền, chọn sai ví, chọn sai type...
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi tạo giao dịch`

### `GET /api/transactions/{id}`
- **`200 OK`**: Thành công
  - **Message**: `Lấy thông tin giao dịch thành công`
  - **Data**: `{ transaction }`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`

### `PUT /api/transactions/{id}` (Cập nhật giao dịch)
- **`200 OK`**: Thành công (Ví được tự động Rollback/Apply lại số dư mới)
  - **Message**: `Cập nhật giao dịch thành công`
  - **Data**: `{ transaction update }`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi cập nhật giao dịch`

### `DELETE /api/transactions/{id}` (Xoá giao dịch)
- **`200 OK`**: Thành công (Ví được tự động hoàn tiền tương ứng)
  - **Message**: `Xoá giao dịch thành công`
  - **Data**: `null`
- **`403 Forbidden`**:
  - **Message**: `Bạn không có quyền truy cập dữ liệu này`
- **`500 Internal Server Error`**:
  - **Message**: `Đã có lỗi xảy ra khi xoá giao dịch`
