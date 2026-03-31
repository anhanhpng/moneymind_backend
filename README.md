# Dự án MoneyMind (API Backend)

Backend API cho ứng dụng quản lý chi tiêu cá nhân (MoneyMind) xây dựng bằng **Laravel**.

## Yêu cầu hệ thống

- PHP >= 8.2
- Composer
- MySQL (XAMPP/Laragon)
- Git

## Hướng dẫn cài đặt và chạy project

Chạy lần lượt các bước sau trong Terminal:

**1. Clone dự án về máy:**
```bash
git clone <URL-Github-của-bạn>
cd be_moneymind
```

**2. Cài đặt thư viện:**
```bash
composer install
```

**3. Tạo file cấu hình môi trường (.env):**
- **Windows**: `copy .env.example .env`
- **Mac/Linux**: `cp .env.example .env`

**4. Khởi tạo App Key:**
```bash
php artisan key:generate
```

**5. Cấu hình Database:**
- Bật MySQL và tạo một bảng Database trống (ví dụ: `moneymind_db`).
- Sửa thông số trong file `.env` vừa tạo cho đúng với Database ở trên:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=moneymind_db
  DB_USERNAME=root
  DB_PASSWORD=
  ```

**6. Chạy Migration tạo bảng:**
```bash
php artisan migrate
```

**7. Khởi động Server ảo:**
```bash
php artisan serve
```
=> **Thành công!** Project đang chạy tại: `http://127.0.0.1:8000`

---

## Kiểm thử API

Cài đặt Extension **REST Client/Thunder Client** trên VS Code. Sau đó vào file **`api-tests.http`** ở thư mục gốc của project và bấm "Send Request" để test toàn bộ các lệnh API.
