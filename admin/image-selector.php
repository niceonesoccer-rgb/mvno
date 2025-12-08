<?php
/**
 * 관리자 페이지 - 인터넷 이미지 선택기
 * assets/images/internets/ 폴더의 이미지를 선택할 수 있습니다.
 */

// 이미지 디렉토리 경로
$imageDir = __DIR__ . '/../assets/images/internets/';
$imageUrl = '/MVNO/assets/images/internets/';

// 이미지 파일 목록 가져오기
$images = [];
if (is_dir($imageDir)) {
    $files = scandir($imageDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(svg|png|jpg|jpeg|gif|webp)$/i', $file)) {
            $images[] = [
                'filename' => $file,
                'url' => $imageUrl . $file,
                'path' => $imageDir . $file
            ];
        }
    }
    // 파일명 순으로 정렬
    usort($images, function($a, $b) {
        return strcmp($a['filename'], $b['filename']);
    });
}

// 선택된 이미지 URL 반환 (AJAX 요청)
if (isset($_GET['action']) && $_GET['action'] === 'get_selected' && isset($_GET['filename'])) {
    header('Content-Type: application/json');
    $filename = basename($_GET['filename']);
    $filepath = $imageDir . $filename;
    
    if (file_exists($filepath)) {
        echo json_encode([
            'success' => true,
            'url' => $imageUrl . $filename,
            'filename' => $filename
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '파일을 찾을 수 없습니다.'
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>인터넷 이미지 선택기</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-item {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        
        .image-item:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .image-item.selected {
            border-color: #6366f1;
            background: #eef2ff;
        }
        
        .image-preview {
            width: 100%;
            height: 120px;
            object-fit: contain;
            margin-bottom: 10px;
            background: #f9fafb;
            border-radius: 4px;
        }
        
        .image-name {
            font-size: 12px;
            color: #666;
            text-align: center;
            word-break: break-all;
        }
        
        .selected-info {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .selected-info h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .selected-image {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .selected-image img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 5px;
        }
        
        .selected-url {
            flex: 1;
            padding: 10px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            word-break: break-all;
        }
        
        .copy-btn {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .copy-btn:hover {
            background: #4f46e5;
        }
        
        .copy-btn.copied {
            background: #10b981;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 인터넷 이미지 선택기</h1>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="이미지 이름으로 검색...">
        </div>
        
        <?php if (empty($images)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>
                <p>이미지가 없습니다.</p>
                <p style="margin-top: 10px; font-size: 14px;">assets/images/internets/ 폴더에 이미지를 추가하세요.</p>
            </div>
        <?php else: ?>
            <div class="image-grid" id="imageGrid">
                <?php foreach ($images as $image): ?>
                    <div class="image-item" data-filename="<?php echo htmlspecialchars($image['filename']); ?>">
                        <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['filename']); ?>" 
                             class="image-preview"
                             onerror="this.style.display='none';">
                        <div class="image-name"><?php echo htmlspecialchars($image['filename']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="selected-info" id="selectedInfo" style="display: none;">
                <h2>선택된 이미지</h2>
                <div class="selected-image">
                    <img id="selectedPreview" src="" alt="">
                    <div class="selected-url" id="selectedUrl"></div>
                    <button class="copy-btn" onclick="copyUrl()">복사</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        let selectedImage = null;
        
        // 이미지 클릭 이벤트
        document.querySelectorAll('.image-item').forEach(item => {
            item.addEventListener('click', function() {
                // 기존 선택 제거
                document.querySelectorAll('.image-item').forEach(i => {
                    i.classList.remove('selected');
                });
                
                // 현재 항목 선택
                this.classList.add('selected');
                
                const filename = this.dataset.filename;
                const url = '/MVNO/assets/images/internets/' + filename;
                
                selectedImage = {
                    filename: filename,
                    url: url
                };
                
                // 선택된 이미지 정보 표시
                document.getElementById('selectedPreview').src = url;
                document.getElementById('selectedUrl').textContent = url;
                document.getElementById('selectedInfo').style.display = 'block';
                
                // 부모 창에 선택된 이미지 전달 (iframe이나 팝업에서 사용하는 경우)
                if (window.opener) {
                    window.opener.postMessage({
                        type: 'image-selected',
                        image: selectedImage
                    }, '*');
                }
            });
        });
        
        // 검색 기능
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.image-item').forEach(item => {
                const filename = item.dataset.filename.toLowerCase();
                if (filename.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // URL 복사 기능
        function copyUrl() {
            const url = document.getElementById('selectedUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.textContent;
                btn.textContent = '복사됨!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // 선택된 이미지 정보를 반환하는 함수 (외부에서 호출 가능)
        window.getSelectedImage = function() {
            return selectedImage;
        };
    </script>
</body>
</html>























