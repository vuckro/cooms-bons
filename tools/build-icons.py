"""Build boutique icons from the official bundled COOMS logotype (Pillow dev only)."""
from pathlib import Path
from PIL import Image
assets = Path(__file__).resolve().parents[1] / 'assets'
root = assets / 'app'
root.mkdir(exist_ok=True)
logo = Image.open(assets / 'brand/cooms-logo.png').convert('RGBA')
# Keep the entire logotype inside the maskable central safe circle.
canvas = Image.new('RGB', (1536, 1536), '#f5decb')
mark = logo.resize((1110, 421), Image.Resampling.LANCZOS)
canvas.paste(mark, ((1536 - mark.width) // 2, (1536 - mark.height) // 2), mark)
for name, size in [('icon-192.png', 192), ('icon-512.png', 512), ('icon-maskable-512.png', 512), ('apple-touch-icon.png', 180)]:
    canvas.resize((size, size), Image.Resampling.LANCZOS).save(root / name, optimize=True)
print('COOMS logo webapp icons built')
