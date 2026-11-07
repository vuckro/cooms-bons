"""Build our geometric boutique app icon. Pillow is a development-only dependency."""
from pathlib import Path
from PIL import Image,ImageDraw
root=Path(__file__).resolve().parents[1]/'assets/app';root.mkdir(exist_ok=True)
svg='''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><rect width="512" height="512" fill="#bc3022"/><g fill="none" stroke="#fff" stroke-width="18"><rect x="136" y="136" width="84" height="84" rx="8"/><rect x="292" y="136" width="84" height="84" rx="8"/><rect x="136" y="292" width="84" height="84" rx="8"/><path d="m270 315 34 35 73-78" stroke-linecap="round" stroke-linejoin="round"/></g></svg>'''
(root/'icon.svg').write_text(svg)
im=Image.new('RGB',(1536,1536),'#bc3022');d=ImageDraw.Draw(im)
for x,y in [(136,136),(292,136),(136,292)]:d.rounded_rectangle((x*3,y*3,(x+84)*3,(y+84)*3),radius=24,outline='white',width=54)
d.line([(270*3,315*3),(304*3,350*3),(377*3,272*3)],fill='white',width=54,joint='curve')
for x,y in [(270,315),(304,350),(377,272)]:d.ellipse(((x-9)*3,(y-9)*3,(x+9)*3,(y+9)*3),fill='white')
for name,size in [('icon-192.png',192),('icon-512.png',512),('icon-maskable-512.png',512),('apple-touch-icon.png',180)]:
 im.resize((size,size),Image.Resampling.LANCZOS).save(root/name,optimize=True)
print('Webapp icons built')
