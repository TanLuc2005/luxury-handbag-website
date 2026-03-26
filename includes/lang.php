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
        
        // Shop Page
        'shop_title'    => 'Luxury Handbags',
        'shop_products' => 'products',
        'btn_add_cart'  => 'Add to Cart',
        'btn_login_buy' => 'Login to Buy',
        'empty_shop'    => 'No products yet. Add some via phpMyAdmin!',
        'item_added'    => 'Item added to cart!',
        
        // Login Page
        'login_title'   => 'Sign In',
        'login_desc'    => 'Access your LuxCarry account',
        'lbl_username'  => 'Username',
        'lbl_password'  => 'Password',
        'btn_signin'    => 'Sign In',
        'forgot_pass'   => 'Forgot password?',
        'create_acc'    => 'Create account',
        
        // MFA Verify (Đã bổ sung đầy đủ)
        'verify_title'  => 'Email Verification',
        'check_email'   => 'Check Your Email',
        'sent_to'       => 'We sent a 6-digit code to:',
        'verify_btn'    => 'Verify Code',
        'time_left'     => 'Time remaining:',
        'expired'       => 'Code expired.',
        'login_again'   => 'Log in again',
        'not_you'       => 'Not you?',
        'back_login'    => 'Back to login',
        'invalid_code'  => 'Invalid or expired code. Please check your email and try again.',
        'success_msg'   => 'MFA Email Verified! Welcome back, '
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
        
        // Shop Page
        'shop_title'    => 'Túi xách Cao cấp',
        'shop_products' => 'sản phẩm',
        'btn_add_cart'  => 'Thêm vào giỏ',
        'btn_login_buy' => 'Đăng nhập để mua',
        'empty_shop'    => 'Chưa có sản phẩm nào!',
        'item_added'    => 'Đã thêm sản phẩm vào giỏ!',
        
        // Login Page
        'login_title'   => 'Đăng nhập',
        'login_desc'    => 'Truy cập tài khoản LuxCarry của bạn',
        'lbl_username'  => 'Tên đăng nhập',
        'lbl_password'  => 'Mật khẩu',
        'btn_signin'    => 'Đăng nhập',
        'forgot_pass'   => 'Quên mật khẩu?',
        'create_acc'    => 'Tạo tài khoản',
        
        // MFA Verify (Đã bổ sung đầy đủ)
        'verify_title'  => 'Xác thực Email',
        'check_email'   => 'Kiểm tra hộp thư',
        'sent_to'       => 'Chúng tôi đã gửi mã 6 số đến:',
        'verify_btn'    => 'Xác nhận mã',
        'time_left'     => 'Thời gian còn lại:',
        'expired'       => 'Mã OTP đã hết hạn.',
        'login_again'   => 'Đăng nhập lại',
        'not_you'       => 'Không phải bạn?',
        'back_login'    => 'Quay lại đăng nhập',
        'invalid_code'  => 'Mã không hợp lệ hoặc đã hết hạn. Vui lòng kiểm tra email và thử lại.',
        'success_msg'   => 'Xác thực MFA thành công! Chào mừng trở lại, '
    ]
];

$lang = $translations[$current_lang];
?>