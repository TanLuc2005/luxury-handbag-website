<?php
/**
 * lang.php — Global i18n Dictionary for LuxCarry
 */

if (isset($_GET['lang'])) {
    $allowed_langs = ['en', 'vi'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

$current_lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        // Navbar & Global
        'nav_shop'      => 'Shop',
        'nav_cart'      => 'Cart',
        'nav_dashboard' => 'Dashboard',
        'nav_profile'   => 'Profile',
        'nav_mfa'       => 'MFA Settings',
        'nav_login'     => 'Login',
        'nav_register'  => 'Register',
        'nav_logout'    => 'Logout',
        'footer_text'   => 'Cybersecurity Research Project | Not for commercial use',
        'research_banner'=> 'CYBERSECURITY RESEARCH ENVIRONMENT — For educational purposes only',
        
        // Hero Section (Trang chủ)
        'hero_subtitle'      => 'Discover our curated collection of premium luxury handbags.',
        'mfa_demo_badge'     => 'MFA Research Demo',
        'btn_shop_now'       => 'Shop Now',
        'btn_dashboard'      => 'Dashboard',
        
        // Security Features (Trang chủ)
        'sec_features_title' => 'Security Features',
        'feat_mfa_title'     => 'Email MFA',
        'feat_mfa_desc'      => 'One-Time Passwords delivered securely via email for two-factor authentication.',
        'feat_hash_title'    => 'bcrypt Hashing',
        'feat_hash_desc'     => 'Passwords hashed with cost-12 bcrypt. Never stored in plain text.',
        'feat_lock_title'    => 'Account Lockout',
        'feat_lock_desc'     => 'Accounts lock after 5 failed attempts for 5 minutes to block brute force.',
        'feat_csrf_title'    => 'CSRF Protection',
        'feat_csrf_desc'     => 'All forms protected by cryptographic CSRF tokens.',
        'feat_pdo_title'     => 'PDO Prepared',
        'feat_pdo_desc'      => 'All queries use PDO prepared statements to prevent SQL injection.',
        'feat_log_title'     => 'Attack Logging',
        'feat_log_desc'      => 'All login events logged with timestamp, IP, username, and result.',

        // Research Platform Section (Trang chủ)
        'res_platform_title' => 'Cybersecurity Research Platform',
        'res_platform_desc'  => 'This platform demonstrates MFA effectiveness against account takeover attacks. Use the Python simulators to observe how brute force and credential stuffing are defeated by Email-based MFA.',
        'tag_brute_force'    => 'Brute Force Simulation',
        'tag_cred_stuffing'  => 'Credential Stuffing',
        'tag_mfa_bypass'     => 'MFA Bypass Resistance',
        'tag_logging'        => 'Login Attempt Logging',

        // Footer
        'footer_desc'        => 'Premium handbag destination. This is a cybersecurity research environment.',
        'footer_quick_links' => 'Quick Links',
        'footer_sec_research'=> 'Security Research',
        'footer_mfa_demo'    => 'MFA Demo • Brute Force Simulation',
        'footer_cred_analysis'=> 'Credential Stuffing Analysis',
        'footer_research_only'=> 'Research Only',

        // Các trang khác...
        'shop_title'    => 'Luxury Handbags',
        'shop_products' => 'products',
        'btn_add_cart'  => 'Add to Cart',
        'btn_login_buy' => 'Login to Buy',
        'empty_shop'    => 'No products yet. Add some via phpMyAdmin!',
        'item_added'    => 'Item added to cart!',
        'login_title'   => 'Sign In',
        'login_desc'    => 'Access your LuxCarry account',
        'lbl_username'  => 'Username',
        'lbl_password'  => 'Password',
        'btn_signin'    => 'Sign In',
        'forgot_pass'   => 'Forgot password?',
        'create_acc'    => 'Create account',
        'verify_title'  => 'Email Verification',
        'check_email'   => 'Check Your Email',
        'sent_to'       => 'We sent a 6-digit code to:',
        'verify_btn'    => 'Verify Code',
        'time_left'     => 'Time remaining:',
        'expired'       => 'Code expired.',
        'login_again'   => 'Log in again',
        'not_you'       => 'Not you?',
        'back_login'    => 'Back to login',
        'invalid_code'  => 'Invalid or expired code. Please try again.',
        'success_msg'   => 'MFA Email Verified! Welcome back, ',
        
        // Profile Page
        'profile_title'  => 'Profile',
        'member_since'   => 'Member since',
        'mfa_active'     => 'MFA Active',
        'mfa_inactive'   => 'MFA Inactive',
        'update_email'   => 'Update Email',
        'email_address'  => 'Email Address',
        'change_password'=> 'Change Password',
        'current_pass'   => 'Current Password',
        'new_pass'       => 'New Password',
        'confirm_pass'   => 'Confirm New Password',
        'two_factor_auth'=> 'Two-Factor Authentication',
        'status_enabled' => 'Status: Enabled',
        'status_disabled'=> 'Status: Disabled',
        'manage_mfa'     => 'Manage MFA',

        // AI Assistant
        'nav_ai'        => 'AI Assistant',
        'ai_title'      => 'Security AI Assistant',
        'ai_prompt'     => 'Enter a query or paste attack logs for analysis:',
        'ai_placeholder'=> 'E.g., What is Credential Stuffing? How does MFA prevent it?',
        'ai_btn_ask'    => 'Ask Gemini',
        'ai_response'   => 'Gemini Response:'
    ],
    
    'vi' => [
        // Navbar & Global
        'nav_shop'      => 'Cửa hàng',
        'nav_cart'      => 'Giỏ hàng',
        'nav_dashboard' => 'Bảng điều khiển',
        'nav_profile'   => 'Hồ sơ',
        'nav_mfa'       => 'Cài đặt MFA',
        'nav_login'     => 'Đăng nhập',
        'nav_register'  => 'Đăng ký',
        'nav_logout'    => 'Đăng xuất',
        'footer_text'   => 'Dự án Nghiên cứu An ninh mạng | Không dùng cho mục đích thương mại',
        'research_banner'=> 'MÔI TRƯỜNG NGHIÊN CỨU AN NINH MẠNG — Chỉ dành cho mục đích giáo dục',
        
        // Hero Section (Trang chủ)
        'hero_subtitle'      => 'Khám phá bộ sưu tập túi xách cao cấp được tuyển chọn của chúng tôi.',
        'mfa_demo_badge'     => 'Bản trình diễn Nghiên cứu MFA',
        'btn_shop_now'       => 'Mua sắm ngay',
        'btn_dashboard'      => 'Bảng điều khiển',
        
        // Security Features (Trang chủ)
        'sec_features_title' => 'Các tính năng Bảo mật',
        'feat_mfa_title'     => 'Xác thực MFA qua Email',
        'feat_mfa_desc'      => 'Mã dùng một lần (OTP) được gửi an toàn qua email để xác thực hai yếu tố.',
        'feat_hash_title'    => 'Băm mật khẩu bcrypt',
        'feat_hash_desc'     => 'Mật khẩu được băm bằng thuật toán bcrypt. Không bao giờ lưu trữ dạng văn bản gốc.',
        'feat_lock_title'    => 'Khóa tài khoản',
        'feat_lock_desc'     => 'Tài khoản sẽ bị khóa 10 phút sau 5 lần đăng nhập sai nhằm chặn tấn công vét cạn.',
        'feat_csrf_title'    => 'Bảo vệ CSRF',
        'feat_csrf_desc'     => 'Tất cả các biểu mẫu được bảo vệ bằng mã thông báo CSRF mật mã học.',
        'feat_pdo_title'     => 'Truy vấn PDO',
        'feat_pdo_desc'      => 'Sử dụng PDO Prepared Statements để ngăn chặn triệt để lỗ hổng SQL Injection.',
        'feat_log_title'     => 'Ghi log Tấn công',
        'feat_log_desc'      => 'Mọi sự kiện đăng nhập đều được ghi lại với mốc thời gian, IP, tên người dùng và kết quả.',

        // Research Platform Section (Trang chủ)
        'res_platform_title' => 'Nền tảng Nghiên cứu An ninh mạng',
        'res_platform_desc'  => 'Hệ thống này chứng minh tính hiệu quả của MFA trước các cuộc tấn công chiếm đoạt tài khoản. Sử dụng trình mô phỏng Python để quan sát cách MFA qua Email đánh bại Brute Force và Credential Stuffing.',
        'tag_brute_force'    => 'Mô phỏng Brute Force',
        'tag_cred_stuffing'  => 'Tấn công Nhồi nhét thông tin (Credential Stuffing)',
        'tag_mfa_bypass'     => 'Kháng lại nỗ lực vượt rào MFA',
        'tag_logging'        => 'Ghi log Nỗ lực đăng nhập',

        // Footer
        'footer_desc'        => 'Điểm đến cho túi xách cao cấp. Đây là môi trường nghiên cứu an ninh mạng.',
        'footer_quick_links' => 'Liên kết nhanh',
        'footer_sec_research'=> 'Nghiên cứu Bảo mật',
        'footer_mfa_demo'    => 'MFA Demo • Mô phỏng Brute Force',
        'footer_cred_analysis'=> 'Phân tích Credential Stuffing',
        'footer_research_only'=> 'Chỉ dành cho nghiên cứu',

        // Các trang khác...
        'shop_title'    => 'Túi xách Cao cấp',
        'shop_products' => 'sản phẩm',
        'btn_add_cart'  => 'Thêm vào giỏ',
        'btn_login_buy' => 'Đăng nhập để mua',
        'empty_shop'    => 'Chưa có sản phẩm nào!',
        'item_added'    => 'Đã thêm sản phẩm vào giỏ!',
        'login_title'   => 'Đăng nhập',
        'login_desc'    => 'Truy cập tài khoản LuxCarry của bạn',
        'lbl_username'  => 'Tên đăng nhập',
        'lbl_password'  => 'Mật khẩu',
        'btn_signin'    => 'Đăng nhập',
        'forgot_pass'   => 'Quên mật khẩu?',
        'create_acc'    => 'Tạo tài khoản',
        'verify_title'  => 'Xác thực Email',
        'check_email'   => 'Kiểm tra hộp thư',
        'sent_to'       => 'Chúng tôi đã gửi mã 6 số đến:',
        'verify_btn'    => 'Xác nhận mã',
        'time_left'     => 'Thời gian còn lại:',
        'expired'       => 'Mã OTP đã hết hạn.',
        'login_again'   => 'Đăng nhập lại',
        'not_you'       => 'Không phải bạn?',
        'back_login'    => 'Quay lại đăng nhập',
        'invalid_code'  => 'Mã không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.',
        'success_msg'   => 'Xác thực MFA thành công! Chào mừng trở lại, ',

        // Profile Page
        'profile_title'  => 'Hồ sơ cá nhân',
        'member_since'   => 'Thành viên từ',
        'mfa_active'     => 'Đã bật MFA',
        'mfa_inactive'   => 'Chưa bật MFA',
        'update_email'   => 'Cập nhật Email',
        'email_address'  => 'Địa chỉ Email',
        'change_password'=> 'Đổi mật khẩu',
        'current_pass'   => 'Mật khẩu hiện tại',
        'new_pass'       => 'Mật khẩu mới',
        'confirm_pass'   => 'Xác nhận mật khẩu mới',
        'two_factor_auth'=> 'Xác thực hai yếu tố',
        'status_enabled' => 'Trạng thái: Đã bật',
        'status_disabled'=> 'Trạng thái: Đã tắt',
        'manage_mfa'     => 'Quản lý MFA',

        // AI Assistant
        'nav_ai'        => 'Trợ lý AI',
        'ai_title'      => 'Trợ lý AI Phân tích Bảo mật',
        'ai_prompt'     => 'Nhập câu hỏi hoặc dán log tấn công để phân tích:',
        'ai_placeholder'=> 'Ví dụ: Credential Stuffing là gì? MFA ngăn chặn nó như thế nào?',
        'ai_btn_ask'    => 'Hỏi Gemini',
        'ai_response'   => 'Gemini trả lời:'
    ]
];

$lang = $translations[$current_lang];
?>