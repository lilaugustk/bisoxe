#!/usr/bin/env node

/**
 * generate-general-image-ai.cjs
 * Sinh ảnh minh họa bất kỳ bằng Gemini Imagen 4.0 API và lưu dưới dạng WebP.
 */

const sharp = require('sharp');
const path = require('path');
const fs = require('fs');

// Đọc API key từ file .env của Laravel
function getEnvApiKey() {
    try {
        const envPath = path.resolve(__dirname, '../.env');
        if (fs.existsSync(envPath)) {
            const envContent = fs.readFileSync(envPath, 'utf8');
            const match = envContent.match(/^GEMINI_API_KEY=(.*)$/m);
            if (match) return match[1].trim();
        }
    } catch (e) {
        console.error('Lỗi khi đọc file .env:', e.message);
    }
    return null;
}

const apiKey = getEnvApiKey();
if (!apiKey) {
    console.error('ERROR: Không tìm thấy GEMINI_API_KEY trong file .env');
    process.exit(1);
}

// Parse CLI arguments
const args = {};
process.argv.slice(2).forEach(arg => {
    const match = arg.match(/^--([^=]+)=(.*)$/);
    if (match) args[match[1]] = match[2];
});

const {
    prompt,
    output,
    aspectRatio = '16:9',
    width,
    height,
} = args;

if (!prompt) {
    console.error('ERROR: Thiếu tham số --prompt');
    process.exit(1);
}

if (!output) {
    console.error('ERROR: Thiếu tham số --output');
    process.exit(1);
}

const outputPath = path.resolve(output);
const outputDir = path.dirname(outputPath);

if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

// Target dimensions for sharp resizing
const targetWidth = width ? parseInt(width, 10) : 1200;
const targetHeight = height ? parseInt(height, 10) : (aspectRatio === '16:9' ? 675 : (aspectRatio === '4:3' ? 900 : 630));

const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key=${apiKey}`;

async function generate() {
    try {
        const finalPrompt = prompt.trim() + ", single unified photograph, single frame, no split screen, no collage, no diptych, no grid, no side-by-side comparison";
        console.log(`Đang gọi Gemini Imagen 4.0 với prompt: "${finalPrompt}"...`);
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                instances: [{ prompt: finalPrompt }],
                parameters: {
                    numberOfImages: 1,
                    outputMimeType: 'image/jpeg',
                    aspectRatio: aspectRatio
                }
            })
        });

        if (!response.ok) {
            const errText = await response.text();
            throw new Error(`API returned status ${response.status}: ${errText}`);
        }

        const data = await response.json();
        const imageBytes = data.predictions?.[0]?.bytesBase64Encoded;

        if (!imageBytes) {
            throw new Error('Không tìm thấy dữ liệu ảnh (imageBytes) trong kết quả trả về của API.');
        }

        const buffer = Buffer.from(imageBytes, 'base64');

        // Chuyển đổi và lưu sang WebP với kích thước mong muốn
        await sharp(buffer)
            .resize(targetWidth, targetHeight, { fit: 'cover' })
            .webp({ quality: 85 })
            .toFile(outputPath);

        console.log(`SUCCESS: ${outputPath}`);
        process.exit(0);

    } catch (err) {
        console.error('ERROR:', err.message);
        process.exit(1);
    }
}

generate();
