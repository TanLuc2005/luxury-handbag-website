<?php
/**
 * ai_assistant.php — Security AI Assistant Interface
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Gemini.php';
requireLogin();

$pageTitle = $lang['ai_title'] ?? 'AI Assistant';
$aiResponse = '';
$userQuery = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $userQuery = trim($_POST['prompt'] ?? '');
    
    if (!empty($userQuery)) {
        $current_lang = $_SESSION['lang'] ?? 'en';
        
        // Cấu hình Prompt động
        if ($current_lang === 'vi') {
            $systemPrompt = "Bạn là một chuyên gia an ninh mạng đang hỗ trợ trên nền tảng nghiên cứu bảo mật LuxCarry. Hãy phân tích và trả lời câu hỏi sau một cách khoa học, chi tiết và chuyên nghiệp, định dạng bằng Markdown. BẮT BUỘC TRẢ LỜI HOÀN TOÀN BẰNG TIẾNG VIỆT:\n\n" . $userQuery;
        } else {
            $systemPrompt = "You are a cybersecurity expert assisting on the LuxCarry security research platform. Analyze and answer the following query scientifically, in detail, and professionally, formatting with Markdown. YOU MUST RESPOND ENTIRELY IN ENGLISH:\n\n" . $userQuery;
        }
        
        // Gọi API
        $aiResponse = Gemini::ask($systemPrompt);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="fw-bold mb-4"><i class="bi bi-robot text-primary me-2"></i><?= $lang['ai_title'] ?? 'Security AI Assistant' ?></h2>
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?= $lang['ai_prompt'] ?? 'Enter a query or paste attack logs for analysis:' ?></label>
                            <textarea name="prompt" class="form-control" rows="4" placeholder="<?= $lang['ai_placeholder'] ?? 'E.g., What is Credential Stuffing?' ?>" required><?= e($userQuery) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="bi bi-stars me-1"></i><?= $lang['ai_btn_ask'] ?? 'Ask Gemini' ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($aiResponse)): ?>
            <div class="card border-0 shadow-sm" style="background-color: #f8f9fa;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= $lang['ai_response'] ?? 'Gemini Response:' ?></h5>
                    
                    <div id="raw-ai-response" style="display: none;">
                        <?= htmlspecialchars($aiResponse, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    
                    <div id="formatted-ai-response" class="text-dark lh-lg" style="font-size: 0.95rem;"></div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawElement = document.getElementById('raw-ai-response');
    if (rawElement) {
        // 1. Lấy chuỗi Markdown thô
        const rawText = rawElement.textContent || rawElement.innerText;
        const formattedDiv = document.getElementById('formatted-ai-response');

        // 2. Biên dịch sang HTML
        formattedDiv.innerHTML = marked.parse(rawText);

        // 3. Tiêm Bootstrap Icons và Class CSS để trang điểm
        // Trang điểm Tiêu đề
        formattedDiv.querySelectorAll('h1, h2, h3').forEach(h => {
            h.classList.add('fw-bold', 'mt-4', 'mb-3', 'text-primary', 'border-bottom', 'pb-2');
            h.innerHTML = '<i class="bi bi-shield-check me-2 text-warning"></i>' + h.innerHTML;
        });

        // Trang điểm Danh sách (Biến dấu * thành icon check màu xanh)
        formattedDiv.querySelectorAll('ul').forEach(ul => {
            ul.classList.add('list-unstyled', 'ms-2', 'my-3');
            ul.querySelectorAll('li').forEach(li => {
                li.classList.add('mb-2', 'd-flex', 'align-items-start');
                li.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2 mt-1"></i><span>' + li.innerHTML + '</span>';
            });
        });

        // Trang điểm Danh sách số
        formattedDiv.querySelectorAll('ol').forEach(ol => {
            ol.classList.add('ms-3', 'my-3');
            ol.querySelectorAll('li').forEach(li => {
                li.classList.add('mb-2');
            });
        });

        // Trang điểm Bôi đậm
        formattedDiv.querySelectorAll('strong').forEach(b => {
            b.classList.add('text-dark', 'fw-bold');
        });

        // Trang điểm Khối Code (nếu có)
        formattedDiv.querySelectorAll('pre').forEach(pre => {
            pre.classList.add('bg-dark', 'text-light', 'p-3', 'rounded', 'shadow-sm', 'mt-3', 'mb-3');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>