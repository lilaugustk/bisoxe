#!/usr/bin/env node

/**
 * generate-plate-image.cjs
 * Sinh ảnh WebP (1200x630) cho bài viết biển số xe với độ chân thực cao.
 *
 * Usage:
 *   node scripts/generate-plate-image.cjs \
 *     --number="30K-999.99" \
 *     --province="Hà Nội" \
 *     --color=0 \
 *     --kinds="Ngũ quý,Tứ quý" \
 *     --type="car" \
 *     --output="public/images/plates/bien-so-30k-99999.webp"
 */

const sharp = require('sharp');
const path = require('path');
const fs = require('fs');

// --- Parse CLI arguments ---
const args = {};
process.argv.slice(2).forEach(arg => {
    const match = arg.match(/^--([^=]+)=(.*)$/);
    if (match) args[match[1]] = match[2];
});

const {
    number = '??-???',
    province = '',
    color = '0',     // 0: trắng, 1: vàng
    kinds = '',
    type = 'car',    // car, motorcycle
    output,
} = args;

if (!output) {
    console.error('Thiếu tham số --output');
    process.exit(1);
}

function escapeXml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

const isYellow = color === '1';
const isMotorcycle = type === 'motorcycle';

// --- Tách số biển cho biển vuông ---
function splitPlateNumber(num, isMoto) {
    let clean = num.trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    
    if (isMoto) {
        const parts = clean.split('-').filter(Boolean);
        if (parts.length >= 3) {
            return {
                top: `${parts[0]}-${parts[1]}`.toUpperCase(),
                bottom: parts.slice(2).join('-').toUpperCase()
            };
        } else if (parts.length === 2) {
            return {
                top: parts[0].toUpperCase(),
                bottom: parts[1].toUpperCase()
            };
        }
    }
    
    if (clean.includes('-')) {
        const parts = clean.split('-');
        return {
            top: parts[0].toUpperCase(),
            bottom: parts.slice(1).join('-').toUpperCase()
        };
    }
    
    const match = clean.match(/^(\d{2,3}[A-Z]{1,2})(.*)$/i);
    if (match) {
        return {
            top: match[1].toUpperCase(),
            bottom: match[2].replace(/^-/, '').trim().toUpperCase()
        };
    }
    
    return { top: clean, bottom: '' };
}

// --- Tạo đường sóng phản quang bảo mật (Security watermark) ---
function getWavyLines(x, y, w, h, isYel) {
    const strokeColor = isYel ? '#B45309' : '#94A3B8';
    const lines = [];
    const steps = 3;
    for (let i = 1; i <= steps; i++) {
        const py = y + (h / (steps + 1)) * i;
        let pathD = `M ${x + 15} ${py}`;
        const waveCount = 6;
        const segmentW = (w - 30) / waveCount;
        for (let j = 0; j < waveCount; j++) {
            const cx1 = x + 15 + j * segmentW + segmentW / 4;
            const cy1 = py - 8;
            const cx2 = x + 15 + j * segmentW + (3 * segmentW) / 4;
            const cy2 = py + 8;
            const ex = x + 15 + (j + 1) * segmentW;
            pathD += ` C ${cx1} ${cy1}, ${cx2} ${cy2}, ${ex} ${py}`;
        }
        lines.push(`<path d="${pathD}" fill="none" stroke="${strokeColor}" stroke-width="0.8" opacity="0.15"/>`);
    }
    return lines.join('\n');
}

// --- Vẽ ốc vít kim loại ---
function drawScrew(sx, sy) {
    return `
    <g transform="translate(${sx}, ${sy})">
      <!-- Bóng đổ đầu vít -->
      <circle cx="0" cy="0.8" r="6.5" fill="#000000" opacity="0.2"/>
      <!-- Đầu vít gradient kim loại -->
      <circle cx="0" cy="0" r="6" fill="url(#screwGrad)" stroke="#4B5563" stroke-width="0.6"/>
      <!-- Rãnh bắt vít -->
      <line x1="-4.5" y1="-1.5" x2="4.5" y2="1.5" stroke="#374151" stroke-width="1.2"/>
      <!-- Ánh sáng phản chiếu -->
      <circle cx="-1.8" cy="-1.8" r="2" fill="#FFFFFF" opacity="0.5"/>
    </g>
    `.trim();
}

// --- Vẽ Quốc huy/Huy hiệu dập chìm công an (CSGT Seal) ---
function drawRegistrySeal(cx, cy) {
    return `
    <g transform="translate(${cx}, ${cy})" opacity="0.6">
      <!-- Vòng ngoài -->
      <circle cx="0" cy="0" r="11" fill="none" stroke="#B91C1C" stroke-width="1.2"/>
      <!-- Vòng trong nét đứt -->
      <circle cx="0" cy="0" r="8.5" fill="none" stroke="#B91C1C" stroke-width="0.5" stroke-dasharray="1.2,1.2"/>
      <!-- Ngôi sao ở giữa -->
      <path d="M 0,-3.8 L 1,-1 L 3.8,-1 L 1.6,0.8 L 2.5,3.6 L 0,1.8 L -2.5,3.6 L -1.6,0.8 L -3.8,-1 L -1,-1 Z" fill="#B91C1C"/>
      <!-- Viền sáng nổi -->
      <circle cx="-0.4" cy="-0.4" r="11" fill="none" stroke="#FFFFFF" stroke-width="0.4" opacity="0.5"/>
    </g>
    `.trim();
}

// --- Vẽ văn bản dạng dập nổi 3D ---
function drawEmbossedText(x, y, text, size, color) {
    const escaped = escapeXml(text);
    return `
    <g font-family="'Arial Black', Arial, sans-serif" font-size="${size}" font-weight="900" text-anchor="middle" dominant-baseline="middle" letter-spacing="0">
      <!-- 1. Bóng dập sâu (Drop shadow) -->
      <text x="${x}" y="${y + 2.5}" fill="#000000" opacity="0.18">${escaped}</text>
      
      <!-- 2. Bóng âm góc trên (Contour shadow) -->
      <text x="${x - 1}" y="${y - 1}" fill="#000000" opacity="0.25">${escaped}</text>
      
      <!-- 3. Viền sáng góc dưới tạo độ nổi 3D -->
      <text x="${x + 1.2}" y="${y + 1.2}" fill="#FFFFFF" opacity="0.8">${escaped}</text>
      
      <!-- 4. Lớp chữ chính -->
      <text x="${x}" y="${y}" fill="${color}">${escaped}</text>
    </g>
    `.trim();
}

// --- Hàm tạo cấu trúc biển số xe (Long/Square) ---
function renderPlateSVG({ x, y, w, h, rx, number, isYel, isSquare, isMoto }) {
    const plateBg = isYel ? 'url(#yellowPlateBg)' : 'url(#whitePlateBg)';
    const plateBorder = '#111827'; 
    const plateText = '#0F172A'; 
    
    // Khung viền ngoài biển số
    const outerBorder = `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="${plateBg}" stroke="${plateBorder}" stroke-width="6" filter="url(#plateShadow)"/>`;
    
    // Đường chỉ đen lõm chạy bên trong (Inset line)
    const insetOffset = 8;
    const insetX = x + insetOffset;
    const insetY = y + insetOffset;
    const insetW = w - insetOffset * 2;
    const insetH = h - insetOffset * 2;
    const insetRx = Math.max(2, rx - insetOffset);
    const innerBorder = `<rect x="${insetX}" y="${insetY}" width="${insetW}" height="${insetH}" rx="${insetRx}" fill="none" stroke="${plateBorder}" stroke-width="1.8" opacity="0.3"/>`;
    
    // Tạo sóng phản quang
    const watermarks = getWavyLines(x, y, w, h, isYel);
    
    let extraElements = '';
    let textElements = '';
    
    if (isSquare) {
        // Biển vuông
        // Ốc vít cố định: trên giữa và dưới giữa
        const topScrew = drawScrew(x + w / 2, y + 14);
        const bottomScrew = drawScrew(x + w / 2, y + h - 14);
        extraElements += topScrew + '\n' + bottomScrew;
        
        // Tem công an chìm góc dưới bên trái
        const sealX = x + 50;
        const sealY = y + h - 50;
        extraElements += '\n' + drawRegistrySeal(sealX, sealY);
        
        const split = splitPlateNumber(number, isMoto);
        
        // Vẽ dòng chữ phía trên
        const topX = x + w / 2;
        const topY = y + h * 0.40;
        const topSize = isMoto ? 74 : 64;
        textElements += drawEmbossedText(topX, topY, split.top, topSize, plateText);
        
        // Vẽ dòng chữ phía dưới
        const botX = x + w / 2;
        const botY = y + h * 0.78;
        const botSize = isMoto ? 82 : 72;
        textElements += drawEmbossedText(botX, botY, split.bottom, botSize, plateText);
        
    } else {
        // Biển dài
        // Ốc vít cố định: trái giữa và phải giữa
        const leftScrew = drawScrew(x + 35, y + h / 2);
        const rightScrew = drawScrew(x + w - 35, y + h / 2);
        extraElements += leftScrew + '\n' + rightScrew;
        
        // Tem công an chìm nằm giữa phần mã vùng và chuỗi số (ở vị trí khoảng 36% chiều dài)
        const sealX = x + w * 0.35;
        const sealY = y + h / 2;
        extraElements += '\n' + drawRegistrySeal(sealX, sealY);
        
        // Vẽ toàn bộ chữ số trên 1 dòng
        const textX = x + w / 2;
        const textY = y + h / 2 + 3; // Lệch nhẹ xuống để cân đối trực quan
        const fontSize = number.length > 10 ? 68 : 78;
        textElements += drawEmbossedText(textX, textY, number.toUpperCase(), fontSize, plateText);
    }
    
    return `
    <!-- Plate Frame -->
    ${outerBorder}
    ${innerBorder}
    ${watermarks}
    ${extraElements}
    ${textElements}
    `.trim();
}

// --- Render Badges danh hiệu ---
const kindsList = kinds ? kinds.split(',').filter(k => k.trim()) : [];
const totalBadges = Math.min(kindsList.length, 4);
const badgeW = 120;
const totalW = totalBadges * (badgeW + 12);
const startX = 600 - totalW / 2;

const kindBadges = kindsList.slice(0, 4).map((k, i) => {
    const cx = startX + i * (badgeW + 12) + badgeW / 2;
    return `
    <rect x="${cx - badgeW / 2}" y="515" width="${badgeW}" height="32" rx="16" fill="#8C1E1E" opacity="0.08"/>
    <text x="${cx}" y="536" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#8C1E1E">${escapeXml(k.trim())}</text>
    `.trim();
}).join('\n');

// --- Khởi tạo bố cục nội dung SVG ---
let platesMarkup = '';

if (isMotorcycle) {
    // Layout Xe Máy: 1 biển vuông lớn căn giữa
    platesMarkup = renderPlateSVG({
        x: 370,
        y: 130,
        w: 460,
        h: 310,
        rx: 16,
        number: number,
        isYel: isYellow,
        isSquare: true,
        isMoto: true
    });
} else {
    // Layout Ô Tô: Cặp 2 biển (biển dài trên, biển vuông dưới)
    const longPlate = renderPlateSVG({
        x: 210,
        y: 110,
        w: 780,
        h: 150,
        rx: 12,
        number: number,
        isYel: isYellow,
        isSquare: false,
        isMoto: false
    });
    
    const squarePlate = renderPlateSVG({
        x: 390,
        y: 285,
        w: 420,
        h: 200,
        rx: 14,
        number: number,
        isYel: isYellow,
        isSquare: true,
        isMoto: false
    });
    
    platesMarkup = longPlate + '\n' + squarePlate;
}

const svg = `
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <!-- Nền dải màu Studio dịu, tạo chiều sâu -->
    <linearGradient id="bgGrad" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#F8FAFC"/>
      <stop offset="60%" stop-color="#F1F5F9"/>
      <stop offset="100%" stop-color="#E2E8F0"/>
    </linearGradient>
    
    <!-- Quầng sáng bổ trợ nổi bật (Soft Spotlight) -->
    <radialGradient id="glowGrad" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="${isYellow ? '#FFD000' : '#8C1E1E'}" stop-opacity="0.14"/>
      <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
    </radialGradient>
    
    <!-- Bộ lọc đổ bóng 3D chân thực cho biển số -->
    <filter id="plateShadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="12" stdDeviation="15" flood-color="#0F172A" flood-opacity="0.22"/>
      <feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#0F172A" flood-opacity="0.12"/>
    </filter>
    
    <!-- Gradient phản quang cho biển Trắng -->
    <linearGradient id="whitePlateBg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#FFFFFF"/>
      <stop offset="40%" stop-color="#F8FAFC"/>
      <stop offset="80%" stop-color="#E6ECF5"/>
      <stop offset="100%" stop-color="#D9E2EC"/>
    </linearGradient>
    
    <!-- Gradient phản quang cho biển Vàng -->
    <linearGradient id="yellowPlateBg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#FFE033"/>
      <stop offset="35%" stop-color="#F5B800"/>
      <stop offset="80%" stop-color="#E09500"/>
      <stop offset="100%" stop-color="#C78000"/>
    </linearGradient>
    
    <!-- Gradient kim loại đầu ốc vít -->
    <linearGradient id="screwGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#F3F4F6"/>
      <stop offset="30%" stop-color="#E5E7EB"/>
      <stop offset="70%" stop-color="#9CA3AF"/>
      <stop offset="100%" stop-color="#4B5563"/>
    </linearGradient>
    
    <!-- Gradient logo thương hiệu -->
    <linearGradient id="logoBgGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#8C1E1E"/>
      <stop offset="100%" stop-color="#5A1212"/>
    </linearGradient>
    
    <linearGradient id="logoPlateGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#FFFFFF"/>
      <stop offset="100%" stop-color="#F3F4F6"/>
    </linearGradient>
  </defs>

  <!-- Nền Canvas -->
  <rect width="1200" height="630" fill="url(#bgGrad)"/>

  <!-- Spotlight sau biển số -->
  <ellipse cx="600" cy="290" rx="420" ry="220" fill="url(#glowGrad)"/>

  <!-- Đường gờ trang trí phía trên -->
  <rect x="0" y="0" width="1200" height="7" fill="#8C1E1E"/>

  <!-- Khối Logo & Brand Header -->
  <g transform="translate(48, 28)">
    <rect width="42" height="42" rx="10" fill="url(#logoBgGrad)"/>
    <rect x="6.7" y="13.4" width="28.6" height="16" rx="2.5" fill="url(#logoPlateGrad)" stroke="#F5B800" stroke-width="1.1"/>
    <rect x="8.4" y="15.1" width="25.2" height="12.6" rx="1.7" fill="none" stroke="#9CA3AF" stroke-width="0.4" opacity="0.4"/>
    <circle cx="8.8" cy="15.5" r="0.6" fill="#9CA3AF"/>
    <circle cx="33.2" cy="15.5" r="0.6" fill="#9CA3AF"/>
    <text x="21" y="24" text-anchor="middle" font-family="'Arial', sans-serif" font-size="10.5" font-weight="900" fill="#111827">B</text>
    <path d="M5 32.8 C 12.6 29.4, 29.4 29.4, 37 32.8" stroke="#F5B800" stroke-width="1.3" stroke-linecap="round"/>
    <path d="M9.2 35.3 C 16 32.8, 26 32.8, 32.8 35.3" stroke="#FFFFFF" stroke-width="0.6" stroke-linecap="round" opacity="0.6"/>
  </g>
  <text x="102" y="50" font-family="Arial, sans-serif" font-size="15" font-weight="900" fill="#8C1E1E">BISOXE.COM</text>
  <text x="102" y="66" font-family="Arial, sans-serif" font-size="10" font-weight="700" fill="#999" letter-spacing="2">GIẢI MÃ PHONG THỦY</text>

  <!-- Biển số xe ghép thực tế (Plates Layout) -->
  ${platesMarkup}

  <!-- Danh hiệu/badges dạng danh sách nhãn -->
  ${kindBadges}

  <!-- Nhãn xuất xứ/tỉnh thành -->
  <text x="600" y="568" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" font-weight="700" fill="#475569">VÙNG ĐĂNG KÝ: ${escapeXml(province.toUpperCase())}</text>

  <!-- Đường phân cách chân trang -->
  <line x1="480" y1="588" x2="720" y2="588" stroke="#CBD5E1" stroke-width="1.2"/>

  <!-- Footer Info -->
  <text x="600" y="608" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="500" fill="#94A3B8">bisoxe.com — Tra cứu phong thủy &amp; Giá trị đấu giá biển số xe Việt Nam chính xác</text>
</svg>
`.trim();

// --- Convert SVG → WebP bằng Sharp ---
const outputPath = path.resolve(output);
const outputDir = path.dirname(outputPath);

if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

sharp(Buffer.from(svg))
    .resize(1200, 630)
    .webp({ quality: 85, effort: 5 })
    .toFile(outputPath)
    .then(() => {
        console.log('OK:' + outputPath);
        process.exit(0);
    })
    .catch(err => {
        console.error('ERROR:' + err.message);
        process.exit(1);
    });
