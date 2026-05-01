<?php
// URL'den metin çekme (CORS aşmak için basit PHP proxy)
if (isset($_POST['fetch_url'])) {
    header('Content-Type: application/json');
    $url = $_POST['fetch_url'];
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // Basit bir cURL isteği
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html) {
            // Script ve style etiketlerini temizle
            $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
            $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);
            // Sadece metni al
            $text = strip_tags($html);
            $text = preg_replace('/\s+/', ' ', $text); // Fazla boşlukları sil
            $text = mb_substr(trim($text), 0, 8000); // Token limitini korumak için kırp
            
            echo json_encode(['success' => true, 'text' => $text]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'URL okunamadı.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Prompt Oluşturucu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: '#0f172a',
                        card: '#1e293b',
                        primary: '#3b82f6',
                        accent: '#8b5cf6'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Sol Taraf: Ayarlar Paneli -->
        <div class="glass-panel rounded-2xl p-8 shadow-2xl relative">
            <!-- Loading overlay -->
            <div id="loadingOverlay" class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-sm rounded-2xl z-10 flex flex-col items-center justify-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-4"></i>
                <p class="text-slate-300">URL analiz ediliyor...</p>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <i class="fa-solid fa-sliders text-primary text-2xl"></i>
                <h1 class="text-2xl font-bold">SEO Blog Ayarları</h1>
            </div>

            <form id="promptForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Kaynak Türü</label>
                    <select id="sourceType" onchange="toggleSourceInput()" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                        <option value="prompt">Serbest Metin (Konu)</option>
                        <option value="url">Web Sitesi URL (Analiz için)</option>
                    </select>
                </div>

                <div>
                    <label id="sourceLabel" class="block text-sm font-medium text-slate-300 mb-1">Konu / Ana Fikir</label>
                    <textarea id="sourceValue" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Ne hakkında yazılacak?"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Yazı Tonu</label>
                        <select id="tone" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                            <option value="Profesyonel">Profesyonel</option>
                            <option value="Samimi">Samimi</option>
                            <option value="Eğitici">Eğitici</option>
                            <option value="İkna Edici">İkna Edici</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">SEO Seviyesi</label>
                        <select id="seoLevel" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                            <option value="Yüksek">Yüksek</option>
                            <option value="Orta">Orta</option>
                            <option value="Düşük">Düşük</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Hedef Kitle</label>
                        <input type="text" id="audience" value="Genel" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Yazı Sayısı</label>
                        <input type="number" id="postCount" value="1" min="1" max="5" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Odak Kelimeler (Virgülle)</label>
                    <input type="text" id="keywords" placeholder="yapay zeka, seo, teknoloji" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-primary outline-none">
                </div>
            </form>
        </div>

        <!-- Sağ Taraf: Aksiyon Butonları ve Çıktı -->
        <div class="glass-panel rounded-2xl p-8 shadow-2xl flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <i class="fa-solid fa-wand-magic-sparkles text-accent text-2xl"></i>
                    <h2 class="text-2xl font-bold">Aksiyon</h2>
                </div>
                
                <p class="text-slate-400 text-sm mb-6">
                    Mükemmel biçimlendirilmiş bir SEO blog promptu oluşturun ve Open WebUI üzerinde anında okuyabileceğiniz formata çevirin.
                </p>

                <div class="space-y-4">
                    <button type="button" onclick="handleAction(false)" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-all">
                        <i class="fa-solid fa-gears"></i> Prompt'a Çevir
                    </button>

                    <button type="button" onclick="handleAction(true)" class="w-full bg-gradient-to-r from-primary to-accent hover:opacity-90 text-white font-bold py-4 px-4 rounded-lg flex items-center justify-center gap-2 transition-all shadow-lg transform hover:-translate-y-1">
                        <i class="fa-solid fa-rocket"></i> Open WebUI'de Aç ve Üret
                    </button>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Oluşturulan Prompt (Önizleme)</label>
                <div id="promptOutput" class="w-full h-32 bg-slate-900 border border-slate-800 rounded-lg p-4 text-slate-400 text-xs font-mono overflow-y-auto">
                    Ayarları doldurup "Prompt'a Çevir" butonuna basın...
                </div>
            </div>
        </div>

    </div>

    <script>
        let currentPrompt = "";
        const OPEN_WEBUI_URL = "http://localhost:8080"; 

        function toggleSourceInput() {
            const type = document.getElementById('sourceType').value;
            const label = document.getElementById('sourceLabel');
            const input = document.getElementById('sourceValue');
            if (type === 'url') {
                label.innerText = 'Analiz Edilecek URL';
                input.placeholder = 'https://ornek-site.com';
            } else {
                label.innerText = 'Konu / Ana Fikir';
                input.placeholder = 'Ne hakkında yazılacak?';
            }
        }

        async function handleAction(openWebUI_after) {
            const sourceType = document.getElementById('sourceType').value;
            const sourceValue = document.getElementById('sourceValue').value;
            
            if (!sourceValue.trim()) {
                alert("Lütfen Konu veya URL alanını doldurun!");
                return;
            }

            let finalContext = sourceValue;

            if (sourceType === 'url') {
                document.getElementById('loadingOverlay').classList.remove('hidden');
                
                // PHP tarafına POST isteği atıp URL içeriğini çekiyoruz
                const formData = new URLSearchParams();
                formData.append('fetch_url', sourceValue);
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    });
                    
                    const result = await response.json();
                    if (result.success && result.text) {
                        finalContext = `[SİTE ANALİZİ İÇİN METİN]\nURL: ${sourceValue}\nİçerik Özeti:\n${result.text}`;
                    } else {
                        alert("URL çekilemedi, sadece link üzerinden analiz denenecek.");
                        finalContext = `Lütfen şu URL'yi analiz et ve içeriğe göre yaz: ${sourceValue}`;
                    }
                } catch (e) {
                    console.error(e);
                    alert("Ağ hatası oluştu!");
                } finally {
                    document.getElementById('loadingOverlay').classList.add('hidden');
                }
            }

            generatePromptText(finalContext, openWebUI_after);
        }

        function generatePromptText(contextData, openWebUI_after) {
            const tone = document.getElementById('tone').value;
            const postCount = document.getElementById('postCount').value;
            const seoLevel = document.getElementById('seoLevel').value;
            const audience = document.getElementById('audience').value;
            const keywords = document.getElementById('keywords').value || 'Belirtilmedi';

            currentPrompt = `Lütfen aşağıdaki ayarlara göre tam olarak ${postCount} adet SEO uyumlu BLOG YAZISI üret:

[AYARLAR]
- Konu / URL İçeriği: ${contextData}
- Yazı Tonu: ${tone}
- Hedef Kitle: ${audience}
- Odak Kelimeler: ${keywords}
- SEO Seviyesi: ${seoLevel}

Lütfen cevabını JSON olarak DEĞİL, doğrudan okunabilir, şık bir Markdown makalesi formatında ver.`;

            document.getElementById('promptOutput').innerText = currentPrompt;
            document.getElementById('promptOutput').classList.remove('text-slate-400');
            document.getElementById('promptOutput').classList.add('text-green-400');

            if (openWebUI_after) {
                openWebUI_function();
            }
        }

        function openWebUI_function() {
            navigator.clipboard.writeText(currentPrompt).then(() => {
                const encodedPrompt = encodeURIComponent(currentPrompt);
                const fullUrl = `${OPEN_WEBUI_URL}/?prompt=${encodedPrompt}`;
                window.open(fullUrl, '_blank');
            }).catch(err => {
                alert('Prompt kopyalanırken hata oluştu: ' + err);
            });
        }
    </script>
</body>
</html>
