import json, urllib.request, urllib.parse, urllib.error, re, html, io, zipfile, csv, concurrent.futures
from pathlib import Path
root=Path(__file__).resolve().parents[1]
fixture=json.loads((root/'output/http-fixture.json').read_text())
base=fixture['base']
assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1'), 'Only localhost HTTP tests are allowed'
cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts']['admin']['cookies'])
passed=0

def check(ok,label):
 global passed
 if not ok: raise AssertionError(label)
 passed+=1; print('PASS:',label,flush=True)

def request(url,data=None,auth=True):
 headers={'Cookie':cookie} if auth else {}
 try:
  with urllib.request.urlopen(urllib.request.Request(url,data=data,headers=headers),timeout=60) as r:
   return r.status,r.read(),r.headers
 except urllib.error.HTTPError as e: return e.code,e.read(),e.headers

def url(index=0): return base+'/?cooms_bon='+fixture['bons'][index]['token']

code,body,headers=request(url(),auth=False)
s=body.decode();check(code==200 and 'Accès équipe COOMS' in s and fixture['bons'][0]['lot_name'] not in s,'Anonymous page hides private lot and provides login')
check('no-store' in headers.get('Cache-Control','') and 'noindex' in headers.get('X-Robots-Tag',''),'Scan cache and indexing headers')
check(headers.get('Referrer-Policy')=='no-referrer','Token referrer suppressed')
check('Valider la remise du cadeau' not in s,'Anonymous cannot see withdrawal action')
code,body,_=request(url(),urllib.parse.urlencode({'_wpnonce':'invalid'}).encode())
check(code==403 and 'Votre session de confirmation a expiré' in body.decode(),'Invalid nonce POST refused')
_,body,_=request(url());s=body.decode();check('<h1>Bon valide</h1>' in s,'Invalid POST did not consume bon')
nonce=re.search(r'name="_wpnonce" value="([^"]+)"',s).group(1)
def redeem(_): return request(url(),urllib.parse.urlencode({'_wpnonce':nonce}).encode())[1].decode()
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool: results=list(pool.map(redeem,range(6)))
check(sum('<h1>Remise enregistrée</h1>' in x for x in results)==1,'Six concurrent requests: exactly one success')
check(sum('<h1>Bon déjà utilisé</h1>' in x for x in results)==5,'Five losing requests see used status')
_,body,_=request(url());check('<h1>Bon déjà utilisé</h1>' in body.decode(),'Fresh scan sees original withdrawal')
code,body,_=request(base+'/?cooms_bon='+'a'*64)
check(code==404 and 'Bon introuvable' in body.decode(),'Unknown token gives safe 404')
_,body,_=request(base+'/wp-admin/admin.php?page=cooms-bons&lot='+str(fixture['lot']))
s=body.decode()
links=[html.unescape(x) for x in re.findall(r'href="([^"]+)"',s)]
zip_url=next(x for x in links if 'format=zip' in x);csv_url=next(x for x in links if 'format=csv' in x)
code,data,_=request(zip_url);check(code==200 and data[:2]==b'PK','Authenticated ZIP download')
archive=zipfile.ZipFile(io.BytesIO(data));check(len(archive.namelist())==25 and archive.testzip() is None,'ZIP contains 25 intact PNGs')
(root/'output/cooms-lot-test.zip').write_bytes(data)
(root/'output/exported-bon.png').write_bytes(archive.read(archive.namelist()[0]))
code,data2,_=request(zip_url);archive2=zipfile.ZipFile(io.BytesIO(data2));check({x:archive.read(x) for x in archive.namelist()}=={x:archive2.read(x) for x in archive2.namelist()},'Repeated export gives identical images')
code,data,_=request(csv_url);rows=list(csv.reader(io.StringIO(data.decode('utf-8-sig')),delimiter=';'))
check(code==200 and len(rows)==26 and len(rows[0])==7,'CSV has 25 bons and expected columns')
check(sum(row[4]=='Utilisé' for row in rows[1:])==1,'CSV withdrawal count matches actual redemption')
check(all(b['token'].encode() not in data for b in fixture['bons']),'CSV contains no secret tokens')
check(request(zip_url,auth=False)[0]!=200,'Anonymous export refused')
check(request(zip_url.replace('_wpnonce=','_wpnonce=bad'),auth=True)[0]==403,'Invalid export nonce refused')
admin_cookie=cookie
for label in ('subscriber', 'seller'):
 cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts'][label]['cookies'])
 code,body,_=request(url(4));s=body.decode()
 if label=='subscriber': check(code==403 and 'Accès non autorisé' in s and fixture['bons'][4]['lot_name'] not in s,'Subscriber denied without leaking lot')
 else:
  check(code==200 and '<h1>Bon valide</h1>' in s,'Seller sees valid bon')
  nonce=re.search(r'name="_wpnonce" value="([^"]+)"',s).group(1)
  code,body,_=request(url(4),urllib.parse.urlencode({'_wpnonce':nonce}).encode())
  check('<h1>Remise enregistrée</h1>' in body.decode(),'Seller redeems through HTTP endpoint')
 code,body,_=request(base+'/wp-admin/admin.php?page=cooms-bons-new')
 check(code==403 or 'Préparer le lot' not in body.decode(),'Limited account cannot create lots: '+label)
 code,_,_=request(base+'/wp-admin/admin-post.php?action=cooms_export&format=csv')
 check(code==403,'Limited account cannot export: '+label)
cookie=admin_cookie
scan=base+'/?cooms_scan=1'
code,body,headers=request(scan,auth=False)
check(code==200 and 'Se connecter à l’espace boutique' in body.decode() and 'jsQR.js' not in body.decode(),'Anonymous scanner shows login without camera scripts')
code,body,_=request(scan);s=body.decode()
check(code==200 and 'Ouvrir la caméra' in s and 'cb-reference' in s,'Authenticated scanner provides camera and manual form')
nonce=re.search(r'name="_wpnonce" value="([^"]+)"',s).group(1)
code,body,_=request(scan,urllib.parse.urlencode({'code':fixture['bons'][5]['code'],'_wpnonce':nonce}).encode())
check(code==200 and '<h1>Bon valide</h1>' in body.decode(),'Manual reference opens existing valid bon')
check('Scanner le bon suivant' in request(url())[1].decode(),'Used bon offers next-scan action')
check('Retour au scanner' in body.decode(),'Valid bon offers return to scanner without consumption')
code,body,_=request(scan,urllib.parse.urlencode({'code':base+'/?cooms_bon='+fixture['bons'][5]['token'],'_wpnonce':nonce}).encode())
check(code==200 and '<h1>Bon valide</h1>' in body.decode(),'Decoded own QR URL resolves without consuming bon')
code,body,_=request(scan,urllib.parse.urlencode({'code':'https://example.invalid/?cooms_bon='+fixture['bons'][5]['token'],'_wpnonce':nonce}).encode())
check(code==404 and 'Bon introuvable' in body.decode(),'External QR does not redirect away')
check(request(scan,urllib.parse.urlencode({'code':fixture['bons'][5]['code'],'_wpnonce':'invalid'}).encode())[0]==403,'Scanner lookup requires valid nonce')
check(request(scan,urllib.parse.urlencode({'code':fixture['bons'][5]['code'],'_wpnonce':nonce}).encode(),auth=False)[0]==403,'Anonymous scanner POST refused')
cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts']['subscriber']['cookies'])
check(request(scan)[0]==403,'Subscriber cannot open scanner')
cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts']['seller']['cookies'])
code,body,_=request(scan);nonce=re.search(r'name="_wpnonce" value="([^"]+)"',body.decode()).group(1)
code,body,_=request(scan,urllib.parse.urlencode({'code':fixture['bons'][6]['code'],'_wpnonce':nonce}).encode())
check(code==200 and '<h1>Bon valide</h1>' in body.decode(),'Seller can resolve manual reference')
code,body,_=request(base+'/wp-admin/admin.php?page=cooms-bons-scan')
check(code==200 and 'Ouvrir la caméra' in body.decode(),'WordPress Scanner menu reaches boutique page')
cookie=admin_cookie
code,body,_=request(base+'/wp-admin/admin.php?page=cooms-bons')
check(code==200 and 'Vos lots' in body.decode() and fixture['bons'][0]['lot_name'] in body.decode(),'Default management view lists distributions')
# Actual creation form: invalid date preserves fields, then creates a custom lot.
_,body,_=request(base+'/wp-admin/admin.php?page=cooms-bons-new');s=body.decode()
nonce=re.search(r'name="_wpnonce" value="([^"]+)"',s).group(1)
key=re.search(r'name="request_key" value="([^"]+)"',s).group(1)
from datetime import date,timedelta
selected=(date.today()+timedelta(days=60)).isoformat()
custom_gift='Coffret de 8 cookies — 40 CHF'
payload={'action':'cooms_create','_wpnonce':nonce,'request_key':key,'name':'RECETTE HTTP personnalisée','quantity':'2','gift':custom_gift,'valid_until':'2020-01-01'}
_,body,_=request(base+'/wp-admin/admin-post.php',urllib.parse.urlencode(payload).encode());s=body.decode()
check('Choisissez une date de fin de validité' in s and custom_gift in s and key in s,'Invalid date preserves entered gift, name and idempotency key')
payload['valid_until']=selected
_,body,_=request(base+'/wp-admin/admin-post.php',urllib.parse.urlencode(payload).encode());s=body.decode()
lot_id=int(re.search(r'name="lot"[^>]*>.*?<option value="(\d+)"\s+selected',s,re.S).group(1))
fixture.setdefault('extra_lots',[]).append({'id':lot_id,'request_key':key})
(root/'output/http-fixture.json').write_text(json.dumps(fixture))
check(custom_gift in s and date.fromisoformat(selected).strftime('%d/%m/%Y') in s,'Custom gift and inclusive validity visible on created lot')
links=[html.unescape(x) for x in re.findall(r'href="([^"]+)"',s)]
custom_csv=next(x for x in links if 'format=csv' in x)
_,data,_=request(custom_csv);custom_rows=list(csv.reader(io.StringIO(data.decode('utf-8-sig')),delimiter=';'))
check(len(custom_rows)==3 and all(row[-1]==custom_gift and row[3]==date.fromisoformat(selected).strftime('%d/%m/%Y') for row in custom_rows[1:]),'Custom gift/date exported on both CSV rows')
_,body,_=request(scan);lookup_nonce=re.search(r'name="_wpnonce" value="([^"]+)"',body.decode()).group(1)
_,body,_=request(scan,urllib.parse.urlencode({'code':custom_rows[1][0],'_wpnonce':lookup_nonce}).encode())
check(custom_gift in body.decode() and 'Valider la remise du cadeau' in body.decode() and 'Une boîte de 4 cookies' not in body.decode(),'Seller lookup displays custom gift without hardcoded box copy')
# Native employee-account management: never send test invitations.
cookie=admin_cookie
staff_page=base+'/wp-admin/admin.php?page=cooms-bons-staff'
code,body,_=request(staff_page);s=body.decode()
check(code==200 and 'Accès employés' in s and 'Créer l’accès vendeur' in s,'Administrator sees employee management')
staff_nonce=re.search(r'name="_wpnonce" value="([^"]+)"',s).group(1)
for label in ['seller','subscriber']:
 cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts'][label]['cookies'])
 check(request(staff_page)[0]==403,label+' cannot open employee management')
 check(request(base+'/wp-admin/admin-post.php',urllib.parse.urlencode({'action':'cooms_staff','operation':'create','_wpnonce':staff_nonce,'staff_name':'Forbidden','staff_email':'forbidden@example.invalid'}).encode())[0]==403,label+' cannot submit employee mutations')
cookie=admin_cookie
check(request(base+'/wp-admin/admin-post.php',urllib.parse.urlencode({'action':'cooms_staff','operation':'create','_wpnonce':'invalid'}).encode())[0]==403,'Employee mutation requires valid nonce')
import secrets
staff_email='cooms-staff-http-'+secrets.token_hex(8)+'@example.invalid'
fixture.setdefault('staff_accounts',[]).append(staff_email)
(root/'output/http-fixture.json').write_text(json.dumps(fixture))
def staff_post(operation,**values):
 return request(base+'/wp-admin/admin-post.php',urllib.parse.urlencode({'action':'cooms_staff','operation':operation,'_wpnonce':staff_nonce,**values}).encode())
code,body,_=staff_post('create',staff_name='RECETTE employé HTTP',staff_email=staff_email,role='administrator')
s=body.decode()
check(code==200 and 'Accès vendeur créé.' in s and staff_email in s,'Real form creates employee with forced seller role')
row=next(r for r in re.findall(r'<tr>.*?</tr>',s,re.S) if staff_email in r)
staff_id=int(re.search(r'name="staff_id" value="(\d+)"',row).group(1))
check('Actif' in row and 'Suspendre l’accès' in row,'New employee listed as active')
code,body,_=staff_post('create',staff_name='Duplicate',staff_email=staff_email)
check(code==200 and 'Un compte utilise déjà cet e-mail' in body.decode(),'Duplicate email returns useful error without changing account')
code,body,_=staff_post('suspend',staff_id=fixture['accounts']['admin']['id'])
check(code==200 and 'ne peut pas être modifié' in body.decode(),'Cannot suspend administrator through employee endpoint')
code,body,_=staff_post('suspend',staff_id=staff_id)
check(code==200 and 'Accès au scanner suspendu' in body.decode(),'Employee suspension succeeds through real POST')
code,body,_=staff_post('enable',staff_id=staff_id)
check(code==200 and 'Accès réactivé' in body.decode(),'Employee reactivation succeeds through real POST')
code,body,_=staff_post('suspend',staff_id=fixture['accounts']['seller']['id'])
check(code==200 and 'Accès au scanner suspendu' in body.decode(),'Existing dedicated seller may be suspended')
cookie='; '.join(c['name']+'='+c['value'] for c in fixture['accounts']['seller']['cookies'])
code,body,_=request(base+'/?cooms_scan=1')
check('Accès équipe COOMS' in body.decode() and 'data-scanner ' not in body.decode(),'Already authenticated seller session is revoked after suspension')
cookie=admin_cookie
staff_post('enable',staff_id=fixture['accounts']['seller']['id'])
print('SUCCESS:',passed,'HTTP checks',flush=True)
