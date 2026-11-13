// Libraro blog cover renderer (macOS: CoreGraphics + CoreText, so Hindi/Devanagari shapes correctly).
// Usage: swift render.swift specs.json   — each spec: {out, title, label, lang, motif, logo, fontEn}
// Writes 1600x900 PNGs; build.sh converts them to WebP.
import Foundation
import CoreGraphics
import CoreText
import ImageIO
import UniformTypeIdentifiers

struct Spec: Decodable { let out: String; let title: String; let label: String; let lang: String; let motif: String; let logo: String; let fontEn: String; let footer: String }

let W: CGFloat = 1600, H: CGFloat = 900
func rgb(_ hex: UInt32, _ a: CGFloat = 1) -> CGColor {
    CGColor(red: CGFloat((hex >> 16) & 0xff) / 255, green: CGFloat((hex >> 8) & 0xff) / 255, blue: CGFloat(hex & 0xff) / 255, alpha: a)
}
let navy = rgb(0x18225f), navy2 = rgb(0x1f3570), teal = rgb(0x2a7f8a), tealBright = rgb(0x5cc6d1), orange = rgb(0xf8a052), white = rgb(0xffffff)

func font(_ spec: Spec, size: CGFloat) -> CTFont {
    if spec.lang == "hi" { return CTFontCreateWithName("KohinoorDevanagari-Bold" as CFString, size, nil) }
    if let data = try? Data(contentsOf: URL(fileURLWithPath: spec.fontEn)) as CFData,
       let provider = CGDataProvider(data: data), let cg = CGFont(provider) {
        return CTFontCreateWithGraphicsFont(cg, size, nil, nil)
    }
    return CTFontCreateWithName("Helvetica-Bold" as CFString, size, nil)
}
func uiFont(_ spec: Spec, size: CGFloat, weight: String = "SemiBold") -> CTFont {
    if spec.lang == "hi" { return CTFontCreateWithName("KohinoorDevanagari-Semibold" as CFString, size, nil) }
    let path = spec.fontEn.replacingOccurrences(of: "Bold", with: weight)
    if let data = try? Data(contentsOf: URL(fileURLWithPath: path)) as CFData,
       let provider = CGDataProvider(data: data), let cg = CGFont(provider) {
        return CTFontCreateWithGraphicsFont(cg, size, nil, nil)
    }
    return font(spec, size: size)
}

func attr(_ s: String, _ f: CTFont, _ color: CGColor, lineHeight: CGFloat? = nil) -> NSAttributedString {
    var lh = lineHeight ?? 0
    let ps: CTParagraphStyle = withUnsafePointer(to: &lh) { p in
        let settings = [
            CTParagraphStyleSetting(spec: .minimumLineHeight, valueSize: MemoryLayout<CGFloat>.size, value: p),
            CTParagraphStyleSetting(spec: .maximumLineHeight, valueSize: MemoryLayout<CGFloat>.size, value: p),
        ]
        return CTParagraphStyleCreate(settings, lineHeight == nil ? 0 : settings.count)
    }
    return NSAttributedString(string: s, attributes: [
        NSAttributedString.Key(kCTFontAttributeName as String): f,
        NSAttributedString.Key(kCTForegroundColorAttributeName as String): color,
        NSAttributedString.Key(kCTParagraphStyleAttributeName as String): ps,
    ])
}

/// Draws wrapped text in a box whose TOP-left is (x, top). Returns used height. Shrinks font to fit maxLines.
func drawWrapped(_ ctx: CGContext, _ spec: Spec, _ text: String, x: CGFloat, top: CGFloat, width: CGFloat, maxHeight: CGFloat, startSize: CGFloat) -> CGFloat {
    var size = startSize
    while size > 40 {
        let lh = size * (spec.lang == "hi" ? 1.32 : 1.08)
        let s = attr(text, font(spec, size: size), white, lineHeight: lh)
        let fs = CTFramesetterCreateWithAttributedString(s)
        let fit = CTFramesetterSuggestFrameSizeWithConstraints(fs, CFRange(location: 0, length: 0), nil, CGSize(width: width, height: .greatestFiniteMagnitude), nil)
        if fit.height <= maxHeight {
            let rect = CGRect(x: x, y: H - top - fit.height, width: width, height: fit.height + 2)
            let frame = CTFramesetterCreateFrame(fs, CFRange(location: 0, length: 0), CGPath(rect: rect, transform: nil), nil)
            CTFrameDraw(frame, ctx)
            return fit.height
        }
        size -= 4
    }
    return 0
}

func roundRect(_ ctx: CGContext, _ r: CGRect, _ radius: CGFloat, fill: CGColor, stroke: CGColor? = nil, lineWidth: CGFloat = 2) {
    let p = CGPath(roundedRect: r, cornerWidth: radius, cornerHeight: radius, transform: nil)
    ctx.addPath(p); ctx.setFillColor(fill); ctx.fillPath()
    if let s = stroke { ctx.addPath(p); ctx.setStrokeColor(s); ctx.setLineWidth(lineWidth); ctx.strokePath() }
}
// Top-left based rect helper (CG origin is bottom-left)
func R(_ x: CGFloat, _ y: CGFloat, _ w: CGFloat, _ h: CGFloat) -> CGRect { CGRect(x: x, y: H - y - h, width: w, height: h) }

func text(_ ctx: CGContext, _ s: String, _ f: CTFont, _ c: CGColor, x: CGFloat, baselineFromTop y: CGFloat) {
    let line = CTLineCreateWithAttributedString(attr(s, f, c))
    ctx.textPosition = CGPoint(x: x, y: H - y)
    CTLineDraw(line, ctx)
}
func textWidth(_ s: String, _ f: CTFont) -> CGFloat {
    CGFloat(CTLineGetTypographicBounds(CTLineCreateWithAttributedString(attr(s, f, white)), nil, nil, nil))
}

func check(_ ctx: CGContext, cx: CGFloat, cy: CGFloat, r: CGFloat, color: CGColor) {
    ctx.setFillColor(color); ctx.fillEllipse(in: R(cx - r, cy - r, r * 2, r * 2))
    ctx.setStrokeColor(white); ctx.setLineWidth(r * 0.28); ctx.setLineCap(.round); ctx.setLineJoin(.round)
    ctx.move(to: CGPoint(x: cx - r * 0.45, y: H - cy)); ctx.addLine(to: CGPoint(x: cx - r * 0.1, y: H - cy - r * 0.35)); ctx.addLine(to: CGPoint(x: cx + r * 0.5, y: H - cy + r * 0.35)); ctx.strokePath()
}

/// Right-hand illustration: a "phone/app card" drawn in brand colours.
func motif(_ ctx: CGContext, _ spec: Spec) {
    let cx: CGFloat = 1060, cy: CGFloat = 110, cw: CGFloat = 440, ch: CGFloat = 680
    // glow behind card
    ctx.saveGState()
    ctx.setShadow(offset: CGSize(width: 0, height: -30), blur: 80, color: rgb(0x000000, 0.45))
    roundRect(ctx, R(cx, cy, cw, ch), 44, fill: rgb(0xffffff, 0.97))
    ctx.restoreGState()
    // app header bar
    roundRect(ctx, R(cx + 28, cy + 30, 120, 14), 7, fill: rgb(0x18225f, 0.85))
    roundRect(ctx, R(cx + cw - 76, cy + 24, 48, 26), 13, fill: rgb(0x34939f, 0.18))
    let ui = uiFont(spec, size: 22, weight: "SemiBold"), small = uiFont(spec, size: 18, weight: "Medium")
    let ink = rgb(0x16171b), muted = rgb(0x6f7077), line = rgb(0xececea)
    let x0 = cx + 28, inner = cw - 56

    switch spec.motif {
    case "seats":
        text(ctx, "Seat map · Evening", ui, ink, x: x0, baselineFromTop: cy + 100)
        let cols = 6, rows = 7, gap: CGFloat = 12
        let s = (inner - gap * CGFloat(cols - 1)) / CGFloat(cols)
        let pattern = "BBFBBEBFBBBBBBEFBBFBBBBBBFBEBBBBFBBBBBFBEBB"
        for r in 0..<rows { for c in 0..<cols {
            let i = r * cols + c; let ch = Array(pattern)[i % pattern.count]
            let col: CGColor = ch == "B" ? rgb(0x2a7f8a) : (ch == "E" ? orange : rgb(0xeef1f6))
            roundRect(ctx, R(x0 + CGFloat(c) * (s + gap), cy + 130 + CGFloat(r) * (s * 0.78 + gap), s, s * 0.78), 12, fill: col, stroke: ch == "F" ? rgb(0xd9d9dc) : nil)
        }}
        let ly = cy + 130 + CGFloat(rows) * (s * 0.78 + gap) + 30
        for (i, (c, l)) in [(rgb(0x2a7f8a), "Booked"), (rgb(0xeef1f6), "Free"), (orange, "Expiring")].enumerated() {
            let lx = x0 + CGFloat(i) * 135
            roundRect(ctx, R(lx, ly - 16, 20, 20), 6, fill: c, stroke: i == 1 ? rgb(0xd9d9dc) : nil)
            text(ctx, l, small, muted, x: lx + 30, baselineFromTop: ly)
        }
    case "fees":
        text(ctx, "Fee receipt", ui, ink, x: x0, baselineFromTop: cy + 100)
        check(ctx, cx: cx + cw - 64, cy: cy + 92, r: 26, color: rgb(0x0f8a4f))
        text(ctx, "₹1,200", font(spec.lang == "hi" ? Spec(out: "", title: "", label: "", lang: "en", motif: "", logo: "", fontEn: spec.fontEn, footer: "") : spec, size: 72), rgb(0x18225f), x: x0, baselineFromTop: cy + 200)
        text(ctx, "Paid via UPI · Sent on WhatsApp", small, muted, x: x0, baselineFromTop: cy + 236)
        let rows = [("Monthly plan", "₹1,000"), ("Registration", "₹300"), ("Discount", "−₹100"), ("Pending", "₹0")]
        for (i, r) in rows.enumerated() {
            let y = cy + 300 + CGFloat(i) * 70
            ctx.setFillColor(line); ctx.fill(R(x0, y - 40, inner, 2))
            text(ctx, r.0, ui, ink, x: x0, baselineFromTop: y)
            text(ctx, r.1, ui, i == 3 ? rgb(0x0f8a4f) : ink, x: x0 + inner - textWidth(r.1, ui), baselineFromTop: y)
        }
        roundRect(ctx, R(x0, cy + ch - 110, inner, 70), 35, fill: rgb(0x25d366))
        let wa = "Share on WhatsApp"; text(ctx, wa, ui, white, x: x0 + (inner - textWidth(wa, ui)) / 2, baselineFromTop: cy + ch - 66)
    case "qr":
        text(ctx, "Scan to mark attendance", ui, ink, x: x0, baselineFromTop: cy + 100)
        let q: CGFloat = 260, qx = cx + (cw - q) / 2, qy = cy + 130
        roundRect(ctx, R(qx - 16, qy - 16, q + 32, q + 32), 26, fill: rgb(0xf3f6fd))
        let n = 13, m = q / CGFloat(n)
        var seed: UInt32 = 7
        for r in 0..<n { for c in 0..<n {
            seed = seed &* 1103515245 &+ 12345
            let finder = (r < 4 && c < 4) || (r < 4 && c > n - 5) || (r > n - 5 && c < 4)
            if finder { continue }
            if (seed >> 16) & 1 == 1 { ctx.setFillColor(navy); ctx.fill(R(qx + CGFloat(c) * m, qy + CGFloat(r) * m, m - 2, m - 2)) }
        }}
        for (fx, fy) in [(0, 0), (n - 4, 0), (0, n - 4)] {
            roundRect(ctx, R(qx + CGFloat(fx) * m, qy + CGFloat(fy) * m, m * 4 - 2, m * 4 - 2), 12, fill: navy)
            roundRect(ctx, R(qx + CGFloat(fx) * m + m * 0.8, qy + CGFloat(fy) * m + m * 0.8, m * 2.4 - 2, m * 2.4 - 2), 8, fill: white)
            roundRect(ctx, R(qx + CGFloat(fx) * m + m * 1.3, qy + CGFloat(fy) * m + m * 1.3, m * 1.4 - 2, m * 1.4 - 2), 5, fill: teal)
        }
        for (i, r) in [("Aarav · 07:58", true), ("Priya · 08:03", true), ("Rohit · 08:11", true)].enumerated() {
            let y = cy + 470 + CGFloat(i) * 62
            check(ctx, cx: x0 + 18, cy: y - 8, r: 18, color: r.1 ? rgb(0x2a7f8a) : orange)
            text(ctx, r.0, ui, ink, x: x0 + 52, baselineFromTop: y)
        }
    case "checklist":
        text(ctx, "Today at the library", ui, ink, x: x0, baselineFromTop: cy + 100)
        let items = [("Renew 4 expiring plans", true), ("Collect ₹6,400 pending", true), ("Assign 3 new seats", true), ("Reply to 2 enquiries", false), ("Check evening shift", false), ("Pay electricity bill", false)]
        for (i, it) in items.enumerated() {
            let y = cy + 175 + CGFloat(i) * 80
            roundRect(ctx, R(x0, y - 48, inner, 66), 18, fill: it.1 ? rgb(0x2a7f8a, 0.08) : rgb(0xf6f6f4))
            if it.1 { check(ctx, cx: x0 + 34, cy: y - 15, r: 16, color: rgb(0x2a7f8a)) }
            else { ctx.setStrokeColor(rgb(0xc4c5ca)); ctx.setLineWidth(3); ctx.strokeEllipse(in: R(x0 + 18, y - 31, 32, 32)) }
            text(ctx, it.0, ui, it.1 ? muted : ink, x: x0 + 66, baselineFromTop: y - 6)
        }
    case "growth":
        text(ctx, "Monthly collection", ui, ink, x: x0, baselineFromTop: cy + 100)
        text(ctx, "₹1.8L", font(Spec(out: "", title: "", label: "", lang: "en", motif: "", logo: "", fontEn: spec.fontEn, footer: ""), size: 72), navy, x: x0, baselineFromTop: cy + 190)
        roundRect(ctx, R(x0 + 230, cy + 140, 110, 40), 20, fill: rgb(0x0f8a4f, 0.12))
        text(ctx, "▲ 32%", ui, rgb(0x0f8a4f), x: x0 + 248, baselineFromTop: cy + 168)
        let bars: [CGFloat] = [0.32, 0.41, 0.38, 0.52, 0.6, 0.71, 0.86, 1.0]
        let bw: CGFloat = 36, gap = (inner - bw * CGFloat(bars.count)) / CGFloat(bars.count - 1), base = cy + ch - 70, maxH: CGFloat = 360
        for (i, b) in bars.enumerated() {
            let h = maxH * b
            roundRect(ctx, R(x0 + CGFloat(i) * (bw + gap), base - h, bw, h), 10, fill: i == bars.count - 1 ? orange : rgb(0x2a7f8a, 0.35 + 0.08 * CGFloat(i)))
        }
    default: // dashboard
        text(ctx, "Dashboard", ui, ink, x: x0, baselineFromTop: cy + 100)
        let tiles = [("Seats filled", "86%", rgb(0x2a7f8a)), ("Due today", "₹4,200", orange), ("Present", "112", rgb(0x18225f)), ("Expiring", "7", rgb(0xc0362c))]
        let tw = (inner - 16) / 2
        for (i, t) in tiles.enumerated() {
            let tx = x0 + CGFloat(i % 2) * (tw + 16), ty = cy + 130 + CGFloat(i / 2) * 136
            roundRect(ctx, R(tx, ty, tw, 120), 22, fill: rgb(0xf6f6f4))
            ctx.setFillColor(t.2); ctx.fillEllipse(in: R(tx + 20, ty + 22, 14, 14))
            text(ctx, t.0, small, muted, x: tx + 44, baselineFromTop: ty + 36)
            text(ctx, t.1, font(Spec(out: "", title: "", label: "", lang: "en", motif: "", logo: "", fontEn: spec.fontEn, footer: ""), size: 44), ink, x: tx + 20, baselineFromTop: ty + 96)
        }
        let bars: [CGFloat] = [0.5, 0.7, 0.55, 0.8, 0.65, 0.9, 0.75]
        let bw: CGFloat = 40, gap = (inner - bw * CGFloat(bars.count)) / CGFloat(bars.count - 1), base = cy + ch - 50
        for (i, b) in bars.enumerated() {
            let h = 170 * b
            roundRect(ctx, R(x0 + CGFloat(i) * (bw + gap), base - h, bw, h), 10, fill: i == 5 ? rgb(0x2a7f8a) : rgb(0x18225f, 0.18))
        }
    }
}

func render(_ spec: Spec) {
    let cs = CGColorSpace(name: CGColorSpace.sRGB)!
    guard let ctx = CGContext(data: nil, width: Int(W), height: Int(H), bitsPerComponent: 8, bytesPerRow: 0, space: cs, bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue) else { return }

    // Brand gradient
    let g = CGGradient(colorsSpace: cs, colors: [navy, navy2, teal] as CFArray, locations: [0, 0.55, 1])!
    ctx.drawLinearGradient(g, start: CGPoint(x: 0, y: H), end: CGPoint(x: W, y: 0), options: [])
    let glow = CGGradient(colorsSpace: cs, colors: [rgb(0xf8a052, 0.42), rgb(0xf8a052, 0)] as CFArray, locations: [0, 1])!
    ctx.drawRadialGradient(glow, startCenter: CGPoint(x: W * 0.92, y: H * 0.95), startRadius: 0, endCenter: CGPoint(x: W * 0.92, y: H * 0.95), endRadius: 720, options: [])
    let glow2 = CGGradient(colorsSpace: cs, colors: [rgb(0x5cc6d1, 0.22), rgb(0x5cc6d1, 0)] as CFArray, locations: [0, 1])!
    ctx.drawRadialGradient(glow2, startCenter: CGPoint(x: 120, y: 60), startRadius: 0, endCenter: CGPoint(x: 120, y: 60), endRadius: 620, options: [])
    // Dot texture
    ctx.setFillColor(rgb(0xffffff, 0.06))
    var y: CGFloat = 24; while y < H { var x: CGFloat = 24; while x < 960 { ctx.fillEllipse(in: CGRect(x: x, y: y, width: 3, height: 3)); x += 36 }; y += 36 }

    // Logo
    if let src = CGImageSourceCreateWithURL(URL(fileURLWithPath: spec.logo) as CFURL, nil), let img = CGImageSourceCreateImageAtIndex(src, 0, nil) {
        let lh: CGFloat = 46, lw = lh * CGFloat(img.width) / CGFloat(img.height)
        ctx.draw(img, in: R(96, 86, lw, lh))
    }

    // Topic pill
    let pf = uiFont(spec, size: 26, weight: "SemiBold")
    let pw = textWidth(spec.label, pf) + 48
    roundRect(ctx, R(96, 196, pw, 54), 27, fill: rgb(0xf8a052, 0.16), stroke: rgb(0xf8a052, 0.55))
    text(ctx, spec.label, pf, orange, x: 120, baselineFromTop: 196 + (spec.lang == "hi" ? 37 : 36))

    // Headline
    _ = drawWrapped(ctx, spec, spec.title, x: 96, top: 286, width: 860, maxHeight: 470, startSize: spec.lang == "hi" ? 76 : 86)

    // Footer
    let ff = uiFont(spec, size: 24, weight: "Medium")
    ctx.setFillColor(rgb(0xf8a052)); ctx.fill(R(96, 806, 40, 4))
    text(ctx, spec.footer, ff, rgb(0xffffff, 0.82), x: 152, baselineFromTop: 818)

    motif(ctx, spec)

    guard let img = ctx.makeImage(), let dest = CGImageDestinationCreateWithURL(URL(fileURLWithPath: spec.out) as CFURL, UTType.png.identifier as CFString, 1, nil) else { return }
    CGImageDestinationAddImage(dest, img, nil)
    CGImageDestinationFinalize(dest)
}

let args = CommandLine.arguments
guard args.count > 1, let data = FileManager.default.contents(atPath: args[1]), let specs = try? JSONDecoder().decode([Spec].self, from: data) else {
    FileHandle.standardError.write("usage: swift render.swift specs.json\n".data(using: .utf8)!); exit(1)
}
for s in specs { render(s); print("rendered \(s.out)") }
