"""End-to-end browser checks with an actual QR carried by a virtual camera.
Prerequisites: prepare-http.php, HTTP checks, prepare-camera.php; Chrome, ffmpeg and
Playwright CLI. Set PWCLI to the CLI executable/wrapper when using another setup.
No physical camera is opened. Only this script's dedicated test session is closed.
"""
from pathlib import Path
import json, subprocess, os, shutil
root=Path(__file__).resolve().parents[1]
f=json.loads((root/'output/http-fixture.json').read_text())
assert f['base'].startswith(('http://localhost:','http://127.0.0.1:'))
assert shutil.which('ffmpeg'), 'ffmpeg is required for virtual-camera checks'
cli=os.environ.get('PWCLI', str(Path.home()/'.codex/skills/playwright/scripts/playwright_cli.sh'))
session='cooms-camera-qa'
video=root/'output/camera.y4m'
subprocess.run(['ffmpeg','-y','-loglevel','error','-loop','1','-i',str(root/'output/camera-bon.png'),'-vf','scale=-2:460,pad=640:480:(ow-iw)/2:(oh-ih)/2:color=white','-t','3','-r','10','-pix_fmt','yuv420p',str(video)],check=True)
config={'browser':{'browserName':'chromium','isolated':True,'launchOptions':{'channel':'chrome','args':['--use-fake-ui-for-media-stream','--use-fake-device-for-media-stream','--use-file-for-fake-video-capture='+str(video)]},'contextOptions':{'viewport':{'width':1440,'height':1000}}}}
config_path=root/'output/browser-config.json'; config_path.write_text(json.dumps(config))
state_path=root/'output/browser-auth.json'; state_path.write_text(json.dumps({'cookies':f['accounts']['admin']['cookies'],'origins':[]})); state_path.chmod(0o600)
source=(root/'tests/browser-flow.js').read_text().strip().removesuffix(';')
code='async (page) => { return await ('+source+')(page, '+json.dumps({'base':f['base'],'lot':f['lot'],'bons':f['bons']})+'); }'
def run(*args):
 r=subprocess.run([cli,'-s='+session,*args],capture_output=True,text=True,cwd=root)
 result=r.stdout+r.stderr
 if r.returncode or '### Error' in result:
  safe=result.split('### Ran Playwright code')[0]
  for b in f['bons']: safe=safe.replace(b['token'],'<test-token>')
  raise RuntimeError(safe)
 return result
try:
 run('open',f['base']+'/?cooms_scan=1','--config',str(config_path))
 run('state-load',str(state_path))
 result=run('run-code',code)
 summary=json.loads(result.split('### Result',1)[1].split('### Ran Playwright code',1)[0].strip())
 assert summary['passed']==14 and len(summary['checks'])==14, 'Incomplete browser test result'
 print(json.dumps(summary,ensure_ascii=False,indent=2))
finally:
 subprocess.run([cli,'-s='+session,'close'],capture_output=True,text=True,cwd=root)
 state_path.unlink(missing_ok=True)
