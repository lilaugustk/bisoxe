#!/usr/bin/env node

/**
 * generate-plate-image-ai.cjs
 * Sinh ảnh biển số xe bằng Gemini Imagen 4.0 API và lưu dưới dạng WebP 1200x630.
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
    number = '30K-999.99',
    output,
} = args;

if (!output) {
    console.error('ERROR: Thiếu tham số --output');
    process.exit(1);
}

const outputPath = path.resolve(output);
const outputDir = path.dirname(outputPath);

if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

// Hàm băm sinh style ngẫu nhiên nhưng cố định cho từng biển số
function getDeterministicStyle(numStr) {
    let hash = 0;
    for (let i = 0; i < numStr.length; i++) {
        hash = numStr.charCodeAt(i) + ((hash << 5) - hash);
    }
    hash = Math.abs(hash);

    const cars = [
        "Mercedes-Benz S-Class",
        "BMW 7 Series",
        "Audi A8",
        "Porsche Panamera",
        "Lexus LS",
        "Rolls-Royce Ghost",
        "Bentley Flying Spur",
        "Mercedes-Maybach S-Class",
        "Range Rover Autobiography",
        "Porsche Cayenne Coupe",
        "Maserati Quattroporte",
        "Aston Martin Rapide",
        "Jaguar XJ",
        "Tesla Model S Plaid",
        "Volvo S90 Recharge"
    ];

    const colors = [
        "obsidian black",
        "pearl white",
        "selenite gray",
        "deep navy blue",
        "emerald green",
        "ruby red",
        "champagne gold",
        "satin bronze",
        "sapphire blue",
        "titanium silver"
    ];

    const settings = [
        "parked on a clean modern city street in Vietnam with green trees in the background",
        "parked on a luxury villa driveway with warm lights in the evening",
        "parked on a sleek asphalt road in a premium urban district of Saigon",
        "parked in a high-end contemporary garage with soft overhead lighting",
        "parked near a luxury yacht marina on a sunny afternoon",
        "parked in front of a modern glass skyscraper on a clear day",
        "parked on a scenic coastal highway in Vietnam overlooking the ocean at sunset",
        "parked on a brick-paved driveway of a colonial-style luxury building",
        "parked near a high-tech smart villa surrounded by beautiful tropical landscaping",
        "parked on a clean street in a premium residential area at dawn with soft morning light"
    ];

    const carIdx = hash % cars.length;
    const colorIdx = (hash >> 2) % colors.length;
    const settingIdx = (hash >> 4) % settings.length;

    return {
        car: cars[carIdx],
        color: colors[colorIdx],
        setting: settings[settingIdx]
    };
}

const style = getDeterministicStyle(number);

// Chỉ thêm chỉ dẫn về chữ D nếu trong biển số thực sự có chứa chữ D
const hasLetterD = number.toLowerCase().includes('d');
const dInstruction = hasLetterD
    ? 'The letter "D" in the license plate registration text must be a standard Latin capital letter "D" with a clean vertical stem and a single curve, and must not have any horizontal crossbar or middle line intersecting it (do not display it as "Đ").'
    : '';

// Thiết lập prompt tối ưu để Imagen 4.0 sinh biển số Việt Nam trên đuôi xe sang
const prompt = `A professional, high-end commercial photograph of the rear view of a luxury ${style.color} ${style.car}. The car is ${style.setting}. The car's license plate is mounted in the center and is extremely clear, displaying exactly the text "${number}" with sharp, bold black characters on a standard Vietnamese rectangular reflective license plate with a white background and a thin black border. The license plate text "${number}" must be perfectly readable, aligned, and look like a genuine Vietnamese license plate. No other text or gibberish characters should be visible on the license plate or the car. ${dInstruction ? dInstruction + ' ' : ''}The photograph features realistic lighting, beautiful reflections on the polished car paint, a shallow depth of field with a clean blurry background, and a premium cinematic look.`;


const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key=${apiKey}`;

async function generate() {
    try {
        const finalPrompt = prompt.trim() + ", single unified photograph, single frame, no split screen, no collage, no diptych, no grid, no side-by-side comparison";
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                instances: [{ prompt: finalPrompt }],
                parameters: {
                    numberOfImages: 1,
                    outputMimeType: 'image/jpeg',
                    aspectRatio: '16:9'
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

        // Chuyển đổi và lưu sang WebP 1200x630
        await sharp(buffer)
            .resize(1200, 630, { fit: 'cover' })
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
