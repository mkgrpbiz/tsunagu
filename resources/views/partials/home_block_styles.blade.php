<style>
.tsn-home{max-width:760px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.tsn-home .hero{background:linear-gradient(180deg,#eaf3ff 0%,#ffffff 100%);border:1px solid #dbeafe;border-radius:22px;padding:24px;box-shadow:0 16px 36px rgba(37,99,235,.10);text-align:center}
.tsn-home .hero .brand{font-weight:900;font-size:22px;color:#0f172a;letter-spacing:.02em}
.tsn-home .hero .brand span{color:#2563eb}
.tsn-home .hero .brand-logo{display:block;max-height:144px;max-width:100%;margin:0 auto}
.tsn-home .hero .tagline{margin-top:6px;font-size:13px;color:#475569}
.tsn-home .hero .welcome{margin-top:16px;font-size:15px;font-weight:700;color:#0f172a}

.tsn-home .block{margin-top:20px}

.tsn-home .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px 18px;box-shadow:0 6px 16px rgba(0,0,0,.04)}
.tsn-home .card .title{font-weight:800;font-size:14.5px;color:#0f172a;margin-bottom:6px}
.tsn-home .card .body{font-size:13.5px;color:#475569;line-height:1.7;white-space:pre-line}
.tsn-home .card.color-blue{background:#eff6ff;border-color:#bfdbfe}
.tsn-home .card.color-blue .title{color:#1d4ed8}
.tsn-home .card.color-orange{background:#fffbeb;border-color:#fde68a}
.tsn-home .card.color-orange .title{color:#d97706}
.tsn-home .card.color-red{background:#fef2f2;border-color:#fecaca}
.tsn-home .card.color-red .title{color:#dc2626}
.tsn-home .card .cta-button{display:block;margin-top:12px;background:#2563eb;color:#fff;font-weight:800;font-size:14px;text-align:center;padding:12px;border-radius:12px;text-decoration:none}
.tsn-home .card .cta-button:hover{background:#1d4ed8}

.tsn-home .benefits-title{font-weight:900;font-size:16px;color:#0f172a;text-align:center;margin-bottom:16px}
.tsn-home .benefit{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:14px 16px;margin:10px 0;box-shadow:0 6px 16px rgba(0,0,0,.04)}
.tsn-home .benefit .num{flex-shrink:0;width:32px;height:32px;border-radius:999px;background:#0f172a;color:#fff;font-weight:900;display:flex;align-items:center;justify-content:center;font-size:14px}
.tsn-home .benefit .title{font-weight:800;font-size:14.5px;color:#0f172a}
.tsn-home .benefit .desc{margin-top:2px;font-size:13px;color:#475569;line-height:1.6}

.tsn-home .image-block img{width:100%;border-radius:16px;border:1px solid #e5e7eb;display:block}
.tsn-home .image-block .caption{margin-top:8px;font-size:12.5px;color:#6b7280;text-align:center}

.tsn-home .cta-card{background:linear-gradient(180deg,#eff6ff,#fff);border:1px solid #dbeafe;border-radius:18px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.05)}
.tsn-home .cta-card .cta-title{font-weight:900;font-size:15px;color:#0f172a;margin-bottom:6px}
.tsn-home .cta-card .cta-body{font-size:13px;color:#475569;line-height:1.7;margin-bottom:12px;white-space:pre-line}
.tsn-home .cta-card input[readonly]{width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:12.5px;background:#f9fafb;color:#374151}
.tsn-home .cta-card button.copy{margin-top:8px;width:100%;border:none;border-radius:10px;padding:11px 14px;font-weight:800;cursor:pointer;background:#2563eb;color:#fff}
.tsn-home .cta-card .cta-link{display:block;text-align:center;margin-top:4px;width:100%;box-sizing:border-box;border-radius:10px;padding:11px 14px;font-weight:800;background:#2563eb;color:#fff;text-decoration:none}
.tsn-home .cta-card .cta-link:hover{background:#1d4ed8}
.tsn-home .cta-card.cta-card-restricted{background:#f3f4f6;border-color:#e5e7eb;text-align:center;filter:grayscale(1)}
.tsn-home .cta-card-restricted .restricted-title{font-weight:800;font-size:14px;color:#6b7280}
.tsn-home .cta-card-restricted .restricted-body{margin-top:4px;font-size:12.5px;color:#9ca3af}

.tsn-home .materials-title{font-weight:900;font-size:15px;color:#0f172a;margin-bottom:10px}
.tsn-home .material-item{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px;margin:8px 0;font-size:13.5px}
.tsn-home .material-item a{color:#2563eb;font-weight:700}

.tsn-home .ticker-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 10px 24px rgba(0,0,0,.06)}
.tsn-home .ticker-head{display:flex;align-items:center;gap:8px;padding:12px 16px;background:linear-gradient(180deg,#eff6ff,#fff);border-bottom:1px solid #e5e7eb;font-weight:800;font-size:13px}
.tsn-home .ticker-dot{width:8px;height:8px;border-radius:999px;background:#ef4444}
.tsn-home .ticker-viewport{height:150px;overflow:hidden;position:relative}
.tsn-home .ticker-fade{position:absolute;left:0;right:0;height:16px;pointer-events:none;z-index:1}
.tsn-home .ticker-fade.top{top:0;background:linear-gradient(to bottom, #fff, rgba(255,255,255,0))}
.tsn-home .ticker-fade.bottom{bottom:0;background:linear-gradient(to top, #fff, rgba(255,255,255,0))}
.tsn-home .ticker-track{animation:tsn-ticker-scroll 18s linear infinite}
.tsn-home .ticker-track:hover{animation-play-state:paused}
.tsn-home .ticker-track--static{animation:none}
.tsn-home .ticker-item{padding:10px 16px;font-size:13.5px;line-height:1.6;color:#334155;border-bottom:1px solid #f1f5f9;display:flex;gap:10px}
.tsn-home .ticker-item .date{flex-shrink:0;color:#2563eb;font-weight:800}
@keyframes tsn-ticker-scroll{0%{transform:translateY(0)}100%{transform:translateY(-50%)}}

.tsn-home .closing{margin-top:24px;text-align:center;font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.8;white-space:pre-line}
</style>
